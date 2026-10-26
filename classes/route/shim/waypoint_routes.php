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

namespace mod_waypoint\route\shim;

use core\router\route;
use core\router\route_controller;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Shims for legacy mod_waypoint URLs.
 *
 * A shim keeps an old-style .php URL working after the page behind it has
 * moved onto the router. The file does not need to exist on disk — the shim
 * route group claims the path either way — so bookmarks, course links and
 * anything else already pointing at the old URL keep resolving.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class waypoint_routes {
    use route_controller;

    /**
     * Send /mod/waypoint/report.php?id=N to the routed report page.
     *
     * The path is relative to the plugin directory: the shim loader prefixes
     * the component's own directory, so '/report.php' becomes
     * /mod/waypoint/report.php. Only core, which may place routes anywhere,
     * gets an empty prefix and writes the whole path itself.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    #[route(
        path: '/report.php',
    )]
    public function report_shim(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        return self::redirect_to_callable(
            $request,
            $response,
            [\mod_waypoint\route\controller\report::class, 'view_report'],
            ['cm' => $this->get_param($request, 'id')],
        );
    }
}
