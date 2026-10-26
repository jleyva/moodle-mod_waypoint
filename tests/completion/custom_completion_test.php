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

namespace mod_waypoint\completion;

use advanced_testcase;
use cm_info;
use mod_waypoint\local\item_manager;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the mod_waypoint custom completion rule.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(custom_completion::class)]
final class custom_completion_test extends advanced_testcase {

    /**
     * Build a course with completion on, an activity, its waypoints and a student.
     *
     * @param int $itemcount How many waypoints to create.
     * @return array{0: cm_info, 1: \stdClass, 2: \stdClass[]}
     */
    private function create_activity(int $itemcount): array {
        $generator = self::getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $activity = $generator->create_module('waypoint', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionallwaypoints' => 1,
        ]);

        /** @var \mod_waypoint_generator $modgenerator */
        $modgenerator = $generator->get_plugin_generator('mod_waypoint');
        $items = [];
        for ($i = 1; $i <= $itemcount; $i++) {
            $items[] = $modgenerator->create_item($activity->id, "Waypoint {$i}");
        }

        $student = $generator->create_and_enrol($course, 'student');
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);

        return [$cm, $student, $items];
    }

    /**
     * Get the completion state of the rule for a user.
     *
     * @param cm_info $cm
     * @param int $userid
     * @return int
     */
    private function get_state(cm_info $cm, int $userid): int {
        return (new custom_completion($cm, $userid))->get_state('completionallwaypoints');
    }

    /**
     * A student who has ticked nothing is not complete.
     */
    public function test_untouched_activity_is_incomplete(): void {
        $this->resetAfterTest();

        [$cm, $student] = $this->create_activity(2);

        $this->assertSame(COMPLETION_INCOMPLETE, $this->get_state($cm, $student->id));
    }

    /**
     * A partly-ticked list is not complete.
     */
    public function test_partial_progress_is_incomplete(): void {
        $this->resetAfterTest();

        [$cm, $student, $items] = $this->create_activity(2);
        $manager = item_manager::instance_from_cm($cm);
        $manager->set_item_state($items[0]->id, true, $student->id);

        $this->assertSame(COMPLETION_INCOMPLETE, $this->get_state($cm, $student->id));
    }

    /**
     * Ticking every waypoint completes the activity.
     */
    public function test_every_waypoint_ticked_is_complete(): void {
        $this->resetAfterTest();

        [$cm, $student, $items] = $this->create_activity(2);
        $manager = item_manager::instance_from_cm($cm);
        foreach ($items as $item) {
            $manager->set_item_state($item->id, true, $student->id);
        }

        $this->assertSame(COMPLETION_COMPLETE, $this->get_state($cm, $student->id));
    }

    /**
     * Unticking a waypoint takes completion away again.
     */
    public function test_unticking_reverts_completion(): void {
        $this->resetAfterTest();

        [$cm, $student, $items] = $this->create_activity(2);
        $manager = item_manager::instance_from_cm($cm);
        foreach ($items as $item) {
            $manager->set_item_state($item->id, true, $student->id);
        }
        $manager->set_item_state($items[0]->id, false, $student->id);

        $this->assertSame(COMPLETION_INCOMPLETE, $this->get_state($cm, $student->id));
    }

    /**
     * An activity with no waypoints is never automatically complete.
     *
     * Otherwise a teacher who has not written the list yet would silently mark
     * every student complete.
     */
    public function test_empty_activity_is_never_complete(): void {
        $this->resetAfterTest();

        [$cm, $student] = $this->create_activity(0);

        $this->assertSame(COMPLETION_INCOMPLETE, $this->get_state($cm, $student->id));
    }

    /**
     * Deleting the last outstanding waypoint completes the activity.
     */
    public function test_deleting_the_last_outstanding_waypoint_completes(): void {
        $this->resetAfterTest();

        [$cm, $student, $items] = $this->create_activity(2);
        $manager = item_manager::instance_from_cm($cm);
        $manager->set_item_state($items[0]->id, true, $student->id);

        $manager->delete_item($items[1]->id);

        $this->assertSame(COMPLETION_COMPLETE, $this->get_state($cm, $student->id));
    }
}
