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
 * The waypoint list.
 *
 * @module     mod_waypoint/ItemList
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import String from '@moodle/lms/core/String';
import ItemRow from '@moodle/lms/mod_waypoint/ItemRow';
import type {WaypointItem} from './common/types';

interface ItemListProps {
    /** The waypoints to render, already filtered and in sort order. */
    items: WaypointItem[];
    /** Whether the user may edit the list. */
    canManage: boolean;
    /** Whether the user may tick waypoints. */
    canTick: boolean;
    /** Whether a request is in flight. */
    busy: boolean;
    /** Called when a row's checkbox is toggled. */
    onToggle: (item: WaypointItem, done: boolean) => void;
    /** Called when a row's delete button is pressed. */
    onDelete: (item: WaypointItem) => void;
}

/**
 * Render the waypoints, or an empty-state message.
 */
export default function ItemList({items, canManage, canTick, busy, onToggle, onDelete}: ItemListProps) {
    if (items.length === 0) {
        return (
            <p className="text-muted mb-0" data-region="waypoint-empty">
                <String
                    identifier={canManage ? 'nowaypointsteacher' : 'nowaypoints'}
                    component="mod_waypoint"
                />
            </p>
        );
    }

    return (
        <ul className="list-unstyled mb-0" data-region="waypoint-list">
            {items.map((item) => (
                <ItemRow
                    key={item.id}
                    item={item}
                    canTick={canTick}
                    canManage={canManage}
                    busy={busy}
                    onToggle={onToggle}
                    onDelete={onDelete}
                />
            ))}
        </ul>
    );
}
