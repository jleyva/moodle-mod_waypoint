var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * The teacher's "add a waypoint" form.
 *
 * @module     mod_waypoint/AddItemForm
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { use, useId, useState } from "react";
import { Button } from "@moodlehq/design-system";
import { getString } from "@moodle/lms/core/stringUtils";
function AddItemForm({ busy, onAdd }) {
  const label = use(getString("addwaypoint", "mod_waypoint"));
  const placeholder = use(getString("addwaypointplaceholder", "mod_waypoint"));
  const [title, setTitle] = useState("");
  const inputId = useId();
  const submit = /* @__PURE__ */ __name((event) => {
    event.preventDefault();
    const trimmed = title.trim();
    if (trimmed === "") {
      return;
    }
    onAdd(trimmed);
    setTitle("");
  }, "submit");
  return /* @__PURE__ */ jsxDEV("form", { className: "d-flex gap-2 mt-3", onSubmit: submit, children: [
    /* @__PURE__ */ jsxDEV("label", { className: "visually-hidden", htmlFor: inputId, children: label }, void 0, false, {
      fileName: "public/mod/waypoint/js/esm/src/AddItemForm.tsx",
      lineNumber: 55,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(
      "input",
      {
        id: inputId,
        type: "text",
        className: "form-control",
        placeholder,
        value: title,
        maxLength: 255,
        disabled: busy,
        onChange: (event) => setTitle(event.target.value)
      },
      void 0,
      false,
      {
        fileName: "public/mod/waypoint/js/esm/src/AddItemForm.tsx",
        lineNumber: 60,
        columnNumber: 13
      },
      this
    ),
    /* @__PURE__ */ jsxDEV(
      Button,
      {
        type: "submit",
        label,
        variant: "primary",
        disabled: busy || title.trim() === ""
      },
      void 0,
      false,
      {
        fileName: "public/mod/waypoint/js/esm/src/AddItemForm.tsx",
        lineNumber: 70,
        columnNumber: 13
      },
      this
    )
  ] }, void 0, true, {
    fileName: "public/mod/waypoint/js/esm/src/AddItemForm.tsx",
    lineNumber: 54,
    columnNumber: 9
  }, this);
}
__name(AddItemForm, "AddItemForm");
export {
  AddItemForm as default
};
//# sourceMappingURL=AddItemForm.dev.js.map
