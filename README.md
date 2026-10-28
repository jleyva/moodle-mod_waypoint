# mod_waypoint

A small Moodle activity module that exists to demonstrate four things that come
together for the first time in **Moodle 5.X**, in one plugin you can read in an
afternoon:

| | Where to look |
|---|---|
| **Routing subsystem** — REST endpoints, a routed HTML page, and a legacy-URL shim | `classes/route/` |
| **REST API** (`/api/rest/v2/…`) with OAuth2 scopes and generated OpenAPI | `classes/route/api/`, `classes/route/scope/` |
| **React** (TypeScript, built with esbuild via grunt) | `js/esm/src/` |
| **Moodle Design System** components | `js/esm/src/*.tsx` |

The activity itself is a checklist: a teacher writes a list of waypoints, students
tick them off, and the activity completes when every box is ticked.

**New to this?** [WALKTHROUGH.md](WALKTHROUGH.md) is a linear tour of the
implementation — each technology on its own, then one click traced end to end.
This README is the reference: endpoints, install, layout.

---

## What it actually demonstrates

### 1. The router is not just for REST

Moodle's router has five route groups. Three are available to plugins, and this
plugin uses all three:

| Group | URL prefix | Namespace | Used here for |
|---|---|---|---|
| `ROUTE_GROUP_API` | `/api/rest/v2` | `route\api` | the seven REST endpoints |
| `ROUTE_GROUP_PAGE` | `/` | `route\controller` | the teacher report page |
| `ROUTE_GROUP_SHIM` | *(none)* | `route\shim` | keeping `/mod/waypoint/report.php` alive |

The other two (`/oauth2` and `/s/` shortlinks) are core-internal.

### 2. The endpoints

| Verb | URL | Answers with | Scopes |
|---|---|---|---|
| `GET` | `/api/rest/v2/mod_waypoint/activities/{activityId}` | `200` — the activity | `read` |
| `GET` | `…/activities/{activityId}/items` | `200` — `{items, pagination}` | `read` |
| `POST` | `…/activities/{activityId}/items` | `201` + `Location` — the new waypoint | `read` + `manage` |
| `GET` | `…/activities/{activityId}/items/{itemId}` | `200` — the waypoint | `read` |
| `PATCH` | `…/activities/{activityId}/items/{itemId}` | `200` — the waypoint | `read` + `manage` |
| `DELETE` | `…/activities/{activityId}/items/{itemId}` | `204` — nothing | `read` + `manage` |
| `PUT` | `…/activities/{activityId}/items/{itemId}/progress` | `200` — the progress | `read` + `write` |

The collection takes `?page=`, `?pagesize=` (25 by default, 100 at most),
`?sort=` (`sortorder`, `title` or `id`, each with an optional leading `-` for
descending) and `?done=true|false`.

`{activityId}` is resolved by `classes/route/parameters/path_activity.php`, a
four-line subclass of `\core\router\parameters\path_coursemodule`. It accepts
either a course module id or `idnumber:some-idnumber`, attaches the course module
record and its context to the request, and returns a 404 on its own if it cannot
resolve it. You never write that lookup yourself.

