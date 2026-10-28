var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * The progress bar above the waypoint list.
 *
 * @module     mod_waypoint/ProgressSummary
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { use } from "react";
import { ProgressBar } from "@moodlehq/design-system";
import { getString } from "@moodle/lms/core/stringUtils";
function ProgressSummary({ progress }) {
  const title = use(getString("progresstitle", "mod_waypoint"));
  const finished = progress.total > 0 && progress.completed === progress.total;
  return /* @__PURE__ */ jsxDEV(
    ProgressBar,
    {
      value: progress.percentage,
      title,
      count: progress.countLabel,
      labelVariant: "title-and-count",
      ...finished ? {} : { status: "in-progress" }
    },
    void 0,
    false,
    {
      fileName: "public/mod/waypoint/js/esm/src/ProgressSummary.tsx",
      lineNumber: 45,
      columnNumber: 9
    },
    this
  );
}
__name(ProgressSummary, "ProgressSummary");
export {
  ProgressSummary as default
};
//# sourceMappingURL=ProgressSummary.dev.js.map
