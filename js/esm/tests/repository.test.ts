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
 * Jest tests for the mod_waypoint repository.
 *
 * These pin down the REST URLs, because the endpoint path is the contract
 * between this module and the #[route] attributes on the PHP side.
 *
 * @module     mod_waypoint/tests/repository
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {addItem, deleteItem, getActivity, getItems, renameItem, setDone} from '../src/repository';

const mockPerformGet = jest.fn();
const mockPerformPost = jest.fn();
const mockPerformPatch = jest.fn();
const mockPerformPut = jest.fn();
const mockPerformDelete = jest.fn();

jest.mock('@moodle/lms/core/fetch', () => ({
    __esModule: true,
    'default': {
        performGet: (...args: unknown[]) => mockPerformGet(...args),
        performPost: (...args: unknown[]) => mockPerformPost(...args),
        performPatch: (...args: unknown[]) => mockPerformPatch(...args),
        performPut: (...args: unknown[]) => mockPerformPut(...args),
        performDelete: (...args: unknown[]) => mockPerformDelete(...args),
    },
}), {virtual: true});

/** The activity, as its endpoint returns it. */
const activity = {
    id: 42,
    name: 'Week 1 checklist',
    progress: {completed: 0, total: 1, percentage: 0, countLabel: '0 of 1'},
    canManage: true,
    canTick: true,
};

/** One page of waypoints, as the collection endpoint returns it. */
const itemPage = {
    items: [{id: 7, title: 'Read the brief', sortOrder: 1, done: false}],
    pagination: {page: 1, pageSize: 25, totalItems: 1},
};

/** Stand in for a fetch Response carrying a payload. */
const response = (payload: unknown) => ({json: () => Promise.resolve(payload)});

describe('mod_waypoint/repository', () => {
    beforeEach(() => {
        [mockPerformGet, mockPerformPost, mockPerformPatch, mockPerformPut, mockPerformDelete]
            .forEach((mock) => mock.mockReset().mockResolvedValue(response(itemPage)));
    });

    it('fetches the activity from its own endpoint', async() => {
        mockPerformGet.mockResolvedValue(response(activity));

        const result = await getActivity(42);

        expect(mockPerformGet).toHaveBeenCalledWith('mod_waypoint', 'activities/42');
        expect(result).toEqual(activity);
    });

    it('fetches the item collection with no parameters by default', async() => {
        const result = await getItems(42);

        expect(mockPerformGet).toHaveBeenCalledWith('mod_waypoint', 'activities/42/items', {params: {}});
        expect(result).toEqual(itemPage);
    });

    it('passes the page through as a query parameter', async() => {
        await getItems(42, {page: 3});

        expect(mockPerformGet).toHaveBeenCalledWith(
            'mod_waypoint',
            'activities/42/items',
            {params: {page: '3'}},
        );
    });

    it('passes the done filter through as a string, including when it is false', async() => {
        await getItems(42, {page: 1, done: false});

        expect(mockPerformGet).toHaveBeenCalledWith(
            'mod_waypoint',
            'activities/42/items',
            {params: {page: '1', done: 'false'}},
        );
    });

    it('posts a new title to the items collection', async() => {
        await addItem(42, 'Celebrate');

        expect(mockPerformPost).toHaveBeenCalledWith(
            'mod_waypoint',
            'activities/42/items',
            {body: {title: 'Celebrate'}},
        );
    });

    it('patches an individual item when renaming', async() => {
        await renameItem(42, 7, 'Read it twice');

        expect(mockPerformPatch).toHaveBeenCalledWith(
            'mod_waypoint',
            'activities/42/items/7',
            {body: {title: 'Read it twice'}},
        );
    });

    it('deletes an individual item, and does not try to decode the 204', async() => {
        mockPerformDelete.mockResolvedValue({
            json: () => {
                throw new Error('A 204 has no body to decode.');
            },
        });

        await expect(deleteItem(42, 7)).resolves.toBeUndefined();

        expect(mockPerformDelete).toHaveBeenCalledWith('mod_waypoint', 'activities/42/items/7');
    });

    it('puts progress against an individual item', async() => {
        await setDone(42, 7, true);

        expect(mockPerformPut).toHaveBeenCalledWith(
            'mod_waypoint',
            'activities/42/items/7/progress',
            {body: {done: true}},
        );
    });

    it('sends done=false when unticking', async() => {
        await setDone(42, 7, false);

        expect(mockPerformPut).toHaveBeenCalledWith(
            'mod_waypoint',
            'activities/42/items/7/progress',
            {body: {done: false}},
        );
    });
});
