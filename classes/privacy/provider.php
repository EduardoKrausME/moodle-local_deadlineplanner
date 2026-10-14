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
 * GDPR export and deletion for scenario and audit ownership.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deadlineplanner\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Provider implementation.
 */
final class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {
    /**
     * Get metadata.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_deadlineplanner_scenario', [
            'userid' => 'privacy:scenario', 'reviewerid' => 'privacy:scenario',
            'cmid' => 'privacy:scenario', 'courseid' => 'privacy:scenario',
            'olddue' => 'privacy:scenario', 'newdue' => 'privacy:scenario',
            'summary' => 'privacy:scenario',
        ], 'privacy:scenario');
        $collection->add_database_table('local_deadlineplanner_audit', [
            'actorid' => 'privacy:audit', 'action' => 'privacy:audit',
            'details' => 'privacy:audit',
        ], 'privacy:audit');
        return $collection;
    }

    /**
     * Get contexts for userid.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $result = new contextlist();
        $sql = 'SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_deadlineplanner_scenario} s ON s.courseid = ctx.instanceid
             LEFT JOIN {local_deadlineplanner_audit} a ON a.scenarioid = s.id
                 WHERE ctx.contextlevel = :level
                   AND (s.userid = :owner OR s.reviewerid = :reviewer OR a.actorid = :actor)';
        $result->add_from_sql($sql, ['level' => CONTEXT_COURSE, 'owner' => $userid,
            'reviewer' => $userid, 'actor' => $userid]);
        return $result;
    }

    /**
     * Get users in context.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        $sql = 'SELECT userid FROM {local_deadlineplanner_scenario} WHERE courseid = :courseid
                UNION SELECT reviewerid FROM {local_deadlineplanner_scenario}
                 WHERE courseid = :reviewcourse AND reviewerid <> 0
                UNION SELECT a.actorid
                    FROM {local_deadlineplanner_audit} a JOIN {local_deadlineplanner_scenario} s ON s.id = a.scenarioid
                   WHERE s.courseid = :auditcourse';
        $userlist->add_from_sql('userid', $sql,
            ['courseid' => $context->instanceid, 'reviewcourse' => $context->instanceid,
                'auditcourse' => $context->instanceid]);
    }

    /**
     * Export user data.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            $sql = 'SELECT s.id, s.cmid, s.olddue, s.newdue, s.status,
                           s.timecreated, s.timemodified
                      FROM {local_deadlineplanner_scenario} s
                     WHERE s.courseid = :courseid AND (s.userid = :owner OR s.reviewerid = :reviewer)';
            $records = $DB->get_records_sql($sql, ['courseid' => $context->instanceid,
                'owner' => $userid, 'reviewer' => $userid]);
            $auditsql = 'SELECT a.id, a.action, a.details, a.timecreated, a.scenarioid
                           FROM {local_deadlineplanner_audit} a
                           JOIN {local_deadlineplanner_scenario} s ON s.id = a.scenarioid
                          WHERE s.courseid = :courseid AND a.actorid = :actor';
            $audits = $DB->get_records_sql($auditsql,
                ['courseid' => $context->instanceid, 'actor' => $userid]);
            writer::with_context($context)->export_data([get_string('pluginname', 'local_deadlineplanner')],
                (object) ['scenarios' => array_values($records), 'audit' => array_values($audits)]);
        }
    }

    /**
     * Delete data for all users in context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        $ids = $DB->get_fieldset_select('local_deadlineplanner_scenario', 'id', 'courseid = :courseid',
            ['courseid' => $context->instanceid]);
        if ($ids) {
            [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('local_deadlineplanner_audit', "scenarioid $insql", $params);
        }
        $DB->delete_records('local_deadlineplanner_scenario', ['courseid' => $context->instanceid]);
    }

    /**
     * Delete data for user.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        self::delete_user_from_contexts((int) $contextlist->get_user()->id, $contextlist->get_contexts());
    }

    /**
     * Delete data for users.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        foreach ($userlist->get_userids() as $userid) {
            self::delete_user_from_contexts((int) $userid, [$userlist->get_context()]);
        }
    }

    /**
     * Delete user from contexts.
     */
    private static function delete_user_from_contexts(int $userid, array $contexts): void {
        global $DB;
        foreach ($contexts as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            $records = $DB->get_records_select('local_deadlineplanner_scenario',
                'courseid = :courseid AND userid = :userid',
                ['courseid' => $context->instanceid, 'userid' => $userid]);
            foreach ($records as $record) {
                $DB->delete_records('local_deadlineplanner_audit', ['scenarioid' => $record->id]);
                $DB->delete_records('local_deadlineplanner_scenario', ['id' => $record->id]);
            }
            $DB->set_field_select('local_deadlineplanner_scenario', 'reviewerid', 0,
                'courseid = :courseid AND reviewerid = :reviewer',
                ['courseid' => $context->instanceid, 'reviewer' => $userid]);
            // Legal audit export/delete policy: delete audit entries owned by the requestor.
            $sql = 'SELECT a.id FROM {local_deadlineplanner_audit} a
                      JOIN {local_deadlineplanner_scenario} s ON s.id = a.scenarioid
                     WHERE s.courseid = :courseid AND a.actorid = :actor';
            $ids = $DB->get_fieldset_sql($sql,
                ['courseid' => $context->instanceid, 'actor' => $userid]);
            if ($ids) {
                [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
                $DB->delete_records_select('local_deadlineplanner_audit', "id $insql", $params);
            }
        }
    }
}
