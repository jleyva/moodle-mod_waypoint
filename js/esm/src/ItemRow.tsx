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
 * One row of the waypoint list.
 *
 * @module     mod_waypoint/ItemRow
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {use} from 'react';
import {Badge, Button, Checkbox} from '@moodlehq/design-system';
import {getString} from '@moodle/lms/core/stringUtils';
import type {WaypointItem} from './common/types';

interface ItemRowProps {
    /** The waypoint to render. */
    item: WaypointItem;
    /** Whether the user may tick it. */
    canTick: boolean;
    /** Whether the user may delete it. */
    canManage: boolean;
    /** Whether a request is in flight, so controls should be inert. */
    busy: boolean;
    /** Called when the checkbox is toggled. */
    onToggle: (item: WaypointItem, done: boolean) => void;
    /** Called when the delete button is pressed. */
    onDelete: (item: WaypointItem) => void;
}

/**
 * Render a single waypoint with its tick box and, for teachers, a delete button.
 */
export default function ItemRow({item, canTick, canManage, busy, onToggle, onDelete}: ItemRowProps) {
    const doneLabel = use(getString('done', 'mod_waypoint'));
    const deleteLabel = use(getString('deletewaypoint', 'mod_waypoint'));

    return (
        <li className="d-flex align-items-center gap-2 py-2 border-bottom" data-itemid={item.id}>
            <div className="flex-grow-1">
                <Checkbox
                    label={item.title}
                    checked={item.done}
                    disabled={!canTick || busy}
                    onChange={(event) => onToggle(item, event.target.checked)}
                />
            </div>
            {item.done && <Badge label={doneLabel} variant="success" subtle pill />}
            {canManage && (
                <Button
                    label={deleteLabel}
                    variant="ghost"
                    size="sm"
                    disabled={busy}
                    onClick={() => onDelete(item)}
                />
            )}
        </li>
    );
}
