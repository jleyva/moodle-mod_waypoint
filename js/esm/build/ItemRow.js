import{use as a}from"react";import{Badge as c,Button as b,Checkbox as g}from"@moodlehq/design-system";import{getString as n}from"@moodle/lms/core/stringUtils";import{jsx as o,jsxs as f}from"react/jsx-runtime";/**
 * One row of the waypoint list.
 *
 * @module     mod_waypoint/ItemRow
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function y({item:e,cantick:i,canmanage:l,busy:t,onToggle:d,onDelete:m}){const r=a(n("done","mod_waypoint")),p=a(n("deletewaypoint","mod_waypoint"));return f("li",{className:"d-flex align-items-center gap-2 py-2 border-bottom","data-itemid":e.id,children:[o("div",{className:"flex-grow-1",children:o(g,{label:e.title,checked:e.done,disabled:!i||t,onChange:s=>d(e,s.target.checked)})}),e.done&&o(c,{label:r,variant:"success",subtle:!0,pill:!0}),l&&o(b,{label:p,variant:"ghost",size:"sm",disabled:t,onClick:()=>m(e)})]})}export{y as default};
