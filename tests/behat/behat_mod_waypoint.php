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
 * Step definitions for mod_waypoint.
 *
 * @package    mod_waypoint
 * @category   test
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Steps definitions related to the waypoint activity.
 *
 * @package    mod_waypoint
 * @category   test
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_waypoint extends behat_base {

    /**
     * Fill an activity with enough waypoints to need more than one page.
     *
     * The collection endpoint pages at twenty-five, so a Gherkin table would
     * need twenty-six rows to say one thing. This says it once.
     *
     * @Given :count waypoints exist in the :activityname waypoint activity
     * @param int $count How many waypoints to create.
     * @param string $activityname The activity to create them in.
     */
    public function waypoints_exist_in_the_waypoint_activity(int $count, string $activityname): void {
        $cm = $this->get_cm_by_activity_name('waypoint', $activityname);
        $generator = behat_util::get_data_generator()->get_plugin_generator('mod_waypoint');

        for ($i = 1; $i <= $count; $i++) {
            $generator->create_item([
                'waypointid' => $cm->instance,
                'title' => sprintf('Waypoint %02d', $i),
            ]);
        }
    }

    /**
     * Convert page names to URLs for steps like 'I am on the "X" "mod_waypoint > Y" page'.
     *
     * Recognised page names are:
     * | pagetype | name meaning  | description                      |
     * | Report   | Activity name | The teacher-facing progress report |
     *
     * The report has no file on disk — it is served by the router — so its URL
     * is asked of the router rather than written out here. That also keeps the
     * tests working whether or not the site under test has
     * $CFG->routerconfigured set, since the two produce different URLs.
     *
     * @param string $type The type of page, e.g. 'Report'.
     * @param string $identifier The activity name or idnumber.
     * @return moodle_url
     * @throws Exception If the page type is not recognised.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch (strtolower($type)) {
            case 'report':
                return \core\router\util::get_path_for_callable(
                    [\mod_waypoint\route\controller\report::class, 'view_report'],
                    ['cm' => $this->get_cm_by_activity_name('waypoint', $identifier)->id],
                );

            default:
                throw new Exception("Unrecognised mod_waypoint page type '{$type}'");
        }
    }
}
