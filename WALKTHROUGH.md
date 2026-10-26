# mod_waypoint: how it works

A guided tour of the implementation. Each of the four technologies on its own
first, then a single click traced end to end, then the traps.

If you want the quick reference instead — endpoints, install, file layout — see
[README.md](README.md). This document is the long-form version.

The activity itself is deliberately boring: a teacher writes a list of steps,
students tick them off, and the activity completes when every box is ticked. The
point is not the feature. It is that this is the smallest honest thing that needs
all four technologies at once.

---

## 1. The design system

### It is not your dependency

There is no `package.json` in this plugin and no `node_modules` inside it. The
design system is a dependency of Moodle core:

```json
// Moodle's own package.json, at the repository root
"@moodlehq/design-system": "^5.3.0",
"react": "^19.1",
```

Core installs it on `npm install` and it lands in the shared `node_modules` at the
top of the tree. From the plugin's point of view it is simply there. Plugins
cannot drift onto different versions, because there is only ever one copy and
core owns it.

### What is in it

Seventeen components at 5.3.0:

`ActivityIcon` · `Avatar` · `Badge` · `Breadcrumb` · `Button` · `Checkbox` ·
`Choicebox` · `CloseButton` · `Dropdown` (plus ~10 sub-parts) · `FavouriteButton` ·
`Link` · `NavPill` · `Pagination` · `ProgressBar` · `Radio` · `Switch` · `Tooltip`

Worth knowing what is **not** there yet: no `Input`, `Alert`, `Tag` or `Textarea`
until 6.0. That bites immediately, because the instinct when building a form is
to reach for `Input`. In `AddItemForm.tsx` the text field is a plain Bootstrap
`form-control` with a comment marking the spot to swap later.

Mixing design system components with raw Bootstrap is expected. The design system
docs say so: it does not aim to replace Bootstrap, it defines Moodle's own
decisions on top of it.

### Using one

```tsx
import {Badge, Button, Checkbox} from '@moodlehq/design-system';
```

This plugin uses five: `Checkbox` per row, `ProgressBar` in the header, `Button`
for add/delete/retry, `Badge` for the green "done" pill, and `Switch` for a
"hide completed" toggle.

### The ProgressBar lesson

`ProgressBar` takes a `value` and a `count`, and deliberately refuses to derive
the second from the first. From its own docblock:

> The component intentionally does not calculate or format this from `value`,
> `min`, and `max`; word order, plural rules, and number formatting belong to the
> consuming application's i18n layer.

That is exactly right, and it is the thing most people get wrong. The lazy version
concatenates the number, the word "of", and the total in JavaScript — which breaks
in any language with different word order or plural rules.

So the server builds it:

```php
// classes/local/progress.php
public function get_count_label(): string {
    return get_string('progresscount', 'mod_waypoint', (object) [
        'completed' => $this->completed,
        'total' => $this->total,
    ]);
}
```

…and ships it in the payload as `countlabel`, already translated. React just
displays it. If you take one habit from this document, take that one.

### Swizzling

`js/esm/src/swizzle.json` declares, per component, whether a theme may eject or
wrap it:

```json
{
  "Waypoint":        {"eject": "prohibited", "wrap": "risky"},
  "ItemRow":         {"eject": "safe",       "wrap": "safe"}
}
```

The root component is prohibited because replacing it would break state handling;
the leaves are safe. Core does the same — `block_timeline` marks its two view
components prohibited and the rest safe.

---

## 2. React

### Layout and build

```
js/esm/src/     your .ts and .tsx
js/esm/tests/   jest tests
js/esm/build/   compiled output (committed, as core does)
```

TypeScript, compiled by esbuild through grunt:

```bash
npx grunt esm          # build once
npx grunt esm:watch    # rebuild on save — keep this open while working
```

**esbuild strips types, it does not check them.** A type error compiles happily
and fails at runtime. Typecheck separately — but note that the obvious command
does not do what you want:

```bash
npx tsc --noEmit -p tsconfig.json     # ← 40 x TS6305, and none of them are real
```

