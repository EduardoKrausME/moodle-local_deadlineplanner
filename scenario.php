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
 * Review and optionally approve a stored proposal.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$scenario = $DB->get_record('local_deadlineplanner_scenario', ['id' => $id], '*', MUST_EXIST);
$course = get_course($scenario->courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/deadlineplanner:view', $context);
$canapprove = has_capability('local/deadlineplanner:apply', $context) &&
    has_capability('moodle/course:manageactivities', context_module::instance($scenario->cmid));
$service = new \local_deadlineplanner\local\date_adjustment_service();
if ($action === 'approve') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new moodle_exception('invalidrequest', 'error');
    }
    require_sesskey();
    if (!$canapprove) {
        throw new required_capability_exception($context, 'local/deadlineplanner:apply', 'nopermissions', '');
    }
    $scenario = $service->approve($id, $USER->id);
    \local_deadlineplanner\event\scenario_approved::create([
        'objectid' => $scenario->id, 'context' => $context,
        'other' => ['cmid' => $scenario->cmid],
    ])->trigger();
    redirect(new moodle_url('/local/deadlineplanner/scenario.php', ['id' => $id]),
        get_string('scenarioapproved', 'local_deadlineplanner'));
}
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/deadlineplanner/scenario.php', ['id' => $id]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('scenario', 'local_deadlineplanner'));
$PAGE->set_heading(format_string($course->fullname));
$summary = json_decode($scenario->summary ?? '', true);
if (!is_array($summary)) {
    $summary = ['available' => false, 'reason' => get_string('privacyrestricted', 'local_deadlineplanner')];
}
$manualurl = '';
if ($scenario->status === 'approved' && $canapprove) {
    try {
        $manualurl = $service->manual_edit_url($id)->out(false);
    } catch (moodle_exception $e) {
        $summary = ['available' => false, 'reason' => get_string('staleproposal', 'local_deadlineplanner')];
    }
}
$data = [
    'id' => $scenario->id,
    'courseid' => $course->id,
    'original' => userdate($scenario->olddue, get_string('strftimedatetime', 'langconfig')),
    'proposed' => userdate($scenario->newdue, get_string('strftimedatetime', 'langconfig')),
    'status' => get_string('status' . $scenario->status, 'local_deadlineplanner'),
    'canapprove' => $canapprove && $scenario->status === 'draft',
    'sesskey' => sesskey(),
    'manualurl' => $manualurl,
    'hasmanualurl' => !empty($manualurl),
    'hasmetrics' => !empty($summary['available']),
    'reason' => $summary['reason'] ?? '',
    'before' => $summary['before'] ?? '',
    'after' => $summary['after'] ?? '',
    'improved' => $summary['improved'] ?? '',
    'worsened' => $summary['worsened'] ?? '',
    'backurl' => (new moodle_url('/local/deadlineplanner/index.php', ['courseid' => $course->id]))->out(false),
    'approveurl' => (new moodle_url('/local/deadlineplanner/scenario.php',
        ['id' => $id, 'action' => 'approve']))->out(false),
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_deadlineplanner/scenario', $data);
echo $OUTPUT->footer();
