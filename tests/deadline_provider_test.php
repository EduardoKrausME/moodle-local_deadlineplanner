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
 * Native module deadline and extension tests.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner;

use local_deadlineplanner\local\assign_deadline_provider;
use local_deadlineplanner\local\quiz_deadline_provider;

/**
 * Deadline provider test implementation.
 *
 * @covers \local_deadlineplanner\local\assign_deadline_provider
 */
final class deadline_provider_test extends \advanced_testcase {
    /**
     * Test assignment extension overrides due date.
     */
    public function test_assignment_extension_overrides_due_date(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id,
            'allowsubmissionsfromdate' => 1000, 'duedate' => 10000, 'cutoffdate' => 30000]);
        $learner = $this->getDataGenerator()->create_user();
        $provider = new assign_deadline_provider();
        $base = $provider->base($assign->id);
        $this->assertSame(1000, $base['open']);
        $this->assertSame(10000, $base['due']);
        $this->assertSame(10000, $provider->effective_due($assign->id, $learner->id, $base));
        $DB->insert_record('assign_user_flags', (object) [
            'assignment' => $assign->id, 'userid' => $learner->id,
            'extensionduedate' => 20000, 'locked' => 0,
        ]);
        $this->assertSame(20000, $provider->effective_due($assign->id, $learner->id, $base));
    }

    /**
     * Test quiz user override changes effective closing date.
     */
    public function test_quiz_user_override_changes_effective_closing_date(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id,
            'timeopen' => 1000, 'timeclose' => 20000]);
        $learner = $this->getDataGenerator()->create_user();
        $DB->insert_record('quiz_overrides', (object) [
            'quiz' => $quiz->id, 'userid' => $learner->id, 'timeclose' => 30000,
        ]);
        $provider = new quiz_deadline_provider();
        $base = $provider->base($quiz->id);
        $this->assertSame(30000, $provider->effective_due($quiz->id, $learner->id, $base));
    }

    /**
     * Test quiz group override respects learner group membership.
     */
    public function test_quiz_group_override_respects_learner_group_membership(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id,
            'timeopen' => 1000, 'timeclose' => 20000]);
        $one = $this->getDataGenerator()->create_user();
        $two = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($one->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($two->id, $course->id, 'student');
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group->id, $one->id);
        $DB->insert_record('quiz_overrides', (object) [
            'quiz' => $quiz->id, 'groupid' => $group->id, 'timeclose' => 40000,
        ]);
        $provider = new quiz_deadline_provider();
        $base = $provider->base($quiz->id);
        $this->assertSame(40000, $provider->effective_due($quiz->id, $one->id, $base));
        $this->assertSame(20000, $provider->effective_due($quiz->id, $two->id, $base));
    }
}