`tsconfig.json` has `"references": [{"path": "./tsconfig.jest.json"}]`, and that
referenced project is `composite: true`. So tsc expects built declaration outputs
that nobody produces, and reports `TS6305` for every ESM file in the tree —
core's included. It is noise, not type errors.

There is no npm script for this. What works is a config of your own that mirrors
`tsconfig.json`'s compiler options, extends the generated aliases, and drops the
project reference:

```jsonc
{
  "extends": "<moodleroot>/tsconfig.aliases.json",
  "compilerOptions": {
    "target": "ES2022", "module": "ESNext", "moduleResolution": "bundler",
    "jsx": "react-jsx", "strict": true, "skipLibCheck": true,
    "lib": ["ES2022", "DOM"],
    "baseUrl": "<moodleroot>",
    "noEmit": true
  },
  "include": [
    "<moodleroot>/node_modules/@types/**/*.d.ts",
    "<moodleroot>/public/mod/waypoint/js/esm/src/**/*"
  ]
}
```

Against that, this plugin reports zero errors.

Do **not** use `tsconfig.jest.json` to judge your source either. It sets
`module: node16`, under which every `@moodlehq/design-system` import is an error
(`TS1479` and `TS2305`) because the package is ESM-only. `block_timeline` and
`core/lib` produce the same errors. That mismatch is precisely why the jest tests
mock the design system.

### The import aliases

```tsx
import ItemRow from '@moodle/lms/mod_waypoint/ItemRow';
```

`@moodle/lms/…` is not an npm package — it is an import map alias. The build
generates `tsconfig.aliases.json` at the Moodle root with an entry for every
component in the tree:

```json
"@moodle/lms/mod_waypoint/*": ["./public/mod/waypoint/js/esm/src/*"],
"@moodle/lms/theme-boost/mod_waypoint/*": ["./public/theme/boost/js/esm/src/overrides/mod_waypoint/*"],
```

The browser resolves it through the import map at runtime; TypeScript and Jest
resolve it through that generated file. You write none of this — it appears the
moment the plugin exists on disk and you run the build. Note the second line:
the theme override slot for swizzling, wired up automatically.

### Two ways to mount a component

**From PHP, in one line** — what `block_timeline` does:

```php
echo \core\output\html_writer::react_component(
    '@moodle/lms/mod_waypoint/Waypoint',
    $props,
);
```

**From a Mustache template** — what this plugin does, in `templates/view.mustache`:

```handlebars
{{#react}}
{
    "component": "@moodle/lms/mod_waypoint/Waypoint",
    "class": "mod-waypoint",
    "props": {{{reactprops}}}
}
<div class="mod-waypoint-fallback">
    ... server-rendered list and progress bar ...
</div>
{{/react}}
```

Both emit `<div data-react-component="…" data-react-props="…">`. The difference is
that the helper lets you put markup **inside** the section, and React replaces it
on mount. That markup is a progressive-enhancement fallback: somebody with no
JavaScript sees their list and their progress, they just cannot tick anything.

Core does this in three places — `editswitch.mustache`, `primarymoremenu.mustache`
and `secondarymoremenu.mustache`. The edit switch template carries a warning that
if the design system's `Switch` markup changes, the fallback **and** the `.tsx`
both need updating. They treat the fallback as a maintained artefact, not a
throwaway spinner.

### What mounts it

`core/react_autoinit` runs on every page. It scans for `[data-react-component]`,
dynamically imports the module, parses the props out of the JSON attribute and
mounts a React root. It installs a `MutationObserver`, so content injected later
by AJAX is mounted too and unmounted cleanly when removed. You never call an
initialiser.

### Component structure

| Component | Responsibility |
|---|---|
| `Waypoint.tsx` | root; owns all state |
| `ProgressSummary.tsx` | the bar |
| `ItemList.tsx` | rows, or the empty message |
| `ItemRow.tsx` | one row |
| `AddItemForm.tsx` | the teacher's field and button |
| `repository.ts` | **the only module that touches the network** |

Components call the repository; components never call `Fetch`. Core follows the
same convention in `block_timeline`. It is worth keeping, because that is exactly
the seam where the old external-functions API used to plug in — if Moodle changes
how you call the server again, you change one file.

