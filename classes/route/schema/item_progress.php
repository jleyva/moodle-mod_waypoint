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
 * The calling user's progress against one waypoint.
 *
 * completedAt is an ISO-8601 string with an offset, in the user's own
 * timezone, because section 9.3 of the style guide forbids Unix timestamps
 * outright. It is null while the waypoint is not ticked; core's scalar_type
 * expresses that as required: false rather than as an explicit nullable.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_progress extends schema_object implements referenced_object {
    /**
     * Describe the properties of a per-item progress record.
     */
    public function __construct() {
        parent::__construct(
            content: [
                'itemId' => new scalar_type(param::INT, required: true),
                'done' => new scalar_type(param::BOOL, required: true),
                'completedAt' => new scalar_type(param::TEXT, required: false),
            ],
        );
    }
}
