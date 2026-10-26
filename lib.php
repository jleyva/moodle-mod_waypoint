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
 * Library of interface functions for mod_waypoint.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Declare which optional activity features this module supports.
 *
 * @param string $feature One of the FEATURE_* constants.
 * @return mixed True/false for boolean features, a value for others, null if unknown.
 */
function waypoint_supports(string $feature): mixed {
    return match ($feature) {
        FEATURE_MOD_INTRO,
        FEATURE_SHOW_DESCRIPTION,
        FEATURE_BACKUP_MOODLE2,
        FEATURE_COMPLETION_TRACKS_VIEWS,
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_GRADE_HAS_GRADE,
        FEATURE_GRADE_OUTCOMES,
        FEATURE_GROUPS,
        FEATURE_GROUPINGS => false,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_CONTENT,
        default => null,
    };
}

/**
 * Add a new waypoint instance.
 *
 * @param stdClass $data Data from the module form.
 * @param null|mod_waypoint_mod_form $mform The form instance.
 * @return int The id of the new instance.
 */
function waypoint_add_instance(stdClass $data, $mform = null): int {
    global $DB;

    $now = \core\di::get(\core\clock::class)->time();
    $data->timecreated = $now;
    $data->timemodified = $now;
    $data->completionallwaypoints = empty($data->completionallwaypoints) ? 0 : 1;

    return $DB->insert_record('waypoint', $data);
}

/**
 * Update an existing waypoint instance.
 *
 * @param stdClass $data Data from the module form.
 * @param null|mod_waypoint_mod_form $mform The form instance.
 * @return bool
 */
function waypoint_update_instance(stdClass $data, $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = \core\di::get(\core\clock::class)->time();
    $data->completionallwaypoints = empty($data->completionallwaypoints) ? 0 : 1;

    return $DB->update_record('waypoint', $data);
}

/**
 * Delete a waypoint instance and everything belonging to it.
 *
 * @param int $id The instance id.
 * @return bool
 */
function waypoint_delete_instance(int $id): bool {
    global $DB;

    if (!$waypoint = $DB->get_record('waypoint', ['id' => $id])) {
        return false;
    }

    $itemids = $DB->get_fieldset_select('waypoint_item', 'id', 'waypointid = :waypointid', [
        'waypointid' => $waypoint->id,
    ]);
    if ($itemids) {
        [$insql, $params] = $DB->get_in_or_equal($itemids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('waypoint_progress', "itemid {$insql}", $params);
    }

    $DB->delete_records('waypoint_item', ['waypointid' => $waypoint->id]);
    $DB->delete_records('waypoint', ['id' => $waypoint->id]);

    return true;
}

/**
 * Provide course module information, including the custom completion rules.
 *
 * Returning the rule here is what lets the course page and the completion
 * reports describe the rule without loading the instance record again.
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info|bool
 */
function waypoint_get_coursemodule_info(stdClass $coursemodule): cached_cm_info|bool {
    global $DB;

    $fields = 'id, name, intro, introformat, completionallwaypoints';
    if (!$waypoint = $DB->get_record('waypoint', ['id' => $coursemodule->instance], $fields)) {
        return false;
    }

    $info = new cached_cm_info();
    $info->name = $waypoint->name;

    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('waypoint', $waypoint, $coursemodule->id, false);
    }

    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules']['completionallwaypoints'] =
            $waypoint->completionallwaypoints;
    }

    return $info;
}

/**
 * Add the progress report to the activity's secondary navigation.
 *
 * Without this the routed report page exists but nothing links to it. The URL
 * is asked of the router rather than written out by hand, so the route
 * attribute in \mod_waypoint\route\controller\report stays the single place
 * that path is defined.
 *
 * @param settings_navigation $settings
 * @param navigation_node $waypointnode
 */
function waypoint_extend_settings_navigation(
    settings_navigation $settings,
    navigation_node $waypointnode,
): void {
    $cm = $settings->get_page()->cm;

    if (!$cm || !has_capability('mod/waypoint:viewreport', $cm->context)) {
        return;
    }

    $waypointnode->add(
        get_string('report', 'mod_waypoint'),
        \core\router\util::get_path_for_callable(
            [\mod_waypoint\route\controller\report::class, 'view_report'],
            ['cm' => $cm->id],
        ),
        navigation_node::TYPE_SETTING,
        null,
        'waypointreport',
    );
}
