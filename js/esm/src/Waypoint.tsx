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
 * It owns all the state. Because each endpoint returns only the resource it
 * addresses, a change to a waypoint tells us nothing about the activity's
 * progress total — so after every mutation we refetch the activity and the
 * current page of waypoints together, and let the server stay the single
 * source of truth for both.
 *
 * @module     mod_waypoint/Waypoint
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {Suspense, use, useCallback, useId, useState} from 'react';
import {Button, Pagination, Switch} from '@moodlehq/design-system';
import String from '@moodle/lms/core/String';
import {getString} from '@moodle/lms/core/stringUtils';
import ProgressSummary from '@moodle/lms/mod_waypoint/ProgressSummary';
import ItemList from '@moodle/lms/mod_waypoint/ItemList';
import AddItemForm from '@moodle/lms/mod_waypoint/AddItemForm';
import * as repository from './repository';
import type {ItemPage, ItemQuery, WaypointActivity, WaypointItem, WaypointProps} from './common/types';

/**
 * Build the collection query for a page and filter combination.
 *
 * @param page The 1-based page to ask for.
 * @param hideCompleted Whether ticked waypoints should be left out.
 * @returns The query to send.
 */
const buildQuery = (page: number, hideCompleted: boolean): ItemQuery =>
    hideCompleted ? {page, done: false} : {page};

/**
 * The activity UI, once its language strings have resolved.
 *
 * @param props The activity id and the server-rendered first page.
 */
function WaypointApp({activityId, initialActivity, initialItems}: WaypointProps) {
    const [activity, setActivity] = useState<WaypointActivity>(initialActivity);
    const [itemPage, setItemPage] = useState<ItemPage>(initialItems);
    const [page, setPage] = useState(initialItems.pagination.page);
    const [hideCompleted, setHideCompleted] = useState(false);
    const [busy, setBusy] = useState(false);
    const [failed, setFailed] = useState(false);

    const hideCompletedLabel = use(getString('hidecompleted', 'mod_waypoint'));
    const retryLabel = use(getString('retry', 'mod_waypoint'));
    const paginationLabel = use(getString('paginationlabel', 'mod_waypoint'));
    const previousPageLabel = use(getString('previouspage', 'mod_waypoint'));
    const nextPageLabel = use(getString('nextpage', 'mod_waypoint'));
    const switchId = useId();

    /**
     * Pull the activity and one page of waypoints, in parallel.
     *
     * Deleting the last waypoint on the last page would otherwise leave the
     * reader staring at an empty page that is not page one, so fall back to
     * the start of the collection when that happens.
     *
     * @param query Which page to fetch, and what to filter it by.
     */
    const refresh = useCallback(async(query: ItemQuery) => {
        const fetchBoth = (next: ItemQuery) => Promise.all([
            repository.getActivity(activityId),
            repository.getItems(activityId, next),
        ]);

        let [nextActivity, nextItems] = await fetchBoth(query);

        if (nextItems.items.length === 0 && nextItems.pagination.page > 1) {
            [nextActivity, nextItems] = await fetchBoth({...query, page: 1});
            setPage(1);
        }

        setActivity(nextActivity);
        setItemPage(nextItems);
    }, [activityId]);

    /**
     * Apply a change, then reload what the screen is showing.
     *
     * @param query The collection query to reload with.
     * @param operation The repository call to run first, if there is one.
     */
    const run = useCallback(async(query: ItemQuery, operation?: () => Promise<unknown>) => {
        setBusy(true);
        setFailed(false);
        try {
            if (operation) {
                await operation();
            }
            await refresh(query);
        } catch {
            // The server rejected the change, so our state is now suspect.
            // Say so and offer a reload rather than guessing.
            setFailed(true);
        } finally {
            setBusy(false);
        }
    }, [refresh]);

    const handleToggle = useCallback(
        (item: WaypointItem, done: boolean) => run(
            buildQuery(page, hideCompleted),
            () => repository.setDone(activityId, item.id, done),
        ),
        [activityId, hideCompleted, page, run],
    );

    const handleDelete = useCallback(
        (item: WaypointItem) => run(
            buildQuery(page, hideCompleted),
            () => repository.deleteItem(activityId, item.id),
        ),
        [activityId, hideCompleted, page, run],
    );

    const handleAdd = useCallback(
        (title: string) => run(
            buildQuery(page, hideCompleted),
            () => repository.addItem(activityId, title),
        ),
        [activityId, hideCompleted, page, run],
    );

    const handleRetry = useCallback(
        () => run(buildQuery(page, hideCompleted)),
        [hideCompleted, page, run],
    );

    /**
     * Hiding completed waypoints is a server-side filter, not a local one:
     * with the list paginated, filtering the page we happen to hold would
     * quietly drop waypoints that live on another one.
     *
     * @param hide Whether to leave ticked waypoints out.
     */
    const handleHideCompleted = useCallback((hide: boolean) => {
        setHideCompleted(hide);
        setPage(1);
        run(buildQuery(1, hide));
    }, [run]);

    const handlePageChange = useCallback((next: number) => {
        setPage(next);
        run(buildQuery(next, hideCompleted));
    }, [hideCompleted, run]);

    const {items, pagination} = itemPage;
    const totalPages = Math.max(1, Math.ceil(pagination.totalItems / pagination.pageSize));

    return (
        <div data-region="waypoint" className="mod-waypoint">
            <ProgressSummary progress={activity.progress} />

            {activity.progress.total > 0 && (
                <div className="d-flex justify-content-end mt-3">
                    <Switch
                        id={switchId}
                        label={hideCompletedLabel}
                        labelSide="start"
                        variant="visibility"
                        checked={hideCompleted}
                        disabled={busy}
                        onChange={(event) => handleHideCompleted(event.target.checked)}
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
                    items={items}
                    canManage={activity.canManage}
                    canTick={activity.canTick}
                    busy={busy}
                    onToggle={handleToggle}
                    onDelete={handleDelete}
                />
            </div>

            {totalPages > 1 && (
                <div className="mt-3" data-region="waypoint-pagination">
                    <Pagination
                        totalPages={totalPages}
                        currentPage={pagination.page}
                        onPageChange={handlePageChange}
                        ariaLabel={paginationLabel}
                        previousPageLabel={previousPageLabel}
                        nextPageLabel={nextPageLabel}
                        disabled={busy}
                    />
                </div>
            )}

            {activity.canManage && <AddItemForm busy={busy} onAdd={handleAdd} />}
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
 * @param props The activity id and the server-rendered first page.
 */
export default function Waypoint({activityId, initialActivity, initialItems}: WaypointProps) {
    return (
        <Suspense fallback={<p className="text-muted"><String identifier="loading" component="core" /></p>}>
            <WaypointApp
                activityId={activityId}
                initialActivity={initialActivity}
                initialItems={initialItems}
            />
        </Suspense>
    );
}
