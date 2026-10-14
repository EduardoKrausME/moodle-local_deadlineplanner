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
 * Cache invalidation on module lifecycle changes.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner\event;

/**
 * Observer implementation.
 */
final class observer {
    /**
     * Changed.
     */
    public static function changed(\core\event\base $event): void {
        if (!empty($event->courseid)) {
            \cache::make('local_deadlineplanner', 'course_deadlines')->delete((string) $event->courseid);
            $task = new \local_deadlineplanner\task\refresh_course();
            $task->set_custom_data(['courseid' => (int) $event->courseid]);
            \core\task\manager::queue_adhoc_task($task);
        }
    }
}
