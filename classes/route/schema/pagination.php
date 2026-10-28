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
 * Where in a collection the returned page sits.
 *
 * Section 10.1 of the REST style guide fixes these three field names exactly,
 * so do not rename them even though pageSize is spelt pagesize in the query
 * string that selects it. That inconsistency is the guide's, not ours.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pagination extends schema_object implements referenced_object {
    /**
     * Describe the properties of a pagination block.
     */
    public function __construct() {
        parent::__construct(
            content: [
                'page' => new scalar_type(param::INT, required: true),
                'pageSize' => new scalar_type(param::INT, required: true),
                'totalItems' => new scalar_type(param::INT, required: true),
            ],
        );
    }
}
