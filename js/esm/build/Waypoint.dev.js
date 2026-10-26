var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
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
 */
import { Suspense, use, useCallback, useId, useMemo, useState } from "react";
import { Button, Switch } from "@moodlehq/design-system";
import String from "@moodle/lms/core/String";
import { getString } from "@moodle/lms/core/stringUtils";
import ProgressSummary from "@moodle/lms/mod_waypoint/ProgressSummary";
import ItemList from "@moodle/lms/mod_waypoint/ItemList";
import AddItemForm from "@moodle/lms/mod_waypoint/AddItemForm";
import * as repository from "./repository";
function WaypointApp({ cmid, initialstate }) {
  const [state, setState] = useState(initialstate);
  const [busy, setBusy] = useState(false);
  const [failed, setFailed] = useState(false);
  const [hideCompleted, setHideCompleted] = useState(false);
  const hideCompletedLabel = use(getString("hidecompleted", "mod_waypoint"));
  const retryLabel = use(getString("retry", "mod_waypoint"));
  const switchId = useId();
  const run = useCallback(async (operation) => {
    setBusy(true);
    setFailed(false);
    try {
      setState(await operation());
    } catch {
      setFailed(true);
    } finally {
      setBusy(false);
    }
  }, []);
  const handleToggle = useCallback(
    (item, done) => run(() => repository.setDone(cmid, item.id, done)),
    [cmid, run]
  );
  const handleDelete = useCallback(
    (item) => run(() => repository.deleteItem(cmid, item.id)),
    [cmid, run]
  );
  const handleAdd = useCallback(
    (title) => run(() => repository.addItem(cmid, title)),
    [cmid, run]
  );
  const handleRetry = useCallback(
    () => run(() => repository.getState(cmid)),
    [cmid, run]
  );
  const visibleItems = useMemo(
    () => hideCompleted ? state.items.filter((item) => !item.done) : state.items,
    [hideCompleted, state.items]
  );
  return /* @__PURE__ */ jsxDEV("div", { "data-region": "waypoint", className: "mod-waypoint", children: [
    /* @__PURE__ */ jsxDEV(ProgressSummary, { progress: state.progress }, void 0, false, {
      fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
      lineNumber: 102,
      columnNumber: 13
    }, this),
    state.items.length > 0 && /* @__PURE__ */ jsxDEV("div", { className: "d-flex justify-content-end mt-3", children: /* @__PURE__ */ jsxDEV(
      Switch,
      {
        id: switchId,
        label: hideCompletedLabel,
        labelSide: "start",
        variant: "visibility",
        checked: hideCompleted,
        onChange: (event) => setHideCompleted(event.target.checked)
      },
      void 0,
      false,
      {
        fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
        lineNumber: 106,
        columnNumber: 21
      },
      this
    ) }, void 0, false, {
      fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
      lineNumber: 105,
      columnNumber: 17
    }, this),
    failed && /* @__PURE__ */ jsxDEV("div", { className: "alert alert-warning d-flex align-items-center gap-3 mt-3", role: "alert", children: [
      /* @__PURE__ */ jsxDEV("span", { className: "flex-grow-1", children: /* @__PURE__ */ jsxDEV(String, { identifier: "savefailed", component: "mod_waypoint" }, void 0, false, {
        fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
        lineNumber: 120,
        columnNumber: 25
      }, this) }, void 0, false, {
        fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
        lineNumber: 119,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV(
        Button,
        {
          label: retryLabel,
          variant: "outline-secondary",
          size: "sm",
          disabled: busy,
          onClick: handleRetry
        },
        void 0,
        false,
        {
          fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
          lineNumber: 122,
          columnNumber: 21
        },
        this
      )
    ] }, void 0, true, {
      fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
      lineNumber: 118,
      columnNumber: 17
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mt-3", children: /* @__PURE__ */ jsxDEV(
      ItemList,
      {
        items: visibleItems,
        canmanage: state.canmanage,
        cantick: state.cantick,
        busy,
        onToggle: handleToggle,
        onDelete: handleDelete
      },
      void 0,
      false,
      {
        fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
        lineNumber: 133,
        columnNumber: 17
      },
      this
    ) }, void 0, false, {
      fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
      lineNumber: 132,
      columnNumber: 13
    }, this),
    state.canmanage && /* @__PURE__ */ jsxDEV(AddItemForm, { busy, onAdd: handleAdd }, void 0, false, {
      fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
      lineNumber: 143,
      columnNumber: 33
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
    lineNumber: 101,
    columnNumber: 9
  }, this);
}
__name(WaypointApp, "WaypointApp");
function Waypoint({ cmid, initialstate }) {
  return /* @__PURE__ */ jsxDEV(Suspense, { fallback: /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: /* @__PURE__ */ jsxDEV(String, { identifier: "loading", component: "core" }, void 0, false, {
    fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
    lineNumber: 159,
    columnNumber: 55
  }, this) }, void 0, false, {
    fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
    lineNumber: 159,
    columnNumber: 29
  }, this), children: /* @__PURE__ */ jsxDEV(WaypointApp, { cmid, initialstate }, void 0, false, {
    fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
    lineNumber: 160,
    columnNumber: 13
  }, this) }, void 0, false, {
    fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
    lineNumber: 159,
    columnNumber: 9
  }, this);
}
__name(Waypoint, "Waypoint");
export {
  Waypoint as default
};
//# sourceMappingURL=Waypoint.dev.js.map
