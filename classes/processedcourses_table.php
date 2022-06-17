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

class processedcourses_table extends table_sql {

    /**
     * @param $uniqueid
     * @throws coding_exception
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $this->define_columns(['courseid', 'coursename', 'shortname', 'processed', 'message', 'timemodified', 'actions']);
        $this->define_headers([
            get_string('courseid', 'local_educaaragon'),
            get_string('coursename', 'local_educaaragon'),
            get_string('shortname', 'local_educaaragon'),
            get_string('processed', 'local_educaaragon'),
            get_string('message', 'local_educaaragon'),
            get_string('timemodified', 'local_educaaragon'),
            get_string('actions', 'local_educaaragon')
        ]);
        // TODO add help string for colums (! button on the side).
        $this->sortable(true, 'processed', SORT_DESC);
        $this->no_sorting('actions');
        $this->no_sorting('coursename');
        $this->no_sorting('shortname');
        $this->column_style('courseid', 'text-align', 'left');
        $this->column_style('coursename', 'text-align', 'center');
        $this->column_style('shortname', 'text-align', 'center');
        $this->column_style('processed', 'text-align', 'center');
        $this->column_style('message', 'text-align', 'center');
        $this->column_style('timemodified', 'text-align', 'center');
        $this->column_style('actions', 'text-align', 'center');
    }

    /**
     * @param stdClass $row
     * @return string
     * @throws dml_exception
     */
    public function col_shortname(stdClass $row): string {
        return get_course($row->courseid)->shortname;
    }

    /**
     * @param stdClass $row
     * @return string
     * @throws coding_exception
     */
    public function col_processed(stdClass $row): string {
        if ((int)$row->processed === 1) {
            return html_writer::tag(
                'div',
                get_string('yes'),
                ['class' => 'alert alert-success', 'role' => 'alert']
            );
        }
        if ($row->message === 'no_associated_folder') {
            return html_writer::tag(
                'div',
                get_string('nofolder', 'local_educaaragon'),
                ['class' => 'alert alert-dark', 'role' => 'alert']
            );
        }
        return html_writer::tag(
            'div',
            get_string('no'),
            ['class' => 'alert alert-danger', 'role' => 'alert']
        );
    }

    /**
     * @param stdClass $row
     * @return string
     * @throws dml_exception
     */
    public function col_coursename(stdClass $row): string {
        return get_course($row->courseid)->fullname;
    }

    /**
     * @param stdClass $row
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function col_message(stdClass $row): string {
        if ($row->message === 'correctly_processed' || $row->message === 'correctly_processed_needassociation') {
            return html_writer::tag(
                'div',
                get_string($row->message, 'local_educaaragon', ['course' => $this->col_shortname($row), 'repository' => get_repository()->get_name()]),
                ['class' => 'alert alert-primary', 'role' => 'alert']
            );
        }
        return html_writer::tag(
            'div',
            get_string($row->message, 'local_educaaragon', ['course' => $this->col_shortname($row), 'repository' => get_repository()->get_name()]),
            ['class' => 'alert alert-warning', 'role' => 'alert']
        );
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
     * @throws moodle_exception
     */
    public function col_actions(stdClass $row): string {
        $reprocessing = html_writer::tag('i', '', [
            'class' => 'icon fa fa-undo', 'title' => get_string('reprocessing', 'local_educaaragon')
        ]);
        (int)$row->processed === 1 ? $contentreprocessing = html_writer::div($reprocessing, '', ['data-action' => 'reprocessing_course', 'data-courseid' => $row->courseid]) : $contentreprocessing = '';
        $editableresources = html_writer::tag('i', '', [
            'class' => 'icon fa fa-list', 'title' => get_string('editableresources', 'local_educaaragon')
        ]);
        (int)$row->processed === 1 ? $linkresources = html_writer::link(new moodle_url('/local/educaaragon/editables.php', ['courseid' => $row->courseid]), $editableresources, ['target' => '_blank']) : $linkresources = '';
        $viewcourse = html_writer::tag('i', '', [
            'class' => 'icon fa fa-eye', 'title' => get_string('viewcourse', 'local_educaaragon')
        ]);
        $linkcourse = html_writer::link(new moodle_url('/course/view.php', ['id' => $row->courseid]), $viewcourse, ['target' => '_blank']);

        return html_writer::div($contentreprocessing . $linkresources . $linkcourse, 'd-inline-flex');
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
