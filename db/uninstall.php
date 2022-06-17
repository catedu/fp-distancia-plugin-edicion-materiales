<?php
// This file is part of Moodle Workplace https://moodle.com/workplace based on Moodle
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
//
// Moodle Workplace Code is dual-licensed under the terms of both the
// single GNU General Public Licence version 3.0, dated 29 June 2007
// and the terms of the proprietary Moodle Workplace Licence strictly
// controlled by Moodle Pty Ltd and its certified premium partners.
// Wherever conflicting terms exist, the terms of the MWL are binding
// and shall prevail.

/**
 * @package local_educaaragon
 * @author 3iPunt <https://www.tresipunt.com/>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 3iPunt <https://www.tresipunt.com/>
 */

defined('MOODLE_INTERNAL') || die;
global $CFG;

require_once($CFG->dirroot . '/lib/datalib.php');
require_once($CFG->dirroot . '/local/educaaragon/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/modinfolib.php');

/**
 * @return void
 * @throws dml_exception
 * @throws moodle_exception
 */
function xmldb_local_educaaragon_uninstall() {
    global $DB;
    // DELETE ALL EDITABLE RESOURCES.
    /*$resources = $DB->get_records('local_educa_editables');
    foreach ($resources as $resource) {
        $cmid = $DB->get_record('course_modules',  ['course' => $resource->courseid, 'instance' => $resource->resourceid], 'id');
        if ($cmid !== false) {
            course_delete_module($cmid->id, true);
        }
    }*/
    $repository = get_repository();
    $courses = $DB->get_records('local_educa_processedcourses');
    foreach ($courses as $course) {
        delete_folder($repository->get_rootpath() . 'editions/' . $course->shortname);
        /*rebuild_course_cache($course->id);*/
    }
    $dbman = $DB->get_manager();
    $tables = [
        'local_educa_processedcourses',
        'local_educa_editables',
        'local_educa_edited',
        'local_educa_resource_links'
    ];
    foreach ($tables as $tablename) {
        if ($dbman->table_exists($tablename)) {
            $table = new xmldb_table($tablename);
            $dbman->drop_table($table);
        }
    }
}
