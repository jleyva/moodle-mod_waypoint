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

namespace mod_waypoint\local;

use cm_info;
use context_module;
use core\exception\access_denied_exception;
use stdClass;

/**
 * All reads and writes for a single waypoint activity.
 *
 * Everything that touches the database lives here, so the REST routes stay
 * thin and the completion rules cannot drift out of step with the API.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_manager {

    /**
     * Use one of the named constructors instead.
     *
     * @param stdClass $waypoint The activity instance record.
     * @param cm_info|stdClass $cm The course module.
     * @param stdClass $course The course record.
     * @param context_module $context The module context.
     */
    private function __construct(
        /** @var stdClass The activity instance record. */
        public readonly stdClass $waypoint,
        /** @var cm_info|stdClass The course module. */
        public readonly cm_info|stdClass $cm,
        /** @var stdClass The course record. */
        public readonly stdClass $course,
        /** @var context_module The module context. */
        public readonly context_module $context,
    ) {
    }

    /**
     * Build a manager from a course module id.
     *
     * @param int $cmid The course module id.
     * @return self
     */
    public static function instance_from_cmid(int $cmid): self {
        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'waypoint');

        return self::instance_from_cm($cm, $course);
    }

    /**
     * Build a manager from an existing course module.
     *
     * @param cm_info|stdClass $cm The course module.
     * @param null|stdClass $course The course, if it is already loaded.
     * @return self
     */
    public static function instance_from_cm(cm_info|stdClass $cm, ?stdClass $course = null): self {
        global $DB;

        $course ??= $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $waypoint = $DB->get_record('waypoint', ['id' => $cm->instance], '*', MUST_EXIST);

        return new self(
            waypoint: $waypoint,
            cm: $cm,
            course: $course,
            context: context_module::instance($cm->id),
        );
    }

    /**
     * Require that the current user may view this activity.
     */
    public function require_can_view(): void {
        $this->require_capability('mod/waypoint:view', 'errornoview');
    }

    /**
     * Require that the current user may edit the waypoint list.
     */
    public function require_can_manage(): void {
        $this->require_capability('mod/waypoint:manage', 'errornomanage');
    }

    /**
     * Require that the current user may tick their own waypoints.
     */
    public function require_can_tick(): void {
        $this->require_capability('mod/waypoint:tick', 'errornotick');
    }

    /**
     * Check a capability, failing with an exception the router understands.
     *
     * require_capability() throws required_capability_exception, which does not
     * implement response_aware_exception, so the router can only turn it into a
     * 500. access_denied_exception names its own response class and becomes the
     * 403 an API client expects — while still rendering a normal error page
     * when a route is serving HTML.
     *
     * @param string $capability The capability to check.
     * @param string $errorkey The mod_waypoint language string to fail with.
     */
    private function require_capability(string $capability, string $errorkey): void {
        if (!has_capability($capability, $this->context)) {
            throw new access_denied_exception($errorkey, 'mod_waypoint');
        }
    }

    /**
     * Whether the current user may edit the waypoint list.
     *
     * @return bool
     */
    public function can_manage(): bool {
        return has_capability('mod/waypoint:manage', $this->context);
    }

    /**
     * Whether the current user may tick their own waypoints.
     *
     * @return bool
     */
    public function can_tick(): bool {
        return has_capability('mod/waypoint:tick', $this->context);
    }

    /**
     * Fetch every waypoint, with the given user's tick state folded in.
     *
     * @param int $userid The user whose progress should be included.
     * @return array[] A list of ['id', 'title', 'sortorder', 'done'] arrays.
     */
    public function get_items(int $userid): array {
        global $DB;

        $sql = "SELECT i.id, i.title, i.sortorder, p.timecompleted
                  FROM {waypoint_item} i
             LEFT JOIN {waypoint_progress} p
                    ON p.itemid = i.id
                   AND p.userid = :userid
                 WHERE i.waypointid = :waypointid
              ORDER BY i.sortorder ASC, i.id ASC";

        $records = $DB->get_records_sql($sql, [
            'userid' => $userid,
            'waypointid' => $this->waypoint->id,
        ]);

        return array_values(array_map(
            fn(stdClass $record): array => [
                'id' => (int) $record->id,
                'title' => $record->title,
                'sortorder' => (int) $record->sortorder,
                'done' => $record->timecompleted !== null,
            ],
            $records,
        ));
    }

    /**
     * Summarise how far through the list a user is.
     *
     * @param int $userid The user to summarise.
     * @return progress
     */
    public function get_progress_for_user(int $userid): progress {
        global $DB;

        $total = $DB->count_records('waypoint_item', ['waypointid' => $this->waypoint->id]);

        $completed = $DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {waypoint_progress} p
               JOIN {waypoint_item} i ON i.id = p.itemid
              WHERE i.waypointid = :waypointid
                AND p.userid = :userid",
            [
                'waypointid' => $this->waypoint->id,
                'userid' => $userid,
            ],
        );

        return new progress(completed: $completed, total: $total);
    }

    /**
     * Summarise progress for many users in one query.
     *
     * The report page would otherwise run two queries per enrolled user.
     *
     * @param int[] $userids The users to summarise.
     * @return progress[] Keyed by user id. Users with no ticks are included.
     */
    public function get_progress_for_users(array $userids): array {
        global $DB;

        $total = $DB->count_records('waypoint_item', ['waypointid' => $this->waypoint->id]);
        $summaries = array_fill_keys($userids, new progress(completed: 0, total: $total));

        if (empty($userids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
        $counts = $DB->get_records_sql(
            "SELECT p.userid, COUNT(1) AS completed
               FROM {waypoint_progress} p
               JOIN {waypoint_item} i ON i.id = p.itemid
              WHERE i.waypointid = :waypointid
                AND p.userid {$insql}
           GROUP BY p.userid",
            array_merge(['waypointid' => $this->waypoint->id], $inparams),
        );

        foreach ($counts as $count) {
            $summaries[(int) $count->userid] = new progress(
                completed: (int) $count->completed,
                total: $total,
            );
        }

        return $summaries;
    }

    /**
     * Build the full activity state for a user.
     *
     * Every REST route returns this, so the client always receives a complete,
     * authoritative picture rather than a partial update it has to merge.
     *
     * @param int $userid The user whose view of the activity to build.
     * @return array
     */
    public function get_state_payload(int $userid): array {
        $progress = $this->get_progress_for_user($userid);

        return [
            'items' => $this->get_items($userid),
            'progress' => [
                'completed' => $progress->completed,
                'total' => $progress->total,
                'percentage' => $progress->get_percentage(),
                'countlabel' => $progress->get_count_label(),
            ],
            'canmanage' => $this->can_manage(),
            'cantick' => $this->can_tick(),
        ];
    }

    /**
     * Add a waypoint to the end of the list.
     *
     * @param string $title The waypoint title.
     * @return array The new item, in the same shape as get_items().
     */
    public function add_item(string $title): array {
        global $DB;

        $now = \core\di::get(\core\clock::class)->time();
        $maxsort = $DB->get_field_sql(
            'SELECT MAX(sortorder) FROM {waypoint_item} WHERE waypointid = :waypointid',
            ['waypointid' => $this->waypoint->id],
        );

        $itemid = $DB->insert_record('waypoint_item', (object) [
            'waypointid' => $this->waypoint->id,
            'title' => $title,
            'sortorder' => ((int) $maxsort) + 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return [
            'id' => (int) $itemid,
            'title' => $title,
            'sortorder' => ((int) $maxsort) + 1,
            'done' => false,
        ];
    }

    /**
     * Rename an existing waypoint.
     *
     * @param int $itemid The waypoint to rename.
     * @param string $title The new title.
     * @param int $userid The user whose tick state should be reported back.
     * @return array The updated item, in the same shape as get_items().
     */
    public function rename_item(int $itemid, string $title, int $userid): array {
        global $DB;

        $item = $this->get_item($itemid);

        $DB->update_record('waypoint_item', (object) [
            'id' => $item->id,
            'title' => $title,
            'timemodified' => \core\di::get(\core\clock::class)->time(),
        ]);

        return [
            'id' => (int) $item->id,
            'title' => $title,
            'sortorder' => (int) $item->sortorder,
            'done' => $this->is_done($item->id, $userid),
        ];
    }

    /**
     * Delete a waypoint and everyone's progress against it.
     *
     * @param int $itemid The waypoint to delete.
     */
    public function delete_item(int $itemid): void {
        global $DB;

        $item = $this->get_item($itemid);

        $DB->delete_records('waypoint_progress', ['itemid' => $item->id]);
        $DB->delete_records('waypoint_item', ['id' => $item->id]);

        // Deleting the last outstanding waypoint can complete the activity.
        $this->update_completion_for_all();
    }

    /**
     * Tick or untick a waypoint for a user.
     *
     * @param int $itemid The waypoint to update.
     * @param bool $done Whether it is now done.
     * @param int $userid The user ticking it.
     * @return array The updated item, in the same shape as get_items().
     */
    public function set_item_state(int $itemid, bool $done, int $userid): array {
        global $DB;

        $item = $this->get_item($itemid);
        $existing = $DB->get_record('waypoint_progress', [
            'itemid' => $item->id,
            'userid' => $userid,
        ]);

        if ($done && !$existing) {
            $DB->insert_record('waypoint_progress', (object) [
                'itemid' => $item->id,
                'userid' => $userid,
                'timecompleted' => \core\di::get(\core\clock::class)->time(),
            ]);
        } else if (!$done && $existing) {
            $DB->delete_records('waypoint_progress', ['id' => $existing->id]);
        }

        $this->update_completion_for_user($userid);

        return [
            'id' => (int) $item->id,
            'title' => $item->title,
            'sortorder' => (int) $item->sortorder,
            'done' => $done,
        ];
    }

    /**
     * Fetch a waypoint, confirming it belongs to this activity.
     *
     * Routes receive an item id straight from the URL. Scoping the lookup to
     * this instance is what stops a valid id from another activity being used
     * to read or change data the user cannot otherwise reach.
     *
     * @param int $itemid The waypoint id from the request.
     * @return stdClass
     */
    private function get_item(int $itemid): stdClass {
        global $DB;

        return $DB->get_record(
            'waypoint_item',
            [
                'id' => $itemid,
                'waypointid' => $this->waypoint->id,
            ],
            '*',
            MUST_EXIST,
        );
    }

    /**
     * Whether a user has ticked a given waypoint.
     *
     * @param int $itemid The waypoint id.
     * @param int $userid The user id.
     * @return bool
     */
    private function is_done(int $itemid, int $userid): bool {
        global $DB;

        return $DB->record_exists('waypoint_progress', [
            'itemid' => $itemid,
            'userid' => $userid,
        ]);
    }

    /**
     * Re-evaluate the completion state for one user.
     *
     * @param int $userid The user to re-evaluate.
     */
    private function update_completion_for_user(int $userid): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $completion = new \completion_info($this->course);
        if ($completion->is_enabled($this->cm) == COMPLETION_TRACKING_AUTOMATIC) {
            $completion->update_state($this->cm, COMPLETION_UNKNOWN, $userid);
        }
    }

    /**
     * Re-evaluate the completion state for everyone with progress recorded.
     *
     * @return void
     */
    private function update_completion_for_all(): void {
        global $DB, $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $completion = new \completion_info($this->course);
        if ($completion->is_enabled($this->cm) != COMPLETION_TRACKING_AUTOMATIC) {
            return;
        }

        $userids = $DB->get_fieldset_sql(
            "SELECT DISTINCT p.userid
               FROM {waypoint_progress} p
               JOIN {waypoint_item} i ON i.id = p.itemid
              WHERE i.waypointid = :waypointid",
            ['waypointid' => $this->waypoint->id],
        );

        foreach ($userids as $userid) {
            $completion->update_state($this->cm, COMPLETION_UNKNOWN, $userid);
        }
    }
}
