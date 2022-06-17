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
use core\invalid_persistent_exception;
use dml_exception;
use external_api;
use external_function_parameters;
use external_value;
use invalid_parameter_exception;
use local_educaaragon\manage_editable_resource;
use moodle_exception;
use repository_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/webservice/lib.php');
require_once($CFG->dirroot . '/local/educaaragon/lib.php');

/**
 *
 * @package    local_educaaragon
 * @category   external
 */
class process_resource_links extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function process_resource_links_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'resourceid' => new external_value(PARAM_INT, 'Resource id'),
                'versionname' => new external_value(PARAM_RAW, 'Name of version to process')
            ]
        );
    }

    /**
     * @param int $courseid
     * @param int $resourceid
     * @param string $versionname
     * @return bool[]
     * @throws invalid_persistent_exception
     * @throws repository_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function process_resource_links(int $courseid, int $resourceid, string $versionname): array {
        global $DB;
        self::validate_parameters(
            self::process_resource_links_parameters(),
            [
                'courseid' => $courseid,
                'resourceid' => $resourceid,
                'versionname' => $versionname
            ]
        );
        $cmrecord = $DB->get_record('course_modules', ['course' => $courseid, 'instance' => $resourceid], 'id');
        $cminfo = get_fast_modinfo($courseid)->get_cm($cmrecord->id);
        $manageeditable = new manage_editable_resource($cminfo, $versionname);
        $manageeditable->process_resource_links();
        return [
            'response' => true
        ];
    }

    /**
     * @return external_function_parameters
     */
    public static function process_resource_links_returns(): external_function_parameters {
        return new external_function_parameters(
            [
                'response' => new external_value(PARAM_BOOL, 'response', VALUE_REQUIRED),
            ]
        );
    }
}
