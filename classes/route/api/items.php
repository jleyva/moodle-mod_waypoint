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
use core\router\require_login;
use core\router\route;
use core\router\schema\objects\scalar_type;
use core\router\schema\objects\schema_object;
use core\router\schema\parameters\path_parameter;
use core\router\schema\parameters\query_parameter;
use core\router\schema\request_body;
use core\router\schema\response\content\payload_response_type;
use core\router\schema\response\payload_response;
use core\router\scope\scopeset;
use core\router\util;
use mod_waypoint\local\item_manager;
use mod_waypoint\local\item_query;
use mod_waypoint\route\parameters\path_activity;
use mod_waypoint\route\responses\created_item_response;
use mod_waypoint\route\responses\item_collection_response;
use mod_waypoint\route\responses\item_response;
use mod_waypoint\route\responses\no_content_response;
use mod_waypoint\route\scope\waypoint\manage;
use mod_waypoint\route\scope\waypoint\read;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

/**
 * REST API for the waypoints inside one activity.
 *
 * The class-level route supplies the shared prefix and the activity
 * parameter. path_activity does the heavy lifting: it resolves the identifier
 * (an id, or `idnumber:...`), attaches the course module record and its
 * context to the request, and returns a 404 on its own if it cannot.
 *
 * Resolves to /api/rest/v2/mod_waypoint/activities/{activityId}/items
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
class items {

    /**
     * Fetch one page of waypoints.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param stdClass $activitydata The course module record, attached by path_activity.
     * @return payload_response
     */
    #[route(
        path: '/items',
        method: ['GET'],
        title: 'Fetch waypoints',
        description: 'Fetch a page of waypoints, with the calling user\'s ticks folded in.',
        queryparams: [
            new query_parameter(
                name: 'page',
                type: param::INT,
                default: 1,
                description: 'The 1-based page to return.',
            ),
            new query_parameter(
                name: 'pagesize',
                type: param::INT,
                default: item_query::DEFAULT_PAGE_SIZE,
                description: 'How many waypoints to return, up to ' . item_query::MAX_PAGE_SIZE . '.',
            ),
            new query_parameter(
                name: 'sort',
                type: param::TEXT,
                default: 'sortorder',
                description: 'Comma-separated sort keys from sortorder, title and id. Prefix with - for descending.',
            ),
            // Declared RAW, not BOOL, on purpose. Core's query_parameter
            // throws a bare \ValueError for a BOOL it cannot read, and
            // nothing maps that to a status — so `?done=maybe` answers 500
            // where it should answer 400. Parsing it in item_query keeps the
            // bad request a bad request.
            new query_parameter(
                name: 'done',
                type: param::RAW,
                description: 'Keep only the ticked waypoints (true) or only the unticked ones (false).',
            ),
        ],
        responses: [
            new item_collection_response(),
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
        stdClass $activitydata,
    ): payload_response {
        global $USER;

        $manager = item_manager::instance_from_cmid((int) $activitydata->id);
        $manager->require_can_view();

        return new payload_response(
            payload: $manager->get_item_page(
                userid: $USER->id,
                query: item_query::from_query_params($request->getQueryParams()),
            ),
            request: $request,
            response: $response,
        );
    }

    /**
     * Fetch one waypoint.
     *
     * This route is what the Location header of a newly created waypoint
     * points at, so it has to exist for that header to be worth sending.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param stdClass $activitydata The course module record, attached by path_activity.
     * @param int $itemId The waypoint to fetch.
     * @return payload_response
     */
    #[route(
        path: '/items/{itemId}',
        method: ['GET'],
        title: 'Fetch a waypoint',
        description: 'Fetch a single waypoint, with the calling user\'s tick folded in.',
        pathtypes: [
            new path_parameter(
                name: 'itemId',
                type: param::INT,
            ),
        ],
        responses: [
            new item_response(),
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
    public function get_item(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $activitydata,
        // phpcs:ignore moodle.NamingConventions.ValidVariableName.VariableNameLowerCase
        int $itemId,
    ): payload_response {
        global $USER;

        $manager = item_manager::instance_from_cmid((int) $activitydata->id);
        $manager->require_can_view();

        return new payload_response(
            payload: $manager->get_item_payload($itemId, $USER->id),
            request: $request,
            response: $response,
        );
    }

    /**
     * Add a waypoint to the end of the list.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param stdClass $activitydata The course module record, attached by path_activity.
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
            new created_item_response(),
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
        stdClass $activitydata,
    ): payload_response {
        $manager = item_manager::instance_from_cmid((int) $activitydata->id);
        $manager->require_can_manage();
        $item = $manager->add_item($this->get_title_from_request($request));

        // 201 Created, and a Location pointing at the waypoint that now
        // exists. get_path_for_callable() builds that address from the route
        // itself, so the two cannot drift apart.
        $location = util::get_path_for_callable(
            [self::class, 'get_item'],
            [
                'activityId' => (int) $activitydata->id,
                'itemId' => $item['id'],
            ],
        );

        return new payload_response(
            payload: $item,
            request: $request,
            response: $response
                ->withStatus(201)
                ->withHeader('Location', $location->out(false)),
        );
    }

    /**
     * Rename an existing waypoint.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param stdClass $activitydata The course module record, attached by path_activity.
     * @param int $itemId The waypoint to rename.
     * @return payload_response
     */
    #[route(
        path: '/items/{itemId}',
        method: ['PATCH'],
        title: 'Rename a waypoint',
        description: 'Change the title of an existing waypoint.',
        pathtypes: [
            new path_parameter(
                name: 'itemId',
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
            new item_response(description: 'The waypoint that was renamed'),
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
        stdClass $activitydata,
        // phpcs:ignore moodle.NamingConventions.ValidVariableName.VariableNameLowerCase
        int $itemId,
    ): payload_response {
        global $USER;

        $manager = item_manager::instance_from_cmid((int) $activitydata->id);
        $manager->require_can_manage();

        return new payload_response(
            payload: $manager->rename_item($itemId, $this->get_title_from_request($request), $USER->id),
            request: $request,
            response: $response,
        );
    }

    /**
     * Delete a waypoint and everyone's progress against it.
     *
     * Returns the response object rather than a payload_response: there is no
     * representation left to send, and a 204 must not carry a body.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param stdClass $activitydata The course module record, attached by path_activity.
     * @param int $itemId The waypoint to delete.
     * @return ResponseInterface
     */
    #[route(
        path: '/items/{itemId}',
        method: ['DELETE'],
        title: 'Delete a waypoint',
        description: 'Remove a waypoint, and every user\'s progress against it.',
        pathtypes: [
            new path_parameter(
                name: 'itemId',
                type: param::INT,
            ),
        ],
        responses: [
            new no_content_response(description: 'The waypoint was deleted'),
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
        stdClass $activitydata,
        // phpcs:ignore moodle.NamingConventions.ValidVariableName.VariableNameLowerCase
        int $itemId,
    ): ResponseInterface {
        $manager = item_manager::instance_from_cmid((int) $activitydata->id);
        $manager->require_can_manage();
        $manager->delete_item($itemId);

        return $response->withStatus(204);
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
