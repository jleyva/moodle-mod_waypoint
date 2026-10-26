import{Suspense as A,use as y,useCallback as i,useId as D,useMemo as L,useState as s}from"react";import{Button as _,Switch as B}from"@moodlehq/design-system";import u from"@moodle/lms/core/String";import{getString as f}from"@moodle/lms/core/stringUtils";import F from"@moodle/lms/mod_waypoint/ProgressSummary";import T from"@moodle/lms/mod_waypoint/ItemList";import z from"@moodle/lms/mod_waypoint/AddItemForm";import*as r from"./repository";import{jsx as e,jsxs as g}from"react/jsx-runtime";/**
 * Root component for the Waypoint activity.
 *
 * Mounted by core/react_autoinit from the data-react-component attribute that
 * the {{#react}} helper writes in mod_waypoint/view.
 *
 * It owns all the state. Every repository call returns the complete activity
 * state, so there is no merging to do — the response simply replaces what we
 * had, and the server stays the single source of truth for progress and
 * capabilities.
 *
 * @module     mod_waypoint/Waypoint
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function H({cmid:a,initialstate:l}){const[o,b]=s(l),[m,p]=s(!1),[h,c]=s(!1),[d,v]=s(!1),S=y(f("hidecompleted","mod_waypoint")),W=y(f("retry","mod_waypoint")),w=D(),n=i(async t=>{p(!0),c(!1);try{b(await t())}catch{c(!0)}finally{p(!1)}},[]),I=i((t,x)=>n(()=>r.setDone(a,t.id,x)),[a,n]),k=i(t=>n(()=>r.deleteItem(a,t.id)),[a,n]),C=i(t=>n(()=>r.addItem(a,t)),[a,n]),N=i(()=>n(()=>r.getState(a)),[a,n]),P=L(()=>d?o.items.filter(t=>!t.done):o.items,[d,o.items]);return g("div",{"data-region":"waypoint",className:"mod-waypoint",children:[e(F,{progress:o.progress}),o.items.length>0&&e("div",{className:"d-flex justify-content-end mt-3",children:e(B,{id:w,label:S,labelSide:"start",variant:"visibility",checked:d,onChange:t=>v(t.target.checked)})}),h&&g("div",{className:"alert alert-warning d-flex align-items-center gap-3 mt-3",role:"alert",children:[e("span",{className:"flex-grow-1",children:e(u,{identifier:"savefailed",component:"mod_waypoint"})}),e(_,{label:W,variant:"outline-secondary",size:"sm",disabled:m,onClick:N})]}),e("div",{className:"mt-3",children:e(T,{items:P,canmanage:o.canmanage,cantick:o.cantick,busy:m,onToggle:I,onDelete:k})}),o.canmanage&&e(z,{busy:m,onAdd:C})]})}function M({cmid:a,initialstate:l}){return e(A,{fallback:e("p",{className:"text-muted",children:e(u,{identifier:"loading",component:"core"})}),children:e(H,{cmid:a,initialstate:l})})}export{M as default};
