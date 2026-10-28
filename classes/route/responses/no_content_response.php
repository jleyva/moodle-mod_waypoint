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

use core\router\schema\response\response;

/**
 * A 204, for a request that succeeded and has nothing to say.
 *
 * The style guide lists 204 in its status table but never says which verbs
 * may use it. DELETE is the conventional answer: the resource is gone, so
 * there is no representation left to return.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class no_content_response extends response {
    /**
     * Describe a 204 response.
     *
     * @param string $description What the status code means here.
     */
    public function __construct(string $description = 'The resource no longer exists') {
        parent::__construct(
            statuscode: 204,
            description: $description,
        );
    }
}
