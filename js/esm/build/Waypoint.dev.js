var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Root component for the Waypoint activity.
 *
 * Mounted by core/react_autoinit from the data-react-component attribute that
 * the {{#react}} helper writes in mod_waypoint/view.
 *
 * It owns all the state. Because each endpoint returns only the resource it
 * addresses, a change to a waypoint tells us nothing about the activity's
 * progress total — so after every mutation we refetch the activity and the
 * current page of waypoints together, and let the server stay the single
 * source of truth for both.
 *
 * @module     mod_waypoint/Waypoint
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { Suspense, use, useCallback, useId, useState } from "react";
import { Button, Pagination, Switch } from "@moodlehq/design-system";
import String from "@moodle/lms/core/String";
import { getString } from "@moodle/lms/core/stringUtils";
import ProgressSummary from "@moodle/lms/mod_waypoint/ProgressSummary";
import ItemList from "@moodle/lms/mod_waypoint/ItemList";
import AddItemForm from "@moodle/lms/mod_waypoint/AddItemForm";
import * as repository from "./repository";
const buildQuery = /* @__PURE__ */ __name((page, hideCompleted) => hideCompleted ? { page, done: false } : { page }, "buildQuery");
function WaypointApp({ activityId, initialActivity, initialItems }) {
  const [activity, setActivity] = useState(initialActivity);
  const [itemPage, setItemPage] = useState(initialItems);
  const [page, setPage] = useState(initialItems.pagination.page);
  const [hideCompleted, setHideCompleted] = useState(false);
  const [busy, setBusy] = useState(false);
  const [failed, setFailed] = useState(false);
  const hideCompletedLabel = use(getString("hidecompleted", "mod_waypoint"));
  const retryLabel = use(getString("retry", "mod_waypoint"));
  const paginationLabel = use(getString("paginationlabel", "mod_waypoint"));
  const previousPageLabel = use(getString("previouspage", "mod_waypoint"));
  const nextPageLabel = use(getString("nextpage", "mod_waypoint"));
  const switchId = useId();
  const refresh = useCallback(async (query) => {
    const fetchBoth = /* @__PURE__ */ __name((next) => Promise.all([
      repository.getActivity(activityId),
      repository.getItems(activityId, next)
    ]), "fetchBoth");
    let [nextActivity, nextItems] = await fetchBoth(query);
    if (nextItems.items.length === 0 && nextItems.pagination.page > 1) {
      [nextActivity, nextItems] = await fetchBoth({ ...query, page: 1 });
      setPage(1);
    }
    setActivity(nextActivity);
    setItemPage(nextItems);
  }, [activityId]);
  const run = useCallback(async (query, operation) => {
    setBusy(true);
    setFailed(false);
    try {
      if (operation) {
        await operation();
      }
      await refresh(query);
    } catch {
      setFailed(true);
    } finally {
      setBusy(false);
    }
  }, [refresh]);
  const handleToggle = useCallback(
    (item, done) => run(
      buildQuery(page, hideCompleted),
      () => repository.setDone(activityId, item.id, done)
    ),
    [activityId, hideCompleted, page, run]
  );
  const handleDelete = useCallback(
    (item) => run(
      buildQuery(page, hideCompleted),
      () => repository.deleteItem(activityId, item.id)
    ),
    [activityId, hideCompleted, page, run]
  );
  const handleAdd = useCallback(
    (title) => run(
      buildQuery(page, hideCompleted),
      () => repository.addItem(activityId, title)
    ),
    [activityId, hideCompleted, page, run]
  );
  const handleRetry = useCallback(
    () => run(buildQuery(page, hideCompleted)),
    [hideCompleted, page, run]
  );
  const handleHideCompleted = useCallback((hide) => {
    setHideCompleted(hide);
    setPage(1);
    run(buildQuery(1, hide));
  }, [run]);
  const handlePageChange = useCallback((next) => {
    setPage(next);
    run(buildQuery(next, hideCompleted));
  }, [hideCompleted, run]);
  const { items, pagination } = itemPage;
  const totalPages = Math.max(1, Math.ceil(pagination.totalItems / pagination.pageSize));
  return /* @__PURE__ */ jsxDEV("div", { "data-region": "waypoint", className: "mod-waypoint", children: [
    /* @__PURE__ */ jsxDEV(ProgressSummary, { progress: activity.progress }, void 0, false, {
      fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
      lineNumber: 173,
      columnNumber: 13
    }, this),
    activity.progress.total > 0 && /* @__PURE__ */ jsxDEV("div", { className: "d-flex justify-content-end mt-3", children: /* @__PURE__ */ jsxDEV(
      Switch,
      {
        id: switchId,
        label: hideCompletedLabel,
        labelSide: "start",
        variant: "visibility",
        checked: hideCompleted,
        disabled: busy,
        onChange: (event) => handleHideCompleted(event.target.checked)
      },
      void 0,
      false,
      {
        fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
        lineNumber: 177,
        columnNumber: 21
      },
      this
    ) }, void 0, false, {
      fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
      lineNumber: 176,
      columnNumber: 17
    }, this),
    failed && /* @__PURE__ */ jsxDEV("div", { className: "alert alert-warning d-flex align-items-center gap-3 mt-3", role: "alert", children: [
      /* @__PURE__ */ jsxDEV("span", { className: "flex-grow-1", children: /* @__PURE__ */ jsxDEV(String, { identifier: "savefailed", component: "mod_waypoint" }, void 0, false, {
        fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
        lineNumber: 192,
        columnNumber: 25
      }, this) }, void 0, false, {
        fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
        lineNumber: 191,
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
          lineNumber: 194,
          columnNumber: 21
        },
        this
      )
    ] }, void 0, true, {
      fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
      lineNumber: 190,
      columnNumber: 17
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mt-3", children: /* @__PURE__ */ jsxDEV(
      ItemList,
      {
        items,
        canManage: activity.canManage,
        canTick: activity.canTick,
        busy,
        onToggle: handleToggle,
        onDelete: handleDelete
      },
      void 0,
      false,
      {
        fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
        lineNumber: 205,
        columnNumber: 17
      },
      this
    ) }, void 0, false, {
      fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
      lineNumber: 204,
      columnNumber: 13
    }, this),
    totalPages > 1 && /* @__PURE__ */ jsxDEV("div", { className: "mt-3", "data-region": "waypoint-pagination", children: /* @__PURE__ */ jsxDEV(
      Pagination,
      {
        totalPages,
        currentPage: pagination.page,
        onPageChange: handlePageChange,
        ariaLabel: paginationLabel,
        previousPageLabel,
        nextPageLabel,
        disabled: busy
      },
      void 0,
      false,
      {
        fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
        lineNumber: 217,
        columnNumber: 21
      },
      this
    ) }, void 0, false, {
      fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
      lineNumber: 216,
      columnNumber: 17
    }, this),
    activity.canManage && /* @__PURE__ */ jsxDEV(AddItemForm, { busy, onAdd: handleAdd }, void 0, false, {
      fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
      lineNumber: 229,
      columnNumber: 36
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
    lineNumber: 172,
    columnNumber: 9
  }, this);
}
__name(WaypointApp, "WaypointApp");
function Waypoint({ activityId, initialActivity, initialItems }) {
  return /* @__PURE__ */ jsxDEV(Suspense, { fallback: /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: /* @__PURE__ */ jsxDEV(String, { identifier: "loading", component: "core" }, void 0, false, {
    fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
    lineNumber: 245,
    columnNumber: 55
  }, this) }, void 0, false, {
    fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
    lineNumber: 245,
    columnNumber: 29
  }, this), children: /* @__PURE__ */ jsxDEV(
    WaypointApp,
    {
      activityId,
      initialActivity,
      initialItems
    },
    void 0,
    false,
    {
      fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
      lineNumber: 246,
      columnNumber: 13
    },
    this
  ) }, void 0, false, {
    fileName: "public/mod/waypoint/js/esm/src/Waypoint.tsx",
    lineNumber: 245,
    columnNumber: 9
  }, this);
}
__name(Waypoint, "Waypoint");
export {
  Waypoint as default
};
//# sourceMappingURL=Waypoint.dev.js.map
