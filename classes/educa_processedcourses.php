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

namespace local_educaaragon;

use core\persistent;
use dml_exception;
use dml_missing_record_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class educa_processedcourses extends persistent {

    public const TABLE = 'local_educa_processedcourses';

    /**
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'courseid' => [
                'type' => PARAM_INT,
                'description' => 'The course id processed by the task',
            ],
            'processed' => [
                'type' => PARAM_BOOL,
                'description' => 'Indicates whether the course has already been processed, or whether it will be processed in the next execution of the task.',
            ],
            'message' => [
                'type' => PARAM_TEXT,
                'description' => 'Additional information on course processing',
                'null' => NULL_ALLOWED,
                'default' => null
            ]
        ];
    }

    /**
     * @param int $courseid
     * @return void
     * @throws dml_exception
     * @throws dml_missing_record_exception
     */
    public static function get_course_processed(int $courseid): stdClass {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['courseid' => $courseid]);
        if (!$record) {
            throw new dml_missing_record_exception(self::TABLE);
        }
        return $record;
    }

}
