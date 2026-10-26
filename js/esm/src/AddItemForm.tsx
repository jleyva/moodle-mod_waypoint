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
 * The teacher's "add a waypoint" form.
 *
 * @module     mod_waypoint/AddItemForm
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {use, useId, useState, type FormEvent} from 'react';
import {Button} from '@moodlehq/design-system';
import {getString} from '@moodle/lms/core/stringUtils';

interface AddItemFormProps {
    /** Whether a request is in flight, so controls should be inert. */
    busy: boolean;
    /** Called with a trimmed, non-empty title. */
    onAdd: (title: string) => void;
}

/**
 * A single-field form that appends a waypoint to the list.
 */
export default function AddItemForm({busy, onAdd}: AddItemFormProps) {
    const label = use(getString('addwaypoint', 'mod_waypoint'));
    const placeholder = use(getString('addwaypointplaceholder', 'mod_waypoint'));
    const [title, setTitle] = useState('');
    const inputId = useId();

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const trimmed = title.trim();
        if (trimmed === '') {
            return;
        }
        onAdd(trimmed);
        setTitle('');
    };

    return (
        <form className="d-flex gap-2 mt-3" onSubmit={submit}>
            <label className="visually-hidden" htmlFor={inputId}>{label}</label>
            {/*
                The design system has no Input component at 5.3.0, so this is a
                plain Bootstrap form control. Swap it for <Input> once it lands.
            */}
            <input
                id={inputId}
                type="text"
                className="form-control"
                placeholder={placeholder}
                value={title}
                maxLength={255}
                disabled={busy}
                onChange={(event) => setTitle(event.target.value)}
            />
            <Button
                type="submit"
                label={label}
                variant="primary"
                disabled={busy || title.trim() === ''}
            />
        </form>
    );
}