There is a second, less obvious payoff. `Fetch` wraps every request in a
`Pending`:

```ts
const resolvePending = new Pending(`Requesting ${component}/${action} with ${method}`);
```

`Pending` drives `M.util.js_pending()` / `js_complete()`, and Behat calls
`wait_for_pending_js()` after every click, navigation and form submission. So an
acceptance test that ticks a waypoint waits for the `PUT` and the re-render **by
itself** — no explicit waits, no sleeps. Routing all traffic through one module
that uses `Fetch` is what buys that; a call made with bare `fetch()` would be
invisible to Behat and would make the tests flaky.

### Language strings

Components read strings with React's `use()` hook, inside a `Suspense` boundary
at the root:

```tsx
const doneLabel = use(getString('done', 'mod_waypoint'));
```

This works because `getString` caches promises — the same arguments return the
same promise object. Its docblock says so explicitly: *"Returns a stable Promise
reference … making it safe to use with React's `use()` hook."* That stability
matters, and it shows up again in the testing notes below.

---

## 3. The router

### It is not just for REST

Five route groups, three available to plugins:

| Group | URL prefix | Namespace | Purpose |
|---|---|---|---|
| `ROUTE_GROUP_API` | `/api/rest/v2` | `route\api` | REST endpoints |
| `ROUTE_GROUP_PAGE` | `/` | `route\controller` | **ordinary HTML pages** |
| `ROUTE_GROUP_SHIM` | *(none)* | `route\shim` | keep legacy `.php` URLs alive |
| `ROUTE_GROUP_OAUTH2` | `/oauth2` | — | core internal |
| `ROUTE_GROUP_SHORTLINK` | `/s/` | — | core internal |

The page group is a full replacement for a top-level `.php` file: no file on disk,
a clean URL, and login requirements declared as metadata rather than as a function
call. This plugin uses all three available groups.

### Defining a route

A class in the right namespace, with a `#[route]` attribute on a method. A
class-level attribute supplies a shared prefix and shared parameters:

```php
#[route(
    path: '/{cm}',
    pathtypes: [new path_coursemodule(name: 'cm')],
)]
class items {

    #[route(
        path: '/items',
        method: ['GET'],
        responses: [new state_response()],
        requirelogin: new require_login(
            requirelogin: false,
            requirecourselogin: true,
            courseattributename: 'course',
        ),
    )]
    #[scopeset(new read())]
    public function get_items(...): payload_response { }
}
```

Two things are prefixed for you and you must not write them: the group prefix, and
your component name. So the report controller declares `path: '/report/{cm}'` and
the real URL is `/mod_waypoint/report/{cm}`.

### path_coursemodule does four jobs for free

Listing `new path_coursemodule(name: 'cm')` in `pathtypes` gets you:

1. acceptance of either a numeric cmid **or** `idnumber:some-idnumber`
2. the lookup itself
3. the course module record and its module context attached to the request
4. a clean JSON 404 if it cannot resolve, before your code runs

We wrote no lookup code and no 404 handler, and got idnumber support we did not
ask for. Sibling classes exist for courses, users, sections, themes and components.

Your method receives them by name — parameter `cm` produces attributes `cmdata`
and `cmcontext`:

```php
public function get_items(
    ServerRequestInterface $request,
    ResponseInterface $response,
    stdClass $cmdata,
): payload_response
```

### Linking to a routed page

A routed page nobody can reach is not much use. The report is added to the
activity's secondary navigation from `lib.php`, the same way `mod_choice` adds
its "Responses" link:

```php
function waypoint_extend_settings_navigation(
    settings_navigation $settings,
    navigation_node $waypointnode,
): void {
    $cm = $settings->get_page()->cm;

    if (!$cm || !has_capability('mod/waypoint:viewreport', $cm->context)) {
        return;
    }

    $waypointnode->add(
        get_string('report', 'mod_waypoint'),
        \core\router\util::get_path_for_callable(
            [\mod_waypoint\route\controller\report::class, 'view_report'],
            ['cm' => $cm->id],
        ),
        navigation_node::TYPE_SETTING,
        null,
        'waypointreport',
    );
}
```

