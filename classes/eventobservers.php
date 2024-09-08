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

namespace local_educaaragon;

use coding_exception;
use core\event\course_deleted;
use core\event\course_module_deleted;
use dml_exception;
use moodle_exception;
use repository_exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/educaaragon/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/modinfolib.php');

class eventobservers {

    /**
     * @param course_module_deleted $event
     * @return void
     * @throws moodle_exception
     * @throws repository_exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function course_module_deleted(course_module_deleted $event): void {
        global $DB;
        $cmid = $event->contextinstanceid;
        if ($event->other['modulename'] === 'resource') {
            $editable =  $DB->get_record('local_educa_editables', ['courseid' => $event->courseid, 'resourceid' => $event->other['instanceid']]);
            if ($editable !== false) {
                $course = get_course($event->courseid);
                $repository = get_repository();
                if ($editable->type === 'editable') {
                    delete_folder($repository->get_rootpath() . 'editions/' . $course->shortname . '/' . $editable->resourceid);
                    $resourceprintable = $DB->get_record('local_educa_editables', ['type' => 'printable', 'relatedcmid' => $cmid]);
                    $cmprintable = $DB->get_record('course_modules', ['instance' => $resourceprintable->resourceid, 'course' => $event->courseid], 'id');
                    if ($cmprintable !== false) {
                        course_delete_module($cmprintable->id, true);
                    }
                    $DB->delete_records('local_educa_editables', ['courseid' => $event->courseid, 'id' => $editable->id]);
                    $DB->delete_records('local_educa_editables', ['courseid' => $event->courseid, 'id' => $resourceprintable->id]);
                    $DB->delete_records('local_educa_edited', ['courseid' => $event->courseid, 'resourceid' => $editable->resourceid]);
                    $DB->delete_records('local_educa_resource_links', ['courseid' => $event->courseid, 'resourceid' => $editable->resourceid]);
                }
                if ($editable->type === 'printable') {
                    $cmrelated = $DB->get_record('course_modules', ['id' => $editable->relatedcmid, 'course' => $event->courseid]);
                    if ($cmrelated !== false) {
                        delete_folder($repository->get_rootpath() . 'editions/' . $course->shortname . '/' . $cmrelated->instance);
                        course_delete_module($cmrelated->id, true);
                        $DB->delete_records('local_educa_editables', ['courseid' => $event->courseid, 'resourceid' => $cmrelated->instance]);
                        $DB->delete_records('local_educa_edited', ['courseid' => $event->courseid, 'resourceid' => $cmrelated->instance]);
                        $DB->delete_records('local_educa_resource_links', ['courseid' => $event->courseid, 'resourceid' => $cmrelated->instance]);
                    }
                    $DB->delete_records('local_educa_editables', ['courseid' => $event->courseid, 'id' => $editable->id]);
                }
                $manage_logs = new manage_logs();
                $manage_logs->create_processed_course($event->courseid);
                $manage_logs->update_proccesed_course(true, 'resource_deleted');
                rebuild_course_cache($event->courseid);
            }
        }
    }

    /**
     * @param course_deleted $event
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws repository_exception
     */
    public static function course_deleted(course_deleted $event): void {
        global $DB;
        $repository = get_repository();
        delete_folder($repository->get_rootpath() . 'editions/' . $event->other['shortname']);
        $DB->delete_records('local_educa_editables', ['courseid' => $event->courseid]);
        $DB->delete_records('local_educa_edited', ['courseid' => $event->courseid]);
        $DB->delete_records('local_educa_processedcourses', ['courseid' => $event->courseid]);
        $DB->delete_records('local_educa_resource_links', ['courseid' => $event->courseid]);
    }
}
