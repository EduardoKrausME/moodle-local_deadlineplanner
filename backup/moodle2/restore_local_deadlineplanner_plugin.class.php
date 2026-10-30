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
 * Restore course policy and activity-scoped scenario data.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/restore_local_plugin.class.php');

/**
 * Restore plugin data through Moodle's course and module connection points.
 */
class restore_local_deadlineplanner_plugin extends restore_local_plugin {
    /**
     * @return restore_path_element[]
     */
    protected function define_course_plugin_structure() {
        return [
            new restore_path_element('deadlineplanner_policy',
                $this->get_pathfor('/deadlineplanner_policy')),
        ];
    }

    /**
     * @return restore_path_element[]
     */
    protected function define_module_plugin_structure() {
        if (!$this->get_setting_value('userinfo')) {
            return [];
        }

        return [
            new restore_path_element('deadlineplanner_scenario',
                $this->get_pathfor('/deadlineplanner_scenarios/deadlineplanner_scenario')),
            new restore_path_element('deadlineplanner_audit',
                $this->get_pathfor('/deadlineplanner_scenarios/deadlineplanner_scenario/' .
                    'deadlineplanner_audits/deadlineplanner_audit')),
        ];
    }

    /**
     * The original context ID must never be used in the target course.
     *
     * @param array $data
     */
    public function process_deadlineplanner_policy($data): void {
        global $DB;

        $data = (object) $data;
        $data->contextid = \context_course::instance($this->task->get_courseid())->id;
        $existing = $DB->get_record('local_deadlineplanner_policy', ['contextid' => $data->contextid]);

        if ($existing) {
            $data->id = $existing->id;
            $DB->update_record('local_deadlineplanner_policy', $data);
        } else {
            unset($data->id);
            $DB->insert_record('local_deadlineplanner_policy', $data);
        }
    }

    /**
     * Restore a proposal only when its required user mappings exist.
     *
     * @param array $data
     */
    public function process_deadlineplanner_scenario($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $userid = $this->get_mappingid('user', (int) $data->userid, 0);
        $reviewerid = !empty($data->reviewerid) ?
            $this->get_mappingid('user', (int) $data->reviewerid, 0) : 0;
        if (!$userid || (!empty($data->reviewerid) && !$reviewerid)) {
            return;
        }

        unset($data->id);
        $data->courseid = $this->task->get_courseid();
        $data->cmid = $this->task->get_moduleid();
        $data->userid = $userid;
        $data->reviewerid = $reviewerid;

        // Keep historical timestamps and fingerprint: the approval service
        // must revalidate the target module before any proposed change.
        $newid = $DB->insert_record('local_deadlineplanner_scenario', $data);
        $this->set_mapping('deadlineplanner_scenario', $oldid, $newid);
    }

    /**
     * The nested scenario mapping supplies the new parent ID.
     *
     * @param array $data
     */
    public function process_deadlineplanner_audit($data): void {
        global $DB;

        $scenarioid = $this->get_new_parentid('deadlineplanner_scenario');
        if (!$scenarioid) {
            return;
        }

        $data = (object) $data;
        $actorid = $this->get_mappingid('user', (int) $data->actorid, 0);
        if (!$actorid) {
            return;
        }

        unset($data->id);
        $data->scenarioid = $scenarioid;
        $data->actorid = $actorid;
        $DB->insert_record('local_deadlineplanner_audit', $data);
    }

    /**
     * Force the next read to rebuild deadline snapshots for the restored course.
     */
    protected function after_restore_module(): void {
        global $DB;

        $courseid = $this->task->get_courseid();
        $DB->delete_records('local_deadlineplanner_sync', ['courseid' => $courseid]);
        \cache::make('local_deadlineplanner', 'course_deadlines')->delete((string) $courseid);
    }
}
