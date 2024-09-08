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
use local_educaaragon\processedcourses_table;
use renderable;
use renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

class processedcourses_page implements renderable, templatable {

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct() {
        $this->page_access();
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
        $PAGE->set_url('/local/educaaragon/processedcourses.php');
        $PAGE->set_title(get_string('processedcourses', 'local_educaaragon'));
        $PAGE->set_heading(get_string('processedcourses', 'local_educaaragon'));
        $PAGE->set_pagelayout('standard');
    }

    /**
     * @param renderer_base $output
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public function export_for_template(renderer_base $output): array {
        $managelogs = new manage_logs();
        return [
            'numprocessed' => $managelogs->get_numprocessed(),
            'numprocessedcorrectly' => $managelogs->get_numprocessedcorrectly(),
            'numprocessedwarnign' => $managelogs->get_numprocessedwarnign(),
            'numprocessederror' => $managelogs->get_numprocessederror(),
            'processedcoursestable' => $this->get_processedcourses_table()
        ];
    }

    /**
     * @return void
     * @throws coding_exception
     */
    public function get_processedcourses_table(): string {
        $table = new processedcourses_table(uniqid('', true));
        $table->is_downloadable(false);
        $table->pageable(false);
        $table->set_sql('*', '{local_educa_processedcourses}', '1 = 1');
        $table->collapsible(false);
        $table->define_baseurl('/local/educaaragon/processedcourses.php');
        $table->no_sorting('actions');
        ob_start();
        $table->out(0, true, false);
        $tablecontent = ob_get_contents();
        ob_end_clean();
        return $tablecontent;
    }
}
