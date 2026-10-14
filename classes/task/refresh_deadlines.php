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
 * Periodic bounded collection of course module dates.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner\task;

/**
 * Refresh deadlines implementation.
 */
final class refresh_deadlines extends \core\task\scheduled_task {
    /**
     * Get name.
     */
    public function get_name(): string {
        return get_string('refreshdeadlines', 'local_deadlineplanner');
    }

    /**
     * Execute.
     */
    public function execute(): void {
        global $DB;
        $service = new \local_deadlineplanner\local\calendar_data_service();
        $lastid = 0;
        // Stream in predictable batches rather than constructing a whole-site array.
        while (true) {
            $ids = $DB->get_records_select('course', 'id > :lastid AND id > 1',
                ['lastid' => $lastid], 'id ASC', 'id', 0, 100);
            if (!$ids) {
                break;
            }
            foreach ($ids as $course) {
                $lastid = (int) $course->id;
                try {
                    $service->refresh_course($lastid);
                } catch (\Throwable $e) {
                    mtrace('local_deadlineplanner: course ' . $lastid . ': ' . $e->getMessage());
                }
            }
        }
    }
}
