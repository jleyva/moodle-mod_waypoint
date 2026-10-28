var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * One row of the waypoint list.
 *
 * @module     mod_waypoint/ItemRow
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { use } from "react";
import { Badge, Button, Checkbox } from "@moodlehq/design-system";
import { getString } from "@moodle/lms/core/stringUtils";
function ItemRow({ item, canTick, canManage, busy, onToggle, onDelete }) {
  const doneLabel = use(getString("done", "mod_waypoint"));
  const deleteLabel = use(getString("deletewaypoint", "mod_waypoint"));
  return /* @__PURE__ */ jsxDEV("li", { className: "d-flex align-items-center gap-2 py-2 border-bottom", "data-itemid": item.id, children: [
    /* @__PURE__ */ jsxDEV("div", { className: "flex-grow-1", children: /* @__PURE__ */ jsxDEV(
      Checkbox,
      {
        label: item.title,
        checked: item.done,
        disabled: !canTick || busy,
        onChange: (event) => onToggle(item, event.target.checked)
      },
      void 0,
      false,
      {
        fileName: "public/mod/waypoint/js/esm/src/ItemRow.tsx",
        lineNumber: 53,
        columnNumber: 17
      },
      this
    ) }, void 0, false, {
      fileName: "public/mod/waypoint/js/esm/src/ItemRow.tsx",
      lineNumber: 52,
      columnNumber: 13
    }, this),
    item.done && /* @__PURE__ */ jsxDEV(Badge, { label: doneLabel, variant: "success", subtle: true, pill: true }, void 0, false, {
      fileName: "public/mod/waypoint/js/esm/src/ItemRow.tsx",
      lineNumber: 60,
      columnNumber: 27
    }, this),
    canManage && /* @__PURE__ */ jsxDEV(
      Button,
      {
        label: deleteLabel,
        variant: "ghost",
        size: "sm",
        disabled: busy,
        onClick: () => onDelete(item)
      },
      void 0,
      false,
      {
        fileName: "public/mod/waypoint/js/esm/src/ItemRow.tsx",
        lineNumber: 62,
        columnNumber: 17
      },
      this
    )
  ] }, void 0, true, {
    fileName: "public/mod/waypoint/js/esm/src/ItemRow.tsx",
    lineNumber: 51,
    columnNumber: 9
  }, this);
}
__name(ItemRow, "ItemRow");
export {
  ItemRow as default
};
//# sourceMappingURL=ItemRow.dev.js.map
