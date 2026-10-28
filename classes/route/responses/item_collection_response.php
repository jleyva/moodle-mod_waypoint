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
use core\router\schema\objects\schema_object;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\response;
use mod_waypoint\route\schema\array_of;
use mod_waypoint\route\schema\pagination;
use mod_waypoint\route\schema\waypoint_item;

/**
 * A page of waypoints.
 *
 * Section 10.1 of the style guide fixes this envelope: a collection endpoint
 * returns items and pagination, and nothing else. That is why the progress
 * summary and the capability flags are not here — they belong to the
 * activity, and activity_response is where they live.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_collection_response extends response {
    /**
     * Describe a page of waypoints.
     */
    public function __construct() {
        parent::__construct(
            statuscode: 200,
            description: 'A page of waypoints, with the calling user\'s ticks folded in',
            content: [
                new json_media_type(
                    schema: new schema_object(
                        content: [
                            'items' => new array_of(itemtype: new waypoint_item()),
                            'pagination' => new pagination(),
                        ],
                    ),
                    example: new example(
                        name: 'The first page of a short list',
                        summary: 'Three waypoints, two of them ticked',
                        value: [
                            'items' => [
                                ['id' => 1, 'title' => 'Read the brief', 'sortOrder' => 1, 'done' => true],
                                ['id' => 2, 'title' => 'Draft an outline', 'sortOrder' => 2, 'done' => true],
                                ['id' => 3, 'title' => 'Submit for review', 'sortOrder' => 3, 'done' => false],
                            ],
                            'pagination' => [
                                'page' => 1,
                                'pageSize' => 25,
                                'totalItems' => 3,
                            ],
                        ],
                    ),
                ),
            ],
        );
    }
}