Note what is *not* there: a hardcoded URL. `util::get_path_for_callable()` asks
the router to build the path from the route's own name, so the `#[route]`
attribute on the controller stays the single place that path is defined. Rename
the route and every link follows. `core_choice` cannot do this — it writes
`new moodle_url('/mod/choice/report.php', …)` — because its page is a real file.

It appears under **More** in the activity's secondary navigation, for anybody
with `mod/waypoint:viewreport`. `tests/lib_test.php` asserts that a teacher gets
it, that it points at the route, and that a student does not.

`tests/behat/behat_mod_waypoint.php` resolves the same page the same way, so
`I am on the "Checklist" "mod_waypoint > Report" page` works whether or not the
site under test has `$CFG->routerconfigured` set. Hardcoding the URL there would
pass locally and fail in CI, or the reverse.

### The shim

```php
#[route(path: '/report.php')]
public function report_shim(...): ResponseInterface {
    return self::redirect_to_callable(
        $request, $response,
        [\mod_waypoint\route\controller\report::class, 'view_report'],
        ['cm' => $this->get_param($request, 'id')],
    );
}
```

The shim loader prefixes the plugin's own directory, so `/report.php` becomes
`/mod/waypoint/report.php`. Only `core` gets an empty prefix, because core may put
routes anywhere in the tree. See trap 4 for when this actually works.

---

## 4. The REST API and scopes

### The endpoints

| Verb | Path | Scopes |
|---|---|---|
| `GET` | `/{cm}/items` | `read` |
| `POST` | `/{cm}/items` | `read` + `manage` |
| `PATCH` | `/{cm}/items/{itemid}` | `read` + `manage` |
| `DELETE` | `/{cm}/items/{itemid}` | `read` + `manage` |
| `PUT` | `/{cm}/progress/{itemid}` | `read` + `write` |

`PUT` for the tick, not `POST`, because the operation is idempotent — sending
`done: true` twice leaves the same state behind.

### Every endpoint returns the whole state

This is the central design decision and the thing most worth copying. All five
endpoints — including the ones that change something — return the complete state
of the activity:

```json
{
  "items": [{"id": 1, "title": "Read the brief", "sortorder": 1, "done": true}],
  "progress": {"completed": 1, "total": 3, "percentage": 33, "countlabel": "1 of 3"},
  "canmanage": false,
  "cantick": true
}
```

Not the thing that changed. Everything, every time. Three consequences:

- the client never merges a partial update — it replaces its state and re-renders,
  which removes an entire category of bug
- the server stays the single source of truth for progress and permissions, so the
  client cannot drift
- there is exactly one response class, and therefore exactly one response
  component in the OpenAPI document

### Describing requests and responses

```php
requestbody: new request_body(
    content: new payload_response_type(
        schema: new schema_object(
            content: ['title' => new scalar_type(param::TEXT, required: true)],
        ),
    ),
),
```

Responses are a reusable class, `classes/route/responses/state_response.php`, so
every route just writes `new state_response()`.

`referenced_object` is an empty marker interface. A schema class implementing it
is emitted **once** into `#/components/schemas/` and referenced from everywhere it
is used, instead of being inlined repeatedly. Both `waypoint_item` and
`waypoint_progress` implement it.

### OpenAPI generates itself

Core registers `/api/rest/v2/openapi.json` automatically. Your endpoints appear
with no extra work: three paths, two schema components, zero dangling references.

### Scopes

New in 5.3, and the part people skip.

A scope is a named permission an OAuth2 access token or personal access token can
be granted. **It does not replace capabilities — it sits alongside them.** The key
sentence: scope checking only applies to token-authenticated requests. With a
normal session cookie, scopes have no effect and your capability checks are what
matter.

They answer different questions:

- **Capabilities:** is this *user* allowed to do this?
- **Scopes:** is this *token* allowed to act on the user's behalf for this kind of
  thing?

A user might be a teacher with full rights who hands a third-party app a token
that can only read.

Defining them — an abstract base naming the resource, then one class per scope:

