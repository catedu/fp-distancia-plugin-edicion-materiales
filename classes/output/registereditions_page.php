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
use local_educaaragon\registereditions_table;
use moodle_exception;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

class registereditions_page implements renderable, templatable {

    public $resourceid;

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct(int $resourceid) {
        $this->page_access();
        $this->resourceid = $resourceid;
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
        $PAGE->set_url('/local/educaaragon/registereditions.php');
        $PAGE->set_title(get_string('registereditions', 'local_educaaragon'));
        $PAGE->set_heading(get_string('registereditions', 'local_educaaragon'));
        $PAGE->set_pagelayout('standard');
    }

    /**
     * @param renderer_base $output
     * @return array
     * @throws coding_exception
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'registereditions' => $this->get_registereditions_table()
        ];
    }

    /**
     * @return void
     * @throws moodle_exception
     * @throws coding_exception
     */
    public function get_registereditions_table(): string {
        $table = new registereditions_table(uniqid('', true));
        $table->is_downloadable(false);
        $table->pageable(false);
        $tsort = optional_param('tsort', '',  PARAM_RAW);
        $tdir = optional_param('tdir', 0,  PARAM_INT);
        $url = ($this->resourceid === 0) ? new moodle_url('/local/educaaragon/registereditions.php') : new moodle_url('/local/educaaragon/registereditions.php', ['resourceid' => $this->resourceid]);
        if (optional_param('treset', 0, PARAM_INT) === 1) {
            redirect($url->out(false), '', 0);
        }
        if ($this->resourceid === 0) {
            $table->set_sql('*', '{local_educa_edited}', '1 = 1');
        } else {
            $table->set_sql('*', '{local_educa_edited}', 'resourceid = ' . $this->resourceid);
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
}
