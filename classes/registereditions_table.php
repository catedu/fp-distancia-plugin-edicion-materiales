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
use moodle_exception;
use moodle_url;
use stdClass;
use table_sql;
use Traversable;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->dirroot . '/local/educaaragon/lib.php');

class registereditions_table extends table_sql {

    /**
     * @param $uniqueid
     * @throws coding_exception
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $this->define_columns(['courseid', 'resourceid', 'resourcename', 'action', 'other', 'version', 'usermodified', 'timemodified', 'actions']);
        $this->define_headers([
            get_string('courseid', 'local_educaaragon'),
            get_string('resourceid', 'local_educaaragon'),
            get_string('resourcename', 'local_educaaragon'),
            get_string('action', 'local_educaaragon'),
            get_string('other', 'local_educaaragon'),
            get_string('version', 'local_educaaragon'),
            get_string('usermodified', 'local_educaaragon'),
            get_string('timemodified', 'local_educaaragon'),
            get_string('actions', 'local_educaaragon')
        ]);
        // TODO add help string for colums (! button on the side).
        $this->sortable(true, 'timemodified', SORT_DESC);
        $this->no_sorting('actions');
        $this->no_sorting('resourcename');
        $this->no_sorting('other');
        $this->column_style('courseid', 'text-align', 'center');
        $this->column_style('resourceid', 'text-align', 'center');
        $this->column_style('resourcename', 'text-align', 'center');
        $this->column_style('action', 'text-align', 'center');
        $this->column_style('other', 'text-align', 'center');
        $this->column_style('version', 'text-align', 'center');
        $this->column_style('usermodified', 'text-align', 'center');
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
     * @throws coding_exception
     */
    public function col_action(stdClass $row): string {
        return get_string($row->action, 'local_educaaragon');
    }

    /**
     * @param stdClass $row
     * @return string
     * @throws coding_exception
     */
    public function col_other(stdClass $row): string {
        if ($row->other === null) {
            return '';
        }
        $datas = json_decode($row->other);

        $html = '';
        foreach ($datas as $key => $data) {
            switch ($key) {
                case 'edit_comments':
                    $html .= html_writer::tag(
                        'div',
                        '<b>' . get_string($key, 'local_educaaragon') . '</b><br>' . utf8_encode(base64_decode($data)),
                        ['class' => 'alert alert-primary', 'role' => 'alert']
                    );
                    break;
                case 'numfiles':
                    $html .= html_writer::tag(
                        'div',
                        '<b>' . get_string($key, 'local_educaaragon') . '</b> ' . $data,
                        ['class' => 'alert alert-primary', 'role' => 'alert']
                    );
                    break;
                case 'numlinksactive':
                    $html .= html_writer::tag(
                        'div',
                        '<b>' . get_string($key, 'local_educaaragon') . '</b> ' . $data,
                        ['class' => 'alert alert-success', 'role' => 'alert']
                    );
                    break;
                case 'numlinksfixed':
                    $html .= html_writer::tag(
                        'div',
                        '<b>' . get_string($key, 'local_educaaragon') . '</b> ' . $data,
                        ['class' => 'alert alert-dark', 'role' => 'alert']
                    );
                    break;
                case 'numlinksbroken':
                    $html .= html_writer::tag(
                        'div',
                        '<b>' . get_string($key, 'local_educaaragon') . '</b> ' . $data,
                        ['class' => 'alert alert-danger', 'role' => 'alert']
                    );
                    break;
                case 'numlinksnotvalid':
                    $html .= html_writer::tag(
                        'div',
                        '<b>' . get_string($key, 'local_educaaragon') . '</b> ' . $data,
                        ['class' => 'alert alert-warning', 'role' => 'alert']
                    );
                    break;
                default:
                    $html .= html_writer::tag(
                        'div',
                        '<b>' . get_string($key, 'local_educaaragon') . '</b> ' . $data,
                        ['class' => 'alert alert-info', 'role' => 'alert']
                    );
                    break;
            }
        }
        if ($row->action === 'process_resource_links') {
            $html = html_writer::div($html, 'd-flex flex-wrap flex-row justify-content-start');
        }
        return $html;
    }

    /**
     * @param stdClass $row
     * @return string
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function col_usermodified(stdClass $row): string {
        global $DB;
        $user = $DB->get_record('user', ['id' => $row->usermodified], 'id, firstname, lastname');
        if ($user !== false) {
            return html_writer::link(new moodle_url('/user/profile.php', ['id' => $user->id]), $user->firstname . ' ' . $user->lastname, ['target' => '_blank']);
        }
        return $row->usermodified;
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
        $cmid !== false ? $contentreviewresource = html_writer::link(new moodle_url('/mod/resource/view.php', ['id' => $cmid->id]), $reviewresource, ['target' => '_blank']) : $contentreviewresource = '';

        $contentprintableresource = '';
        if ($cmid !== false) {
            $printableinstance = $DB->get_record('local_educa_editables', ['type' => 'printable', 'relatedcmid' => $cmid->id], 'resourceid');
            if ($printableinstance !== false) {
                $printablecmid = $DB->get_record('course_modules', ['instance' => $printableinstance->resourceid], 'id');
                if ($printablecmid !== false) {
                    $printableresource = html_writer::tag('i', '', [
                        'class' => 'icon fa fa-print', 'title' => get_string('viewprintresource', 'local_educaaragon')
                    ]);
                    $contentprintableresource = html_writer::link(new moodle_url('/mod/resource/view.php', ['id' => $printablecmid->id]), $printableresource, ['target' => '_blank']);
                }
            }
        }

        return html_writer::div($contentreviewresource . $contentprintableresource, 'd-inline-flex');
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
