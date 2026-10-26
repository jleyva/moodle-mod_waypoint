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
 * The behaviour under test is the central design decision of the plugin: every
 * repository call returns the whole activity state, and the component replaces
 * what it had rather than merging a partial update.
 *
 * @module     mod_waypoint/tests/Waypoint
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {act, render, screen} from '@testing-library/react';
import Waypoint from '../src/Waypoint';
import type {WaypointState} from '../src/common/types';

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
}), {virtual: true});

const mockSetDone = jest.fn();
const mockDeleteItem = jest.fn();
const mockAddItem = jest.fn();
const mockGetState = jest.fn();
jest.mock('../src/repository', () => ({
    setDone: (...args: unknown[]) => mockSetDone(...args),
    deleteItem: (...args: unknown[]) => mockDeleteItem(...args),
    addItem: (...args: unknown[]) => mockAddItem(...args),
    getState: (...args: unknown[]) => mockGetState(...args),
}));

/**
 * Build an activity state.
 *
 * @param overrides Fields to override.
 */
function makeState(overrides: Partial<WaypointState> = {}): WaypointState {
    return {
        items: [
            {id: 1, title: 'Read the brief', sortorder: 1, done: false},
            {id: 2, title: 'Draft an outline', sortorder: 2, done: true},
        ],
        progress: {completed: 1, total: 2, percentage: 50, countlabel: '1 of 2'},
        canmanage: false,
        cantick: true,
        ...overrides,
    };
}

/** Render and flush the suspended string reads. */
async function renderApp(state: WaypointState) {
    await act(async() => {
        render(<Waypoint cmid={42} initialstate={state} />);
    });
}

describe('mod_waypoint/Waypoint', () => {
    beforeEach(() => {
        [mockSetDone, mockDeleteItem, mockAddItem, mockGetState].forEach((mock) => mock.mockReset());
    });

    it('renders the server-supplied state without fetching anything', async() => {
        await renderApp(makeState());

        expect(mockGetState).not.toHaveBeenCalled();
        expect(screen.getByTestId('progressbar')).toHaveAttribute('data-count', '1 of 2');
        expect(screen.getAllByRole('checkbox', {checked: false})).toHaveLength(1);
    });

    it('replaces its state with whatever the tick request returns', async() => {
        mockSetDone.mockResolvedValue(makeState({
            items: [
                {id: 1, title: 'Read the brief', sortorder: 1, done: true},
                {id: 2, title: 'Draft an outline', sortorder: 2, done: true},
            ],
            progress: {completed: 2, total: 2, percentage: 100, countlabel: '2 of 2'},
        }));

        await renderApp(makeState());

        await act(async() => {
            screen.getByLabelText('Read the brief').click();
        });

        expect(mockSetDone).toHaveBeenCalledWith(42, 1, true);
        expect(screen.getByTestId('progressbar')).toHaveAttribute('data-count', '2 of 2');
    });

    it('offers a retry when a request fails, and keeps the old state', async() => {
        mockSetDone.mockRejectedValue(new Error('Forbidden'));

        await renderApp(makeState());

        await act(async() => {
            screen.getByLabelText('Read the brief').click();
        });

        expect(screen.getByRole('alert')).toBeInTheDocument();
        expect(screen.getByTestId('progressbar')).toHaveAttribute('data-count', '1 of 2');
    });

    it('reloads from the server when retry is pressed', async() => {
        mockSetDone.mockRejectedValue(new Error('Forbidden'));
        mockGetState.mockResolvedValue(makeState());

        await renderApp(makeState());

        await act(async() => {
            screen.getByLabelText('Read the brief').click();
        });
        await act(async() => {
            screen.getByText('retry').click();
        });

        expect(mockGetState).toHaveBeenCalledWith(42);
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('filters out completed waypoints when the switch is on', async() => {
        await renderApp(makeState());

        // The Switch carries role="switch", which replaces its implicit checkbox role,
        // so only the two item checkboxes match here.
        expect(screen.getAllByRole('checkbox')).toHaveLength(2);

        await act(async() => {
            screen.getByRole('switch').click();
        });

        expect(screen.queryByLabelText('Draft an outline')).not.toBeInTheDocument();
        expect(screen.getByLabelText('Read the brief')).toBeInTheDocument();
    });

    it('hides the add form from a user who may not manage', async() => {
        await renderApp(makeState());

        expect(screen.queryByText('addwaypoint')).not.toBeInTheDocument();
    });

    it('shows the add form to a user who may manage', async() => {
        await renderApp(makeState({canmanage: true}));

        expect(screen.getAllByText('addwaypoint').length).toBeGreaterThan(0);
    });
});
