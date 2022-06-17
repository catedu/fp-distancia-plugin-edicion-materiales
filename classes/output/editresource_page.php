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

use atto_texteditor;
use cm_info;
use coding_exception;
use context_system;
use core\invalid_persistent_exception;
use dml_exception;
use DOMException;
use html_writer;
use local_educaaragon\manage_editable_resource;
use moodle_exception;
use moodle_url;
use renderable;
use renderer_base;
use repository_exception;
use templatable;

defined('MOODLE_INTERNAL') || die();

class editresource_page implements renderable, templatable {

    public $resourceid;
    public $iseditable;
    public $courseid;

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct(int $resourceid) {
        $this->page_access();
        $this->resourceid = $resourceid;
        $this->iseditable = $this->check_editable();
    }

    /**
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function page_access() {
        global $PAGE;
        $context = context_system::instance();
        $PAGE->set_context($context);
        $PAGE->set_url('/local/educaaragon/edit.php');
        $PAGE->set_title(get_string('editables', 'local_educaaragon'));
        $PAGE->set_heading(get_string('editables', 'local_educaaragon'));
        $PAGE->set_pagelayout('standard');
    }

    /**
     * @param renderer_base $output
     * @return array
     * @throws DOMException
     * @throws invalid_persistent_exception
     * @throws repository_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
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
        if ($version === '') {
            $managecm = new manage_editable_resource($cm);
            $versions = $managecm->get_versions();
            return [
                'resourcename' => $cm->name,
                'resourceid' => $cm->instance,
                'viewesource' => (new moodle_url('/mod/resource/view.php', ['id' => $cm->id]))->out(),
                'viewprintresource' => (new moodle_url('/mod/resource/view.php', ['id' => $printablecm->id]))->out(),
                'viewcourse' => (new moodle_url('/course/view.php', ['id' => $cm->course]))->out(),
                'coursename' => $cm->get_course()->fullname,
                'courseid' => $cm->course,
                'hasversions' => count($versions) > 0,
                'versions' => $versions,
                'viewversionlinks' => (new moodle_url('/local/educaaragon/resourcelinks.php'))->out()
            ];
        }
        if ($version === 'original') {
            return ['error' => get_string('versionnoteditable', 'local_educaaragon')];
        }

        $managecm = new manage_editable_resource($cm, $version);
        $filehtml = optional_param('file', 'index.html', PARAM_RAW);
        $html = $managecm->get_html_for_edit($filehtml);
        $attohtml = $this->get_atto($html, $version);
        return [
            'resourcename' => $cm->name,
            'resourceid' => $cm->instance,
            'coursename' => $cm->get_course()->fullname,
            'courseid' => $cm->course,
            'hasversions' => false,
            'versions' => [],
            'versionloaded' => true,
            'versionname' => $version,
            'css' => $managecm->get_css(),
            'navigation' => $managecm->get_html_nav(),
            'html_for_edit' => $attohtml,
            'filename' => $filehtml,
            'viewesource' => (new moodle_url('/mod/resource/view.php', ['id' => $cm->id]))->out(),
            'viewprintresource' => (new moodle_url('/mod/resource/view.php', ['id' => $printablecm->id]))->out(),
            'viewcourse' => (new moodle_url('/course/view.php', ['id' => $cm->course]))->out(),
            'backversions' => (new moodle_url('/local/educaaragon/editresource.php', ['resourceid' => $cm->instance]))->out(),
            'viewversionlinks' => (new moodle_url('/local/educaaragon/resourcelinks.php', ['resourceid' => $cm->instance, 'version' => $version]))->out(false)
        ];
    }

    /**
     * @return bool
     * @throws dml_exception
     */
    private function check_editable(): bool {
        global $DB;
        $editable = $DB->get_record('local_educa_editables', ['resourceid' => $this->resourceid]);
        if ($editable === false) {
            return false;
        }
        if ($editable->type !== 'editable') {
            return false;
        }
        $this->courseid = $editable->courseid;
        $course = $DB->get_record('course', ['id' => $this->courseid]);
        return $course !== false;
    }

    /**
     * @return cm_info
     * @throws moodle_exception
     * @throws dml_exception
     */
    private function get_cm(): cm_info {
        global $DB;
        $recordcm = $DB->get_record('course_modules', ['instance' => $this->resourceid, 'course' => $this->courseid], 'id');
        return get_fast_modinfo($this->courseid)->get_cm($recordcm->id);
    }

    /**
     * @param cm_info $cm
     * @return cm_info
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function get_printablecm(cm_info $cm): cm_info {
        global $DB;
        $related = $DB->get_record('local_educa_editables', ['courseid' => $this->courseid, 'relatedcmid' => $cm->id], 'resourceid');
        $relatedcm = $DB->get_record('course_modules', ['instance' => $related->resourceid, 'course' => $this->courseid], 'id');
        return get_fast_modinfo($this->courseid)->get_cm($relatedcm->id);
    }

    /**
     * @param string $html
     * @param string $versions
     * @return string
     * @throws dml_exception
     */
    private function get_atto(string $html, string $versions): string {
        $atto = new atto_texteditor();
        $attohtml = html_writer::start_div('content_resourceeditoratto');
        $options['atto:toolbar'] = get_config('editor_atto', 'toolbar');
        $options['atto:toolbar'] = str_replace(['collapse = collapse
', 'fullscreen = fullscreen
'], '', $options['atto:toolbar']);
        $options['autosave'] = false;
        $options['enable_filemanagement'] = true;
        $attohtml .= html_writer::div(html_writer::tag('textarea', $html,
            ['id' => $this->resourceid . '_' . $versions, 'name' => $this->resourceid . '_' . $versions, 'rows' => 30]));
        $atto->use_editor($this->resourceid . '_' . $versions, $options);
        $attohtml .=  html_writer::end_div();
        return $attohtml;
    }
}
