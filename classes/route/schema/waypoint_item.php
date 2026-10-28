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

namespace mod_waypoint\route\schema;

use core\param;
use core\router\schema\objects\scalar_type;
use core\router\schema\objects\schema_object;
use core\router\schema\referenced_object;

/**
 * A single waypoint, as returned by the REST API.
 *
 * Implementing referenced_object means this shape is emitted once into
 * #/components/schemas/ in the generated OpenAPI document and referenced by
 * every route that returns a waypoint, rather than being inlined each time.
 *
 * Field names are camelCase because the REST style guide says JSON fields
 * SHOULD be, even though the database columns they come from are not.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class waypoint_item extends schema_object implements referenced_object {
    /**
     * Describe the properties of a waypoint.
     */
    public function __construct() {
        parent::__construct(
            content: [
                'id' => new scalar_type(param::INT, required: true),
                'title' => new scalar_type(param::TEXT, required: true),
                'sortOrder' => new scalar_type(param::INT, required: true),
                'done' => new scalar_type(param::BOOL, required: true),
            ],
        );
    }
}
