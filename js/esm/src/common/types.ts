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
 * These mirror the REST API response described by
 * mod_waypoint\route\responses\state_response. If you change one, change both.
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
    sortorder: number;
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
    countlabel: string;
}

/** Everything the UI needs, as returned by every endpoint. */
export interface WaypointState {
    /** The waypoints, in sort order. */
    items: WaypointItem[];
    /** The calling user's progress. */
    progress: WaypointProgress;
    /** Whether the calling user may edit the list. */
    canmanage: boolean;
    /** Whether the calling user may tick waypoints. */
    cantick: boolean;
}

/** Props passed from view.mustache. */
export interface WaypointProps {
    /** The course module id, used to build every API URL. */
    cmid: number;
    /** The state rendered server-side, so the first paint needs no request. */
    initialstate: WaypointState;
}
