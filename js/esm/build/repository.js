import o from"@moodle/lms/core/fetch";/**
 * Data-access layer for mod_waypoint.
 *
 * The only module that talks to the network. Components call these functions
 * and never import core/fetch themselves, which keeps the REST API an
 * implementation detail of this one file.
 *
 * Every endpoint returns the full activity state, so each function resolves
 * to a WaypointState the caller can drop straight into component state.
 *
 * @module     mod_waypoint/repository
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const n="mod_waypoint",a=(t,e)=>`${t}/${e}`,r=async t=>await t.json(),i=async t=>r(await o.performGet(n,a(t,"items"))),p=async(t,e)=>r(await o.performPost(n,a(t,"items"),{body:{title:e}})),y=async(t,e,s)=>r(await o.performPatch(n,a(t,`items/${e}`),{body:{title:s}})),c=async(t,e)=>r(await o.performDelete(n,a(t,`items/${e}`))),b=async(t,e,s)=>r(await o.performPut(n,a(t,`progress/${e}`),{body:{done:s}}));export{p as addItem,c as deleteItem,i as getState,y as renameItem,b as setDone};
