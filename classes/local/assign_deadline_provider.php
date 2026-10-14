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

    public function effective_due(int $instanceid, int $userid, array $base): int {
        global $DB;
        $due = (int) $base['due'];
        $extension = $DB->get_field('assign_user_flags', 'grantextension',
            ['assignment' => $instanceid, 'userid' => $userid]);
        // Granted extensions are explicit individual overrides, never inferred from submission data.
        if ($extension !== false && (int) $extension > 0) {
            return (int) $extension;
        }
        return $due;
    }
}
