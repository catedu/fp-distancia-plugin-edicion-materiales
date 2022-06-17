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

use local_educaaragon\output\processedcourses_page;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

require_login();

$context = context_system::instance();
require_capability('local/educaaragon:manageall', $context);

$PAGE->set_url('/local/educaaragon/processedcourses.php');
$PAGE->set_pagelayout('frontpage');
$PAGE->set_context($context);

$output = $PAGE->get_renderer('local_educaaragon');

$PAGE->set_title(get_string('processedcourses', 'local_educaaragon'));
$PAGE->set_heading(get_string('processedcourses', 'local_educaaragon'));

$processedcoursespage = new processedcourses_page();

echo $output->header();
echo $output->render($processedcoursespage);
echo $output->footer();
