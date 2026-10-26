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

namespace mod_waypoint\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_waypoint.
 *
 * The only personal data stored is which waypoints a user has ticked, and when.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'waypoint_progress',
            [
                'itemid' => 'privacy:metadata:waypoint_progress:itemid',
                'userid' => 'privacy:metadata:waypoint_progress:userid',
                'timecompleted' => 'privacy:metadata:waypoint_progress:timecompleted',
            ],
            'privacy:metadata:waypoint_progress',
        );

        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $contextlist->add_from_sql(
            "SELECT ctx.id
               FROM {waypoint_progress} p
               JOIN {waypoint_item} i ON i.id = p.itemid
               JOIN {waypoint} w ON w.id = i.waypointid
               JOIN {course_modules} cm ON cm.instance = w.id
               JOIN {modules} m ON m.id = cm.module AND m.name = :modname
               JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
              WHERE p.userid = :userid",
            [
                'modname' => 'waypoint',
                'contextlevel' => CONTEXT_MODULE,
                'userid' => $userid,
            ],
        );

        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $userlist->add_from_sql(
            'userid',
            "SELECT p.userid
               FROM {waypoint_progress} p
               JOIN {waypoint_item} i ON i.id = p.itemid
               JOIN {waypoint} w ON w.id = i.waypointid
               JOIN {course_modules} cm ON cm.instance = w.id
               JOIN {modules} m ON m.id = cm.module AND m.name = :modname
              WHERE cm.id = :cmid",
            [
                'modname' => 'waypoint',
                'cmid' => $context->instanceid,
            ],
        );
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $records = $DB->get_records_sql(
                "SELECT p.id, i.title, p.timecompleted
                   FROM {waypoint_progress} p
                   JOIN {waypoint_item} i ON i.id = p.itemid
                   JOIN {waypoint} w ON w.id = i.waypointid
                   JOIN {course_modules} cm ON cm.instance = w.id
                   JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  WHERE cm.id = :cmid
                    AND p.userid = :userid
               ORDER BY i.sortorder ASC",
                [
                    'modname' => 'waypoint',
                    'cmid' => $context->instanceid,
                    'userid' => $userid,
                ],
            );

            if (!$records) {
                continue;
            }

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'mod_waypoint')],
                (object) [
                    'waypoints' => array_values(array_map(
                        fn($record) => (object) [
                            'title' => $record->title,
                            'timecompleted' => transform::datetime($record->timecompleted),
                        ],
                        $records,
                    )),
                ],
            );
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $DB->delete_records_select(
            'waypoint_progress',
            "itemid IN (
                SELECT i.id
                  FROM {waypoint_item} i
                  JOIN {waypoint} w ON w.id = i.waypointid
                  JOIN {course_modules} cm ON cm.instance = w.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid
            )",
            [
                'modname' => 'waypoint',
                'cmid' => $context->instanceid,
            ],
        );
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $DB->delete_records_select(
                'waypoint_progress',
                "userid = :userid
                 AND itemid IN (
                    SELECT i.id
                      FROM {waypoint_item} i
                      JOIN {waypoint} w ON w.id = i.waypointid
                      JOIN {course_modules} cm ON cm.instance = w.id
                      JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                     WHERE cm.id = :cmid
                 )",
                [
                    'userid' => $userid,
                    'modname' => 'waypoint',
                    'cmid' => $context->instanceid,
                ],
            );
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');

        $DB->delete_records_select(
            'waypoint_progress',
            "userid {$insql}
             AND itemid IN (
                SELECT i.id
                  FROM {waypoint_item} i
                  JOIN {waypoint} w ON w.id = i.waypointid
                  JOIN {course_modules} cm ON cm.instance = w.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid
             )",
            array_merge($params, [
                'modname' => 'waypoint',
                'cmid' => $context->instanceid,
            ]),
        );
    }
}
