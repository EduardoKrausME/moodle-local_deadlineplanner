<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Module snapshot collector and restricted calendar feed.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner\local;

/**
 * Calendar data service implementation.
 */
final class calendar_data_service {
    /**
     * Collect course dates once per course and refresh only when stale.
     * User-specific availability is deliberately not stored in these rows.
     */
    public function refresh_course(int $courseid): void {
        global $DB;
        $course = get_course($courseid);
        $modinfo = get_fast_modinfo($course);
        $seen = [];
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->deletioninprogress) {
                continue;
            }
            $provider = provider_registry::get($cm->modname);
            if (!$provider) {
                continue;
            }
            $base = $provider->base((int) $cm->instance);
            if ($base === null) {
                continue;
            }
            $seen[] = (int) $cm->id;
            $row = (object) [
                'cmid' => $cm->id,
                'courseid' => $courseid,
                'modname' => $cm->modname,
                'instanceid' => $cm->instance,
                'opentime' => $base['open'],
                'duetime' => $base['due'],
                'closetime' => $base['close'],
                'cutofftime' => $base['cutoff'],
                'gradingtime' => $base['grading'],
                'modmodified' => $base['modified'],
                'fingerprint' => self::fingerprint($base),
                'timemodified' => time(),
            ];
            $old = $DB->get_record('local_deadlineplanner_deadline', ['cmid' => $cm->id]);
            if ($old) {
                $row->id = $old->id;
                $DB->update_record('local_deadlineplanner_deadline', $row);
            } else {
                $DB->insert_record('local_deadlineplanner_deadline', $row);
            }
        }
        // Deleted or unsupported modules never keep producing obligations.
        if ($seen) {
            [$insql, $params] = $DB->get_in_or_equal($seen, SQL_PARAMS_NAMED, 'cm', false);
            $params['courseid'] = $courseid;
            $DB->delete_records_select('local_deadlineplanner_deadline', "courseid = :courseid AND cmid $insql", $params);
        } else {
            $DB->delete_records('local_deadlineplanner_deadline', ['courseid' => $courseid]);
        }
        $sync = $DB->get_record('local_deadlineplanner_sync', ['courseid' => $courseid]);
        if ($sync) {
            $DB->set_field('local_deadlineplanner_sync', 'timemodified', time(), ['id' => $sync->id]);
        } else {
            $DB->insert_record('local_deadlineplanner_sync', (object) [
                'courseid' => $courseid, 'timemodified' => time(),
            ]);
        }
        \cache::make('local_deadlineplanner', 'course_deadlines')->delete((string) $courseid);
    }

    /** Course-level snapshots, refreshing at most once each five minutes in web requests. */
    /**
     * Get course.
     */
    public function get_course(int $courseid): array {
        global $DB;
        $cache = \cache::make('local_deadlineplanner', 'course_deadlines');
        $cached = $cache->get((string) $courseid);
        if ($cached !== false) {
            return $cached;
        }
        $recent = $DB->get_field('local_deadlineplanner_sync', 'timemodified', ['courseid' => $courseid]);
        if (!$recent || (int) $recent < time() - 900) {
            $this->refresh_course($courseid);
        }
        $rows = $DB->get_records('local_deadlineplanner_deadline', ['courseid' => $courseid]);
        $cache->set((string) $courseid, $rows);
        return $rows;
    }

    /** A fingerprint binds a proposal to the module's entire relevant date state. */
    /**
     * Fingerprint.
     */
    public static function fingerprint(array $base): string {
        $dates = [$base['open'], $base['due'], $base['close'], $base['cutoff'], $base['grading'], $base['modified']];
        return hash('sha256', json_encode($dates));
    }

    /** For displayed entries use only activities visible in this course to this viewer. */
    /**
     * Calendar.
     */
    public function calendar(int $courseid, int $userid, int $start, int $end): array {
        $course = get_course($courseid);
        $modinfo = get_fast_modinfo($course, $userid);
        $result = [];
        foreach ($this->get_course($courseid) as $row) {
            if (!$row->duetime || $row->duetime < $start || $row->duetime > $end ||
                    !isset($modinfo->cms[$row->cmid]) || !$modinfo->cms[$row->cmid]->uservisible) {
                continue;
            }
            $cm = $modinfo->cms[$row->cmid];
            $result[] = [
                'id' => $cm->id,
                'name' => format_string($cm->name, true, ['context' => \context_module::instance($cm->id)]),
                'date' => userdate($row->duetime, get_string('strftimedatetime', 'langconfig')),
                'timestamp' => (int) $row->duetime,
                'type' => get_string('modulename', $cm->modname),
                'url' => (new \moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]))->out(false),
                'source' => get_string('supportedprovider', 'local_deadlineplanner'),
                'gradingdate' => $row->gradingtime ? userdate($row->gradingtime) : '',
            ];
        }
        // The official Calendar API can supply due-type events for other modules.
        // They are display-only until an audited learner-aware provider exists.
        global $CFG, $USER;
        if ($userid === (int) $USER->id) {
            require_once($CFG->dirroot . '/calendar/lib.php');
            $events = calendar_get_events($start, $end, false, false, [$courseid], true, true);
            foreach ($events as $event) {
                if (($event->eventtype ?? '') !== 'due' || empty($event->modulename) ||
                        in_array($event->modulename, ['assign', 'quiz'], true)) {
                    continue;
                }
                if (empty($event->instance) ||
                        !isset($modinfo->instances[$event->modulename][$event->instance])) {
                    continue;
                }
                $cm = $modinfo->instances[$event->modulename][$event->instance];
                if (!$cm->uservisible || (int) $event->courseid !== $courseid) {
                    continue;
                }
                $result[] = [
                    'id' => $cm->id,
                    'name' => format_string($cm->name, true,
                        ['context' => \context_module::instance($cm->id)]),
                    'date' => userdate($event->timestart, get_string('strftimedatetime', 'langconfig')),
                    'timestamp' => (int) $event->timestart,
                    'type' => get_string('modulename', $cm->modname),
                    'url' => (new \moodle_url('/mod/' . $cm->modname . '/view.php',
                        ['id' => $cm->id]))->out(false),
                    'source' => get_string('calendaronly', 'local_deadlineplanner'),
                    'gradingdate' => '',
                ];
            }
        }
        usort($result, static fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
        return $result;
    }
}
