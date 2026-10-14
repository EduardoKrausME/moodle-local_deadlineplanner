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
 * Reliable Behat navigation to a course's local planner.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Planner-specific Behat steps.
 */
class behat_local_deadlineplanner extends behat_base {
    /**
     * Open the planner route for a known course without clicking offscreen course navigation links.
     *
     * @Given /^I open the deadline planner for course "(?P<course>[^"]+)"$/
     * @param string $course Course fullname.
     */
    public function i_open_the_deadline_planner_for_course(string $course): void {
        global $DB;
        $record = $DB->get_record('course', ['fullname' => $course], 'id', MUST_EXIST);
        $path = '/local/deadlineplanner/index.php?courseid=' . $record->id;
        $this->getSession()->visit($this->locate_path($path));
    }
}
