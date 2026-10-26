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

import {addItem, deleteItem, getState, renameItem, setDone} from '../src/repository';

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

/** A minimal state payload, as every endpoint returns. */
const state = {
    items: [{id: 7, title: 'Read the brief', sortorder: 1, done: false}],
    progress: {completed: 0, total: 1, percentage: 0, countlabel: '0 of 1'},
    canmanage: true,
    cantick: true,
};

/** Stand in for a fetch Response carrying that payload. */
const response = () => ({json: () => Promise.resolve(state)});

describe('mod_waypoint/repository', () => {
    beforeEach(() => {
        [mockPerformGet, mockPerformPost, mockPerformPatch, mockPerformPut, mockPerformDelete]
            .forEach((mock) => mock.mockReset().mockResolvedValue(response()));
    });

    it('fetches the activity state from the items endpoint', async() => {
        const result = await getState(42);

        expect(mockPerformGet).toHaveBeenCalledWith('mod_waypoint', '42/items');
        expect(result).toEqual(state);
    });

    it('posts a new title to the items collection', async() => {
        await addItem(42, 'Celebrate');

        expect(mockPerformPost).toHaveBeenCalledWith(
            'mod_waypoint',
            '42/items',
            {body: {title: 'Celebrate'}},
        );
    });

    it('patches an individual item when renaming', async() => {
        await renameItem(42, 7, 'Read it twice');

        expect(mockPerformPatch).toHaveBeenCalledWith(
            'mod_waypoint',
            '42/items/7',
            {body: {title: 'Read it twice'}},
        );
    });

    it('deletes an individual item', async() => {
        await deleteItem(42, 7);

        expect(mockPerformDelete).toHaveBeenCalledWith('mod_waypoint', '42/items/7');
    });

    it('puts progress against an individual item', async() => {
        await setDone(42, 7, true);

        expect(mockPerformPut).toHaveBeenCalledWith(
            'mod_waypoint',
            '42/progress/7',
            {body: {done: true}},
        );
    });

    it('sends done=false when unticking', async() => {
        await setDone(42, 7, false);

        expect(mockPerformPut).toHaveBeenCalledWith(
            'mod_waypoint',
            '42/progress/7',
            {body: {done: false}},
        );
    });
});
