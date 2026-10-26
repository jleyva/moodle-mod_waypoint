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

use core\param;
use core\router\parameters\path_coursemodule;
use core\router\require_login;
use core\router\route;
use core\router\schema\objects\scalar_type;
use core\router\schema\objects\schema_object;
use core\router\schema\parameters\path_parameter;
use core\router\schema\request_body;
use core\router\schema\response\content\payload_response_type;
use core\router\schema\response\payload_response;
use core\router\scope\scopeset;
use mod_waypoint\local\item_manager;
use mod_waypoint\route\responses\state_response;
use mod_waypoint\route\scope\waypoint\read;
use mod_waypoint\route\scope\waypoint\write;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

/**
 * REST API for the calling user's own progress.
 *
 * Split from the items API on purpose: ticking a waypoint and editing the
 * waypoint list are different privileges, so they get different capabilities
 * and different OAuth2 scopes. A token granted mod_waypoint:waypoint:write can
 * tick boxes but cannot touch the list.
 *
 * Resolves to /api/rest/v2/mod_waypoint/{cm}/progress/{itemid}
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
    path: '/{cm}',
    pathtypes: [
        new path_coursemodule(name: 'cm'),
    ],
)]
class progress {

    /**
     * Tick or untick one waypoint for the calling user.
     *
     * PUT rather than POST because the operation is idempotent: sending
     * done=true twice leaves the same state behind.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param stdClass $cmdata The course module record, attached by path_coursemodule.
     * @param int $itemid The waypoint to tick or untick.
     * @return payload_response
     */
    #[route(
        path: '/progress/{itemid}',
        method: ['PUT'],
        title: 'Tick or untick a waypoint',
        description: 'Record, or withdraw, the calling user\'s completion of a single waypoint.',
        pathtypes: [
            new path_parameter(
                name: 'itemid',
                type: param::INT,
            ),
        ],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(
                    content: [
                        'done' => new scalar_type(param::BOOL, required: true),
                    ],
                ),
            ),
        ),
        responses: [
            new state_response(),
        ],
        requirelogin: new require_login(
            requirelogin: false,
            requirecourselogin: true,
            courseattributename: 'course',
        ),
    )]
    #[scopeset(
        new read(),
        new write(),
    )]
    public function set_progress(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $cmdata,
        int $itemid,
    ): payload_response {
        global $USER;

        $manager = item_manager::instance_from_cmid((int) $cmdata->id);
        $manager->require_can_tick();

        $body = $request->getParsedBody();
        $done = (bool) clean_param($body['done'] ?? false, PARAM_BOOL);

        $manager->set_item_state($itemid, $done, $USER->id);

        return new payload_response(
            payload: $manager->get_state_payload($USER->id),
            request: $request,
            response: $response,
        );
    }
}
