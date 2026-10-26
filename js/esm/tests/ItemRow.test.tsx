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
 * Jest tests for the ItemRow component.
 *
 * @module     mod_waypoint/tests/ItemRow
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {act, render, screen} from '@testing-library/react';
import ItemRow from '../src/ItemRow';
import type {WaypointItem} from '../src/common/types';

// The getString mock must hand back the SAME promise for the same string: ItemRow reads it
// with use(), which re-invokes getString on every render and would suspend forever
// against a fresh promise each time.
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

// The design system is ESM-only, so Jest's CJS resolver cannot load it. Stand the
// three components in with markup carrying enough of their DOM contract for these
// tests, which are about ItemRow's prop wiring rather than design system rendering.
jest.mock('@moodlehq/design-system', () => ({
    Checkbox: (props: {label: string; checked: boolean; disabled: boolean; onChange: (e: unknown) => void}) => (
        <label>
            {props.label}
            <input
                type="checkbox"
                checked={props.checked}
                disabled={props.disabled}
                onChange={props.onChange}
            />
        </label>
    ),
    Badge: (props: {label: string; variant: string}) => (
        <span data-testid="badge" data-variant={props.variant}>{props.label}</span>
    ),
    Button: (props: {label: string; disabled: boolean; onClick: () => void}) => (
        <button type="button" disabled={props.disabled} onClick={props.onClick}>{props.label}</button>
    ),
}), {virtual: true});

/** Render and flush the suspended string reads. */
async function renderRow(ui: React.ReactElement) {
    await act(async() => {
        render(ui);
    });
}

/**
 * Build a waypoint.
 *
 * @param overrides Fields to override.
 */
function makeItem(overrides: Partial<WaypointItem> = {}): WaypointItem {
    return {id: 7, title: 'Read the brief', sortorder: 1, done: false, ...overrides};
}

describe('mod_waypoint/ItemRow', () => {
    const noop = () => undefined;

    it('renders the waypoint title against the checkbox', async() => {
        await renderRow(
            <ItemRow item={makeItem()} cantick canmanage={false} busy={false} onToggle={noop} onDelete={noop} />
        );

        expect(screen.getByRole('checkbox')).not.toBeChecked();
        expect(screen.getByText('Read the brief')).toBeInTheDocument();
    });

    it('checks the box and shows a badge when the waypoint is done', async() => {
        await renderRow(
            <ItemRow item={makeItem({done: true})} cantick canmanage={false} busy={false} onToggle={noop} onDelete={noop} />
        );

        expect(screen.getByRole('checkbox')).toBeChecked();
        expect(screen.getByTestId('badge')).toHaveAttribute('data-variant', 'success');
    });

    it('does not show a badge while the waypoint is outstanding', async() => {
        await renderRow(
            <ItemRow item={makeItem()} cantick canmanage={false} busy={false} onToggle={noop} onDelete={noop} />
        );

        expect(screen.queryByTestId('badge')).not.toBeInTheDocument();
    });

    it('reports the new state when the box is toggled', async() => {
        const onToggle = jest.fn();
        const item = makeItem();
        await renderRow(
            <ItemRow item={item} cantick canmanage={false} busy={false} onToggle={onToggle} onDelete={noop} />
        );

        await act(async() => {
            screen.getByRole('checkbox').click();
        });

        expect(onToggle).toHaveBeenCalledWith(item, true);
    });

    it('disables the checkbox for a user who may not tick', async() => {
        await renderRow(
            <ItemRow item={makeItem()} cantick={false} canmanage={false} busy={false} onToggle={noop} onDelete={noop} />
        );

        expect(screen.getByRole('checkbox')).toBeDisabled();
    });

    it('disables the checkbox while a request is in flight', async() => {
        await renderRow(
            <ItemRow item={makeItem()} cantick canmanage={false} busy onToggle={noop} onDelete={noop} />
        );

        expect(screen.getByRole('checkbox')).toBeDisabled();
    });

    it('hides the delete button from a user who may not manage', async() => {
        await renderRow(
            <ItemRow item={makeItem()} cantick canmanage={false} busy={false} onToggle={noop} onDelete={noop} />
        );

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('offers a delete button to a user who may manage', async() => {
        const onDelete = jest.fn();
        const item = makeItem();
        await renderRow(
            <ItemRow item={item} cantick canmanage busy={false} onToggle={noop} onDelete={onDelete} />
        );

        await act(async() => {
            screen.getByRole('button').click();
        });

        expect(onDelete).toHaveBeenCalledWith(item);
    });
});
