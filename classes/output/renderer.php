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
use core\invalid_persistent_exception;
use dml_exception;
use DOMException;
use moodle_exception;
use plugin_renderer_base;
use repository_exception;

defined('MOODLE_INTERNAL') || die();

class renderer extends plugin_renderer_base {

    /**
     * @param processedcourses_page $page
     * @return bool|string
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function render_processedcourses_page(processedcourses_page $page) {
        $context = $page->export_for_template($this);
        return $this->render_from_template('local_educaaragon/processedcourses', $context);
    }

    /**
     * @param editables_page $page
     * @return bool|string
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function render_editables_page(editables_page $page) {
        $context = $page->export_for_template($this);
        return $this->render_from_template('local_educaaragon/editables', $context);
    }

    /**
     * @param editresource_page $page
     * @return bool|string
     * @throws DOMException
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_persistent_exception
     * @throws moodle_exception
     * @throws repository_exception
     */
    public function render_editresource_page(editresource_page $page) {
        $context = $page->export_for_template($this);
        return $this->render_from_template('local_educaaragon/editresource', $context);
    }

    /**
     * @param editresource_page $page
     * @return bool|string
     * @throws DOMException
     * @throws invalid_persistent_exception
     * @throws repository_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function render_editresourcetoc_page(editresource_page $page) {
        $context = $page->export_for_template($this);
        return $this->render_from_template('local_educaaragon/editresourcetoc', $context);
    }

    /**
     * @param registereditions_page $page
     * @return bool|string
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function render_registereditions_page(registereditions_page $page) {
        $context = $page->export_for_template($this);
        return $this->render_from_template('local_educaaragon/registereditions', $context);
    }

    /**
     * @param resourcelinks_page $page
     * @return bool|string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function render_resourcelinks_page(resourcelinks_page $page) {
        $context = $page->export_for_template($this);
        return $this->render_from_template('local_educaaragon/resourcelinks', $context);
    }

}
