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

namespace mod_waypoint;

use advanced_testcase;
use context_system;

/**
 * Tests that the Mustache templates render without leaking their own documentation.
 *
 * A Mustache comment ends at the FIRST closing double brace it meets. Writing a
 * tag, or any JSON containing two adjacent closing braces, inside a template
 * docblock therefore terminates the comment early and dumps the remainder of
 * the docblock onto the page where users can read it. This has happened twice
 * in this plugin, so it is now asserted rather than eyeballed.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class templates_test extends advanced_testcase {

    /**
     * Templates to render, with a realistic context for each.
     *
     * @return array<string, array{0: string, 1: array}>
     */
    public static function template_provider(): array {
        return [
            'view' => [
                'mod_waypoint/view',
                [
                    'reactprops' => '{"activityId":543,"initialActivity":{},"initialItems":{}}',
                    'items' => [
                        ['id' => 1, 'title' => 'Read the brief', 'sortOrder' => 1, 'done' => true],
                        ['id' => 2, 'title' => 'Draft an outline', 'sortOrder' => 2, 'done' => false],
                    ],
                    'hasitems' => true,
                    'countlabel' => '1 of 2',
                    'percentage' => 50,
                ],
            ],
            'view, empty' => [
                'mod_waypoint/view',
                ['reactprops' => '{}', 'items' => [], 'hasitems' => false, 'countlabel' => '0 of 0', 'percentage' => 0],
            ],
            'report' => [
                'mod_waypoint/report',
                [
                    'hasrows' => true,
                    'rows' => [
                        ['fullname' => 'Ada Lovelace', 'countlabel' => '2 of 2', 'percentage' => 100, 'complete' => true],
                    ],
                ],
            ],
            'report, empty' => [
                'mod_waypoint/report',
                ['hasrows' => false, 'rows' => []],
            ],
        ];
    }

    /**
     * No line of a template's documentation may appear in its rendered output.
     *
     * @param string $templatename
     * @param array $context
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('template_provider')]
    public function test_docblocks_do_not_leak(string $templatename, array $context): void {
        global $CFG, $OUTPUT, $PAGE;

        $this->resetAfterTest();
        $PAGE->set_context(context_system::instance());
        $PAGE->set_url('/');

        $rendered = $OUTPUT->render_from_template($templatename, $context);

        foreach ($this->get_comment_lines($templatename) as $line) {
            $this->assertStringNotContainsString(
                $line,
                $rendered,
                "Documentation from {$templatename} reached the rendered output. A Mustache "
                    . "comment ends at the first '}}' — check the docblock for a tag or for JSON "
                    . "containing two adjacent closing braces.",
            );
        }
    }

    /**
     * Every substantial line living inside a {{! ... }} block in a template.
     *
     * @param string $templatename
     * @return string[]
     */
    private function get_comment_lines(string $templatename): array {
        global $CFG;

        $file = sprintf(
            '%s/mod/waypoint/templates/%s.mustache',
            $CFG->dirroot,
            substr($templatename, strlen('mod_waypoint/')),
        );
        $this->assertFileExists($file);

        $lines = [];
        $inside = false;

        foreach (explode("\n", file_get_contents($file)) as $line) {
            $trimmed = trim($line);

            if (!$inside && str_starts_with($trimmed, '{{!') && !str_ends_with($trimmed, '}}')) {
                $inside = true;
                continue;
            }
            if ($inside && $trimmed === '}}') {
                $inside = false;
                continue;
            }
            // Short lines are too likely to collide with real markup to assert on.
            if ($inside && strlen($trimmed) >= 20) {
                $lines[] = $trimmed;
            }
        }

        $this->assertNotEmpty($lines, "No documentation found in {$templatename}; the scanner is wrong.");

        return $lines;
    }
}
