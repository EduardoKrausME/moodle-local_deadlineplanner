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
 * End-to-end backup/restore tests for course policies and activity proposals.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner;

/**
 * @coversNothing
 */
final class backup_restore_test extends \advanced_testcase {
    /**
     * Insert the plugin data that must survive a course backup.
     *
     * @return array Course, course-module, scenario and users.
     */
    private function create_fixture(): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'duedate' => time() + WEEKSECS,
        ]);
        $cm = get_coursemodule_from_instance('assign', $assign->id, $course->id, false, MUST_EXIST);
        $author = $this->getDataGenerator()->create_user();
        $reviewer = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($author->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($reviewer->id, $course->id, 'editingteacher');

        $DB->insert_record('local_deadlineplanner_policy', (object) [
            'contextid' => \context_course::instance($course->id)->id,
            'dailycount' => 2,
            'weeklycount' => 6,
            'dailyweight' => 3.5,
            'privacymin' => 7,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $scenarioid = $DB->insert_record('local_deadlineplanner_scenario', (object) [
            'cmid' => $cm->id,
            'courseid' => $course->id,
            'userid' => $author->id,
            'reviewerid' => $reviewer->id,
            'olddue' => $assign->duedate,
            'newdue' => $assign->duedate + DAYSECS,
            'fingerprint' => str_repeat('a', 64),
            'status' => 'approved',
            'summary' => '{"available":false}',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        foreach (['created' => $author->id, 'approved' => $reviewer->id] as $action => $actorid) {
            $DB->insert_record('local_deadlineplanner_audit', (object) [
                'scenarioid' => $scenarioid,
                'actorid' => $actorid,
                'action' => $action,
                'details' => '{}',
                'timecreated' => time(),
            ]);
        }
        $DB->insert_record('local_deadlineplanner_sync', (object) [
            'courseid' => $course->id,
            'timemodified' => time(),
        ]);
        return [$course, $cm, $scenarioid, $author, $reviewer];
    }

    /**
     * Use Moodle's actual controller and restore plan, not a mocked XML parser.
     *
     * @param int $sourceid
     * @param int $targetid
     * @param bool $withusers
     */
    private function copy_course(int $sourceid, int $targetid, bool $withusers): void {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $mode = $withusers ? \backup::MODE_GENERAL : \backup::MODE_IMPORT;
        $bc = new \backup_controller(\backup::TYPE_1COURSE, $sourceid, \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO, $mode, $USER->id);
        $this->assertSame($withusers, (bool) $bc->get_plan()->get_setting('users')->get_value());
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // General backups generate an MBZ. Import backups keep their temporary
        // extracted contents for the restore step on the same site.
        if ($withusers) {
            $files = get_file_storage()->get_area_files(
                \context_course::instance($sourceid)->id, 'backup', 'course', false, 'id DESC', false);
            $this->assertNotEmpty($files);
            $file = reset($files);
            $path = make_backup_temp_directory($backupid, false);
            $this->assertNotFalse(get_file_packer('application/vnd.moodle.backup')->extract_to_pathname($file, $path));
        }

        $rc = new \restore_controller($backupid, $targetid, \backup::INTERACTIVE_NO,
            $mode, $USER->id, \backup::TARGET_EXISTING_ADDING);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();
    }

    /**
     * Userless backups keep a course's non-personal policy, but no proposals.
     */
    public function test_restore_without_users_copies_policy_not_scenarios(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        [$source] = $this->create_fixture();
        $target = $this->getDataGenerator()->create_course();

        $this->copy_course($source->id, $target->id, false);

        $policy = $DB->get_record('local_deadlineplanner_policy', [
            'contextid' => \context_course::instance($target->id)->id,
        ], '*', MUST_EXIST);
        $this->assertEquals(2, $policy->dailycount);
        $this->assertEquals(7, $policy->privacymin);
        $this->assertSame(0, $DB->count_records('local_deadlineplanner_scenario',
            ['courseid' => $target->id]));
        $this->assertFalse($DB->record_exists('local_deadlineplanner_sync',
            ['courseid' => $target->id]));
    }

    /**
     * Full backups must remap course and module references and retain audit actors.
     */
    public function test_restore_with_users_copies_scenarios_and_audit(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        [$source, $sourcecm, $oldid, $author, $reviewer] = $this->create_fixture();
        $target = $this->getDataGenerator()->create_course();

        $this->copy_course($source->id, $target->id, true);

        $targetmodules = $DB->get_records('course_modules', ['course' => $target->id]);
        $this->assertCount(1, $targetmodules);
        $targetcm = reset($targetmodules);
        $this->assertNotEquals($sourcecm->id, $targetcm->id);

        $scenario = $DB->get_record('local_deadlineplanner_scenario',
            ['courseid' => $target->id], '*', MUST_EXIST);
        $this->assertNotEquals($oldid, $scenario->id);
        $this->assertEquals($targetcm->id, $scenario->cmid);
        $this->assertEquals($author->id, $scenario->userid);
        $this->assertEquals($reviewer->id, $scenario->reviewerid);
        $this->assertSame('approved', $scenario->status);
        $this->assertSame(str_repeat('a', 64), $scenario->fingerprint);

        $audits = $DB->get_records('local_deadlineplanner_audit',
            ['scenarioid' => $scenario->id], 'action ASC');
        $this->assertCount(2, $audits);
        $actors = array_map(static fn($row) => (int) $row->actorid, $audits);
        $this->assertContains((int) $author->id, $actors);
        $this->assertContains((int) $reviewer->id, $actors);
        $this->assertFalse($DB->record_exists('local_deadlineplanner_sync',
            ['courseid' => $target->id]));

        $policy = $DB->get_record('local_deadlineplanner_policy', [
            'contextid' => \context_course::instance($target->id)->id,
        ], '*', MUST_EXIST);
        $this->assertEquals(6, $policy->weeklycount);
    }
}
