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

defined('MOODLE_INTERNAL') || die();

class educa_editables extends persistent {

    public const TABLE = 'local_educa_editables';

    /**
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'courseid' => [
                'type' => PARAM_INT,
                'description' => 'The course id processed by the task',
            ],
            'resourceid' => [
                'type' => PARAM_INT,
                'description' => 'Resource id affected.',
            ],
            'type' => [
                'type' => PARAM_RAW,
                'description' => 'editable or printable',
            ],
            'relatedcmid' => [
                'type' => PARAM_INT,
                'description' => 'Resource id related, only for printables.',
                'null' => NULL_ALLOWED,
                'default' => null
            ],
            'version' => [
                'type' => PARAM_RAW,
                'description' => 'Applied version'
            ]
        ];
    }

}
