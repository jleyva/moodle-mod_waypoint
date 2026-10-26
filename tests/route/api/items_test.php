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
 * Tests for the mod_waypoint items REST API.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(items::class)]
#[CoversClass(progress::class)]
final class items_test extends route_testcase {

    /**
     * Register both API classes with the test route loader.
     */
    private function register_routes(): void {
        $this->add_class_routes_to_route_loader(
            items::class,
            route_loader_interface::ROUTE_GROUP_API,
        );
        $this->add_class_routes_to_route_loader(
            progress::class,
            route_loader_interface::ROUTE_GROUP_API,
        );
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
            $modgenerator->create_item($activity->id, 'Read the brief'),
            $modgenerator->create_item($activity->id, 'Draft an outline'),
            $modgenerator->create_item($activity->id, 'Submit for review'),
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
     * A student sees every waypoint, and starts with no progress.
     */
    public function test_get_items_returns_list_and_progress(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student] = $this->create_activity();
        $this->setUser($student);

        $response = $this->process_api_request('GET', "/{$activity->cmid}/items");
        $this->assert_valid_response($response);
        $payload = $this->decode_response($response, true);

        $this->assertCount(3, $payload['items']);
        $this->assertSame('Read the brief', $payload['items'][0]['title']);
        $this->assertFalse($payload['items'][0]['done']);
        $this->assertSame(0, $payload['progress']['completed']);
        $this->assertSame(3, $payload['progress']['total']);
        $this->assertSame(0, $payload['progress']['percentage']);
        $this->assertFalse($payload['canmanage']);
        $this->assertTrue($payload['cantick']);
    }

    /**
     * The items are returned as a JSON list, in sort order — not as a map.
     */
    public function test_get_items_returns_a_json_list(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student] = $this->create_activity();
        $this->setUser($student);

        $response = $this->process_api_request('GET', "/{$activity->cmid}/items");
        $decoded = json_decode((string) $response->getBody(), true);

        $this->assertTrue(
            array_is_list($decoded['items']),
            'The items payload must be a JSON list so it matches the declared array schema.',
        );
    }

    /**
     * Ticking a waypoint updates both the item and the progress summary.
     */
    public function test_set_progress_ticks_a_waypoint(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [, $activity, $student, $items] = $this->create_activity();
        $this->setUser($student);

        $response = $this->process_api_request(
            'PUT',
            "/{$activity->cmid}/progress/{$items[0]->id}",
            $this->json_headers(),
            $this->json_body(['done' => true]),
        );
        $this->assert_valid_response($response);
        $payload = $this->decode_response($response, true);

        $this->assertTrue($payload['items'][0]['done']);
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

        $this->process_api_request(
            'PUT',
            "/{$activity->cmid}/progress/{$items[0]->id}",
            $this->json_headers(),
            $this->json_body(['done' => true]),
        );

        $response = $this->process_api_request(
            'PUT',
            "/{$activity->cmid}/progress/{$items[0]->id}",
            $this->json_headers(),
            $this->json_body(['done' => false]),
        );
        $payload = $this->decode_response($response, true);

        $this->assertFalse($payload['items'][0]['done']);
        $this->assertSame(0, $payload['progress']['completed']);
    }

    /**
     * A teacher can append a waypoint.
     */
    public function test_add_item_as_teacher(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [$course, $activity] = $this->create_activity();
        $teacher = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $response = $this->process_api_request(
            'POST',
            "/{$activity->cmid}/items",
            $this->json_headers(),
            $this->json_body(['title' => 'Celebrate']),
        );
        $this->assert_valid_response($response);
        $payload = $this->decode_response($response, true);

        $this->assertCount(4, $payload['items']);
        $this->assertSame('Celebrate', $payload['items'][3]['title']);
        $this->assertTrue($payload['canmanage']);
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
            "/{$activity->cmid}/items",
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
            "/{$activity->cmid}/items",
            $this->json_headers(),
            $this->json_body(['title' => '   ']),
        );

        $this->assert_invalid_parameter_response($response);
    }

    /**
     * A teacher can rename a waypoint.
     */
    public function test_rename_item(): void {
        $this->resetAfterTest();
        $this->register_routes();

        [$course, $activity, , $items] = $this->create_activity();
        $teacher = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $response = $this->process_api_request(
            'PATCH',
            "/{$activity->cmid}/items/{$items[0]->id}",
            $this->json_headers(),
            $this->json_body(['title' => 'Read the brief carefully']),
        );
        $this->assert_valid_response($response);
        $payload = $this->decode_response($response, true);

        $this->assertSame('Read the brief carefully', $payload['items'][0]['title']);
    }

    /**
     * A teacher can delete a waypoint, and the progress against it goes too.
     */
    public function test_delete_item(): void {
        global $DB;

        $this->resetAfterTest();
        $this->register_routes();

        [$course, $activity, $student, $items] = $this->create_activity();

        $this->setUser($student);
        $this->process_api_request(
            'PUT',
            "/{$activity->cmid}/progress/{$items[0]->id}",
            $this->json_headers(),
            $this->json_body(['done' => true]),
        );
        $this->assertEquals(1, $DB->count_records('waypoint_progress', ['itemid' => $items[0]->id]));

        $teacher = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $response = $this->process_api_request(
            'DELETE',
            "/{$activity->cmid}/items/{$items[0]->id}",
        );
        $this->assert_valid_response($response);
        $payload = $this->decode_response($response, true);

        $this->assertCount(2, $payload['items']);
        $this->assertEquals(0, $DB->count_records('waypoint_progress', ['itemid' => $items[0]->id]));
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

        $response = $this->process_api_request(
            'PUT',
            "/{$activity->cmid}/progress/{$otheritems[0]->id}",
            $this->json_headers(),
            $this->json_body(['done' => true]),
        );

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
            $this->process_api_request(
                'PUT',
                "/{$activity->cmid}/progress/{$item->id}",
                $this->json_headers(),
                $this->json_body(['done' => true]),
            );
        }
        $this->assertEquals(3, $DB->count_records('waypoint_progress', ['userid' => $alice->id]));

        // Bob ticks one, then unticks it again.
        $this->setUser($bob);
        $this->process_api_request(
            'PUT',
            "/{$activity->cmid}/progress/{$items[0]->id}",
            $this->json_headers(),
            $this->json_body(['done' => true]),
        );
        $this->process_api_request(
            'PUT',
            "/{$activity->cmid}/progress/{$items[0]->id}",
            $this->json_headers(),
            $this->json_body(['done' => false]),
        );

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
            [items::class, 'get_items'],
        );
        $this->assert_route_required_scopes(
            [['mod_waypoint:waypoint:read', 'mod_waypoint:waypoint:manage']],
            [items::class, 'add_item'],
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
