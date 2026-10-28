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
use mod_waypoint\route\schema\waypoint_item;

/**
 * A single waypoint.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_response extends response {
    /**
     * Describe a single-waypoint response.
     *
     * @param int $statuscode The status code this response describes.
     * @param string $description What the status code means here.
     * @param array $headers Any headers the response carries.
     */
    public function __construct(
        int $statuscode = 200,
        string $description = 'The waypoint, with the calling user\'s tick folded in',
        array $headers = [],
    ) {
        parent::__construct(
            statuscode: $statuscode,
            description: $description,
            headers: $headers,
            content: [
                new json_media_type(
                    schema: new waypoint_item(),
                    example: new example(
                        name: 'An unticked waypoint',
                        summary: 'The third waypoint in a list',
                        value: [
                            'id' => 3,
                            'title' => 'Submit for review',
                            'sortOrder' => 3,
                            'done' => false,
                        ],
                    ),
                ),
            ],
        );
    }
}
