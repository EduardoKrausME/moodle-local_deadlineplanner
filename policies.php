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
 * Course-level policy administration; system-level policy capability is required.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/deadlineplanner:configure', context_system::instance());
require_capability('local/deadlineplanner:view', $context);
$policy = \local_deadlineplanner\local\policy_evaluator::policy($courseid);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $values = [
        'dailycount' => required_param('dailycount', PARAM_INT),
        'weeklycount' => required_param('weeklycount', PARAM_INT),
        'dailyweight' => required_param('dailyweight', PARAM_FLOAT),
        'privacymin' => required_param('privacymin', PARAM_INT),
    ];
    if ($values['dailycount'] < 1 || $values['weeklycount'] < 1 ||
            $values['dailyweight'] < 0.1 || $values['privacymin'] < 5 ||
            $values['dailycount'] > 100 || $values['weeklycount'] > 500 ||
            $values['dailyweight'] > 1000 || $values['privacymin'] > 1000) {
        throw new moodle_exception('invalidpolicy', 'local_deadlineplanner');
    }
    $previous = $DB->get_record('local_deadlineplanner_policy', ['contextid' => $context->id]);
    $values['contextid'] = $context->id;
    $values['timemodified'] = time();
    if ($previous) {
        $values['id'] = $previous->id;
        $DB->update_record('local_deadlineplanner_policy', (object) $values);
    } else {
        $values['timecreated'] = time();
        $DB->insert_record('local_deadlineplanner_policy', (object) $values);
    }
    \local_deadlineplanner\event\policy_updated::create([
        'context' => $context, 'other' => ['courseid' => $courseid],
    ])->trigger();
    redirect(new moodle_url('/local/deadlineplanner/policies.php', ['courseid' => $courseid]),
        get_string('policysaved', 'local_deadlineplanner'));
}
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/deadlineplanner/policies.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('policyconfiguration', 'local_deadlineplanner'));
$PAGE->set_heading(format_string($course->fullname));
$data = [
    'courseid' => $courseid,
    'dailycount' => $policy['dailycount'],
    'weeklycount' => $policy['weeklycount'],
    'dailyweight' => $policy['dailyweight'],
    'privacymin' => $policy['privacymin'],
    'sesskey' => sesskey(),
    'formurl' => (new moodle_url('/local/deadlineplanner/policies.php'))->out(false),
    'backurl' => (new moodle_url('/local/deadlineplanner/index.php', ['courseid' => $courseid]))->out(false),
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_deadlineplanner/policies', $data);
echo $OUTPUT->footer();
