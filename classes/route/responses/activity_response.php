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

namespace mod_waypoint\route\responses;

use core\router\schema\example;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\response;
use mod_waypoint\route\schema\waypoint_activity;

/**
 * One waypoint activity, seen by the calling user.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_response extends response {
    /**
     * Describe an activity response.
     */
    public function __construct() {
        parent::__construct(
            statuscode: 200,
            description: 'The activity, with the calling user\'s progress and capabilities',
            content: [
                new json_media_type(
                    schema: new waypoint_activity(),
                    example: new example(
                        name: 'A part-finished waypoint list',
                        summary: 'Two of three waypoints ticked, viewed by a student',
                        value: [
                            'id' => 42,
                            'name' => 'Week 1 checklist',
                            'progress' => [
                                'completed' => 2,
                                'total' => 3,
                                'percentage' => 67,
                                'countLabel' => '2 of 3',
                            ],
                            'canManage' => false,
                            'canTick' => true,
                        ],
                    ),
                ),
            ],
        );
    }
}
