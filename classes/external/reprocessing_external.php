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

namespace local_educaaragon\external;

use coding_exception;
use external_api;
use external_function_parameters;
use external_value;
use invalid_parameter_exception;
use local_educaaragon\educa_processedcourses;
use local_educaaragon\manage_logs;
use local_educaaragon\output\processedcourses_page;
use Exception;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/webservice/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/modinfolib.php');
require_once($CFG->dirroot . '/local/educaaragon/lib.php');

/**
 * Processed course table.
 *
 * @package    local_educaaragon
 * @category   external
 */
class reprocessing_external extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function reprocessing_course_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'courseid of table'),
            ]
        );
    }

    /**
     * @param int $courseid
     * @return array
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function reprocessing_course(int $courseid): array {
        global $DB;
        $params = self::validate_parameters(
            self::reprocessing_course_parameters(),
            [
                'courseid' => $courseid
            ]
        );
        $resources = $DB->get_records('local_educa_editables', ['courseid' => $params['courseid']]);
        foreach ($resources as $resource) {
            $cmid = $DB->get_record('course_modules',  ['course' => $params['courseid'], 'instance' => $resource->resourceid], 'id');
            if ($cmid !== false) {
                course_delete_module($cmid->id, true);
            }
        }
        $course = get_course($courseid);
        $repository = get_repository();
        delete_folder($repository->get_rootpath() . 'editions/' . $course->shortname);
        $DB->delete_records('local_educa_editables', ['courseid' => $params['courseid']]);
        $DB->delete_records('local_educa_edited', ['courseid' => $params['courseid']]);
        $DB->delete_records('local_educa_resource_links', ['courseid' => $params['courseid']]);
        rebuild_course_cache($params['courseid']);
        $manage_logs = new manage_logs();
        $manage_logs->create_processed_course($params['courseid']);
        $manage_logs->update_proccesed_course(false, 'selected_for_reprocessing');
        return ['response' => true];
    }

    /**
     * @return external_function_parameters
     */
    public static function reprocessing_course_returns(): external_function_parameters {
        return new external_function_parameters(
            [
                'response' => new external_value(PARAM_BOOL, 'Response of reprocessing course', VALUE_REQUIRED)
            ]
        );
    }
}
