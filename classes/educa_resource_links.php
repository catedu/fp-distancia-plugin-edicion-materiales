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

class educa_resource_links extends persistent {

    public const TABLE = 'local_educa_resource_links';

    /**
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'courseid' => [
                'type' => PARAM_INT,
                'description' => 'The course id',
            ],
            'resourceid' => [
                'type' => PARAM_INT,
                'description' => 'Resource id affected.',
            ],
            'version' => [
                'type' => PARAM_RAW,
                'description' => 'version affected',
            ],
            'action' => [
                'type' => PARAM_RAW,
                'description' => 'case',
            ],
            'message' => [
                'type' => PARAM_TEXT,
                'description' => 'Additional information on resource action',
                'null' => NULL_ALLOWED,
                'default' => null
            ],
            'other' => [
                'type' => PARAM_TEXT,
                'description' => 'Additional information on resource action',
                'null' => NULL_ALLOWED,
                'default' => null
            ],
            'link' => [
                'type' => PARAM_TEXT,
                'description' => 'Link of reference'
            ],
            'file' => [
                'type' => PARAM_TEXT,
                'description' => 'File where the link was found'
            ]
        ];
    }
}
