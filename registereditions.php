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

use local_educaaragon\output\registereditions_page;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE, $DB;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

require_login();

$resourceid = optional_param('resourceid', 0, PARAM_INT);
if ($resourceid !== 0) {
    $resource = $DB->get_record('resource', ['id' => $resourceid], 'course');
    if ($resource !== false) {
        $context = context_course::instance($resource->course);
        require_capability('local/educaaragon:editresources', $context);
    }
} else {
    $context = context_system::instance();
    require_capability('local/educaaragon:manageall', $context);
}

$PAGE->set_url('/local/educaaragon/registereditions.php');
$PAGE->set_pagelayout('frontpage');
$contextsystem = context_system::instance();
$PAGE->set_context($contextsystem);

$output = $PAGE->get_renderer('local_educaaragon');

$PAGE->set_title(get_string('registereditions', 'local_educaaragon'));
$PAGE->set_heading(get_string('registereditions', 'local_educaaragon'));


$registereditionspage = new registereditions_page($resourceid);

echo $output->header();
echo $output->render($registereditionspage);
echo $output->footer();
