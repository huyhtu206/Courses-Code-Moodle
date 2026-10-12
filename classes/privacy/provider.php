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
 * Privacy Subsystem implementation for local_courses.
 *
 * @package    local_courses
 * @copyright  2026 Huynh Tu
 * @author     Huynh Tu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courses\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider implementation for local_courses.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe the metadata stored by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_courses_requests',
            [
                'courseid'     => 'privacy:metadata:local_courses_requests:courseid',
                'userid'       => 'privacy:metadata:local_courses_requests:userid',
                'roleid'       => 'privacy:metadata:local_courses_requests:roleid',
                'status'       => 'privacy:metadata:local_courses_requests:status',
                'codeentered'  => 'privacy:metadata:local_courses_requests:codeentered',
                'actionby'     => 'privacy:metadata:local_courses_requests:actionby',
                'timecreated'  => 'privacy:metadata:local_courses_requests:timecreated',
                'timemodified' => 'privacy:metadata:local_courses_requests:timemodified',
            ],
            'privacy:metadata:local_courses_requests'
        );

        return $collection;
    }

    /**
     * Get the list of contexts where the user has data.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course} co ON co.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {local_courses_requests} r ON r.courseid = co.id
                 WHERE r.userid = :userid OR r.actionby = :actionby";

        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid'       => $userid,
            'actionby'     => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $sql = "SELECT userid
                  FROM {local_courses_requests}
                 WHERE courseid = :courseid";
        $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);

        $sql = "SELECT actionby AS userid
                  FROM {local_courses_requests}
                 WHERE courseid = :courseid AND actionby IS NOT NULL";
        $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);
    }

    /**
     * Export all user data for the specified context list.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }

            $courseid = $context->instanceid;
            $requests = $DB->get_records_select(
                'local_courses_requests',
                'courseid = :courseid AND (userid = :userid OR actionby = :actionby)',
                [
                    'courseid' => $courseid,
                    'userid'   => $userid,
                    'actionby' => $userid,
                ]
            );

            if (!empty($requests)) {
                $data = [];
                foreach ($requests as $request) {
                    $data[] = [
                        'status'       => $request->status,
                        'codeentered'  => $request->codeentered,
                        'timecreated'  => transform::datetime($request->timecreated),
                        'timemodified' => transform::datetime($request->timemodified),
                    ];
                }

                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_courses')],
                    (object) ['requests' => $data]
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $DB->delete_records('local_courses_requests', ['courseid' => $context->instanceid]);
        $DB->delete_records('local_courses_codes', ['courseid' => $context->instanceid]);
    }

    /**
     * Delete all user data for the specified user in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }

            $DB->delete_records('local_courses_requests', [
                'courseid' => $context->instanceid,
                'userid'   => $userid,
            ]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $inparams['courseid'] = $context->instanceid;

        $DB->delete_records_select('local_courses_requests', "courseid = :courseid AND userid $insql", $inparams);
    }
}
