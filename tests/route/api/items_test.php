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

use core\router\route_loader_interface;
use core\tests\router\route_testcase;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the mod_waypoint REST API.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(activities::class)]
#[CoversClass(items::class)]
#[CoversClass(progress::class)]
#[CoversClass(\mod_waypoint\local\item_query::class)]
final class items_test extends route_testcase {

    /**
     * Register every API class with the test route loader.
     */
    private function register_routes(): void {
        foreach ([activities::class, items::class, progress::class] as $class) {
            $this->add_class_routes_to_route_loader(
                $class,
                route_loader_interface::ROUTE_GROUP_API,
            );
        }
    }

    /**
     * Build a course, a waypoint activity with three waypoints, and an enrolled student.
     *
     * @return array{0: \stdClass, 1: \stdClass, 2: \stdClass, 3: \stdClass[]}
     */
    private function create_activity(): array {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $activity = $generator->create_module('waypoint', ['course' => $course->id]);

        /** @var \mod_waypoint_generator $modgenerator */
        $modgenerator = $generator->get_plugin_generator('mod_waypoint');
        $items = [
            $modgenerator->create_item(['waypointid' => $activity->id, 'title' => 'Read the brief']),
            $modgenerator->create_item(['waypointid' => $activity->id, 'title' => 'Draft an outline']),
            $modgenerator->create_item(['waypointid' => $activity->id, 'title' => 'Submit for review']),
        ];

        $student = $generator->create_and_enrol($course, 'student');

        return [$course, $activity, $student, $items];
    }

    /**
     * A JSON request body.
     *
     * @param array $payload
     * @return \Psr\Http\Message\StreamInterface
     */
    private function json_body(array $payload) {
        return Utils::streamFor(json_encode($payload));
    }

    /**
     * Headers for a request that carries a JSON body.
     *
     * @return array
     */
    private function json_headers(): array {
        return [
            'HTTP_ACCEPT' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Tick a waypoint as the current user.
     *
     * @param int $cmid
     * @param int $itemid
     * @param bool $done
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function tick(int $cmid, int $itemid, bool $done = true) {
        return $this->process_api_request(
            'PUT',
            "/activities/{$cmid}/items/{$itemid}/progress",
            $this->json_headers(),
            $this->json_body(['done' => $done]),
        );
    }

    /**
     * The activity carries the progress summary and the capability flags.
     */
    public function test_get_activity_returns_progress_and_capabilities(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student] = $this->create_activity();
        $this->setUser($student);

        $response = $this->process_api_request('GET', "/activities/{$activity->cmid}");
        $this->assert_valid_response($response);
        $payload = $this->decode_response($response, true);

        $this->assertSame((int) $activity->cmid, $payload['id']);
        $this->assertSame(0, $payload['progress']['completed']);
        $this->assertSame(3, $payload['progress']['total']);
        $this->assertSame(0, $payload['progress']['percentage']);
        $this->assertArrayHasKey('countLabel', $payload['progress']);
        $this->assertFalse($payload['canManage']);
        $this->assertTrue($payload['canTick']);
    }

    /**
     * A teacher is told they may manage the list.
     */
    public function test_get_activity_tells_a_teacher_they_may_manage(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [$course, $activity] = $this->create_activity();
        $this->setUser(self::getDataGenerator()->create_and_enrol($course, 'editingteacher'));

        $payload = $this->decode_response(
            $this->process_api_request('GET', "/activities/{$activity->cmid}"),
            true,
        );

        $this->assertTrue($payload['canManage']);
    }

    /**
     * The collection answers with items and pagination, and nothing else.
     */
    public function test_get_items_returns_the_collection_envelope(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student] = $this->create_activity();
        $this->setUser($student);

        $response = $this->process_api_request('GET', "/activities/{$activity->cmid}/items");
        $this->assert_valid_response($response);
        $payload = $this->decode_response($response, true);

        $this->assertSame(['items', 'pagination'], array_keys($payload));
        $this->assertCount(3, $payload['items']);
        $this->assertSame('Read the brief', $payload['items'][0]['title']);
        $this->assertSame(1, $payload['items'][0]['sortOrder']);
        $this->assertFalse($payload['items'][0]['done']);
        $this->assertSame(
            ['page' => 1, 'pageSize' => 25, 'totalItems' => 3],
            $payload['pagination'],
        );
    }

