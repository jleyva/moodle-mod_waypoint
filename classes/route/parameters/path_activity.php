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

namespace mod_waypoint\route\parameters;

use core\router\parameters\path_coursemodule;

/**
 * The {activityId} path parameter.
 *
 * path_coursemodule names the request attributes it attaches after the
 * parameter: a parameter called `cm` produces `cmdata` and `cmcontext`. The
 * REST style guide requires path parameters to be camelCase and to end in
 * `Id`, which would otherwise leave the handler signature asking for
 * `$activityIddata` — a camelCase local variable, which Moodle's own coding
 * style forbids.
 *
 * Overriding get_value_name() decouples the two: the parameter keeps the name
 * the style guide wants, and the attributes keep the name the PHP coding
 * style wants.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class path_activity extends path_coursemodule {
    /**
     * Name the attached request attributes after the activity, not the parameter.
     *
     * @param string $suffix Either 'data' or 'context'.
     * @return string
     */
    #[\Override]
    protected function get_value_name(string $suffix): string {
        return 'activity' . $suffix;
    }
}
