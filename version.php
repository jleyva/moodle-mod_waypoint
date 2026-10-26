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

/**
 * Version details for mod_waypoint.
 *
 * @package    mod_waypoint
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_waypoint';
$plugin->version   = 2026102300;
$plugin->requires  = 2026100500; // Moodle 5.3.0. Everything this plugin uses
                                 // (router scopes, path_coursemodule, the ESM/React
                                 // pipeline, design system 5.3) shipped in 5.3.
$plugin->supported = [503, 600]; // Built on 6.0dev; every API it uses shipped in 5.3.
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1.0';
