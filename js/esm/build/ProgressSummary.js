import{use as e}from"react";import{ProgressBar as s}from"@moodlehq/design-system";import{getString as a}from"@moodle/lms/core/stringUtils";import{jsx as i}from"react/jsx-runtime";/**
 * The progress bar above the waypoint list.
 *
 * @module     mod_waypoint/ProgressSummary
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function n({progress:t}){const o=e(a("progresstitle","mod_waypoint")),r=t.total>0&&t.completed===t.total;return i(s,{value:t.percentage,title:o,count:t.countLabel,labelVariant:"title-and-count",...r?{}:{status:"in-progress"}})}export{n as default};
