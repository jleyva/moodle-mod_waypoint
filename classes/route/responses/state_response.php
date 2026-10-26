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

use core\param;
use core\router\schema\example;
use mod_waypoint\route\schema\array_of;
use core\router\schema\objects\scalar_type;
use core\router\schema\objects\schema_object;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\response;
use mod_waypoint\route\schema\waypoint_item;
use mod_waypoint\route\schema\waypoint_progress;

/**
 * The whole state of one waypoint activity for the calling user.
 *
 * Every route in this plugin returns this same shape, including the ones that
 * change something. That keeps the React client trivial — it replaces its
 * state with the response and never has to merge a partial update — and it
 * means there is exactly one response component in the OpenAPI document.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class state_response extends response {
    /**
     * Describe a waypoint state response.
     */
    public function __construct() {
        parent::__construct(
            statuscode: 200,
            description: 'The waypoint list and the calling user\'s progress through it',
            content: [
                new json_media_type(
                    schema: new schema_object(
                        content: [
                            'items' => new array_of(itemtype: new waypoint_item()),
                            'progress' => new waypoint_progress(),
                            'canmanage' => new scalar_type(param::BOOL, required: true),
                            'cantick' => new scalar_type(param::BOOL, required: true),
                        ],
                    ),
                    example: new example(
                        name: 'A part-finished waypoint list',
                        summary: 'Two of three waypoints ticked, viewed by a student',
                        value: [
                            'items' => [
                                ['id' => 1, 'title' => 'Read the brief', 'sortorder' => 1, 'done' => true],
                                ['id' => 2, 'title' => 'Draft an outline', 'sortorder' => 2, 'done' => true],
                                ['id' => 3, 'title' => 'Submit for review', 'sortorder' => 3, 'done' => false],
                            ],
                            'progress' => [
                                'completed' => 2,
                                'total' => 3,
                                'percentage' => 67,
                                'countlabel' => '2 of 3',
                            ],
                            'canmanage' => false,
                            'cantick' => true,
                        ],
                    ),
                ),
            ],
        );
    }
}
