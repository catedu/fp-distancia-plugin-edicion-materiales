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

use local_educaaragon\external\applyversion_external;
use local_educaaragon\external\createversion_external;
use local_educaaragon\external\deleteversion_external;
use local_educaaragon\external\process_resource_links;
use local_educaaragon\external\processedtable_external;
use local_educaaragon\external\reprocessing_external;
use local_educaaragon\external\savechanges_external;

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_educaaragon_processed_table' => [
        'classname'       => processedtable_external::class,
        'methodname'      => 'processed_table',
        'description'     => 'Generate table of courses processed',
        'type'            => 'read',
        'ajax'            => true,
        'loginrequired'   => true
    ],
    'local_educaaragon_reprocessing' => [
        'classname'       => reprocessing_external::class,
        'methodname'      => 'reprocessing_course',
        'description'     => 'Set a course for re-prosecution',
        'type'            => 'write',
        'ajax'            => true,
        'loginrequired'   => true
    ],
    'local_educaaragon_createversion' => [
        'classname'       => createversion_external::class,
        'methodname'      => 'createversion',
        'description'     => 'Create new version for edit',
        'type'            => 'write',
        'ajax'            => true,
        'loginrequired'   => true
    ],
    'local_educaaragon_deleteversion' => [
        'classname'       => deleteversion_external::class,
        'methodname'      => 'deleteversion',
        'description'     => 'Delete a version',
        'type'            => 'write',
        'ajax'            => true,
        'loginrequired'   => true
    ],
    'local_educaaragon_savechanges' => [
        'classname'       => savechanges_external::class,
        'methodname'      => 'savechanges',
        'description'     => 'Save changes for a file and version',
        'type'            => 'write',
        'ajax'            => true,
        'loginrequired'   => true
    ],
    'local_educaaragon_applyversion' => [
        'classname'       => applyversion_external::class,
        'methodname'      => 'applyversion',
        'description'     => 'Apply an edited version on a resource',
        'type'            => 'write',
        'ajax'            => true,
        'loginrequired'   => true
    ],
    'local_educaaragon_processresourcelinks' => [
        'classname'       => process_resource_links::class,
        'methodname'      => 'process_resource_links',
        'description'     => 'Processes broken links from generated resources',
        'type'            => 'write',
        'ajax'            => true,
        'loginrequired'   => true
    ]
];

$services = [
    'local_educaaragon' => [
        'functions' => [
            'local_educaaragon_processed_table',
            'local_educaaragon_reprocessing',
            'local_educaaragon_createversion',
            'local_educaaragon_deleteversion',
            'local_educaaragon_savechanges',
            'local_educaaragon_applyversion',
            'local_educaaragon_processresourcelinks'
        ],
        'restrictedusers' => 0,
        'enabled'         => 1
    ]
];
