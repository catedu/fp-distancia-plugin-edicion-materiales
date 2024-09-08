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
use local_educaaragon\output\processedcourses_page;
use Exception;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/webservice/lib.php');

/**
 * Processed course table.
 *
 * @package    local_educaaragon
 * @category   external
 */
class processedtable_external extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function processed_table_parameters(): external_function_parameters {
        return new external_function_parameters(
            []
        );
    }

    /**
     * @return array
     * @throws moodle_exception
     */
    public static function processed_table(): array {
        try {
            $enrollmentpage = new processedcourses_page();
            $table = $enrollmentpage->get_processedcourses_table();
            return ['processedcoursestable' => $table];
        } catch (Exception $exception) {
            throw new moodle_exception($exception->getMessage());
        }
    }

    /**
     * @return external_function_parameters
     */
    public static function processed_table_returns(): external_function_parameters {
        return new external_function_parameters(
            [
                'processedcoursestable' => new external_value(PARAM_RAW, 'table HTML', VALUE_REQUIRED)
            ]
        );
    }
}
