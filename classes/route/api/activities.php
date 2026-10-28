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

namespace mod_waypoint\route\api;

use core\router\require_login;
use core\router\route;
use core\router\schema\response\payload_response;
use core\router\scope\scopeset;
use mod_waypoint\local\item_manager;
use mod_waypoint\route\parameters\path_activity;
use mod_waypoint\route\responses\activity_response;
use mod_waypoint\route\scope\waypoint\read;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

/**
 * REST API for the waypoint activity itself.
 *
 * This resource exists because a collection may not carry anything but its
 * items and its pagination. The progress summary and the capability flags
 * used to travel with the item list; they belong to the activity, so this is
 * where they now live.
 *
 * Resolves to /api/rest/v2/mod_waypoint/activities/{activityId}
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
    // Note: requirelogin is declared on every method below, not here. The
    // authentication middleware reads it straight off the matched route and
    // never consults the class-level route, so a class-level requirelogin is
    // silently ignored — and the route ends up with no login check at all.
#[route(
    path: '/activities/{activityId}',
    pathtypes: [
        new path_activity(name: 'activityId'),
    ],
)]
class activities {

    /**
     * Fetch one waypoint activity as the calling user sees it.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param stdClass $activitydata The course module record, attached by path_activity.
     * @return payload_response
     */
    #[route(
        path: '',
        method: ['GET'],
        title: 'Fetch a waypoint activity',
        description: 'Fetch the activity, with the calling user\'s progress and capabilities.',
        responses: [
            new activity_response(),
        ],
        requirelogin: new require_login(
            requirelogin: false,
            requirecourselogin: true,
            courseattributename: 'course',
        ),
    )]
    #[scopeset(
        new read(),
    )]
    public function get_activity(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $activitydata,
    ): payload_response {
        global $USER;

        $manager = item_manager::instance_from_cmid((int) $activitydata->id);
        $manager->require_can_view();

        return new payload_response(
            payload: $manager->get_activity_payload($USER->id),
            request: $request,
            response: $response,
        );
    }
}
