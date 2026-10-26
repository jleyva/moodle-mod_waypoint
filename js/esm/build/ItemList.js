import l from"@moodle/lms/core/String";import r from"@moodle/lms/mod_waypoint/ItemRow";import{jsx as t}from"react/jsx-runtime";/**
 * The waypoint list.
 *
 * @module     mod_waypoint/ItemList
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function s({items:o,canmanage:e,cantick:i,busy:a,onToggle:m,onDelete:p}){return o.length===0?t("p",{className:"text-muted mb-0","data-region":"waypoint-empty",children:t(l,{identifier:e?"nowaypointsteacher":"nowaypoints",component:"mod_waypoint"})}):t("ul",{className:"list-unstyled mb-0","data-region":"waypoint-list",children:o.map(n=>t(r,{item:n,cantick:i,canmanage:e,busy:a,onToggle:m,onDelete:p},n.id))})}export{s as default};