This surface follows the [REST API style guide](https://moodledev.io/docs/5.3/guides/rest/style)
published on 1 October 2026. That is also why it does **not** look like
`core_user`'s `/user/{user}/preferences` — see
[Where the guide and core disagree](#where-the-guide-and-core-disagree).

**No endpoint returns the whole activity state.** Section 10.1 of the guide lets
a collection response carry `items` and `pagination` and nothing else, so the
progress summary and the capability flags belong to the activity instead. The
React client pays for that: after any change it refetches the activity and the
current page of waypoints together, in parallel, because the response to a
change says nothing about the totals.

The generated spec is at `/api/rest/v2/openapi.json` with no extra work.

### 3. Scopes

Every route in the `api` namespace **must** carry `#[scopeset(...)]` or
`#[unscoped_resource]` — a core PHPUnit test scans for it and fails the build
otherwise. The three scopes are in `classes/route/scope/waypoint/`:

- `mod_waypoint:waypoint:read` — see the list and your own progress
- `mod_waypoint:waypoint:write` — tick your own boxes
- `mod_waypoint:waypoint:manage` — edit the list

Scopes only apply to OAuth2 / personal-access-token requests. Session-authenticated
requests are governed by the capabilities in `db/access.php`, which the routes
check through `item_manager::require_can_*()`.

### 4. React, two mounting styles

`templates/view.mustache` uses the `{{#react}}` Mustache helper. The markup
*inside* the helper is a working, read-only fallback that React replaces when it
mounts — the same progressive-enhancement pattern core uses in
`lib/templates/editswitch.mustache` and the nav menus.

The one-line alternative, used by `block_timeline`, is:

```php
echo \core\output\html_writer::react_component('@moodle/lms/mod_waypoint/Waypoint', $props);
```

Both end up as `<div data-react-component=… data-react-props=…>`, which
`core/react_autoinit` finds and mounts.

`js/esm/src/repository.ts` is the only module that imports `core/fetch`. Views
call the repository and never the network directly.

### 5. Design System components

Using `@moodlehq/design-system` 5.3.0: `Checkbox`, `ProgressBar`, `Button`,
`Badge`, `Switch`, `Pagination`.

Text fields are plain Bootstrap `form-control` — the design system had no `Input`
component at 5.3.0 (it arrives in 6.x, along with `Alert`, `Tag` and `Textarea`).

`ProgressBar` deliberately will not derive its count text from `value`/`max`:
word order and plural rules belong to the language pack. So `countLabel` is built
server-side with `get_string()` and sent down in the payload. Worth copying.

`Pagination` takes `totalPages` / `currentPage` / `onPageChange` and renders
`<button>` elements — not links — so a Behat step has to say `"button"`, not
`"link"`.

`js/esm/src/swizzle.json` declares which components a theme may override or wrap.

---

## Six things that will bite you

None of these is in the documentation; all of them cost real time while building
this. [WALKTHROUGH.md](WALKTHROUGH.md#6-six-traps) explains each one in full,
with the evidence.

1. **Class-level `requirelogin` is silently ignored** — and this one is a security
   hole. `path`, `pathtypes`, `queryparams` and `headerparams` *are* inherited from
   a class-level `#[route]`; `requirelogin` is not. Declare it on every method or
   your endpoints serve data to anonymous callers.
2. **`require_capability()` surfaces as a 500, not a 403.** Throw
   `\core\exception\access_denied_exception` instead — see
   `item_manager::require_capability()`.
3. **`array_of_things` describes a map, not a list**, and leaves a dangling `$ref`
   in the OpenAPI document. This plugin ships `classes/route/schema/array_of.php`
   instead.
4. **Shims need `$CFG->routerconfigured = true`.** Through the `/r.php/…` fallback,
   Apache matches the trailing `.php` and answers before the router sees the
   request. Core's own example shims behave identically.
5. **The design system `Switch` cannot be clicked by Behat.** Its real `<input>`
   is visually hidden and `.mds-switch` is not positioned, so the input lands
   outside the control. Both `I click on ... "checkbox"` and `I set the field ...
   to` throw `ElementClickInterceptedException`. Click the label text instead.
6. **A `param::BOOL` query parameter answers 500, not 400, when it cannot be
   read.** `query_parameter::validate()` throws a bare `\ValueError` for anything
   that is not the literal `true` or `false`, and nothing maps that to a status.
   This plugin declares `?done=` as `param::RAW` and parses it in
   `item_query::read_done()` so a bad value is the 400 it should be. The cost is
   that `openapi.json` now types `done` as a string rather than a boolean.


## Decisions taken without documentation

A lot of this plugin could not be written from the docs. The pages on
moodledev.io cover the `api` route group, parameters, responses and scopes; they
say nothing about the other route groups, how React is mounted, or which design
system components exist. The rest came from reading core's source, from core's
own plugins, or from things failing.

URL naming used to be on that list. It is not any more: the
[REST API style guide](https://moodledev.io/docs/5.3/guides/rest/style) was
published on 1 October 2026 and settles it, so this plugin now follows the guide
rather than core's example. That turns out to be a real choice, not a free one —
see below.

This section exists so you can tell which parts of this plugin are *Moodle
convention* and which are *one developer's reading of core in October 2026*. If
a convention is published later and contradicts something here, the convention
wins.

### Read out of core's source — likely right, but not promised API

| Decision | Based on | If it changes |
|---|---|---|
| **Plugins may use three route groups** (`api`, `controller`, `shim`) | The constants in `route_loader_interface` and the body of `route_loader::configure_routes()`. The docs only describe `api`, and warn that "any unknown Route Group will be ignored". | A group could be made core-only. |
| **Shim paths are relative to the plugin directory** — `/report.php`, not `/mod/waypoint/report.php` | The `componentpathcallback` in `get_all_shimmed_routes()`, which prefixes the component directory for everything except `core`. | The shim 404s. |
| **`swizzle.json` values** `safe` / `risky` / `prohibited` | `block_timeline`'s own `swizzle.json`. I found no documentation of the file or its vocabulary. | Themes may not override as intended. |
| **One module owns all network calls** (`repository.ts`) | `block_timeline` does this, and its docblock asserts it as a rule. It is a convention in practice, not a documented one. | Nothing; it is just a good habit. |
| **Which design system components exist** | Read from `node_modules/@moodlehq/design-system/dist/components/index.d.ts`. The moodledev.io pages describe tokens and say components are "planned"; there is no published list, and no Storybook URL in the docs. | Imports break on upgrade. Check the `.d.ts`, not the docs. |

### Found by it failing, not by reading

| Discovery | How |
|---|---|
| **`requirelogin` is not inherited from a class-level `#[route]`** | An unauthenticated `curl` returned `200` with the full item list. See trap 1 — this is a security footgun, and I believe it is a core bug rather than intended behaviour. |
| **`require_capability()` becomes a 500, not a 403** | A test asserting `403` got `500`. The mechanism (`response_aware_exception`) is not documented, and nothing lists which exceptions implement it. |
| **`array_of_things` emits a map and a dangling `$ref`** | Reading the generated `openapi.json` and finding a `$ref` to a component that was never defined. My conclusion that it is wrong for lists is *my reading of its behaviour*, not an official position — if core intended a map, `classes/route/schema/array_of.php` is unnecessary. |
| **A Mustache comment ends at the first `}}`** | The docblock in `view.mustache` described the fallback and mentioned the `{{#react}}` tag by name. Its closing braces terminated the comment, and the rest of the docblock — prose and example JSON — rendered onto the page for every student. Never write a tag inside a template docblock. |
| **`install.xml`'s `PATH` is relative to `dirroot`** | `mod/waypoint/db`, not `public/mod/waypoint/db`. The installer rejects it otherwise. |
| **The design system `Switch` cannot be clicked by Behat** | Its real `<input>` is visually hidden (1x1px, clipped) with the visible control in a sibling `<label for=...>`, and `.mds-switch` sets no `position: relative` — so the absolutely positioned input lands outside the control. Both `I click on ... "checkbox"` and `I set the field ... to` call `click()` on that input and get `ElementClickInterceptedException`. Click the label text instead. |
| **A `param::BOOL` query parameter answers 500 on a bad value** | `?done=maybe` returned a 500 HTML page. `query_parameter::validate()` matches the literal strings `true` and `false` and throws a bare `\ValueError` otherwise, which no handler maps to a status. Declaring the parameter `param::RAW` and parsing it yourself is the only way to get the 400 the caller deserves. |
| **`path_coursemodule` names its request attributes after the parameter** | A parameter called `cm` attaches `cmdata` and `cmcontext`. Name it `activityId` as the style guide requires and the handler has to ask for `$activityIddata` — a camelCase variable, which Moodle's own coding style forbids. Overriding the trait's `get_value_name()` in a four-line subclass is what reconciles the two. |
| **`tsc --noEmit -p tsconfig.json` is not a usable typecheck** | 40 x `TS6305` from the `composite` project reference, for core's files as much as this plugin's. See the build notes in [WALKTHROUGH.md](WALKTHROUGH.md#2-react). |

### Where the guide and core disagree

The style guide is normative and recent. Core's own REST endpoints predate it,
and in places contradict it. Where the two differ this plugin follows the guide,
because the guide is the published convention and core's endpoints are not
documented as examples to copy.

| Rule | The guide | Core | Here |
|---|---|---|---|
| §4.3 URI shape | `<component>/<resource>/{identifier}`, e.g. `/user/users/42` | `core_user` serves `/user/{user}/preferences` — an identifier with no resource noun before it | `/mod_waypoint/activities/{activityId}/items` |
| §6.1 path parameters | camelCase, ending in `Id` | `{user}`, `{cm}` | `{activityId}`, `{itemId}` |
| §9.1 JSON fields | camelCase (a SHOULD) | concatenated lowercase | `canManage`, `sortOrder`, `countLabel` |
| §12.1 error bodies | RFC-9457: `type`, `title`, `detail`, `instance` | `exception_response` emits `message`, `errorcode`, `stacktrace` | core's shape — a plugin cannot change it |
| §3.2, §17 statelessness | no server-side session state | the router authenticates by cookie as well as by token | core's behaviour |

The last two are not ours to fix: both are decided by the framework, and a
plugin route gets whatever `exception_response` and the authentication
middleware produce.

Two consequences are worth knowing before you copy this:

- **Following §4.3 costs you the resemblance to core.** If you grep core for a
  precedent you will find `core_user` and conclude the opposite of what the
  guide says. The guide is newer.
- **The guide is not yet self-consistent.** §12.1 and §12.2 specify two
  different error shapes and both are marked required; the page size is
  `pageSize` in the body but `pagesize` in the query string; §9.3 forbids Unix
  timestamps while §11.5's own example uses one; §7.1 suggests a maximum nesting
  depth of two and then gives a REQUIRED example three deep; and §16 carries a
  "this section is under revision" note from its authors. Where it contradicts
  itself this plugin picks one side and says so in a comment at the point of
  use.

Only ALL-CAPS keywords are normative (the guide says so in §2), so "maximum
recommended depth: 2" in §7.1 is advice, not a rule — which is why
`/items/{itemId}/progress` sits three deep without apology.

### My own choices, with no precedent in core

These are design decisions, not conventions. Disagree freely.

- **The activity is a resource in its own right.** The guide says where the
  progress summary and the capability flags may *not* go — a collection carries
  items and pagination only — but not where they should go instead. Hanging them
  off `GET /activities/{activityId}` is my reading; a separate
  `/activities/{activityId}/progress` would be just as defensible.
- **`?done=` is a filter rather than two endpoints.** §10.4 asks for filtering
  through query parameters and leaves the vocabulary open.
- **Which fields are sortable** (`sortorder`, `title`, `id`). The guide fixes the
  `sort` syntax and says nothing about which keys an endpoint should accept, or
  whether they are spelt as the JSON field (`sortOrder`) or lowercased
  (`sortorder`). Its own example lowercases them, so this plugin does too, and
  rejects anything else with a 400.
- **Three scopes (`read` / `write` / `manage`) that are deliberately not 1:1 with
  the capabilities.** The docs explain how to define a scope, not how to choose
  the granularity.
- **`{{#react}}` with a real progressive-enhancement fallback** rather than a
  spinner. The helper's own docblock example shows `<p>Loading...</p>`; that the
  inner markup can be a working server-rendered fallback is inferred from
  `editswitch.mustache` and the nav templates, where core does exactly that.
- **The teacher report is server-rendered, with no React.** No guidance exists on
  when to reach for React in a plugin; a read-only table seemed a poor reason to
  ship a bundle.

## Installing

Requires **Moodle 5.3.0 or later** (version `2026100500`), and Node 22 to build the
JavaScript. Router scopes, `path_coursemodule`, the ESM/React build pipeline, the
`{{#react}}` helper and design system 5.3 all shipped in 5.3.

Note that 5.3 branched on 2026-10-05, so `MOODLE_503_STABLE` and `main` (6.0dev)
currently share the version number `2026100500` — it is 5.3's branch point, not a
6.0 requirement.

The `supported` range is not a guess: the full suite has been run on both ends.
On `MOODLE_503_STABLE` (build 20261021) and on 6.0dev, the plugin installs, the
seven routes register, the scope identifiers resolve, `openapi.json` validates
with no dangling references, and the same 37 PHPUnit and 27 Jest tests pass.

```bash
# From your Moodle root (this plugin lives at public/mod/waypoint in the new layout,
# and admin/cli/ sits beside public/ rather than inside it).
php admin/cli/upgrade.php --non-interactive
npx grunt esm           # builds js/esm/src/*.tsx into js/esm/build/
```

On a development branch such as 6.0dev the upgrade script refuses to run without
`--allow-unstable`; on a 5.3 stable site you do not need it.

### Scopes need core's composer dependencies

On a fresh git checkout, run `composer install` before expecting any of this to
work. `league/oauth2-server` is a production `require` in core's `composer.json`
and is **not** vendored into `lib/` the way Slim and the PSR interfaces are. Without
it, `\core\router\scope\abstract_scope` cannot load, because it implements
`ScopeEntityInterface` from that package.

The failure is confusing, because it is silent and total: every route carrying a
`#[scopeset]` returns a bare `500`, `/api/rest/v2/openapi.json` returns `500`, and
a CLI script touching a scope class dies with no message. Core's own
`/api/rest/v2/user/{user}/preferences` fails the same way, which is the quickest
way to tell this apart from a bug in your plugin — if core's endpoint is also
500, the problem is the checkout, not your code.

`npx grunt esm:watch` rebuilds on change while you work.

## Running the tests

```bash
php public/admin/tool/phpunit/cli/util.php --buildconfig
vendor/bin/phpunit --testsuite mod_waypoint_testsuite    # 37 tests
npx jest public/mod/waypoint                             # 27 tests
```

TypeScript is checked separately, because the root `tsconfig.json` cannot be
used directly (see the traps below):

```bash
npx grunt esm --root=public/mod/waypoint
```

The PHP tests extend `\core\tests\router\route_testcase`, which gives you
`process_api_request()`, `assert_valid_response()`,
`assert_access_denied_response()` and `assert_route_required_scopes()`.

Behat, once the site is configured for it:

```bash
php public/admin/tool/behat/cli/run.php --tags=@mod_waypoint   # 15 scenarios
```

Every interactive scenario is `@javascript`, because the UI is React. One feature,
`waypoint_nojs.feature`, is deliberately *not* tagged: it asserts that the
server-rendered fallback inside the `{{#react}}` helper is readable without
JavaScript and that the interactive controls are absent.

You do not need to write waits for the REST calls. `core/fetch` wraps every
request in a `Pending`, which drives `M.util.js_pending()`, and Behat's
`wait_for_pending_js()` already runs after each click and navigation. Adding
sleeps would only hide a future call that bypassed the repository.

There is deliberately no `I change window size to "large"` step. Core already
resizes every scenario to medium (1024x768) in `behat_hooks.php`, explicitly "to
avoid responsive problems", so adding a resize overrides the size core
standardises on rather than helping. `block_timeline` needs one because its
layout genuinely reflows; a list of checkboxes does not. All 15 scenarios pass at
the default.

`tests/behat/behat_mod_waypoint.php` resolves the routed report page through
`util::get_path_for_callable()` rather than hardcoding a URL, so the scenarios
work whether or not the site under test has `$CFG->routerconfigured` set — the
two produce different URLs.

The jest tests mock `@moodlehq/design-system`, which is ESM-only and cannot be
resolved by jest's CJS resolver — the same workaround core uses in
`blocks/timeline`. Note that the `getString` mock must return a *stable* promise
per string, because the components read it with React's `use()`; a fresh promise
on every render suspends forever.

## Reading it alongside the old way

Eduardo Kraus's book project
([moodle-for-developers-book-Final_Project](https://github.com/EduardoKrausME/moodle-for-developers-book-Final_Project))
builds the same kind of activity entirely in the pre-5.x style: `amd/src/`,
`db/services.php` external functions, `classes/external/`, renderers and
templates. Setting its `classes/external/` beside this plugin's
`classes/route/api/`, and its `amd/src/` beside this one's `js/esm/src/`, is
probably the clearest before-and-after available.

## Deliberately not included

Grading, groups, backup/restore, file areas and calendar events. Each is real
work that teaches nothing about the four APIs above, and the book project already
covers that ground thoroughly.

## Licence

GPL v3 or later, same as Moodle.
