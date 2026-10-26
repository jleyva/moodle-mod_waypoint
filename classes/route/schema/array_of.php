<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_waypoint\route\schema;

use core\exception\invalid_parameter_exception;
use core\router\schema\objects\type_base;
use core\router\schema\specification;
use stdClass;

/**
 * A JSON array whose entries all share one schema.
 *
 * Core's array_of_things describes `{"type": "object", "additionalProperties": …}`
 * — a map keyed by string, not a list — and it builds the child `$ref` by
 * calling get_reference() directly, which never registers the referenced
 * component. The result is a dangling $ref for a shape the API does not
 * actually return.
 *
 * This type emits a real `{"type": "array", "items": …}` and resolves the
 * child through get_openapi_schema(), which is what adds a referenced_object
 * to #/components/schemas as a side effect.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class array_of extends type_base {

    /**
     * Describe a list of a single type.
     *
     * @param type_base $itemtype The schema shared by every entry.
     * @param mixed ...$extra Future-proofing, per the core base class.
     */
    public function __construct(
        /** @var type_base The schema shared by every entry. */
        protected type_base $itemtype,
        ...$extra,
    ) {
        parent::__construct(...$extra);
    }

    #[\Override]
    public function get_openapi_description(
        specification $api,
        ?string $path = null,
    ): ?stdClass {
        $data = parent::get_openapi_description($api, $path);

        $data->type = 'array';

        // get_openapi_schema(), unlike get_reference(), registers the child in
        // #/components/schemas before handing back the $ref to it.
        $data->items = $this->itemtype->get_openapi_schema(
            api: $api,
            path: $path,
        );

        return $data;
    }

    #[\Override]
    public function validate_data(mixed $data) {
        if (!is_array($data) || !array_is_list($data)) {
            throw new invalid_parameter_exception('Expected a list.');
        }

        return array_map(
            fn($entry) => $this->itemtype->validate_data($entry),
            $data,
        );
    }
}
