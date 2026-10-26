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

namespace mod_waypoint\route\controller;

use core\router\parameters\path_coursemodule;
use core\router\require_login;
use core\router\route;
use core\router\util;
use mod_waypoint\local\item_manager;
use Psr\Http\Message\ResponseInterface;
use stdClass;

/**
 * The teacher-facing progress report.
 *
 * This is an ordinary HTML page served by the router rather than by a .php
 * file on disk — the ROUTE_GROUP_PAGE group, not the REST one. The component
 * name is prefixed automatically, so the path below resolves to
 * /mod_waypoint/report/{cm}.
 *
 * It is deliberately server-rendered. Not every page needs React, and a table
 * that is read once and never mutated is a poor excuse to ship a bundle.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report {
    use \core\router\route_controller;

    /**
     * Show how far every enrolled student has got.
     *
     * @param ResponseInterface $response
     * @param stdClass $cmdata The course module record, attached by path_coursemodule.
     * @return ResponseInterface
     */
    #[route(
        path: '/report/{cm}',
        pathtypes: [
            new path_coursemodule(name: 'cm'),
        ],
        requirelogin: new require_login(
            requirelogin: false,
            requirecourselogin: true,
            courseattributename: 'course',
        ),
    )]
    public function view_report(
        ResponseInterface $response,
        stdClass $cmdata,
    ): ResponseInterface {
        global $OUTPUT, $PAGE;

        $manager = item_manager::instance_from_cmid((int) $cmdata->id);
        require_capability('mod/waypoint:viewreport', $manager->context);

        $title = get_string('reportfor', 'mod_waypoint', format_string($manager->waypoint->name));

        $PAGE->set_context($manager->context);
        $PAGE->set_cm($manager->cm, $manager->course);
        $PAGE->set_url(util::get_path_for_callable(
            [self::class, 'view_report'],
            ['cm' => $cmdata->id],
        ));
        $PAGE->set_title($title);
        $PAGE->set_heading(format_string($manager->course->fullname));
        $PAGE->navbar->add(get_string('report', 'mod_waypoint'));

        $response->getBody()->write($OUTPUT->header());
        $response->getBody()->write($OUTPUT->heading($title));
        $response->getBody()->write(
            $OUTPUT->render_from_template('mod_waypoint/report', $this->build_context($manager)),
        );
        $response->getBody()->write($OUTPUT->footer());

        return $response;
    }

    /**
     * Build the template context for the report table.
     *
     * @param item_manager $manager
     * @return array
     */
    private function build_context(item_manager $manager): array {
        $users = get_enrolled_users(
            context: $manager->context,
            withcapability: 'mod/waypoint:tick',
            userfields: 'u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, '
                . 'u.middlename, u.alternatename',
            orderby: 'u.lastname ASC, u.firstname ASC',
        );

        $summaries = $manager->get_progress_for_users(array_keys($users));

        $rows = [];
        foreach ($users as $user) {
            $progress = $summaries[$user->id];
            $rows[] = [
                'fullname' => fullname($user),
                'countlabel' => $progress->get_count_label(),
                'percentage' => $progress->get_percentage(),
                'complete' => $progress->is_complete(),
            ];
        }

        return [
            'hasrows' => !empty($rows),
            'rows' => $rows,
        ];
    }
}
