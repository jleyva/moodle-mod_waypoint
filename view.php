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

/**
 * Student view of a waypoint activity.
 *
 * This stays a real file rather than a route: the course page, the activity
 * chooser and every existing link point at /mod/waypoint/view.php?id=N, and a
 * plugin cannot route that away. See classes/route/shim/ for the pattern that
 * does move a legacy URL onto the router.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/completionlib.php');

$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'waypoint');
require_login($course, true, $cm);

$manager = \mod_waypoint\local\item_manager::instance_from_cm($cm, $course);
$manager->require_can_view();

$PAGE->set_url('/mod/waypoint/view.php', ['id' => $cm->id]);
$PAGE->set_context($manager->context);
$PAGE->set_title(format_string($manager->waypoint->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_activity_record($manager->waypoint);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$activity = $manager->get_activity_payload($USER->id);
$itempage = $manager->get_item_page($USER->id);

// The activity header renders the description, the completion requirements and
// the activity name on its own. Printing format_module_intro() here as well
// would show the description twice.
echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_waypoint/view', [
    'reactprops' => json_encode([
        'activityId' => (int) $cm->id,
        'initialActivity' => $activity,
        'initialItems' => $itempage,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    'items' => $itempage['items'],
    'hasitems' => !empty($itempage['items']),
    'countlabel' => $activity['progress']['countLabel'],
    'percentage' => $activity['progress']['percentage'],
]);

echo $OUTPUT->footer();
