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
 * Adapter contract for module deadlines.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner\local;

/**
 * Effective deadlines are epoch timestamps, never formatted calendar dates.
 */
interface deadline_provider_interface {
    /**
     * Read the module's non-personal deadline fields.
     *
     * @param int $instanceid instance PK
     * @return array|null timestamps or null if unsupported
     */
    public function base(int $instanceid): ?array;

    /**
     * Determine an enrolled learner's actual deadline with supported overrides.
     *
     * @param int $instanceid module instance
     * @param int $userid learner ID
     * @param array $base non-personal base fields
     * @return int actual submission due date or 0 if there is no deadline
     */
    public function effective_due(int $instanceid, int $userid, array $base): int;
}
