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
 * Moodle messaging integration.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner\local;

/**
 * Notification service implementation.
 */
final class notification_service {
    /**
     * Status changed.
     */
    public static function status_changed(\stdClass $scenario, int $actorid): void {
        if ((int) $scenario->userid === $actorid) {
            return;
        }
        $message = new \core\message\message();
        $message->component = 'local_deadlineplanner';
        $message->name = 'proposalstatus';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = \core_user::get_user($scenario->userid);
        $message->subject = get_string('proposalstatus', 'local_deadlineplanner');
        $message->fullmessage = get_string('proposalstatusbody', 'local_deadlineplanner', $scenario->status);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '';
        $message->smallmessage = $message->fullmessage;
        $message->notification = 1;
        $message->contexturl = (new \moodle_url('/local/deadlineplanner/scenario.php',
            ['id' => $scenario->id]))->out(false);
        $message->contexturlname = get_string('pluginname', 'local_deadlineplanner');
        message_send($message);
    }
}
