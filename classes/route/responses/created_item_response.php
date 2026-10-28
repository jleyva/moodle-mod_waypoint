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
use core\router\schema\header_object;

/**
 * A waypoint that has just been created.
 *
 * Section 11.3 of the style guide requires a POST to answer 201, to carry a
 * Location header pointing at the new resource, and to return the created
 * representation. Core has no response that does this, so the header is
 * declared here and set on the response in the route itself.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class created_item_response extends item_response {
    /**
     * Describe a 201 Created response.
     */
    public function __construct() {
        parent::__construct(
            statuscode: 201,
            description: 'The waypoint that was created',
            headers: [
                new header_object(
                    name: 'Location',
                    type: param::URL,
                    description: 'The address of the waypoint that was created',
                ),
            ],
        );
    }
}
