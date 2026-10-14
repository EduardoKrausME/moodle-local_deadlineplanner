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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Permission-aware learner timetables kept solely in request memory.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner\local;

/**
 * Learner workload calculator implementation.
 */
final class learner_workload_calculator {
    /** @var array Cached nonpersonal snapshots for this calculation only. */
    private array $snapshots = [];

    /**
     * Extract visible, due-dated obligations for a learner, across active enrolments.
     * Only modinfo for the learner is used to assess conditional availability.
     *
     * @param int $userid learner
     * @param int $start Unix inclusive
     * @param int $end Unix inclusive
     * @param array|null $override ['cmid' => x, 'due' => y] for a scenario
     * @return array timestamp/weight pairs, never learner names
     */
    public function collect(int $userid, int $start, int $end, ?array $override = null): array {
        global $DB;
        $result = [];
        $courses = enrol_get_users_courses($userid, true, 'id');
        $dataservice = new calendar_data_service();
        foreach ($courses as $course) {
            // Skip front page and only analyze real active course enrolments.
            if ((int) $course->id <= SITEID) {
                continue;
            }
            if (!isset($this->snapshots[$course->id])) {
                $this->snapshots[$course->id] = $dataservice->get_course((int) $course->id);
            }
            if (!$this->snapshots[$course->id]) {
                continue;
            }
            $modinfo = get_fast_modinfo($course->id, $userid);
            foreach ($this->snapshots[$course->id] as $snapshot) {
                if (!isset($modinfo->cms[$snapshot->cmid]) ||
                        !$modinfo->cms[$snapshot->cmid]->uservisible) {
                    continue;
                }
                $cm = $modinfo->cms[$snapshot->cmid];
                // Visibility alone is insufficient: check the learner's submission capability.
                $context = \context_module::instance($cm->id);
                $capability = $cm->modname === 'assign' ? 'mod/assign:submit' : 'mod/quiz:attempt';
                if (!has_capability($capability, $context, $userid)) {
                    continue;
                }
                $provider = provider_registry::get($snapshot->modname);
                if (!$provider) {
                    continue;
                }
                $base = [
                    'open' => (int) $snapshot->opentime,
                    'due' => (int) $snapshot->duetime,
                    'close' => (int) $snapshot->closetime,
                    'cutoff' => (int) $snapshot->cutofftime,
                    'grading' => (int) $snapshot->gradingtime,
                    'modified' => (int) $snapshot->modmodified,
                ];
                if ($override && $snapshot->cmid === $override['cmid']) {
                    $base['due'] = $override['due'];
                    if ($snapshot->modname === 'quiz') {
                        $base['close'] = $override['due'];
                    }
                }
                $due = $provider->effective_due((int) $snapshot->instanceid, $userid, $base);
                if ($due < $start || $due > $end || $due === 0) {
                    continue;
                }
                $result[] = [
                    'timestamp' => $due,
                    'weight' => policy_evaluator::weight($snapshot->modname),
                ];
            }
        }
        return $result;
    }

    /** Resolve per-learner timezone, preserving DST boundaries and avoiding server-time slicing. */
    /**
     * Zone.
     */
    public static function zone(int $userid): \DateTimeZone {
        $user = \core_user::get_user($userid, 'id, timezone', MUST_EXIST);
        try {
            return \core_date::get_user_timezone_object($user);
        } catch (\Exception $e) {
            return new \DateTimeZone('UTC');
        }
    }
}
