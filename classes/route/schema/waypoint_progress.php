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
 * A user's progress through a waypoint list, as returned by the REST API.
 *
 * countlabel is built server-side on purpose: plural rules and word order
 * belong to the site language, not to the JavaScript that renders the bar.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class waypoint_progress extends schema_object implements referenced_object {
    /**
     * Describe the properties of a progress summary.
     */
    public function __construct() {
        parent::__construct(
            content: [
                'completed' => new scalar_type(param::INT, required: true),
                'total' => new scalar_type(param::INT, required: true),
                'percentage' => new scalar_type(param::INT, required: true),
                'countlabel' => new scalar_type(param::TEXT, required: true),
            ],
        );
    }
}
