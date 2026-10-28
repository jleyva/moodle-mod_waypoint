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
 * Each function returns the resource its endpoint returns, not the whole
 * activity. Refreshing everything after a change therefore takes two
 * requests, one per resource — see Waypoint.tsx.
 *
 * @module     mod_waypoint/repository
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Fetch from '@moodle/lms/core/fetch';
import type {ItemPage, ItemProgress, ItemQuery, WaypointActivity, WaypointItem} from './common/types';

/** The frankenstyle component name, used as the first URL segment after rest/v2. */
const COMPONENT = 'mod_waypoint';

/**
 * Build the endpoint path for one activity, or something inside it.
 *
 * Fetch prepends `{apibase}/rest/v2/mod_waypoint/`, so the full URL becomes
 * `/api/rest/v2/mod_waypoint/activities/42/items` — or the same path behind
 * `/r.php` on a site where the rewrite is not configured.
 *
 * @param activityId The activity id.
 * @param suffix The path within the activity, including its leading slash.
 * @returns The endpoint path.
 */
const endpoint = (activityId: number, suffix: string = ''): string => `activities/${activityId}${suffix}`;

/**
 * Decode a JSON response.
 *
 * @param response The fetch response.
 * @returns The decoded body.
 */
const decode = async<T>(response: Response): Promise<T> => await response.json() as T;

/**
 * Turn a collection query into the query string the API expects.
 *
 * The style guide spells the page size `pagesize` in the query string even
 * though the field it controls is `pageSize` in the response body.
 *
 * @param query What to ask for.
 * @returns Query-string parameters.
 */
const toParams = ({page, done}: ItemQuery): Record<string, string> => {
    const params: Record<string, string> = {};
    if (page !== undefined) {
        params.page = String(page);
    }
    if (done !== undefined) {
        params.done = done ? 'true' : 'false';
    }
    return params;
};

/**
 * Fetch the activity, with the calling user's progress and capabilities.
 *
 * @param activityId The activity id.
 */
export const getActivity = async(activityId: number): Promise<WaypointActivity> =>
    decode<WaypointActivity>(await Fetch.performGet(COMPONENT, endpoint(activityId)));

/**
 * Fetch one page of waypoints.
 *
 * @param activityId The activity id.
 * @param query Which page to fetch, and what to filter it by.
 */
export const getItems = async(activityId: number, query: ItemQuery = {}): Promise<ItemPage> =>
    decode<ItemPage>(await Fetch.performGet(COMPONENT, endpoint(activityId, '/items'), {params: toParams(query)}));

/**
 * Append a new waypoint.
 *
 * Answers 201 with the created waypoint, and a Location header addressing it.
 *
 * @param activityId The activity id.
 * @param title The waypoint title.
 */
export const addItem = async(activityId: number, title: string): Promise<WaypointItem> =>
    decode<WaypointItem>(await Fetch.performPost(COMPONENT, endpoint(activityId, '/items'), {body: {title}}));

/**
 * Rename an existing waypoint.
 *
 * @param activityId The activity id.
 * @param itemId The waypoint to rename.
 * @param title The new title.
 */
export const renameItem = async(activityId: number, itemId: number, title: string): Promise<WaypointItem> =>
    decode<WaypointItem>(
        await Fetch.performPatch(COMPONENT, endpoint(activityId, `/items/${itemId}`), {body: {title}}),
    );

/**
 * Delete a waypoint.
 *
 * Answers 204, so there is no body to decode.
 *
 * @param activityId The activity id.
 * @param itemId The waypoint to delete.
 */
export const deleteItem = async(activityId: number, itemId: number): Promise<void> => {
    await Fetch.performDelete(COMPONENT, endpoint(activityId, `/items/${itemId}`));
};

/**
 * Tick or untick a waypoint for the calling user.
 *
 * @param activityId The activity id.
 * @param itemId The waypoint to update.
 * @param done Whether it is now done.
 */
export const setDone = async(activityId: number, itemId: number, done: boolean): Promise<ItemProgress> =>
    decode<ItemProgress>(
        await Fetch.performPut(COMPONENT, endpoint(activityId, `/items/${itemId}/progress`), {body: {done}}),
    );
