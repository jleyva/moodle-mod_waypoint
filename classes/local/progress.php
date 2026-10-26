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

namespace mod_waypoint\local;

/**
 * How far through the waypoint list a single user is.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progress {
    /**
     * Create a progress summary.
     *
     * @param int $completed Number of waypoints the user has ticked.
     * @param int $total Number of waypoints in the activity.
     */
    public function __construct(
        /** @var int Number of waypoints the user has ticked. */
        public readonly int $completed,
        /** @var int Number of waypoints in the activity. */
        public readonly int $total,
    ) {
    }

    /**
     * Whether the user has ticked every waypoint.
     *
     * An empty list is never finished — there is nothing to have done.
     *
     * @return bool
     */
    public function is_complete(): bool {
        return $this->total > 0 && $this->completed === $this->total;
    }

    /**
     * The progress as a percentage, for the ProgressBar value.
     *
     * @return int
     */
    public function get_percentage(): int {
        if ($this->total === 0) {
            return 0;
        }

        return (int) round(($this->completed / $this->total) * 100);
    }

    /**
     * A translated "x of y" label.
     *
     * Built server-side with get_string() so word order and number formatting
     * follow the site language rather than being assembled in JavaScript.
     *
     * @return string
     */
    public function get_count_label(): string {
        return get_string('progresscount', 'mod_waypoint', (object) [
            'completed' => $this->completed,
            'total' => $this->total,
        ]);
    }
}
