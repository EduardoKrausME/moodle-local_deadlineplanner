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
 * Scenario lists scoped to authorized courses.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner\local;

/**
 * Scenario repository implementation.
 */
final class scenario_repository {
    /**
     * List course.
     */
    public function list_course(int $courseid): array {
        global $DB;
        require_capability('local/deadlineplanner:view', \context_course::instance($courseid));
        return $DB->get_records('local_deadlineplanner_scenario', ['courseid' => $courseid],
            'timecreated DESC', '*', 0, 100);
    }
}