```php
#[\core\router\scope\identifier_attribute('waypoint')]
abstract class abstract_scope extends \core\router\scope\abstract_scope {}

#[\core\router\scope\identifier_attribute('read')]
#[\core\router\scope\summary_attribute('waypoint_read_scope_summary', 'mod_waypoint')]
#[\core\router\scope\description_attribute('waypoint_read_scope_desc', 'mod_waypoint')]
class read extends abstract_scope {}
```

The full identifier is assembled for you: `mod_waypoint:waypoint:read`. The summary
and description point at language strings so they can be shown on a consent screen.

Attach them with `#[scopeset]`. Several scopes in one scopeset means **AND**;
repeating the attribute means **OR**.

Note the scopes are deliberately *not* the same as the capabilities. Capabilities
are about who you are in this course; scopes are about what an application may do
on your behalf.

**Two rules.** Every route in the `api` namespace must carry `#[scopeset]` or
`#[unscoped_resource]` — a core PHPUnit test scans every component and fails the
build otherwise. And if you add, rename or remove a scope class, purge caches
before it is picked up.

One warning from the docs: removing or renaming a scope is a breaking change for
clients already granted it. Name them carefully the first time.

---

## 5. One click, end to end

A student ticks a box. Here is every piece doing its part.

**Getting there.** The course page links to `view.php?id=N`. That stays a real file
on disk — core links to it and a plugin cannot route that away. It calls
`require_login`, builds the `item_manager`, checks the view capability, and asks
for the full state payload: the same structure the REST API returns.

**First paint.** It passes that state into `mod_waypoint/view` as props. The
browser receives the mount div with the state inlined in `data-react-props`, and
the server-rendered fallback inside it. `react_autoinit` finds the div, imports the
module and mounts the root, replacing the fallback. Because the state arrived in
the props, **there is no request on load.**

**The click.** The design system `Checkbox` fires `onChange` → `ItemRow`'s
`onToggle` prop → the root component's handler → `repository.setDone()`.

**The request.** The repository calls core's `Fetch`:

```ts
Fetch.performPut('mod_waypoint', `${cmid}/progress/${itemid}`, {body: {done}});
```

`Fetch` prepends the API base and `rest/v2`. The API base is computed server-side
and handles both cases — `/api` where the rewrite is configured, `/r.php/api` where
it is not. Your code never knows the difference, so the plugin is portable across
both.

**Middleware, in order.** Routing matches the pattern → `path_coursemodule`
resolves the course module and attaches the record and context → the authentication
middleware reads `requirelogin` off the matched route and runs the course login
check → if a token was used, the scope middleware checks the scopeset.

**The method.** Builds the manager, requires the tick capability, reads `done` from
the parsed body, calls `set_item_state()`.

**The manager.** Looks up the item **scoped to this activity**:

```php
return $DB->get_record('waypoint_item', [
    'id' => $itemid,
    'waypointid' => $this->waypoint->id,   // <- this line is a security control
], '*', MUST_EXIST);
```

That scoping is not tidiness. The item id came straight off the URL, and without it
a valid id from a different activity would be accepted. There is a test for exactly
that case.

It writes or deletes the progress row, then asks the Completion API to re-evaluate.
`custom_completion` asks the *same manager* for the *same* progress summary, so the
completion rule and the API can never disagree about what "complete" means — one
code path, not two.

**The response.** The full state again. Not the item. Everything.

**Back in the browser.** The repository decodes, the root drops it into state, React
re-renders. The bar moves because its `countlabel` and `percentage` came down in
that payload. The badge appears. If that was the last box, the activity is already
complete — that happened server-side during the same request.

**On failure**, the catch block sets a flag, a warning with a retry button appears,
and the old state is left untouched. Retry re-fetches the whole state. That is clean
precisely because the payload is always complete.

---

## 6. Five traps

None of these is in the documentation. All of them cost real time.

### 1. Class-level `requirelogin` is silently ignored — this one is a security hole

Put `requirelogin` on a class-level `#[route]` and your endpoints serve data to
**anonymous callers**.

It is easy to hit because `path`, `pathtypes`, `queryparams` and `headerparams` *are*
inherited — there are explicit getters that walk up to the parent and merge. So you
assume everything is. `requirelogin` is not: both authentication middlewares read
the property directly off the matched route with no parent lookup. No warning, no
error, it simply does not apply.

