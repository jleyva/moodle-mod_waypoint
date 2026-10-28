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
 * Jest tests for the Waypoint root component.
 *
 * The behaviour under test is the consequence of a REST API where each
 * endpoint answers with the resource it addresses and nothing more: a change
 * to one waypoint says nothing about the activity's totals, so the component
 * refetches the activity and the current page together afterwards, and
 * filtering has to happen on the server rather than in the page.
 *
 * @module     mod_waypoint/tests/Waypoint
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {act, render, screen} from '@testing-library/react';
import Waypoint from '../src/Waypoint';
import type {ItemPage, WaypointActivity} from '../src/common/types';

const mockStrings = new Map<string, Promise<string>>();
jest.mock('@moodle/lms/core/stringUtils', () => ({
    getString: (identifier: string, component = 'core') => {
        const key = `${component}::${identifier}`;
        if (!mockStrings.has(key)) {
            mockStrings.set(key, Promise.resolve(identifier));
        }
        return mockStrings.get(key)!;
    },
}), {virtual: true});

jest.mock('@moodle/lms/core/String', () => ({
    __esModule: true,
    'default': (props: {identifier: string}) => <span>{props.identifier}</span>,
}), {virtual: true});

jest.mock('@moodlehq/design-system', () => ({
    ProgressBar: (props: {title: string; count: string}) => (
        <div data-testid="progressbar" data-count={props.count}>{props.title}</div>
    ),
    Checkbox: (props: {label: string; checked: boolean; disabled: boolean; onChange: (e: unknown) => void}) => (
        <label>
            {props.label}
            <input type="checkbox" checked={props.checked} disabled={props.disabled} onChange={props.onChange} />
        </label>
    ),
    Badge: (props: {label: string}) => <span data-testid="badge">{props.label}</span>,
    Button: (props: {label: string; disabled: boolean; onClick: () => void; type?: string}) => (
        <button type="button" disabled={props.disabled} onClick={props.onClick}>{props.label}</button>
    ),
    Switch: (props: {label: string; checked: boolean; onChange: (e: unknown) => void}) => (
        <label>
            {props.label}
            <input type="checkbox" role="switch" checked={props.checked} onChange={props.onChange} />
        </label>
    ),
    Pagination: (props: {totalPages: number; currentPage: number; onPageChange: (page: number) => void}) => (
        <nav data-testid="pagination" data-total-pages={props.totalPages} data-current-page={props.currentPage}>
            <button type="button" onClick={() => props.onPageChange(props.currentPage + 1)}>nextpage</button>
        </nav>
    ),
}), {virtual: true});

const mockSetDone = jest.fn();
const mockDeleteItem = jest.fn();
const mockAddItem = jest.fn();
const mockGetActivity = jest.fn();
const mockGetItems = jest.fn();
jest.mock('../src/repository', () => ({
    setDone: (...args: unknown[]) => mockSetDone(...args),
    deleteItem: (...args: unknown[]) => mockDeleteItem(...args),
    addItem: (...args: unknown[]) => mockAddItem(...args),
    getActivity: (...args: unknown[]) => mockGetActivity(...args),
    getItems: (...args: unknown[]) => mockGetItems(...args),
}));

/**
 * Build an activity.
 *
 * @param overrides Fields to override.
 */
function makeActivity(overrides: Partial<WaypointActivity> = {}): WaypointActivity {
    return {
        id: 42,
        name: 'Week 1 checklist',
        progress: {completed: 1, total: 2, percentage: 50, countLabel: '1 of 2'},
        canManage: false,
        canTick: true,
        ...overrides,
    };
}

/**
 * Build a page of waypoints.
 *
 * @param overrides Fields to override.
 */
function makeItems(overrides: Partial<ItemPage> = {}): ItemPage {
    return {
        items: [
            {id: 1, title: 'Read the brief', sortOrder: 1, done: false},
            {id: 2, title: 'Draft an outline', sortOrder: 2, done: true},
        ],
        pagination: {page: 1, pageSize: 25, totalItems: 2},
        ...overrides,
    };
}

/**
 * Render and flush the suspended string reads.
 *
 * @param activity The activity to start from.
 * @param items The page of waypoints to start from.
 */
async function renderApp(activity = makeActivity(), items = makeItems()) {
    await act(async() => {
        render(<Waypoint activityId={42} initialActivity={activity} initialItems={items} />);
    });
}

