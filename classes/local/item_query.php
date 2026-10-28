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

namespace mod_waypoint\local;

use core\exception\invalid_parameter_exception;

/**
 * The paging, sorting and filtering asked of the waypoint collection.
 *
 * Sections 10.2 to 10.4 of the REST style guide require every collection
 * endpoint to accept all three, and fix the spelling of each: `page` and
 * `pagesize` for paging, a comma-separated `sort` with a leading `-` for
 * descending, and one query parameter per filter.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_query {
    /** @var int The page size used when the caller does not ask for one. */
    public const DEFAULT_PAGE_SIZE = 25;

    /** @var int The largest page a caller may ask for. */
    public const MAX_PAGE_SIZE = 100;

    /** @var array<string, string> The sort keys this collection accepts, mapped to SQL. */
    private const SORTABLE = [
        'sortorder' => 'i.sortorder',
        'title' => 'i.title',
        'id' => 'i.id',
    ];

    /**
     * Describe one request for a page of waypoints.
     *
     * @param int $page The 1-based page number.
     * @param int $pagesize How many waypoints the page holds.
     * @param string $sort A style-guide sort expression, e.g. `-title,id`.
     * @param bool|null $done Keep only ticked, or only unticked, waypoints.
     */
    public function __construct(
        /** @var int The 1-based page number. */
        public readonly int $page = 1,
        /** @var int How many waypoints the page holds. */
        public readonly int $pagesize = self::DEFAULT_PAGE_SIZE,
        /** @var string A style-guide sort expression. */
        public readonly string $sort = 'sortorder',
        /** @var bool|null Keep only ticked, or only unticked, waypoints. */
        public readonly ?bool $done = null,
    ) {
    }

    /**
     * Build a query from validated route query parameters.
     *
     * The route's query_parameter declarations have already rejected anything
     * that is not an integer, and filled the defaults. What is left to do here
     * is clamp the page, clamp the page size, read the done filter and reject
     * an unknown sort key — none of which a parameter type can express.
     *
     * @param array $params The query parameters from the request.
     * @return self
     */
    public static function from_query_params(array $params): self {
        $page = max(1, (int) ($params['page'] ?? 1));
        $pagesize = (int) ($params['pagesize'] ?? self::DEFAULT_PAGE_SIZE);
        $pagesize = max(1, min(self::MAX_PAGE_SIZE, $pagesize));

        $query = new self(
            page: $page,
            pagesize: $pagesize,
            sort: (string) ($params['sort'] ?? 'sortorder'),
            done: self::read_done($params['done'] ?? null),
        );

        // Parsing now, rather than at the point of use, so a bad sort key is a
        // 400 on the way in instead of a 500 from the database later.
        $query->get_order_by();

        return $query;
    }

    /**
     * Read the done filter, which the route hands over unparsed.
     *
     * @param string|null $value The raw query parameter.
     * @return bool|null Null when the caller did not filter at all.
     * @throws invalid_parameter_exception If the value is neither true nor false.
     */
    private static function read_done(?string $value): ?bool {
        return match ($value) {
            null, '' => null,
            'true' => true,
            'false' => false,
            default => throw new invalid_parameter_exception(
                "The done filter takes 'true' or 'false', not '{$value}'.",
            ),
        };
    }

    /**
     * Turn the sort expression into an ORDER BY clause.
     *
     * @return string
     * @throws invalid_parameter_exception If the caller asked to sort by something that is not sortable.
     */
    public function get_order_by(): string {
        $clauses = [];

        foreach (explode(',', $this->sort) as $key) {
            $key = trim($key);
            if ($key === '') {
                continue;
            }

            $descending = str_starts_with($key, '-');
            $field = $descending ? substr($key, 1) : $key;

            if (!array_key_exists($field, self::SORTABLE)) {
                throw new invalid_parameter_exception(
                    "Waypoints cannot be sorted by '{$field}'. Sortable fields are: "
                        . implode(', ', array_keys(self::SORTABLE)) . '.',
                );
            }

            $clauses[] = self::SORTABLE[$field] . ($descending ? ' DESC' : ' ASC');
        }

        if (empty($clauses)) {
            $clauses[] = self::SORTABLE['sortorder'] . ' ASC';
        }

        // Always break ties on the id, so paging cannot show the same row twice.
        $clauses[] = 'i.id ASC';

        return implode(', ', $clauses);
    }

    /**
     * The row offset this page starts at.
     *
     * @return int
     */
    public function get_offset(): int {
        return ($this->page - 1) * $this->pagesize;
    }
}
