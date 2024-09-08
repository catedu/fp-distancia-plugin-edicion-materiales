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
 * @package local_educaaragon
 * @author 3iPunt <https://www.tresipunt.com/>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 3iPunt <https://www.tresipunt.com/>
 */

use local_educaaragon\output\resourcelinks_page;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE, $DB;
require_once($CFG->libdir . '/adminlib.php');

require_login();

$resourceid = required_param('resourceid', PARAM_INT);
$version = optional_param('version', 'original', PARAM_RAW);
$showactives = optional_param('showactives', false, PARAM_BOOL);
$resource = $DB->get_record('resource', ['id' => $resourceid], 'course');

$context = context_course::instance($resource->course);
require_capability('local/educaaragon:editresources', $context);

$PAGE->set_url('/local/educaaragon/resourcelinks.php', ['resourceid' => $resourceid, 'version' => $version, 'showactives' => $showactives]);
$PAGE->set_pagelayout('frontpage');
$contextsystem = context_system::instance();
$PAGE->set_context($contextsystem);

$output = $PAGE->get_renderer('local_educaaragon');

$PAGE->set_title(get_string('editables', 'local_educaaragon'));
$PAGE->set_heading(get_string('editables', 'local_educaaragon'));

$editresource = new resourcelinks_page($resourceid, $version);

echo $output->header();
echo $output->render($editresource);
echo $output->footer();
