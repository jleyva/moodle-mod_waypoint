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

use core\exception\invalid_parameter_exception;
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
use mod_waypoint\route\scope\waypoint\manage;
use mod_waypoint\route\scope\waypoint\read;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

/**
 * REST API for the waypoint list itself.
 *
 * The class-level route supplies the shared prefix and the course module
 * parameter. path_coursemodule does the heavy lifting: it resolves the
 * identifier (an id, or `idnumber:...`), attaches the course module record and
 * its context to the request, and returns a 404 on its own if it cannot.
 *
 * Resolves to /api/rest/v2/mod_waypoint/{cm}/items
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
class items {

    /**
     * Fetch the waypoint list and the calling user's progress through it.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param stdClass $cmdata The course module record, attached by path_coursemodule.
     * @return payload_response
     */
    #[route(
        path: '/items',
        method: ['GET'],
        title: 'Fetch waypoints',
        description: 'Fetch every waypoint in the activity, with the calling user\'s progress folded in.',
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
    )]
    public function get_items(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $cmdata,
    ): payload_response {
        global $USER;

        $manager = item_manager::instance_from_cmid((int) $cmdata->id);
        $manager->require_can_view();

        return new payload_response(
            payload: $manager->get_state_payload($USER->id),
            request: $request,
            response: $response,
        );
    }

    /**
     * Add a waypoint to the end of the list.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param stdClass $cmdata The course module record, attached by path_coursemodule.
     * @return payload_response
     */
    #[route(
        path: '/items',
        method: ['POST'],
        title: 'Add a waypoint',
        description: 'Append a new waypoint to the activity.',
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(
                    content: [
                        'title' => new scalar_type(param::TEXT, required: true),
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
        new manage(),
    )]
    public function add_item(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $cmdata,
    ): payload_response {
        global $USER;

        $manager = item_manager::instance_from_cmid((int) $cmdata->id);
        $manager->require_can_manage();
        $manager->add_item($this->get_title_from_request($request));

        return new payload_response(
            payload: $manager->get_state_payload($USER->id),
            request: $request,
            response: $response,
        );
    }

    /**
     * Rename an existing waypoint.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param stdClass $cmdata The course module record, attached by path_coursemodule.
     * @param int $itemid The waypoint to rename.
     * @return payload_response
     */
    #[route(
        path: '/items/{itemid}',
        method: ['PATCH'],
        title: 'Rename a waypoint',
        description: 'Change the title of an existing waypoint.',
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
                        'title' => new scalar_type(param::TEXT, required: true),
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
        new manage(),
    )]
    public function rename_item(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $cmdata,
        int $itemid,
    ): payload_response {
        global $USER;

        $manager = item_manager::instance_from_cmid((int) $cmdata->id);
        $manager->require_can_manage();
        $manager->rename_item($itemid, $this->get_title_from_request($request), $USER->id);

        return new payload_response(
            payload: $manager->get_state_payload($USER->id),
            request: $request,
            response: $response,
        );
    }

    /**
     * Delete a waypoint and everyone's progress against it.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param stdClass $cmdata The course module record, attached by path_coursemodule.
     * @param int $itemid The waypoint to delete.
     * @return payload_response
     */
    #[route(
        path: '/items/{itemid}',
        method: ['DELETE'],
        title: 'Delete a waypoint',
        description: 'Remove a waypoint, and every user\'s progress against it.',
        pathtypes: [
            new path_parameter(
                name: 'itemid',
                type: param::INT,
            ),
        ],
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
        new manage(),
    )]
    public function delete_item(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $cmdata,
        int $itemid,
    ): payload_response {
        global $USER;

        $manager = item_manager::instance_from_cmid((int) $cmdata->id);
        $manager->require_can_manage();
        $manager->delete_item($itemid);

        return new payload_response(
            payload: $manager->get_state_payload($USER->id),
            request: $request,
            response: $response,
        );
    }

    /**
     * Pull a usable waypoint title out of the request body.
     *
     * The schema guarantees the key is present and is a string; it does not
     * guarantee somebody did not send whitespace, so check that here.
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    private function get_title_from_request(ServerRequestInterface $request): string {
        $body = $request->getParsedBody();
        $title = trim(clean_param($body['title'] ?? '', PARAM_TEXT));

        if ($title === '') {
            throw new invalid_parameter_exception('A waypoint needs a title.');
        }

        if (\core_text::strlen($title) > 255) {
            throw new invalid_parameter_exception('A waypoint title cannot be longer than 255 characters.');
        }

        return $title;
    }
}