Found by running an unauthenticated `curl` against the endpoint and getting a `200`
with the full item list. **Declare `requirelogin` on every method.** Core's question
bank API does exactly that.

### 2. `require_capability()` becomes a 500, not a 403

The router maps exceptions to status codes via `\core\exception\response_aware_exception`,
which lets an exception name its own response class. `required_capability_exception`
does not implement it, so a permission failure reaches your client as a 500.

Throw `\core\exception\access_denied_exception` instead. See
`item_manager::require_capability()` — one private helper so every check goes
through one place. It still renders a sensible error page when a route serves HTML,
so you do not need two code paths.

### 3. `array_of_things` describes a map, not a list

The obvious choice for an endpoint returning a JSON array. Do not use it. It emits
`{"type": "object", "additionalProperties": …}` — a string-keyed map. If your
endpoint returns a list, your schema is lying.

It has a second problem. It builds the child `$ref` by calling `get_reference()`
directly. But the method that *registers* a component is `get_openapi_schema()`,
which calls `get_reference()` as part of its work and adds the component as a side
effect. Skipping it leaves a `$ref` pointing at a component that was never defined
— a dangling reference, in the document you publish as your API contract.

This plugin ships `classes/route/schema/array_of.php`, ~40 lines, which emits a
real `{"type": "array", "items": …}` and resolves the child through
`get_openapi_schema()`. After that: zero dangling references across the whole
document, core's included.

### 4. Shims need `$CFG->routerconfigured = true`

The shim registers correctly either way — you can dump the router's route table and
see the pattern. But through the `/r.php/…` fallback it returns 404.

The URL is `/r.php/mod/waypoint/report.php`. Apache's PHP handler matches the
**trailing** `.php`, tries to execute a file that does not exist, and returns its own
404 before PHP runs. The router never sees it.

The proof: request a made-up path **not** ending in `.php` and you get Moodle's styled
404 (router handled it); one that does end in `.php` gives Apache's plain-text 404.
Two different servers answering. Core's own `/lib/exampleshimroute.php` behaves
identically.

Which makes sense — the whole purpose of a shim is to catch a legacy URL, and a
legacy URL only reaches the router if the server sends it there.

### 5. The design system `Switch` cannot be clicked by Behat

Its real `<input>` is visually hidden — 1x1px and clipped — with the visible
control in a sibling `<label for=...>`:

```css
.mds-switch-input { position: absolute; width: 1px; height: 1px; clip: rect(0, 0, 0, 0); }
.mds-switch       { display: flex; align-self: start; }   /* note: no position: relative */
```

Because the wrapper is not positioned, that absolutely positioned input resolves
against some distant ancestor and lands outside the control — in our case at
x=2329, well away from the switch the user sees.

Both obvious Behat steps fail with `ElementClickInterceptedException`, because
`behat_form_checkbox::set_value()` calls `click()` on the input:

```gherkin
And I click on "Hide completed" "checkbox"      # fails
And I set the field "Hide completed" to "1"     # fails too
```

Click the label text, which is what a person clicks:

```gherkin
And I click on "Hide completed" "text"
```

Core's Edit mode switch is the same component and does work with
`I set the field`, which is why this is easy to get wrong — but that works
because of the layout around it, not by design.

### Bonus: the `getString` mock in Jest

If you mock `getString`, it must return the **same promise object** for the same
string. Components read it with `use()`, which re-invokes `getString` on every
render; a fresh promise each time suspends forever and the test hangs with no useful
error. Cache the promises in your mock, keyed by component and identifier.

---

## What to take away

- Return the **complete state** from every endpoint, including the mutating ones.
- Keep **one module** as the only thing that touches the network.
- Build **translated strings server-side**. Do not assemble sentences in JavaScript.
- Put business logic in **one place** both the API and the completion rules call, so
  they cannot disagree.
- Always **scope a lookup** by a URL id to the thing that id should belong to.
- Declare `requirelogin` on **every route method**, never on the class.
- Throw `access_denied_exception`, not `require_capability`, in anything serving JSON.
- Describe your schemas properly — the OpenAPI document is only as honest as they are.
