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

use core_completion\activity_custom_completion;
use mod_waypoint\local\item_manager;

/**
 * Custom completion rules for mod_waypoint.
 *
 * The single rule is "every waypoint ticked". The work is delegated to
 * item_manager so the completion state and the REST API can never disagree
 * about what "complete" means.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    #[\Override]
    public function get_state(string $rule): int {
        $this->validate_rule($rule);

        $manager = item_manager::instance_from_cm($this->cm);
        $progress = $manager->get_progress_for_user($this->userid);

        // An empty waypoint list is never automatically complete: there is
        // nothing for the student to have done yet.
        if ($progress->total === 0) {
            return COMPLETION_INCOMPLETE;
        }

        return $progress->completed === $progress->total
            ? COMPLETION_COMPLETE
            : COMPLETION_INCOMPLETE;
    }

    #[\Override]
    public static function get_defined_custom_rules(): array {
        return ['completionallwaypoints'];
    }

    #[\Override]
    public function get_custom_rule_descriptions(): array {
        return [
            'completionallwaypoints' => get_string('completiondetail:allwaypoints', 'mod_waypoint'),
        ];
    }

    #[\Override]
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionallwaypoints',
        ];
    }
}
