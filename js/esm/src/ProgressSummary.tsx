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
 * The progress bar above the waypoint list.
 *
 * @module     mod_waypoint/ProgressSummary
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {use} from 'react';
import {ProgressBar} from '@moodlehq/design-system';
import {getString} from '@moodle/lms/core/stringUtils';
import type {WaypointProgress} from './common/types';

interface ProgressSummaryProps {
    /** The calling user's progress. */
    progress: WaypointProgress;
}

/**
 * Show how far through the list the user is.
 *
 * ProgressBar deliberately does not derive its count text from value/max — word
 * order and plural rules belong to the language pack — so countLabel arrives
 * from the server already translated.
 */
export default function ProgressSummary({progress}: ProgressSummaryProps) {
    const title = use(getString('progresstitle', 'mod_waypoint'));
    const finished = progress.total > 0 && progress.completed === progress.total;

    return (
        <ProgressBar
            value={progress.percentage}
            title={title}
            count={progress.countLabel}
            labelVariant="title-and-count"
            {...(finished ? {} : {status: 'in-progress' as const})}
        />
    );
}