    /**
     * The items are returned as a JSON list, in sort order — not as a map.
     */
    public function test_get_items_returns_a_json_list(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student] = $this->create_activity();
        $this->setUser($student);

        $response = $this->process_api_request('GET', "/activities/{$activity->cmid}/items");
        $decoded = json_decode((string) $response->getBody(), true);

        $this->assertTrue(
            array_is_list($decoded['items']),
            'The items payload must be a JSON list so it matches the declared array schema.',
        );
    }

    /**
     * Paging returns a slice, and reports the size of the whole collection.
     */
    public function test_get_items_pages_the_collection(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student] = $this->create_activity();
        $this->setUser($student);

        $first = $this->decode_response(
            $this->process_api_request('GET', "/activities/{$activity->cmid}/items?page=1&pagesize=2"),
            true,
        );
        $second = $this->decode_response(
            $this->process_api_request('GET', "/activities/{$activity->cmid}/items?page=2&pagesize=2"),
            true,
        );

        $this->assertCount(2, $first['items']);
        $this->assertCount(1, $second['items']);
        $this->assertSame(3, $first['pagination']['totalItems']);
        $this->assertSame(3, $second['pagination']['totalItems']);
        $this->assertSame(2, $second['pagination']['page']);
        $this->assertSame('Submit for review', $second['items'][0]['title']);
    }

    /**
     * A page size beyond the maximum is clamped rather than honoured.
     */
    public function test_get_items_clamps_the_page_size(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student] = $this->create_activity();
        $this->setUser($student);

        $payload = $this->decode_response(
            $this->process_api_request('GET', "/activities/{$activity->cmid}/items?pagesize=5000"),
            true,
        );

        $this->assertSame(100, $payload['pagination']['pageSize']);
    }

    /**
     * A leading minus reverses the sort.
     */
    public function test_get_items_sorts_descending(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student] = $this->create_activity();
        $this->setUser($student);

        $payload = $this->decode_response(
            $this->process_api_request('GET', "/activities/{$activity->cmid}/items?sort=-sortorder"),
            true,
        );

        $this->assertSame('Submit for review', $payload['items'][0]['title']);
        $this->assertSame('Read the brief', $payload['items'][2]['title']);
    }

    /**
     * Sorting by something that is not sortable is rejected on the way in.
     */
    public function test_get_items_rejects_an_unknown_sort_key(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student] = $this->create_activity();
        $this->setUser($student);

        $response = $this->process_api_request(
            'GET',
            "/activities/{$activity->cmid}/items?sort=timecreated",
        );

        $this->assert_invalid_parameter_response($response);
    }

    /**
     * The done filter narrows the collection, and the count with it.
     */
    public function test_get_items_filters_by_done(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student, $items] = $this->create_activity();
        $this->setUser($student);
        $this->tick((int) $activity->cmid, (int) $items[0]->id);

        $ticked = $this->decode_response(
            $this->process_api_request('GET', "/activities/{$activity->cmid}/items?done=true"),
            true,
        );
        $unticked = $this->decode_response(
            $this->process_api_request('GET', "/activities/{$activity->cmid}/items?done=false"),
            true,
        );

        $this->assertCount(1, $ticked['items']);
        $this->assertSame('Read the brief', $ticked['items'][0]['title']);
        $this->assertSame(1, $ticked['pagination']['totalItems']);

        $this->assertCount(2, $unticked['items']);
        $this->assertSame(2, $unticked['pagination']['totalItems']);
    }

    /**
     * A done filter that is neither true nor false is a bad request, not a crash.
     *
     * Core's query_parameter would answer 500 here, so the route takes the
     * value raw and item_query rejects it. See the note on the route.
     */
    public function test_get_items_rejects_a_nonsense_done_filter(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student] = $this->create_activity();
        $this->setUser($student);

        $response = $this->process_api_request(
            'GET',
            "/activities/{$activity->cmid}/items?done=maybe",
        );

        $this->assert_invalid_parameter_response($response);
    }

    /**
     * A single waypoint can be fetched on its own.
     */
    public function test_get_item(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student, $items] = $this->create_activity();
        $this->setUser($student);

        $response = $this->process_api_request(
            'GET',
            "/activities/{$activity->cmid}/items/{$items[1]->id}",
        );
        $this->assert_valid_response($response);
        $payload = $this->decode_response($response, true);

        $this->assertSame((int) $items[1]->id, $payload['id']);
        $this->assertSame('Draft an outline', $payload['title']);
    }

    /**
     * Ticking a waypoint answers with that waypoint's progress.
     */
    public function test_set_progress_ticks_a_waypoint(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student, $items] = $this->create_activity();
        $this->setUser($student);

        $response = $this->tick((int) $activity->cmid, (int) $items[0]->id);
        $this->assert_valid_response($response);
        $payload = $this->decode_response($response, true);

        $this->assertSame((int) $items[0]->id, $payload['itemId']);
        $this->assertTrue($payload['done']);
        $this->assertNotNull($payload['completedAt']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $payload['completedAt'],
            'completedAt must be ISO-8601 with an offset, never a Unix timestamp.',
        );
    }

    /**
     * The activity's progress summary moves when a waypoint is ticked.
     */
    public function test_ticking_moves_the_activity_progress(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student, $items] = $this->create_activity();
        $this->setUser($student);
        $this->tick((int) $activity->cmid, (int) $items[0]->id);

        $payload = $this->decode_response(
            $this->process_api_request('GET', "/activities/{$activity->cmid}"),
            true,
        );

        $this->assertSame(1, $payload['progress']['completed']);
        $this->assertSame(33, $payload['progress']['percentage']);
    }

    /**
     * Unticking a waypoint removes the progress again.
     */
    public function test_set_progress_unticks_a_waypoint(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student, $items] = $this->create_activity();
        $this->setUser($student);

        $this->tick((int) $activity->cmid, (int) $items[0]->id);
        $payload = $this->decode_response(
            $this->tick((int) $activity->cmid, (int) $items[0]->id, false),
            true,
        );

        $this->assertFalse($payload['done']);
        $this->assertNull($payload['completedAt']);
    }

    /**
     * A teacher can append a waypoint, and is told where it now lives.
     */
    public function test_add_item_as_teacher(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [$course, $activity] = $this->create_activity();
        $teacher = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $response = $this->process_api_request(
            'POST',
            "/activities/{$activity->cmid}/items",
            $this->json_headers(),
            $this->json_body(['title' => 'Celebrate']),
        );
        $this->assert_valid_response($response, 201);
        $payload = $this->decode_response($response, true);

        $this->assertSame('Celebrate', $payload['title']);
        $this->assertSame(4, $payload['sortOrder']);
        $this->assertFalse($payload['done']);
        $this->assertStringEndsWith(
            "/activities/{$activity->cmid}/items/{$payload['id']}",
            $response->getHeaderLine('Location'),
            'A 201 must say where the created waypoint lives.',
        );
    }

    /**
     * The address in the Location header is one a client can actually fetch.
     */
    public function test_the_created_location_is_fetchable(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [$course, $activity] = $this->create_activity();
        $this->setUser(self::getDataGenerator()->create_and_enrol($course, 'editingteacher'));

        $created = $this->decode_response(
            $this->process_api_request(
                'POST',
                "/activities/{$activity->cmid}/items",
                $this->json_headers(),
                $this->json_body(['title' => 'Celebrate']),
            ),
            true,
        );

        $fetched = $this->decode_response(
            $this->process_api_request(
                'GET',
                "/activities/{$activity->cmid}/items/{$created['id']}",
            ),
            true,
        );

        $this->assertSame($created, $fetched);
    }

    /**
     * A student cannot add a waypoint, even though they may read the list.
     */
    public function test_add_item_as_student_is_denied(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student] = $this->create_activity();
        $this->setUser($student);

        $response = $this->process_api_request(
            'POST',
            "/activities/{$activity->cmid}/items",
            $this->json_headers(),
            $this->json_body(['title' => 'Should not appear']),
        );

        $this->assert_access_denied_response($response);
    }

    /**
     * An empty title is rejected rather than stored.
     */
    public function test_add_item_rejects_a_blank_title(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [$course, $activity] = $this->create_activity();
        $teacher = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $response = $this->process_api_request(
            'POST',
            "/activities/{$activity->cmid}/items",
            $this->json_headers(),
            $this->json_body(['title' => '   ']),
        );

        $this->assert_invalid_parameter_response($response);
    }

    /**
     * A teacher can rename a waypoint, and gets the waypoint back.
     */
    public function test_rename_item(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [$course, $activity, , $items] = $this->create_activity();
        $teacher = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $response = $this->process_api_request(
            'PATCH',
            "/activities/{$activity->cmid}/items/{$items[0]->id}",
            $this->json_headers(),
            $this->json_body(['title' => 'Read the brief carefully']),
        );
        $this->assert_valid_response($response);
        $payload = $this->decode_response($response, true);

        $this->assertSame((int) $items[0]->id, $payload['id']);
        $this->assertSame('Read the brief carefully', $payload['title']);
    }

    /**
     * Deleting a waypoint answers 204 with an empty body, and takes the progress with it.
     */
    public function test_delete_item(): void {
        global $DB;

        $this->resetAfterTest();
        $this->register_routes();

        [$course, $activity, $student, $items] = $this->create_activity();

        $this->setUser($student);
        $this->tick((int) $activity->cmid, (int) $items[0]->id);
        $this->assertEquals(1, $DB->count_records('waypoint_progress', ['itemid' => $items[0]->id]));

        $teacher = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $response = $this->process_api_request(
            'DELETE',
            "/activities/{$activity->cmid}/items/{$items[0]->id}",
        );
        $this->assert_valid_response($response, 204);

        $this->assertSame('', (string) $response->getBody(), 'A 204 must not carry a body.');
        $this->assertEquals(0, $DB->count_records('waypoint_progress', ['itemid' => $items[0]->id]));
        $this->assertEquals(2, $DB->count_records('waypoint_item', ['waypointid' => $activity->id]));
    }

    /**
     * A waypoint id belonging to another activity is not reachable through this one.
     */
    public function test_item_from_another_activity_is_not_found(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student] = $this->create_activity();
        [, $otheractivity, , $otheritems] = $this->create_activity();

        self::getDataGenerator()->enrol_user(
            $student->id,
            $otheractivity->course,
            'student',
        );
        $this->setUser($student);

        $response = $this->tick((int) $activity->cmid, (int) $otheritems[0]->id);

        $this->assert_exception_response($response);
    }

    /**
     * One user's ticking must never touch another user's progress.
     *
     * progress rows are keyed by (itemid, userid); a bug that dropped the
     * userid from either the write or the delete would silently destroy other
     * students' records, and the acting user would see nothing wrong.
     */
    public function test_ticking_does_not_disturb_another_users_progress(): void {
        global $DB;

        $this->resetAfterTest();
        $this->register_routes();

        [$course, $activity, $alice, $items] = $this->create_activity();
        $bob = self::getDataGenerator()->create_and_enrol($course, 'student');

        // Alice ticks all three.
        $this->setUser($alice);
        foreach ($items as $item) {
            $this->tick((int) $activity->cmid, (int) $item->id);
        }
        $this->assertEquals(3, $DB->count_records('waypoint_progress', ['userid' => $alice->id]));

        // Bob ticks one, then unticks it again.
        $this->setUser($bob);
        $this->tick((int) $activity->cmid, (int) $items[0]->id);
        $this->tick((int) $activity->cmid, (int) $items[0]->id, false);

        $this->assertEquals(
            0,
            $DB->count_records('waypoint_progress', ['userid' => $bob->id]),
            'Bob unticked his own waypoint, so he should have no progress left.',
        );
        $this->assertEquals(
            3,
            $DB->count_records('waypoint_progress', ['userid' => $alice->id]),
            'Alice still ticked three waypoints; Bob acting must not have removed any of them.',
        );
    }

    /**
     * Every route declares the scopes it needs.
     */
    public function test_routes_declare_their_scopes(): void {
        $this->assert_route_required_scopes(
            [['mod_waypoint:waypoint:read']],
            [activities::class, 'get_activity'],
        );
        $this->assert_route_required_scopes(
            [['mod_waypoint:waypoint:read']],
            [items::class, 'get_items'],
        );
        $this->assert_route_required_scopes(
            [['mod_waypoint:waypoint:read']],
            [items::class, 'get_item'],
        );
        $this->assert_route_required_scopes(
            [['mod_waypoint:waypoint:read', 'mod_waypoint:waypoint:manage']],
            [items::class, 'add_item'],
        );
        $this->assert_route_required_scopes(
            [['mod_waypoint:waypoint:read', 'mod_waypoint:waypoint:manage']],
            [items::class, 'rename_item'],
        );
        $this->assert_route_required_scopes(
            [['mod_waypoint:waypoint:read', 'mod_waypoint:waypoint:manage']],
            [items::class, 'delete_item'],
        );
        $this->assert_route_required_scopes(
            [['mod_waypoint:waypoint:read', 'mod_waypoint:waypoint:write']],
            [progress::class, 'set_progress'],
        );
    }
}
