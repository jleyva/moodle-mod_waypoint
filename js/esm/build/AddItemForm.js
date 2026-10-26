import{use as i,useId as u,useState as c}from"react";import{Button as f}from"@moodlehq/design-system";import{getString as d}from"@moodle/lms/core/stringUtils";import{jsx as o,jsxs as y}from"react/jsx-runtime";/**
 * The teacher's "add a waypoint" form.
 *
 * @module     mod_waypoint/AddItemForm
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function b({busy:r,onAdd:s}){const a=i(d("addwaypoint","mod_waypoint")),p=i(d("addwaypointplaceholder","mod_waypoint")),[t,m]=c(""),l=u();return y("form",{className:"d-flex gap-2 mt-3",onSubmit:e=>{e.preventDefault();const n=t.trim();n!==""&&(s(n),m(""))},children:[o("label",{className:"visually-hidden",htmlFor:l,children:a}),o("input",{id:l,type:"text",className:"form-control",placeholder:p,value:t,maxLength:255,disabled:r,onChange:e=>m(e.target.value)}),o(f,{type:"submit",label:a,variant:"primary",disabled:r||t.trim()===""})]})}export{b as default};
