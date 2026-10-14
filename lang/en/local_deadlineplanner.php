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
 * Academic deadline planner language strings.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['activity'] = 'Activity';
$string['after'] = 'Simulated overload';
$string['aftercutoff'] = 'The assignment deadline must not exceed the submission cut-off date.';
$string['approve'] = 'Approve proposal';
$string['backtodashboard'] = 'Back to planning dashboard';
$string['before'] = 'Previous overload';
$string['beforeopen'] = 'The deadline must not precede the opening date.';
$string['cachedef_course_deadlines'] = 'Course deadline snapshots';
$string['calendaronly'] = 'Native calendar (informational)';
$string['calendartitle'] = 'Scheduled academic deadlines';
$string['category'] = 'Category';
$string['course'] = 'Course';
$string['courseonly'] = 'Counts cover visible activities in this selection, not student obligations.';
$string['createproposal'] = 'Simulate a change';
$string['currentcourse'] = 'Current course';
$string['dailyconcentration'] = 'Deadline concentrations';
$string['dailycount'] = 'Maximum indicative deliveries per day';
$string['dailycountlabel'] = 'Due on day';
$string['dailyweight'] = 'Maximum indicative weighted load per day';
$string['datasource'] = 'Source';
$string['day'] = 'Day';
$string['deadline'] = 'Deadline';
$string['deadlineplanner:apply'] = 'Approve deadline changes';
$string['deadlineplanner:configure'] = 'Configure planning rules';
$string['deadlineplanner:simulate'] = 'Create deadline simulations';
$string['deadlineplanner:view'] = 'View course deadline planning';
$string['deadlineplanner:viewall'] = 'View institution-wide planning';
$string['exportcsv'] = 'Export CSV';
$string['filter'] = 'Filter';
$string['fri'] = 'Fri';
$string['history'] = 'Scenario history';
$string['improved'] = 'Relieved';
$string['indicative'] = 'Counts are coarse, privacy-preserving ranges of affected learners; weights are not measured student effort.';
$string['invaliddate'] = 'The proposed date or date range is invalid.';
$string['invalidmodule'] = 'The module is unavailable or not visible.';
$string['invalidpolicy'] = 'Invalid planning policy values.';
$string['invalidstate'] = 'This proposal cannot be changed in its current state.';
$string['limitexceeded'] = 'The cohort is larger than the configured safe calculation limit. No partial results were published.';
$string['manualnotice'] = 'This version does not automatically change activity dates. After approval, use the native Moodle activity editor to apply the proposed deadline and verify other settings.';
$string['maxlearners'] = 'Maximum learners analyzed in a single simulation';
$string['messageprovider:proposalstatus'] = 'Deadline proposal status';
$string['mon'] = 'Mon';
$string['noactivities'] = 'No eligible deadlines were found in the selected period.';
$string['noduedate'] = 'This activity has no defined submission or closing deadline.';
$string['none'] = 'None';
$string['noscenarios'] = 'No proposals have been recorded for this course.';
$string['openeditor'] = 'Open activity editor';
$string['originaldate'] = 'Original deadline';
$string['periodend'] = 'End date';
$string['periodstart'] = 'Start date';
$string['pluginname'] = 'Academic deadline planner';
$string['policyconfiguration'] = 'Configure course planning policy';
$string['policysaved'] = 'Course planning policy saved.';
$string['privacy:audit'] = 'Proposal audit history';
$string['privacy:metadata'] = 'The academic deadline planner stores proposals, their authors/reviewers, and an audit trail. It does not persist individual student workloads.';
$string['privacy:scenario'] = 'Stored proposals';
$string['privacyhint'] = 'Deadlines in this view refer only to activities visible in the selected course. Cross-course comparisons use privacy-restricted aggregates and indicative weights; they do not display other courses or student names.';
$string['privacymin'] = 'Minimum privacy cohort size (minimum enforced: 5)';
$string['privacyrestricted'] = 'Cross-course impact details were suppressed to protect smaller groups of students.';
$string['proposalstatus'] = 'Deadline proposal status';
$string['proposalstatusbody'] = 'Deadline planning proposal status: {$a}';
$string['proposeddate'] = 'Proposed date';
$string['ratelimited'] = 'You have reached the 30-simulation limit for this 24-hour period.';
$string['refreshdeadlines'] = 'Refresh academic deadline snapshots';
$string['sat'] = 'Sat';
$string['scenario'] = 'Scenario';
$string['scenarioapproved'] = 'Proposal approved. The module dates have not been changed.';
$string['scenariocreated'] = 'Simulation saved.';
$string['simulate'] = 'Simulate without changing Moodle';
$string['staleproposal'] = 'The module dates have changed. Generate a new proposal before approval or application.';
$string['status'] = 'Status';
$string['statusapproved'] = 'Approved';
$string['statusarchived'] = 'Archived';
$string['statusdraft'] = 'Draft';
$string['suggestionhint'] = 'Candidates are scored only from this course. Confirm by running the cross-course simulation.';
$string['suggestions'] = 'Possible lower-density dates';
$string['sun'] = 'Sun';
$string['supportedprovider'] = 'Validated module adapter';
$string['thu'] = 'Thu';
$string['tue'] = 'Tue';
$string['type'] = 'Type';
$string['unsupportedmodule'] = 'This module does not have a supported deadline adapter.';
$string['wed'] = 'Wed';
$string['weeklycount'] = 'Maximum indicative deliveries per week';
$string['weeklycountlabel'] = 'Due in week';
$string['weightassign'] = 'Indicative weight for assignments';
$string['weightquiz'] = 'Indicative weight for quizzes';
$string['worsened'] = 'Additional conflicts';
