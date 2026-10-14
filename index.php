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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * Course deadline planning dashboard.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$startdate = optional_param('startdate', '', PARAM_TEXT);
$enddate = optional_param('enddate', '', PARAM_TEXT);
$zone = \local_deadlineplanner\local\learner_workload_calculator::zone($USER->id);
$start = optional_param('start', strtotime('today'), PARAM_INT);
$end = optional_param('end', $start + 28 * DAYSECS, PARAM_INT);
if ($startdate !== '') {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $startdate, $zone);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$parsed || ($errors && ($errors['warning_count'] || $errors['error_count']))) {
        throw new moodle_exception('invaliddate', 'local_deadlineplanner');
    }
    $start = $parsed->getTimestamp();
}
if ($enddate !== '') {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $enddate, $zone);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$parsed || ($errors && ($errors['warning_count'] || $errors['error_count']))) {
        throw new moodle_exception('invaliddate', 'local_deadlineplanner');
    }
    $end = $parsed->setTime(23, 59, 59)->getTimestamp();
}
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/deadlineplanner:view', $context);
if ($start < 0 || $end <= $start || $end - $start > 366 * DAYSECS) {
    throw new moodle_exception('invaliddate', 'local_deadlineplanner');
}
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/deadlineplanner/index.php',
    ['courseid' => $courseid, 'start' => $start, 'end' => $end, 'categoryid' => $categoryid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pluginname', 'local_deadlineplanner'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->js_call_amd('local_deadlineplanner/planner', 'init');

$service = new \local_deadlineplanner\local\calendar_data_service();
$rows = [];
$categories = [];
if ($categoryid !== 0) {
    require_capability('local/deadlineplanner:viewall', context_system::instance());
    $category = core_course_category::get($categoryid, MUST_EXIST, true);
    $courses = $category->get_courses(['recursive' => true, 'sort' => ['id' => 1]]);
    if (count($courses) > 100) {
        throw new moodle_exception('limitexceeded', 'local_deadlineplanner');
    }
    foreach ($courses as $candidate) {
        if (has_capability('local/deadlineplanner:view', context_course::instance($candidate->id))) {
            foreach ($service->calendar($candidate->id, $USER->id, $start, $end) as $row) {
                $row['course'] = format_string($candidate->fullname);
                $rows[] = $row;
            }
        }
    }
} else {
    foreach ($service->calendar($courseid, $USER->id, $start, $end) as $row) {
        $row['course'] = format_string($course->fullname);
        $rows[] = $row;
    }
}
foreach (core_course_category::make_categories_list() as $id => $name) {
    if (has_capability('local/deadlineplanner:viewall', context_system::instance())) {
        $categories[] = ['id' => $id, 'name' => $name, 'selected' => ((int) $id === $categoryid)];
    }
}
usort($rows, static fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
$scenarios = (new \local_deadlineplanner\local\scenario_repository())->list_course($courseid);
$scenarioitems = [];
foreach ($scenarios as $scenario) {
    $scenarioitems[] = [
        'id' => $scenario->id,
        'original' => userdate($scenario->olddue, get_string('strftimedatetime', 'langconfig')),
        'proposed' => userdate($scenario->newdue, get_string('strftimedatetime', 'langconfig')),
        'status' => get_string('status' . $scenario->status, 'local_deadlineplanner'),
        'url' => (new moodle_url('/local/deadlineplanner/scenario.php', ['id' => $scenario->id]))->out(false),
    ];
}
$modules = [];
$modinfo = get_fast_modinfo($course);
foreach ($service->get_course($courseid) as $row) {
    if ($row->duetime && isset($modinfo->cms[$row->cmid]) && $modinfo->cms[$row->cmid]->uservisible &&
            has_capability('moodle/course:manageactivities', context_module::instance($row->cmid))) {
        $modules[] = [
            'id' => $row->cmid,
            'name' => format_string($modinfo->cms[$row->cmid]->name),
            'due' => userdate($row->duetime, get_string('strftimedatetime', 'langconfig')),
        ];
    }
}
$calendarweeks = [];
$calendarmode = ($end - $start) <= 50 * DAYSECS;
if ($calendarmode) {
    $eventsbyday = [];
    foreach ($rows as $row) {
        $datekey = (new DateTimeImmutable('@' . $row['timestamp']))->setTimezone($zone)->format('Y-m-d');
        $eventsbyday[$datekey][] = ['name' => $row['name'], 'url' => $row['url']];
    }
    $first = (new DateTimeImmutable('@' . $start))->setTimezone($zone)->setTime(0, 0);
    $last = (new DateTimeImmutable('@' . $end))->setTimezone($zone)->setTime(0, 0);
    $first = $first->modify('-' . ((int) $first->format('N') - 1) . ' days');
    $last = $last->modify('+' . (7 - (int) $last->format('N')) . ' days');
    $week = [];
    for ($day = $first; $day <= $last; $day = $day->modify('+1 day')) {
        $datekey = $day->format('Y-m-d');
        $week[] = [
            'day' => $day->format('d'),
            'isodate' => $datekey,
            'events' => $eventsbyday[$datekey] ?? [],
            'hasitems' => !empty($eventsbyday[$datekey]),
            'outside' => $day->getTimestamp() < $start - DAYSECS ||
                $day->getTimestamp() > $end + DAYSECS,
        ];
        if (count($week) === 7) {
            $calendarweeks[] = ['days' => $week];
            $week = [];
        }
    }
}
$daycounts = [];
$weekcounts = [];
foreach ($rows as $row) {
    $date = (new DateTimeImmutable('@' . $row['timestamp']))->setTimezone($zone);
    $daykey = $date->format('Y-m-d');
    $weekkey = $date->format('o-\WW');
    $daycounts[$daykey] = ($daycounts[$daykey] ?? 0) + 1;
    $weekcounts[$weekkey] = ($weekcounts[$weekkey] ?? 0) + 1;
}
ksort($daycounts);
$policy = \local_deadlineplanner\local\policy_evaluator::policy($courseid);
$indicators = [];
foreach ($daycounts as $day => $count) {
    $weekkey = (new DateTimeImmutable($day, $zone))->format('o-\WW');
    $indicators[] = [
        'day' => $day,
        'count' => $count,
        'weekcount' => $weekcounts[$weekkey],
        'high' => $count > $policy['dailycount'] || $weekcounts[$weekkey] > $policy['weeklycount'],
    ];
}
$suggestions = [];
$suggestionmodule = '';
if ($modules && has_capability('local/deadlineplanner:simulate', $context)) {
    $chosen = reset($modules);
    $suggestionmodule = $chosen['name'];
    $candidates = (new \local_deadlineplanner\local\date_suggestion_service())->suggest(
        $courseid, (int) $chosen['id']);
    foreach ($candidates as $candidate) {
        $suggestions[] = [
            'cmid' => $chosen['id'],
            'date' => $candidate['date'],
            'inputdate' => $candidate['inputdate'],
            'count' => $candidate['count'],
        ];
    }
}
$startstr = (new DateTimeImmutable('@' . $start))->setTimezone($zone)->format('Y-m-d');
$endstr = (new DateTimeImmutable('@' . $end))->setTimezone($zone)->format('Y-m-d');
$contextdata = [
    'courseid' => $courseid,
    'sesskey' => sesskey(),
    'start' => $start,
    'end' => $end,
    'startstr' => $startstr,
    'endstr' => $endstr,
    'categoryid' => $categoryid,
    'categories' => $categories,
    'hascategories' => !empty($categories),
    'hasrows' => !empty($rows),
    'hascalendar' => $calendarmode,
    'calendarweeks' => $calendarweeks,
    'rows' => $rows,
    'hasindicators' => !empty($indicators),
    'indicators' => $indicators,
    'hassuggestions' => !empty($suggestions),
    'suggestions' => $suggestions,
    'suggestionmodule' => $suggestionmodule,
    'hasmodules' => has_capability('local/deadlineplanner:simulate', $context) && !empty($modules),
    'modules' => $modules,
    'hasscenarios' => !empty($scenarioitems),
    'scenarios' => $scenarioitems,
    'exporturl' => (new moodle_url('/local/deadlineplanner/export.php',
        ['courseid' => $courseid, 'start' => $start, 'end' => $end]))->out(false),
    'dashboardurl' => (new moodle_url('/local/deadlineplanner/index.php'))->out(false),
    'canconfigure' => has_capability('local/deadlineplanner:configure', context_system::instance()),
    'policyurl' => (new moodle_url('/local/deadlineplanner/policies.php',
        ['courseid' => $courseid]))->out(false),
    'createurl' => (new moodle_url('/local/deadlineplanner/create.php'))->out(false),
    'privacytext' => get_string('privacyhint', 'local_deadlineplanner'),
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_deadlineplanner/dashboard', $contextdata);
echo $OUTPUT->footer();
