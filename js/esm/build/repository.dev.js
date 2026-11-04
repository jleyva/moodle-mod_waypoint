var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Service module for mod_waypoint.
 *
 * "Service module" is the frontend guide's term: components should not embed
 * data fetching, and should go through a module that encapsulates the API
 * calls, handles request and response transformations, and stays independent
 * of the UI. See https://moodledev.io/docs/5.3/guides/frontend
 *
 * The guide's example names the folder services/; this file follows
 * block_timeline, which is core's own React plugin, and the guide allows for it:
 * "the exact structure may differ".
 *
 * So: the only module that talks to the network. Components call these
 * functions and never import core/fetch themselves, which keeps the REST API
 * an implementation detail of this one file.
 *
 * Each function returns the resource its endpoint returns, not the whole
 * activity. Refreshing everything after a change therefore takes two
 * requests, one per resource — see Waypoint.tsx.
 *
 * @module     mod_waypoint/repository
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Fetch from "@moodle/lms/core/fetch";
const COMPONENT = "mod_waypoint";
const endpoint = /* @__PURE__ */ __name((activityId, suffix = "") => `activities/${activityId}${suffix}`, "endpoint");
const decode = /* @__PURE__ */ __name(async (response) => await response.json(), "decode");
const toParams = /* @__PURE__ */ __name(({ page, done }) => {
  const params = {};
  if (page !== void 0) {
    params.page = String(page);
  }
  if (done !== void 0) {
    params.done = done ? "true" : "false";
  }
  return params;
}, "toParams");
const getActivity = /* @__PURE__ */ __name(async (activityId) => decode(await Fetch.performGet(COMPONENT, endpoint(activityId))), "getActivity");
const getItems = /* @__PURE__ */ __name(async (activityId, query = {}) => decode(await Fetch.performGet(COMPONENT, endpoint(activityId, "/items"), { params: toParams(query) })), "getItems");
const addItem = /* @__PURE__ */ __name(async (activityId, title) => decode(await Fetch.performPost(COMPONENT, endpoint(activityId, "/items"), { body: { title } })), "addItem");
const renameItem = /* @__PURE__ */ __name(async (activityId, itemId, title) => decode(
  await Fetch.performPatch(COMPONENT, endpoint(activityId, `/items/${itemId}`), { body: { title } })
), "renameItem");
const deleteItem = /* @__PURE__ */ __name(async (activityId, itemId) => {
  await Fetch.performDelete(COMPONENT, endpoint(activityId, `/items/${itemId}`));
}, "deleteItem");
const setDone = /* @__PURE__ */ __name(async (activityId, itemId, done) => decode(
  await Fetch.performPut(COMPONENT, endpoint(activityId, `/items/${itemId}/progress`), { body: { done } })
), "setDone");
export {
  addItem,
  deleteItem,
  getActivity,
  getItems,
  renameItem,
  setDone
};
//# sourceMappingURL=repository.dev.js.map
