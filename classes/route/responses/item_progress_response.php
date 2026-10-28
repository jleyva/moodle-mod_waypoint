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
use mod_waypoint\route\schema\item_progress;

/**
 * The calling user's progress against one waypoint.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_progress_response extends response {
    /**
     * Describe a per-item progress response.
     */
    public function __construct() {
        parent::__construct(
            statuscode: 200,
            description: 'The calling user\'s progress against the waypoint',
            content: [
                new json_media_type(
                    schema: new item_progress(),
                    example: new example(
                        name: 'A waypoint that has just been ticked',
                        summary: 'Ticked at a quarter past ten, British Summer Time',
                        value: [
                            'itemId' => 3,
                            'done' => true,
                            'completedAt' => '2026-10-28T10:15:00+01:00',
                        ],
                    ),
                ),
            ],
        );
    }
}
