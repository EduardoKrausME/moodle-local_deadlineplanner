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
 * Administrator settings.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_deadlineplanner', get_string('pluginname', 'local_deadlineplanner'));
    $settings->add(new admin_setting_configtext('local_deadlineplanner/dailycount',
        get_string('dailycount', 'local_deadlineplanner'), '', 3, PARAM_INT));
    $settings->add(new admin_setting_configtext('local_deadlineplanner/weeklycount',
        get_string('weeklycount', 'local_deadlineplanner'), '', 8, PARAM_INT));
    $settings->add(new admin_setting_configtext('local_deadlineplanner/dailyweight',
        get_string('dailyweight', 'local_deadlineplanner'), '', 4, PARAM_FLOAT));
    $settings->add(new admin_setting_configtext('local_deadlineplanner/weightassign',
        get_string('weightassign', 'local_deadlineplanner'), '', 1, PARAM_FLOAT));
    $settings->add(new admin_setting_configtext('local_deadlineplanner/weightquiz',
        get_string('weightquiz', 'local_deadlineplanner'), '', 1.5, PARAM_FLOAT));
    $settings->add(new admin_setting_configtext('local_deadlineplanner/privacymin',
        get_string('privacymin', 'local_deadlineplanner'), '', 5, PARAM_INT));
    $settings->add(new admin_setting_configtext('local_deadlineplanner/maxlearners',
        get_string('maxlearners', 'local_deadlineplanner'), '', 1500, PARAM_INT));
    $ADMIN->add('localplugins', $settings);
}
