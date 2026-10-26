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
 * Test data generator for mod_waypoint.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Generator for waypoint activities and their waypoints.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_waypoint_generator extends testing_module_generator {

    #[\Override]
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;

        $record->completionallwaypoints ??= 0;

        return parent::create_instance($record, (array) $options);
    }

    /**
     * Add a waypoint to an activity.
     *
     * @param int $waypointid The activity instance id.
     * @param string $title The waypoint title.
     * @param null|int $sortorder The position, defaulting to the end of the list.
     * @return stdClass The new waypoint_item record.
     */
    public function create_item(int $waypointid, string $title, ?int $sortorder = null): stdClass {
        global $DB;

        $sortorder ??= (int) $DB->get_field_sql(
            'SELECT MAX(sortorder) FROM {waypoint_item} WHERE waypointid = :waypointid',
            ['waypointid' => $waypointid],
        ) + 1;

        $now = time();
        $id = $DB->insert_record('waypoint_item', (object) [
            'waypointid' => $waypointid,
            'title' => $title,
            'sortorder' => $sortorder,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return $DB->get_record('waypoint_item', ['id' => $id], '*', MUST_EXIST);
    }
}
