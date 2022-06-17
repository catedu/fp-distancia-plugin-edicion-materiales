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
use dml_exception;
use local_educaaragon\manage_logs;
use local_educaaragon\resourcelinks_table;
use moodle_exception;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

class resourcelinks_page implements renderable, templatable {

    public $resourceid;
    public $iseditable;
    public $courseid;
    public $version;
    public $showactives;

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct(int $resourceid, string $version) {
        $this->resourceid = $resourceid;
        $this->version = $version;
        $this->page_access();
        $this->iseditable = $this->check_editable();
        $this->showactives = optional_param('showactives', false, PARAM_BOOL);
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
        $PAGE->set_url('/local/educaaragon/resourcelinks.php', ['resourceid' => $this->resourceid, 'version' => $this->version]);
        $PAGE->set_title(get_string('link_report', 'local_educaaragon'));
        $PAGE->set_heading(get_string('link_report', 'local_educaaragon'));
        $PAGE->set_pagelayout('standard');
    }

    /**
     * @param renderer_base $output
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function export_for_template(renderer_base $output): array {
        global $DB;
        if ($this->iseditable === false) {
            return [
                'error' => get_string('resourcenoteditable', 'local_educaaragon')
            ];
        }
        $managelogs = new manage_logs();
        $resource = $DB->get_record('resource', ['id' => $this->resourceid], 'name');
        $numlinksactive = $managelogs->get_numlinksactive($this->courseid, $this->resourceid, $this->version);
        $numlinksfixed = $managelogs->get_numlinksfixed($this->courseid, $this->resourceid, $this->version);
        $numlinksbroken = $managelogs->get_numlinksbroken($this->courseid, $this->resourceid, $this->version);
        $numlinksnotvalid = $managelogs->get_numlinksnotvalid($this->courseid, $this->resourceid, $this->version);
        return [
            'showactivelinks' =>
                ((int)$this->showactives === 0) ? (new moodle_url(
                    '/local/educaaragon/resourcelinks.php',
                    ['resourceid' => $this->resourceid, 'version' => $this->version, 'showactives' => true]
                ))->out(false) : false,
            'hideactivelinks' =>
                ((int)$this->showactives === 1) ? (new moodle_url(
                    '/local/educaaragon/resourcelinks.php',
                    ['resourceid' => $this->resourceid, 'version' => $this->version, 'showactives' => false]
                ))->out(false) : false,
            'resource' => $resource->name,
            'version' => $this->version,
            'numlinksactive' => $numlinksactive,
            'numlinksfixed' => $numlinksfixed,
            'numlinksbroken' => $numlinksbroken,
            'numlinksnotvalid' => $numlinksnotvalid,
            'numlinks' => $numlinksactive + $numlinksfixed + $numlinksbroken + $numlinksnotvalid,
            'resourcelinks' => $this->get_resourcelinks_table()
        ];
    }

    /**
     * @return void
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function get_resourcelinks_table(): string {
        $table = new resourcelinks_table(uniqid('', true));
        $table->is_downloadable(false);
        $table->pageable(false);
        $tsort = optional_param('tsort', '',  PARAM_RAW);
        $tdir= optional_param('tdir', 0,  PARAM_INT);
        $url = new moodle_url('/local/educaaragon/resourcelinks.php', ['resourceid' => $this->resourceid, 'version' => $this->version]);
        if (optional_param('treset', 0, PARAM_INT) === 1) {
            redirect($url->out(false));
        }
        if ($this->showactives) {
            $table->set_sql(
                '*',
                '{local_educa_resource_links}',
                'resourceid = ' . $this->resourceid . ' AND courseid = ' . $this->courseid . ' AND version = "' . $this->version . '"'
            );
            $url->params(['showactives' => (int)$this->showactives]);
        } else {
            $table->set_sql(
                '*',
                '{local_educa_resource_links}',
                'resourceid = ' . $this->resourceid . ' AND courseid = ' . $this->courseid . ' AND version = "' . $this->version . '" AND action != "link_active"'
            );
        }
        if ($tsort !== '') {
            $url->params(['tsort' => $tsort]);
        }
        if ($tdir !== 0) {
            $url->params(['tdir' => $tdir]);
        }
        $table->define_baseurl($url->out(false));
        $table->collapsible(false);
        ob_start();
        $table->out(100, true, false);
        $tablecontent = ob_get_contents();
        ob_end_clean();
        return $tablecontent;
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
}