describe('mod_waypoint/Waypoint', () => {
    beforeEach(() => {
        [mockSetDone, mockDeleteItem, mockAddItem, mockGetActivity, mockGetItems]
            .forEach((mock) => mock.mockReset());
        mockGetActivity.mockResolvedValue(makeActivity());
        mockGetItems.mockResolvedValue(makeItems());
    });

    it('renders the server-supplied state without fetching anything', async() => {
        await renderApp();

        expect(mockGetActivity).not.toHaveBeenCalled();
        expect(mockGetItems).not.toHaveBeenCalled();
        expect(screen.getByTestId('progressbar')).toHaveAttribute('data-count', '1 of 2');
        expect(screen.getAllByRole('checkbox', {checked: false})).toHaveLength(1);
    });

    it('refetches the activity and the page after ticking a waypoint', async() => {
        mockGetActivity.mockResolvedValue(makeActivity({
            progress: {completed: 2, total: 2, percentage: 100, countLabel: '2 of 2'},
        }));
        mockGetItems.mockResolvedValue(makeItems({
            items: [
                {id: 1, title: 'Read the brief', sortOrder: 1, done: true},
                {id: 2, title: 'Draft an outline', sortOrder: 2, done: true},
            ],
        }));

        await renderApp();

        await act(async() => {
            screen.getByLabelText('Read the brief').click();
        });

        expect(mockSetDone).toHaveBeenCalledWith(42, 1, true);
        expect(mockGetActivity).toHaveBeenCalledWith(42);
        expect(mockGetItems).toHaveBeenCalledWith(42, {page: 1});
        expect(screen.getByTestId('progressbar')).toHaveAttribute('data-count', '2 of 2');
    });

    it('offers a retry when a request fails, and keeps the old state', async() => {
        mockSetDone.mockRejectedValue(new Error('Forbidden'));

        await renderApp();

        await act(async() => {
            screen.getByLabelText('Read the brief').click();
        });

        expect(screen.getByRole('alert')).toBeInTheDocument();
        expect(screen.getByTestId('progressbar')).toHaveAttribute('data-count', '1 of 2');
    });

    it('reloads from the server when retry is pressed', async() => {
        mockSetDone.mockRejectedValue(new Error('Forbidden'));

        await renderApp();

        await act(async() => {
            screen.getByLabelText('Read the brief').click();
        });
        await act(async() => {
            screen.getByText('retry').click();
        });

        expect(mockGetActivity).toHaveBeenCalledWith(42);
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('asks the server for the unticked waypoints when the switch goes on', async() => {
        mockGetItems.mockResolvedValue(makeItems({
            items: [{id: 1, title: 'Read the brief', sortOrder: 1, done: false}],
            pagination: {page: 1, pageSize: 25, totalItems: 1},
        }));

        await renderApp();

        // The Switch carries role="switch", which replaces its implicit checkbox role,
        // so only the two item checkboxes match here.
        expect(screen.getAllByRole('checkbox')).toHaveLength(2);

        await act(async() => {
            screen.getByRole('switch').click();
        });

        expect(mockGetItems).toHaveBeenCalledWith(42, {page: 1, done: false});
        expect(screen.queryByLabelText('Draft an outline')).not.toBeInTheDocument();
        expect(screen.getByLabelText('Read the brief')).toBeInTheDocument();
    });

    it('leaves the pager out while everything fits on one page', async() => {
        await renderApp();

        expect(screen.queryByTestId('pagination')).not.toBeInTheDocument();
    });

    it('pages through a collection that does not fit', async() => {
        const paged = makeItems({pagination: {page: 1, pageSize: 2, totalItems: 5}});
        mockGetItems.mockResolvedValue(makeItems({pagination: {page: 2, pageSize: 2, totalItems: 5}}));

        await renderApp(makeActivity(), paged);

        expect(screen.getByTestId('pagination')).toHaveAttribute('data-total-pages', '3');

        await act(async() => {
            screen.getByText('nextpage').click();
        });

        expect(mockGetItems).toHaveBeenCalledWith(42, {page: 2});
        expect(screen.getByTestId('pagination')).toHaveAttribute('data-current-page', '2');
    });

    it('falls back to the first page when the last one empties out', async() => {
        const lastPage = makeItems({
            items: [{id: 5, title: 'Submit for review', sortOrder: 5, done: false}],
            pagination: {page: 3, pageSize: 2, totalItems: 5},
        });
        mockGetItems
            .mockResolvedValueOnce({items: [], pagination: {page: 3, pageSize: 2, totalItems: 4}})
            .mockResolvedValueOnce(makeItems({pagination: {page: 1, pageSize: 2, totalItems: 4}}));

        await renderApp(makeActivity({canManage: true}), lastPage);

        await act(async() => {
            screen.getByText('deletewaypoint').click();
        });

        expect(mockGetItems).toHaveBeenNthCalledWith(1, 42, {page: 3});
        expect(mockGetItems).toHaveBeenNthCalledWith(2, 42, {page: 1});
        expect(screen.getByTestId('pagination')).toHaveAttribute('data-current-page', '1');
    });

    it('hides the add form from a user who may not manage', async() => {
        await renderApp();

        expect(screen.queryByText('addwaypoint')).not.toBeInTheDocument();
    });

    it('shows the add form to a user who may manage', async() => {
        await renderApp(makeActivity({canManage: true}));

        expect(screen.getAllByText('addwaypoint').length).toBeGreaterThan(0);
    });
});
