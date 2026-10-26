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
 * Behat data generator for mod_waypoint.
 *
 * @package    mod_waypoint
 * @category   test
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_waypoint_generator extends behat_generator_base {

    #[\Override]
    protected function get_creatable_entities(): array {
        return [
            'items' => [
                'singular' => 'item',
                'datagenerator' => 'item',
                'required' => ['waypoint', 'title'],
                'switchids' => ['waypoint' => 'waypointid'],
            ],
        ];
    }

    /**
     * Resolve a waypoint activity's name or idnumber to its instance id.
     *
     * Named get_waypoint_id() because the entity above declares
     * 'switchids' => ['waypoint' => 'waypointid'], and the base class derives
     * this method name from that mapping.
     *
     * @param string $idnumberorname The activity idnumber or name.
     * @return int The waypoint instance id.
     */
    protected function get_waypoint_id(string $idnumberorname): int {
        return $this->get_cm_by_activity_name('waypoint', $idnumberorname)->instance;
    }
}
