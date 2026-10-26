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

namespace mod_waypoint;

use advanced_testcase;
use context_module;
use navigation_node;
use PHPUnit\Framework\Attributes\CoversFunction;

defined('MOODLE_INTERNAL') || die();

global $CFG;
// lib.php is normally pulled in by the module machinery; a test that calls its
// functions directly has to load it itself.
require_once($CFG->dirroot . '/mod/waypoint/lib.php');

/**
 * Tests for the mod_waypoint library functions.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('waypoint_supports')]
#[CoversFunction('waypoint_extend_settings_navigation')]
final class lib_test extends advanced_testcase {

    /**
     * Build a course and a waypoint activity.
     *
     * @return array{0: \stdClass, 1: \cm_info}
     */
    private function create_activity(): array {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $activity = $generator->create_module('waypoint', ['course' => $course->id]);
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);

        return [$course, $cm];
    }

    /**
     * Put $PAGE on the activity so settingsnav can be built.
     *
     * @param \cm_info $cm
     */
    private function prepare_page(\cm_info $cm): void {
        global $PAGE;

        $PAGE->set_cm($cm);
        $PAGE->set_context(context_module::instance($cm->id));
        $PAGE->set_url('/mod/waypoint/view.php', ['id' => $cm->id]);
    }

    /**
     * The module declares the features it actually implements.
     */
    public function test_waypoint_supports(): void {
        $this->assertTrue(waypoint_supports(FEATURE_MOD_INTRO));
        $this->assertTrue(waypoint_supports(FEATURE_COMPLETION_HAS_RULES));
        $this->assertTrue(waypoint_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
        $this->assertFalse(waypoint_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertFalse(waypoint_supports(FEATURE_GROUPS));
        $this->assertNull(waypoint_supports('something_that_does_not_exist'));
    }

    /**
     * A teacher gets a link to the report in the activity's navigation.
     */
    public function test_report_link_is_offered_to_a_teacher(): void {
        global $PAGE;

        $this->resetAfterTest();

        [$course, $cm] = $this->create_activity();
        $teacher = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        $this->prepare_page($cm);

        $node = navigation_node::create('testnavigationnode');
        waypoint_extend_settings_navigation($PAGE->settingsnav, $node);

        $this->assertSame(['waypointreport'], $node->get_children_key_list());
    }

    /**
     * The link points at the routed report page for this course module.
     */
    public function test_report_link_targets_the_route(): void {
        global $PAGE;

        $this->resetAfterTest();

        [$course, $cm] = $this->create_activity();
        $teacher = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        $this->prepare_page($cm);

        $node = navigation_node::create('testnavigationnode');
        waypoint_extend_settings_navigation($PAGE->settingsnav, $node);

        $url = $node->get('waypointreport')->action()->out(false);

        $this->assertStringContainsString("/mod_waypoint/report/{$cm->id}", $url);
    }

    /**
     * A student does not, because they lack mod/waypoint:viewreport.
     */
    public function test_report_link_is_hidden_from_a_student(): void {
        global $PAGE;

        $this->resetAfterTest();

        [$course, $cm] = $this->create_activity();
        $student = self::getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->prepare_page($cm);

        $node = navigation_node::create('testnavigationnode');
        waypoint_extend_settings_navigation($PAGE->settingsnav, $node);

        $this->assertEmpty($node->get_children_key_list());
    }
}
