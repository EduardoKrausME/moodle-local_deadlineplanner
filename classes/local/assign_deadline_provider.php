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
 * Assignment due date adapter.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner\local;

/**
 * Uses the assignment instance schema and the explicit per-user grant extension.
 * Do not interpret an opening date as a due date.
 */
final class assign_deadline_provider implements deadline_provider_interface {
    /**
     * Base.
     */
    public function base(int $instanceid): ?array {
        global $DB;
        $record = $DB->get_record('assign', ['id' => $instanceid],
            'id, allowsubmissionsfromdate, duedate, cutoffdate, gradingduedate, timemodified', IGNORE_MISSING);
        if (!$record) {
            return null;
        }
        return [
            'open' => (int) $record->allowsubmissionsfromdate,
            'due' => (int) $record->duedate,
            'close' => 0,
            'cutoff' => (int) $record->cutoffdate,
            'grading' => (int) $record->gradingduedate,
            'modified' => (int) $record->timemodified,
        ];
    }

    /**
     * Effective due.
     */
    public function effective_due(int $instanceid, int $userid, array $base): int {
        global $DB;
        $due = (int) $base['due'];
        $extension = $DB->get_field('assign_user_flags', 'extensionduedate',
            ['assignment' => $instanceid, 'userid' => $userid]);
        // Assignment overrides are module-owned settings. Individual overrides take priority.
        // Reading these tables is necessary for Moodle versions without a public per-learner dates API.
        $useroverride = $DB->get_record('assign_overrides',
            ['assignid' => $instanceid, 'userid' => $userid], 'id, duedate');
        if ($useroverride && $useroverride->duedate !== null) {
            $due = (int) $useroverride->duedate;
        } else {
            $groups = groups_get_user_groups((int) $DB->get_field('assign', 'course', ['id' => $instanceid]), $userid);
            if (!empty($groups[0])) {
                [$insql, $params] = $DB->get_in_or_equal(array_values($groups[0]), SQL_PARAMS_NAMED, 'grp');
                $params['assignid'] = $instanceid;
                $overrides = $DB->get_records_select('assign_overrides',
                    "assignid = :assignid AND groupid $insql", $params, '', 'id, duedate');
                $groupdates = [];
                foreach ($overrides as $override) {
                    if ($override->duedate !== null) {
                        $groupdates[] = (int) $override->duedate;
                    }
                }
                if ($groupdates) {
                    $due = in_array(0, $groupdates, true) ? 0 : max($groupdates);
                }
            }
        }
        // A granted extension is an explicit individual deadline; do not infer it from submissions.
        if ($extension !== false && (int) $extension > 0) {
            return (int) $extension;
        }
        return $due;
    }
}
