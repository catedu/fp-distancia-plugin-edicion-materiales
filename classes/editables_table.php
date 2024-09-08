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
use dml_exception;
use html_writer;
use lang_string;
use moodle_exception;
use moodle_url;
use stdClass;
use table_sql;
use Traversable;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->dirroot . '/local/educaaragon/lib.php');

class editables_table extends table_sql {

    /**
     * @param $uniqueid
     * @throws coding_exception
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $this->define_columns(['courseid', 'resourceid', 'resourcename', 'relatedcmid', 'version', 'timemodified', 'actions']);
        $this->define_headers([
            get_string('courseid', 'local_educaaragon'),
            get_string('resourceid', 'local_educaaragon'),
            get_string('resourcename', 'local_educaaragon'),
            get_string('relatedcmid', 'local_educaaragon'),
            get_string('version', 'local_educaaragon'),
            get_string('timemodified', 'local_educaaragon'),
            get_string('actions', 'local_educaaragon')
        ]);
        // TODO add help string for colums (! button on the side).
        $this->sortable(true);
        $this->no_sorting('actions');
        $this->no_sorting('resourcename');
        $this->column_style('courseid', 'text-align', 'left');
        $this->column_style('resourceid', 'text-align', 'center');
        $this->column_style('resourcename', 'text-align', 'center');
        $this->column_style('relatedcmid', 'text-align', 'center');
        $this->column_style('version', 'text-align', 'center');
        $this->column_style('timemodified', 'text-align', 'center');
        $this->column_style('actions', 'text-align', 'center');
    }

    /**
     * @param stdClass $row
     * @return string
     * @throws dml_exception
     */
    public function col_resourcename(stdClass $row): string {
        global $DB;
        $resource = $DB->get_record('resource', ['id' => $row->resourceid], 'name');
        if ($resource !== false) {
            return $resource->name;
        }
        return '';
    }

    /**
     * @param stdClass $row
     * @return string
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function col_relatedcmid(stdClass $row): string {
        global $DB;
        if ($row->relatedcmid !== null) {
            $cm = $DB->get_record('course_modules', ['id' => $row->relatedcmid, 'course' => $row->courseid]);
            if ($cm !== false) {
                $moduletype = $DB->get_record('modules', ['id' => $cm->module], 'name');
                $cmtype = $DB->get_record($moduletype->name, ['id' => $cm->instance], 'name');
                return html_writer::link(new moodle_url('/mod/' . $moduletype->name . '/view.php', ['id' => $cm->id]), $cmtype->name, ['target' => '_blank']);
            }
        }
        return '';
    }

    /**
     * @param stdClass $row
     * @return string
     * @throws coding_exception
     */
    public function col_timemodified(stdClass $row): string {
        return userdate($row->timemodified, get_string('strftimedatetimeshort'));
    }

    /**
     * @param stdClass $row
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function col_actions(stdClass $row): string {
        global $DB;
        $cmid = $DB->get_record('course_modules', ['instance' => $row->resourceid], 'id');
        $reviewresource = html_writer::tag('i', '', [
            'class' => 'icon fa fa-eye', 'title' => get_string('revieweditableresource', 'local_educaaragon')
        ]);
        $cmid !== false ? $contentreviewresource = html_writer::link(new moodle_url('/mod/resource/view.php', ['id' => $cmid->id, 'version' => $row->version]), $reviewresource, ['target' => '_blank']) : $contentreviewresource = '';

        $contenteditresource = '';
        if ($cmid !== false) {
            $editresource = html_writer::tag('i', '', [
                'class' => 'icon fa fa-edit', 'title' => get_string('editresource', 'local_educaaragon')
            ]);
            $contenteditresource = html_writer::link(new moodle_url('/local/educaaragon/editresource.php', ['resourceid' => $row->resourceid]), $editresource, ['target' => '_blank']);
        }

        $contentprintableresource = '';
        $printableinstance = $DB->get_record('local_educa_editables', ['type' => 'printable', 'relatedcmid' => $cmid->id], 'resourceid');
        if ($printableinstance !== false) {
            $printablecmid = $DB->get_record('course_modules', ['instance' => $printableinstance->resourceid], 'id');
            if ($printablecmid !== false) {
                $printableresource = html_writer::tag('i', '', [
                    'class' => 'icon fa fa-print', 'title' => get_string('viewprintresource', 'local_educaaragon')
                ]);
                $contentprintableresource = html_writer::link(new moodle_url('/mod/resource/view.php', ['id' => $printablecmid->id, 'version' => $row->version]), $printableresource, ['target' => '_blank']);
            }
        }

        $registereditions = html_writer::tag('i', '', [
            'class' => 'icon fa fa-list', 'title' => get_string('registereditions', 'local_educaaragon')
        ]);
        $registereditions = html_writer::link(new moodle_url('/local/educaaragon/registereditions.php', ['resourceid' => $row->resourceid]), $registereditions, ['target' => '_blank']);

        $resourcellinks = html_writer::tag('i', '', [
            'class' => 'icon fa fa-link', 'title' => get_string('link_report', 'local_educaaragon')
        ]);
        $resourcellinks = html_writer::link(new moodle_url('/local/educaaragon/resourcelinks.php', ['resourceid' => $row->resourceid, 'version' => $row->version]), $resourcellinks, ['target' => '_blank']);

        return html_writer::div($contentreviewresource . $contenteditresource . $contentprintableresource . $registereditions . $resourcellinks, 'd-inline-flex');
    }

    /**
     * @return void
     * @throws dml_exception
     */
    public function build_table(): void {
        global $DB;
        if ($this->rawdata instanceof Traversable && !$this->rawdata->valid()) {
            return;
        }
        if (!$this->rawdata) {
            return;
        }
        foreach ($this->rawdata as $key => $row) {
            if ($DB->get_record('course', ['id' => $row->courseid]) === false) {
                unset($this->rawdata[$key]);
                continue;
            }
            $formattedrow = $this->format_row($row);
            $this->add_data_keyed($formattedrow, $this->get_row_class($row));
        }
    }
}
