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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * Nonmutating scenario generation.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_deadlineplanner\local;

final class scenario_simulator {
    /** Simulations never update Moodle activity tables. */
    public function simulate(int $courseid, int $cmid, int $newdue, int $userid): \stdClass {
        global $DB;
        $context = \context_course::instance($courseid);
        require_capability('local/deadlineplanner:simulate', $context);
        $modinfo = get_fast_modinfo(get_course($courseid));
        if (!isset($modinfo->cms[$cmid]) || !$modinfo->cms[$cmid]->uservisible) {
            throw new \moodle_exception('invalidmodule', 'local_deadlineplanner');
        }
        $cm = $modinfo->cms[$cmid];
        $modulecontext = \context_module::instance($cmid);
        require_capability('moodle/course:manageactivities', $modulecontext);
        if (!provider_registry::get($cm->modname)) {
            throw new \moodle_exception('unsupportedmodule', 'local_deadlineplanner');
        }
        $provider = provider_registry::get($cm->modname);
        $base = $provider->base((int) $cm->instance);
        if (!$base || empty($base['due'])) {
            throw new \moodle_exception('noduedate', 'local_deadlineplanner');
        }
        if ($newdue <= time() || $newdue > time() + 5 * 366 * DAYSECS) {
            throw new \moodle_exception('invaliddate', 'local_deadlineplanner');
        }
        if ($base['open'] && $newdue < $base['open']) {
            throw new \moodle_exception('beforeopen', 'local_deadlineplanner');
        }
        if ($cm->modname === 'assign' && $base['cutoff'] && $newdue > $base['cutoff']) {
            throw new \moodle_exception('aftercutoff', 'local_deadlineplanner');
        }
        // Bound adaptive what-if queries to limit cross-course inference by differencing.
        $dailycreated = $DB->count_records_select('local_dlp_scenario',
            'userid = :userid AND timecreated > :since',
            ['userid' => $userid, 'since' => time() - DAYSECS]);
        if ($dailycreated >= 30) {
            throw new \moodle_exception('ratelimited', 'local_deadlineplanner');
        }
        // The target course snapshot must be current for the what-if calculation.
        (new calendar_data_service())->refresh_course($courseid);
        $comparison = (new conflict_detector())->compare($courseid, $cmid, $base['due'], $newdue);
        $sanitized = privacy_guard::public_summary($comparison, $courseid);
        $scenario = (object) [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'userid' => $userid,
            'reviewerid' => 0,
            'olddue' => $base['due'],
            'newdue' => $newdue,
            'fingerprint' => calendar_data_service::fingerprint($base),
            'status' => 'draft',
            'summary' => json_encode($sanitized),
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $scenario->id = $DB->insert_record('local_dlp_scenario', $scenario);
        audit_history::add((int) $scenario->id, $userid, 'created',
            ['old' => $scenario->olddue, 'new' => $scenario->newdue]);
        return $scenario;
    }
}
