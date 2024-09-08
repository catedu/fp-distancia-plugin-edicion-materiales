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

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/lib/datalib.php');

function xmldb_local_educaaragon_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2022060700) {
        $table = new xmldb_table('local_educa_processedcourses');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('processed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('message', XMLDB_TYPE_TEXT);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $table->add_index('processedcourses', XMLDB_INDEX_UNIQUE, ['courseid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_educa_resource_links');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('resourceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('version', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null);
        $table->add_field('action', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table->add_field('message', XMLDB_TYPE_TEXT);
        $table->add_field('other', XMLDB_TYPE_TEXT);
        $table->add_field('link', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('file', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $table->add_index('educa_resource_links', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'resourceid', 'action', 'version']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_educa_editables');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('resourceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table->add_field('relatedcmid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('version', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $table->add_index('educa_editables', XMLDB_INDEX_UNIQUE, ['courseid', 'resourceid', 'type']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_educa_edited');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('resourceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('action', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table->add_field('other', XMLDB_TYPE_TEXT);
        $table->add_field('version', XMLDB_TYPE_CHAR, '255', null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $table->add_index('educa_edited', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'resourceid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2022060700, 'local', 'educaaragon');
    }

    return true;
}
