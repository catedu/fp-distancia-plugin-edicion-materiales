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

use dml_exception;
use external_api;
use external_function_parameters;
use external_value;
use invalid_parameter_exception;
use local_educaaragon\manage_editable_resource;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/webservice/lib.php');

/**
  *
 * @package    local_educaaragon
 * @category   external
 */
class deleteversion_external extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function deleteversion_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'resourceid' => new external_value(PARAM_INT, 'Resource id'),
                'versionname' => new external_value(PARAM_RAW, 'Name of new version')
            ]
        );
    }

    /**
     * @param int $courseid
     * @param int $resourceid
     * @param string $versionname
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function deleteversion(int $courseid, int $resourceid, string $versionname): array {
        global $DB;
        self::validate_parameters(
            self::deleteversion_parameters(),
            [
                'courseid' => $courseid,
                'resourceid' => $resourceid,
                'versionname' => $versionname
            ]
        );
        $cmrecord = $DB->get_record('course_modules', ['course' => $courseid, 'instance' => $resourceid], 'id');
        $cminfo = get_fast_modinfo($courseid)->get_cm($cmrecord->id);
        $manageeditable = new manage_editable_resource($cminfo);
        return [
            'response' => $manageeditable->delete_version($versionname),
        ];
    }

    /**
     * @return external_function_parameters
     */
    public static function deleteversion_returns(): external_function_parameters {
        return new external_function_parameters(
            [
                'response' => new external_value(PARAM_BOOL, 'response', VALUE_REQUIRED)
            ]
        );
    }
}
