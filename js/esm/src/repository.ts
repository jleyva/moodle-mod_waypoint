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
 * Data-access layer for mod_waypoint.
 *
 * The only module that talks to the network. Components call these functions
 * and never import core/fetch themselves, which keeps the REST API an
 * implementation detail of this one file.
 *
 * Every endpoint returns the full activity state, so each function resolves
 * to a WaypointState the caller can drop straight into component state.
 *
 * @module     mod_waypoint/repository
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Fetch from '@moodle/lms/core/fetch';
import type {WaypointState} from './common/types';

/** The frankenstyle component name, used as the first URL segment after rest/v2. */
const COMPONENT = 'mod_waypoint';

/**
 * Build the endpoint path for one activity.
 *
 * Fetch prepends `{apibase}/rest/v2/mod_waypoint/`, so the full URL becomes
 * `/api/rest/v2/mod_waypoint/42/items` — or the same path behind
 * `/r.php` on a site where the rewrite is not configured.
 *
 * @param cmid The course module id.
 * @param path The path within the activity.
 * @returns The endpoint path.
 */
const endpoint = (cmid: number, path: string): string => `${cmid}/${path}`;

/**
 * Decode a response into the activity state.
 *
 * @param response The fetch response.
 * @returns The decoded state.
 */
const toState = async(response: Response): Promise<WaypointState> =>
    await response.json() as WaypointState;

/**
 * Fetch the waypoint list and the calling user's progress.
 *
 * @param cmid The course module id.
 */
export const getState = async(cmid: number): Promise<WaypointState> =>
    toState(await Fetch.performGet(COMPONENT, endpoint(cmid, 'items')));

/**
 * Append a new waypoint.
 *
 * @param cmid The course module id.
 * @param title The waypoint title.
 */
export const addItem = async(cmid: number, title: string): Promise<WaypointState> =>
    toState(await Fetch.performPost(COMPONENT, endpoint(cmid, 'items'), {body: {title}}));

/**
 * Rename an existing waypoint.
 *
 * @param cmid The course module id.
 * @param itemid The waypoint to rename.
 * @param title The new title.
 */
export const renameItem = async(cmid: number, itemid: number, title: string): Promise<WaypointState> =>
    toState(await Fetch.performPatch(COMPONENT, endpoint(cmid, `items/${itemid}`), {body: {title}}));

/**
 * Delete a waypoint.
 *
 * @param cmid The course module id.
 * @param itemid The waypoint to delete.
 */
export const deleteItem = async(cmid: number, itemid: number): Promise<WaypointState> =>
    toState(await Fetch.performDelete(COMPONENT, endpoint(cmid, `items/${itemid}`)));

/**
 * Tick or untick a waypoint for the calling user.
 *
 * @param cmid The course module id.
 * @param itemid The waypoint to update.
 * @param done Whether it is now done.
 */
export const setDone = async(cmid: number, itemid: number, done: boolean): Promise<WaypointState> =>
    toState(await Fetch.performPut(COMPONENT, endpoint(cmid, `progress/${itemid}`), {body: {done}}));
