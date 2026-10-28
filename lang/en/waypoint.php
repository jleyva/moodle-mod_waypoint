<?php
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
 * Strings for mod_waypoint.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Waypoint';
$string['modulename'] = 'Waypoint';
$string['modulenameplural'] = 'Waypoints';
$string['modulename_help'] = 'The waypoint activity lets a teacher publish a list of steps that students tick off as they go. It exists mainly to demonstrate the Moodle routing subsystem, the REST API, React components and the Moodle Design System in a single plugin.';
$string['pluginadministration'] = 'Waypoint administration';

// Capabilities.
$string['waypoint:addinstance'] = 'Add a new waypoint activity';
$string['waypoint:view'] = 'View a waypoint activity';
$string['waypoint:tick'] = 'Tick off your own waypoints';
$string['waypoint:manage'] = 'Add, edit and delete waypoints';
$string['waypoint:viewreport'] = 'View everyone\'s waypoint progress';

// Completion.
$string['completionallwaypoints'] = 'Student must tick every waypoint';
$string['completionallwaypoints_desc'] = 'The activity is complete once the student has ticked every waypoint in the list.';
$string['completiondetail:allwaypoints'] = 'Tick every waypoint';

// Errors.
$string['errornoview'] = 'You cannot view this waypoint activity.';
$string['errornotick'] = 'You cannot tick off waypoints in this activity.';
$string['errornomanage'] = 'You cannot change the waypoints in this activity.';

// Student view.
$string['addwaypoint'] = 'Add waypoint';
$string['addwaypointplaceholder'] = 'Describe the next step…';
$string['deletewaypoint'] = 'Delete waypoint';
$string['nowaypoints'] = 'No waypoints yet.';
$string['nowaypointsteacher'] = 'No waypoints yet. Add the first one below.';
$string['progresstitle'] = 'Your progress';
$string['progresscount'] = '{$a->completed} of {$a->total}';
$string['done'] = 'Done';
$string['nextpage'] = 'Next page';
$string['paginationlabel'] = 'Waypoint pages';
$string['previouspage'] = 'Previous page';
$string['hidecompleted'] = 'Hide completed';
$string['savefailed'] = 'Could not save that change.';
$string['retry'] = 'Retry';

// Report.
$string['report'] = 'Waypoint progress';
$string['reportfor'] = 'Waypoint progress: {$a}';
$string['nostudents'] = 'Nobody is enrolled on this course yet.';

// OAuth2 / Personal access token scopes.
$string['waypoint_read_scope_summary'] = 'View waypoints';
$string['waypoint_read_scope_desc'] = 'Read the waypoint list and your own progress through it.';
$string['waypoint_write_scope_summary'] = 'Tick off waypoints';
$string['waypoint_write_scope_desc'] = 'Mark your own waypoints as done or not done.';
$string['waypoint_manage_scope_summary'] = 'Manage waypoints';
$string['waypoint_manage_scope_desc'] = 'Add, rename, reorder and delete the waypoints in an activity.';

// Privacy.
$string['privacy:metadata:waypoint_progress'] = 'Records which waypoints a user has ticked off.';
$string['privacy:metadata:waypoint_progress:itemid'] = 'The waypoint that was ticked.';
$string['privacy:metadata:waypoint_progress:userid'] = 'The user who ticked the waypoint.';
$string['privacy:metadata:waypoint_progress:timecompleted'] = 'The time the waypoint was ticked.';
