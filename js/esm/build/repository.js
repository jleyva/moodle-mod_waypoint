import o from"@moodle/lms/core/fetch";/**
 * Data-access layer for mod_waypoint.
 *
 * The only module that talks to the network. Components call these functions
 * and never import core/fetch themselves, which keeps the REST API an
 * implementation detail of this one file.
 *
 * Each function returns the resource its endpoint returns, not the whole
 * activity. Refreshing everything after a change therefore takes two
 * requests, one per resource — see Waypoint.tsx.
 *
 * @module     mod_waypoint/repository
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const n="mod_waypoint",s=(e,t="")=>`activities/${e}${t}`,m=async e=>await e.json(),i=({page:e,done:t})=>{const r={};return e!==void 0&&(r.page=String(e)),t!==void 0&&(r.done=t?"true":"false"),r},p=async e=>m(await o.performGet(n,s(e))),c=async(e,t={})=>m(await o.performGet(n,s(e,"/items"),{params:i(t)})),y=async(e,t)=>m(await o.performPost(n,s(e,"/items"),{body:{title:t}})),g=async(e,t,r)=>m(await o.performPatch(n,s(e,`/items/${t}`),{body:{title:r}})),u=async(e,t)=>{await o.performDelete(n,s(e,`/items/${t}`))},I=async(e,t,r)=>m(await o.performPut(n,s(e,`/items/${t}/progress`),{body:{done:r}}));export{y as addItem,u as deleteItem,p as getActivity,c as getItems,g as renameItem,I as setDone};
