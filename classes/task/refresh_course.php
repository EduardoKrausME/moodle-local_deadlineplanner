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
 * Refresh a single course's cached deadlines asynchronously.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner\task;

/**
 * Refresh course implementation.
 */
final class refresh_course extends \core\task\adhoc_task {
    /**
     * Execute.
     */
    public function execute(): void {
        global $DB;
        $data = $this->get_custom_data();
        if (empty($data->courseid) || !$DB->record_exists('course', ['id' => $data->courseid])) {
            return;
        }
        (new \local_deadlineplanner\local\calendar_data_service())->refresh_course((int) $data->courseid);
    }
}
