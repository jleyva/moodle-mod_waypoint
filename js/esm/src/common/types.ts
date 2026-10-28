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
 * Shared types for the Waypoint React app.
 *
 * These mirror the REST API responses described in
 * mod_waypoint\route\responses. If you change one, change both.
 *
 * @module     mod_waypoint/common/types
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** A single waypoint. */
export interface WaypointItem {
    /** The waypoint id. */
    id: number;
    /** The waypoint title, already cleaned server-side. */
    title: string;
    /** Position in the list. */
    sortOrder: number;
    /** Whether the calling user has ticked it. */
    done: boolean;
}

/** How far through the list the calling user is. */
export interface WaypointProgress {
    /** Number of waypoints ticked. */
    completed: number;
    /** Number of waypoints in total. */
    total: number;
    /** Progress as a whole percentage. */
    percentage: number;
    /** A translated "x of y" label, formatted server-side. */
    countLabel: string;
}

/** The activity, with everything that belongs to it rather than to one waypoint. */
export interface WaypointActivity {
    /** The course module id. */
    id: number;
    /** The activity name. */
    name: string;
    /** The calling user's progress. */
    progress: WaypointProgress;
    /** Whether the calling user may edit the list. */
    canManage: boolean;
    /** Whether the calling user may tick waypoints. */
    canTick: boolean;
}

/** Where in the collection the returned page sits. */
export interface Pagination {
    /** The 1-based page number. */
    page: number;
    /** How many waypoints the page holds. */
    pageSize: number;
    /** How many waypoints match, across every page. */
    totalItems: number;
}

/** One page of the waypoint collection. */
export interface ItemPage {
    /** The waypoints on this page. */
    items: WaypointItem[];
    /** Where the page sits in the collection. */
    pagination: Pagination;
}

/** The calling user's progress against one waypoint. */
export interface ItemProgress {
    /** The waypoint. */
    itemId: number;
    /** Whether it is now ticked. */
    done: boolean;
    /** When it was ticked, ISO-8601 with an offset, or null if it is not. */
    completedAt: string | null;
}

/** What to ask the collection endpoint for. */
export interface ItemQuery {
    /** The 1-based page to fetch. */
    page?: number;
    /** Keep only ticked, or only unticked, waypoints. */
    done?: boolean;
}

/** Props passed from view.mustache. */
export interface WaypointProps {
    /** The activity id, used to build every API URL. */
    activityId: number;
    /** The activity rendered server-side, so the first paint needs no request. */
    initialActivity: WaypointActivity;
    /** The first page of waypoints, likewise. */
    initialItems: ItemPage;
}
