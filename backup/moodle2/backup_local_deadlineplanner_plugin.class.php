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
 * Backup integration for course policies and activity scenarios.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/backup_local_plugin.class.php');

/**
 * Store policies at course level and proposals at the activity connection point.
 */
class backup_local_deadlineplanner_plugin extends backup_local_plugin {
    /**
     * Course policies have no user information and survive userless backups.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $policy = new backup_nested_element('deadlineplanner_policy', ['id'], [
            'dailycount', 'weeklycount', 'dailyweight', 'privacymin',
            'timecreated', 'timemodified',
        ]);

        $plugin->add_child($policy);
        $policy->set_source_table('local_deadlineplanner_policy',
            ['contextid' => backup::VAR_CONTEXTID]);

        return $plugin;
    }

    /**
     * Activity proposals contain identifiable authors, reviewers and audit actors.
     * Exclude them when the activity's user information is disabled.
     *
     * @return backup_plugin_element|null
     */
    protected function define_module_plugin_structure() {
        if (!$this->get_setting_value('userinfo')) {
            return null;
        }

        $plugin = $this->get_plugin_element();
        $scenarios = new backup_nested_element('deadlineplanner_scenarios');
        $scenario = new backup_nested_element('deadlineplanner_scenario', ['id'], [
            'userid', 'reviewerid', 'olddue', 'newdue', 'fingerprint',
            'status', 'summary', 'timecreated', 'timemodified',
        ]);
        $audits = new backup_nested_element('deadlineplanner_audits');
        $audit = new backup_nested_element('deadlineplanner_audit', ['id'], [
            'actorid', 'action', 'details', 'timecreated',
        ]);

        $plugin->add_child($scenarios);
        $scenarios->add_child($scenario);
        $scenario->add_child($audits);
        $audits->add_child($audit);

        $scenario->set_source_table('local_deadlineplanner_scenario',
            ['cmid' => backup::VAR_MODID]);
        $audit->set_source_table('local_deadlineplanner_audit',
            ['scenarioid' => backup::VAR_PARENTID]);

        $scenario->annotate_ids('user', 'userid');
        $scenario->annotate_ids('user', 'reviewerid');
        $audit->annotate_ids('user', 'actorid');

        // Deadline and synchronization tables are derived from native activities.
        return $plugin;
    }
}
