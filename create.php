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
 * Save nonmutating simulation from an authorized course.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_sesskey();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new moodle_exception('invalidrequest', 'error');
}
$courseid = required_param('courseid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$proposed = required_param('newdue', PARAM_TEXT);
$course = get_course($courseid);
require_login($course);
require_capability('local/deadlineplanner:simulate', context_course::instance($courseid));
$zone = \local_deadlineplanner\local\learner_workload_calculator::zone($USER->id);
$date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $proposed, $zone);
$errors = DateTimeImmutable::getLastErrors();
if (!$date || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) {
    throw new moodle_exception('invaliddate', 'local_deadlineplanner');
}
$simulator = new \local_deadlineplanner\local\scenario_simulator();
$scenario = $simulator->simulate($courseid, $cmid, $date->getTimestamp(), $USER->id);
\local_deadlineplanner\event\scenario_created::create([
    'objectid' => $scenario->id,
    'context' => context_course::instance($courseid),
    'other' => ['cmid' => $cmid],
])->trigger();
redirect(new moodle_url('/local/deadlineplanner/scenario.php', ['id' => $scenario->id]),
    get_string('scenariocreated', 'local_deadlineplanner'));
