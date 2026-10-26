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
 * Root component for the Waypoint activity.
 *
 * Mounted by core/react_autoinit from the data-react-component attribute that
 * the {{#react}} helper writes in mod_waypoint/view.
 *
 * It owns all the state. Every repository call returns the complete activity
 * state, so there is no merging to do — the response simply replaces what we
 * had, and the server stays the single source of truth for progress and
 * capabilities.
 *
 * @module     mod_waypoint/Waypoint
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {Suspense, use, useCallback, useId, useMemo, useState} from 'react';
import {Button, Switch} from '@moodlehq/design-system';
import String from '@moodle/lms/core/String';
import {getString} from '@moodle/lms/core/stringUtils';
import ProgressSummary from '@moodle/lms/mod_waypoint/ProgressSummary';
import ItemList from '@moodle/lms/mod_waypoint/ItemList';
import AddItemForm from '@moodle/lms/mod_waypoint/AddItemForm';
import * as repository from './repository';
import type {WaypointItem, WaypointProps, WaypointState} from './common/types';

/**
 * The activity UI, once its language strings have resolved.
 *
 * @param props The cmid and the server-rendered initial state.
 */
function WaypointApp({cmid, initialstate}: WaypointProps) {
    const [state, setState] = useState<WaypointState>(initialstate);
    const [busy, setBusy] = useState(false);
    const [failed, setFailed] = useState(false);
    const [hideCompleted, setHideCompleted] = useState(false);

    const hideCompletedLabel = use(getString('hidecompleted', 'mod_waypoint'));
    const retryLabel = use(getString('retry', 'mod_waypoint'));
    const switchId = useId();

    /**
     * Run a repository call, replacing our state with whatever it returns.
     *
     * @param operation The repository call to run.
     */
    const run = useCallback(async(operation: () => Promise<WaypointState>) => {
        setBusy(true);
        setFailed(false);
        try {
            setState(await operation());
        } catch {
            // The server rejected the change, so our state is now suspect.
            // Say so and offer a reload rather than guessing.
            setFailed(true);
        } finally {
            setBusy(false);
        }
    }, []);

    const handleToggle = useCallback(
        (item: WaypointItem, done: boolean) => run(() => repository.setDone(cmid, item.id, done)),
        [cmid, run],
    );

    const handleDelete = useCallback(
        (item: WaypointItem) => run(() => repository.deleteItem(cmid, item.id)),
        [cmid, run],
    );

    const handleAdd = useCallback(
        (title: string) => run(() => repository.addItem(cmid, title)),
        [cmid, run],
    );

    const handleRetry = useCallback(
        () => run(() => repository.getState(cmid)),
        [cmid, run],
    );

    const visibleItems = useMemo(
        () => (hideCompleted ? state.items.filter((item) => !item.done) : state.items),
        [hideCompleted, state.items],
    );

    return (
        <div data-region="waypoint" className="mod-waypoint">
            <ProgressSummary progress={state.progress} />

            {state.items.length > 0 && (
                <div className="d-flex justify-content-end mt-3">
                    <Switch
                        id={switchId}
                        label={hideCompletedLabel}
                        labelSide="start"
                        variant="visibility"
                        checked={hideCompleted}
                        onChange={(event) => setHideCompleted(event.target.checked)}
                    />
                </div>
            )}

            {failed && (
                <div className="alert alert-warning d-flex align-items-center gap-3 mt-3" role="alert">
                    <span className="flex-grow-1">
                        <String identifier="savefailed" component="mod_waypoint" />
                    </span>
                    <Button
                        label={retryLabel}
                        variant="outline-secondary"
                        size="sm"
                        disabled={busy}
                        onClick={handleRetry}
                    />
                </div>
            )}

            <div className="mt-3">
                <ItemList
                    items={visibleItems}
                    canmanage={state.canmanage}
                    cantick={state.cantick}
                    busy={busy}
                    onToggle={handleToggle}
                    onDelete={handleDelete}
                />
            </div>

            {state.canmanage && <AddItemForm busy={busy} onAdd={handleAdd} />}
        </div>
    );
}

/**
 * Suspense boundary for the activity UI.
 *
 * Every component below here resolves its language strings with use(), which
 * suspends until they arrive. getString returns a stable promise per string,
 * so this is safe to re-render.
 *
 * @param props The cmid and the server-rendered initial state.
 */
export default function Waypoint({cmid, initialstate}: WaypointProps) {
    return (
        <Suspense fallback={<p className="text-muted"><String identifier="loading" component="core" /></p>}>
            <WaypointApp cmid={cmid} initialstate={initialstate} />
        </Suspense>
    );
}
