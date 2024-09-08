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

namespace local_educaaragon\output;

use coding_exception;
use context_system;
use core\invalid_persistent_exception;
use dml_exception;
use DOMException;
use local_educaaragon\manage_editable_resource;
use moodle_exception;
use moodle_url;
use renderable;
use renderer_base;
use repository_exception;
use templatable;

defined('MOODLE_INTERNAL') || die();

class editresourcetoc_page extends editresource_page {

    /**
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function page_access() {
        global $PAGE;
        $context = context_system::instance();
        $PAGE->set_context($context);
        $PAGE->set_url('/local/educaaragon/editresourcetoc.php');
        $PAGE->set_title(get_string('edittoctitle', 'local_educaaragon'));
        $PAGE->set_heading(get_string('edittoctitle', 'local_educaaragon'));
        $PAGE->set_pagelayout('standard');
    }

    /**
     * @param renderer_base $output
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_persistent_exception
     * @throws moodle_exception
     * @throws repository_exception
     */
    public function export_for_template(renderer_base $output): array {
        if ($this->iseditable === false) {
            return [
                'error' => get_string('resourcenoteditable', 'local_educaaragon')
            ];
        }
        $version = optional_param('version', '', PARAM_RAW);
        $cm = $this->get_cm();
        $printablecm = $this->get_printablecm($cm);
        @$managecm = new manage_editable_resource($cm, $version);
        if ($version === '' || $version === 'original') {
            return ['error' => get_string('versionnoteditable', 'local_educaaragon', (new moodle_url('/local/educaaragon/editresource.php', ['resourceid' => $cm->instance]))->out(false))];
        }
        $tochtml = @$managecm->get_toc_for_edit();
        return [
            'resourcename' => $cm->name,
            'resourceid' => $cm->instance,
            'coursename' => $cm->get_course()->fullname,
            'courseid' => $cm->course,
            'hasversions' => false,
            'versions' => [],
            'tochtml' => $tochtml,
            'versionloaded' => true,
            'versionname' => $version,
            'vieweditcontent' => (new moodle_url('/local/educaaragon/editresource.php', ['resourceid' => $cm->instance, 'version' => $version]))->out(false),
            'viewesource' => (new moodle_url('/mod/resource/view.php', ['id' => $cm->id, 'version' => $version]))->out(false),
            'viewprintresource' => (new moodle_url('/mod/resource/view.php', ['id' => $printablecm->id, 'version' => $version]))->out(false),
            'viewcourse' => (new moodle_url('/course/view.php', ['id' => $cm->course]))->out(),
            'backversions' => (new moodle_url('/local/educaaragon/editresource.php', ['resourceid' => $cm->instance]))->out(),
            'viewversionlinks' => (new moodle_url('/local/educaaragon/resourcelinks.php', ['resourceid' => $cm->instance, 'version' => $version]))->out(false)
        ];
    }
}
