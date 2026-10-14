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
 * Cross-course visibility, enrolment and scenario behaviour.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_deadlineplanner;

use local_deadlineplanner\local\conflict_detector;
use local_deadlineplanner\local\learner_workload_calculator;
use local_deadlineplanner\local\privacy_guard;
use local_deadlineplanner\local\scenario_simulator;
use local_deadlineplanner\local\date_adjustment_service;

final class conflict_detector_test extends \advanced_testcase {
    private function seed_two_course_case(): array {
        global $DB;
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $day = strtotime('2026-12-14 17:00 UTC');
        $a = $this->getDataGenerator()->create_module('assign',
            ['course' => $course1->id, 'duedate' => $day]);
        $b = $this->getDataGenerator()->create_module('assign',
            ['course' => $course2->id, 'duedate' => $day]);
        $users = [];
        for ($i = 0; $i < 12; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
            $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');
            $users[] = $user;
        }
        $coursecm = get_coursemodule_from_instance('assign', $a->id);
        $othercm = get_coursemodule_from_instance('assign', $b->id);
        return [$course1, $course2, $a, $b, $coursecm, $othercm, $users, $day];
    }

    public function test_two_course_student_has_two_visible_deadlines(): void {
        $this->resetAfterTest();
        [$course1, $course2, $a, $b, $cm, $othercm, $users, $day] = $this->seed_two_course_case();
        $activities = (new learner_workload_calculator())->collect($users[0]->id, $day - DAYSECS, $day + DAYSECS);
        $this->assertCount(2, $activities);
    }

    public function test_hidden_activity_is_not_counted(): void {
        global $DB;
        $this->resetAfterTest();
        [$course1, $course2, $a, $b, $cm, $othercm, $users, $day] = $this->seed_two_course_case();
        set_coursemodule_visible($othercm->id, 0);
        rebuild_course_cache($course2->id, true);
        $activities = (new learner_workload_calculator())->collect($users[0]->id, $day - DAYSECS, $day + DAYSECS);
        $this->assertCount(1, $activities);
    }

    public function test_suspended_enrolment_is_not_used_for_cross_course_workload(): void {
        global $DB;
        $this->resetAfterTest();
        [$course1, $course2, $a, $b, $cm, $othercm, $users, $day] = $this->seed_two_course_case();
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED,
            ['userid' => $users[0]->id, 'enrolid' => $DB->get_field('enrol', 'id',
            ['courseid' => $course2->id, 'enrol' => 'manual'])]);
        $activities = (new learner_workload_calculator())->collect($users[0]->id, $day - DAYSECS, $day + DAYSECS);
        $this->assertCount(1, $activities);
    }

    public function test_privacy_suppression_for_small_conflict_cells(): void {
        $this->resetAfterTest();
        set_config('privacymin', 5, 'local_deadlineplanner');
        $suppressed = privacy_guard::public_summary(['unavailable' => false,
            'analysed' => 12, 'before' => 1, 'after' => 0, 'improved' => 1, 'worsened' => 0]);
        $this->assertFalse($suppressed['available']);
    }

    public function test_manual_application_requires_live_date_fingerprint(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        [$course1, $course2, $a, $b, $cm, $othercm, $users, $day] = $this->seed_two_course_case();
        $scenario = (new scenario_simulator())->simulate($course1->id, $cm->id,
            $day + 2 * DAYSECS, (int) $DB->get_field('user', 'id', ['username' => 'admin']));
        // Another instructor modifies the activity in the native editor meanwhile.
        $DB->set_field('assign', 'duedate', $day + DAYSECS, ['id' => $a->id]);
        $this->expectException(\moodle_exception::class);
        (new date_adjustment_service())->approve($scenario->id, 2);
    }

    public function test_authorized_approval_does_not_change_module_deadline(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        [$course1, $course2, $a, $b, $cm, $othercm, $users, $day] = $this->seed_two_course_case();
        $scenario = (new scenario_simulator())->simulate($course1->id, $cm->id,
            $day + DAYSECS, 2);
        $approved = (new date_adjustment_service())->approve($scenario->id, 2);
        $this->assertSame('approved', $approved->status);
        $this->assertEquals($day, $DB->get_field('assign', 'duedate', ['id' => $a->id]));
    }

    public function test_simulation_recalculates_cross_course_overload(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('dailycount', 1, 'local_deadlineplanner');
        set_config('weeklycount', 20, 'local_deadlineplanner');
        set_config('dailyweight', 20, 'local_deadlineplanner');
        [$course1, $course2, $a, $b, $cm, $othercm, $users, $day] = $this->seed_two_course_case();
        $stats = (new conflict_detector())->compare($course1->id, $cm->id,
            $day, $day + DAYSECS);
        $this->assertFalse($stats['unavailable']);
        $this->assertSame(12, $stats['before']);
        $this->assertSame(0, $stats['after']);
    }

    public function test_cross_course_teacher_permission_does_not_extend_to_other_course(): void {
        $this->resetAfterTest();
        [$course1, $course2, $a, $b, $cm, $othercm, $users, $day] = $this->seed_two_course_case();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, 'editingteacher');
        $this->setUser($teacher);
        $this->assertTrue(has_capability('local/deadlineplanner:view',
            \context_course::instance($course1->id)));
        $this->assertFalse(has_capability('local/deadlineplanner:view',
            \context_course::instance($course2->id)));
    }
}
