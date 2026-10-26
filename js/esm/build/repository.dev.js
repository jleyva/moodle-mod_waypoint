var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
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
 */
import Fetch from "@moodle/lms/core/fetch";
const COMPONENT = "mod_waypoint";
const endpoint = /* @__PURE__ */ __name((cmid, path) => `${cmid}/${path}`, "endpoint");
const toState = /* @__PURE__ */ __name(async (response) => await response.json(), "toState");
const getState = /* @__PURE__ */ __name(async (cmid) => toState(await Fetch.performGet(COMPONENT, endpoint(cmid, "items"))), "getState");
const addItem = /* @__PURE__ */ __name(async (cmid, title) => toState(await Fetch.performPost(COMPONENT, endpoint(cmid, "items"), { body: { title } })), "addItem");
const renameItem = /* @__PURE__ */ __name(async (cmid, itemid, title) => toState(await Fetch.performPatch(COMPONENT, endpoint(cmid, `items/${itemid}`), { body: { title } })), "renameItem");
const deleteItem = /* @__PURE__ */ __name(async (cmid, itemid) => toState(await Fetch.performDelete(COMPONENT, endpoint(cmid, `items/${itemid}`))), "deleteItem");
const setDone = /* @__PURE__ */ __name(async (cmid, itemid, done) => toState(await Fetch.performPut(COMPONENT, endpoint(cmid, `progress/${itemid}`), { body: { done } })), "setDone");
export {
  addItem,
  deleteItem,
  getState,
  renameItem,
  setDone
};
//# sourceMappingURL=repository.dev.js.map
