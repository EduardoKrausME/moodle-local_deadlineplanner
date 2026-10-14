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
 * Quiz due date adapter.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner\local;

/**
 * Quiz closing time is its effective assessment deadline.
 */
final class quiz_deadline_provider implements deadline_provider_interface {
    /**
     * Base.
     */
    public function base(int $instanceid): ?array {
        global $DB;
        $record = $DB->get_record('quiz', ['id' => $instanceid],
            'id, timeopen, timeclose, timemodified', IGNORE_MISSING);
        if (!$record) {
            return null;
        }
        return [
            'open' => (int) $record->timeopen,
            'due' => (int) $record->timeclose,
            'close' => (int) $record->timeclose,
            'cutoff' => 0,
            'grading' => 0,
            'modified' => (int) $record->timemodified,
        ];
    }

    /**
     * Effective due.
     */
    public function effective_due(int $instanceid, int $userid, array $base): int {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        $quiz = $DB->get_record('quiz', ['id' => $instanceid], '*', MUST_EXIST);
        $quiz->timeclose = (int) $base['due'];
        // Native API handles user and group overrides with quiz's leniency rules.
        $effective = quiz_update_effective_access($quiz, $userid);
        return (int) $effective->timeclose;
    }
}
