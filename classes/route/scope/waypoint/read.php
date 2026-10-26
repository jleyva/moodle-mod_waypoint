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

namespace mod_waypoint\route\scope\waypoint;

/**
 * The mod_waypoint:waypoint:read scope.
 *
 * Guards reading the waypoint list and the caller's own progress.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\router\scope\identifier_attribute('read')]
#[\core\router\scope\summary_attribute('waypoint_read_scope_summary', 'mod_waypoint')]
#[\core\router\scope\description_attribute('waypoint_read_scope_desc', 'mod_waypoint')]
class read extends abstract_scope {
}
