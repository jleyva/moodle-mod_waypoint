var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * The waypoint list.
 *
 * @module     mod_waypoint/ItemList
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import String from "@moodle/lms/core/String";
import ItemRow from "@moodle/lms/mod_waypoint/ItemRow";
function ItemList({ items, canManage, canTick, busy, onToggle, onDelete }) {
  if (items.length === 0) {
    return /* @__PURE__ */ jsxDEV("p", { className: "text-muted mb-0", "data-region": "waypoint-empty", children: /* @__PURE__ */ jsxDEV(
      String,
      {
        identifier: canManage ? "nowaypointsteacher" : "nowaypoints",
        component: "mod_waypoint"
      },
      void 0,
      false,
      {
        fileName: "public/mod/waypoint/js/esm/src/ItemList.tsx",
        lineNumber: 49,
        columnNumber: 17
      },
      this
    ) }, void 0, false, {
      fileName: "public/mod/waypoint/js/esm/src/ItemList.tsx",
      lineNumber: 48,
      columnNumber: 13
    }, this);
  }
  return /* @__PURE__ */ jsxDEV("ul", { className: "list-unstyled mb-0", "data-region": "waypoint-list", children: items.map((item) => /* @__PURE__ */ jsxDEV(
    ItemRow,
    {
      item,
      canTick,
      canManage,
      busy,
      onToggle,
      onDelete
    },
    item.id,
    false,
    {
      fileName: "public/mod/waypoint/js/esm/src/ItemList.tsx",
      lineNumber: 60,
      columnNumber: 17
    },
    this
  )) }, void 0, false, {
    fileName: "public/mod/waypoint/js/esm/src/ItemList.tsx",
    lineNumber: 58,
    columnNumber: 9
  }, this);
}
__name(ItemList, "ItemList");
export {
  ItemList as default
};
//# sourceMappingURL=ItemList.dev.js.map
