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

namespace local_educaaragon;

use coding_exception;
use core\invalid_persistent_exception;
use dml_exception;
use Exception;
use stdClass;

require_once(__DIR__ . '/../../../config.php');
global $CFG;
require_once($CFG->dirroot . '/local/educaaragon/lib.php');

class manage_logs {

    public $processedcourse;
    public $editable;
    public $edited;
    public $resourcelink;

    /**
     * @param int $courseid
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_persistent_exception
     */
    public function create_processed_course(int $courseid): void {
        global $DB;
        if ($processedcourse = $DB->get_record('local_educa_processedcourses', ['courseid' => $courseid])) {
            $this->processedcourse = new educa_processedcourses($processedcourse->id);
        } else {
            $data = new stdClass();
            $data->courseid = $courseid;
            $data->processed = false;
            $data->message = null;
            $this->processedcourse = new educa_processedcourses(0, $data);
            $this->processedcourse->create();
        }
    }

    /**
     * @param bool $processed
     * @param string|null $message
     * @return void
     * @throws coding_exception
     * @throws invalid_persistent_exception
     */
    public function update_proccesed_course(bool $processed, string $message = null): void {
        $this->processedcourse->set('processed', $processed);
        $this->processedcourse->set('message', $message);
        $this->processedcourse->update();
    }

    /**
     * @return array
     * @throws dml_exception
     */
    public function get_processed_courses(): array {
        global $DB;
        return $DB->get_records('local_educa_processedcourses');
    }

    /**
     * @return int
     * @throws dml_exception
     */
    public function get_numprocessed(): int  {
        global $DB;
        return $DB->count_records('local_educa_processedcourses');
    }

    /**
     * @return int
     * @throws dml_exception
     */
    public function get_numprocessedcorrectly(): int {
        global $DB;
        return $DB->count_records('local_educa_processedcourses', ['processed' => 1]);
    }

    /**
     * @return int
     * @throws dml_exception
     */
    public function get_numprocessedwarnign(): int {
        global $DB;
        return $DB->count_records('local_educa_processedcourses', ['message' => 'no_associated_folder']);
    }

    /**
     * @return int
     * @throws dml_exception
     */
    public function get_numprocessederror(): int {
        global $DB;
        return $DB->count_records_select('local_educa_processedcourses', 'processed = :p AND message != :m', ['p' => 0, 'm' => 'no_associated_folder']);
        //return $DB->count_records('local_educa_processedcourses', ['processed' => 0, 'message' =>]);
    }

    /**
     * @param int $resourceid
     * @param int $courseid
     * @param string $type
     * @param string $version
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_persistent_exception
     */
    public function create_editable(int $resourceid, int $courseid, string $type, string $version): void {
        global $DB;
        if ($editable = $DB->get_record('local_educa_editables', ['courseid' => $courseid, 'resourceid' => $resourceid])) {
            $this->editable = new educa_editables($editable->id);
        } else {
            $data = new stdClass();
            $data->courseid = $courseid;
            $data->resourceid = $resourceid;
            $data->type = $type;
            $data->version = $version;
            $this->editable = new educa_editables(0, $data);
            $this->editable->create();
        }
    }

    /**
     * @param int $relatedcmid
     * @param string $version
     * @return void
     * @throws coding_exception
     * @throws invalid_persistent_exception
     */
    public function update_editable(int $relatedcmid, string $version) {
        $this->editable->set('relatedcmid', $relatedcmid);
        $this->editable->set('version', $version);
        $this->editable->update();
    }

    /**
     * @return mixed|null
     * @throws coding_exception
     */
    public function get_relatedcmid() {
        return $this->editable->get('relatedcmid');
    }

    /**
     * @param int $courseid
     * @param int $resourceid
     * @param string $action
     * @param stdClass|null $other
     * @param string|null $version
     * @return void
     * @throws coding_exception
     * @throws invalid_persistent_exception
     */
    public function create_edited(int $courseid, int $resourceid, string $action, stdClass $other = null, string $version = null): void {
        $data = new stdClass();
        $data->courseid = $courseid;
        $data->resourceid = $resourceid;
        $data->action = $action;
        $data->other = ($other !== null) ? json_encode($other) : $other;
        $data->version = $version;
        $this->edited = new educa_edited(0, $data);
        $this->edited->create();
    }

    /**
     * @param string $action
     * @param stdClass|null $other
     * @param string|null $version
     * @return void
     * @throws coding_exception
     * @throws invalid_persistent_exception
     */
    public function update_edited(string $action, stdClass $other = null, string $version = null): void {
        $this->edited->set('action', $action);
        $this->edited->set('other', ($other !== null) ? json_encode($other) : $other);
        $this->edited->set('version', $version);
        $this->edited->update();
    }

    /**
     * @param int $courseid
     * @param int $resourceid
     * @param string $version
     * @return int
     * @throws dml_exception
     */
    public function get_numlinksactive(int $courseid, int $resourceid, string $version): int {
        global $DB;
        return $DB->count_records('local_educa_resource_links', [
            'courseid' => $courseid,
            'resourceid' => $resourceid,
            'version' => $version,
            'action' => 'link_active'
        ]);
    }

    /**
     * @param int $courseid
     * @param int $resourceid
     * @param string $version
     * @return int
     * @throws dml_exception
     */
    public function get_numlinksfixed(int $courseid, int $resourceid, string $version): int {
        global $DB;
        return $DB->count_records('local_educa_resource_links', [
            'courseid' => $courseid,
            'resourceid' => $resourceid,
            'version' => $version,
            'action' => 'link_fixed'
        ]);
    }

    /**
     * @param int $courseid
     * @param int $resourceid
     * @param string $version
     * @return int
     * @throws dml_exception
     */
    public function get_numlinksbroken(int $courseid, int $resourceid, string $version): int {
        global $DB;
        $broken = $DB->count_records('local_educa_resource_links', [
            'courseid' => $courseid,
            'resourceid' => $resourceid,
            'version' => $version,
            'action' => 'link_broken'
        ]);
        $cantfix = $DB->count_records('local_educa_resource_links', [
            'courseid' => $courseid,
            'resourceid' => $resourceid,
            'version' => $version,
            'action' => 'link_broken_cantfix'
        ]);
        $linkbrokenafterchangehttps = $DB->count_records('local_educa_resource_links', [
            'courseid' => $courseid,
            'resourceid' => $resourceid,
            'version' => $version,
            'action' => 'link_broken_afterchangehttps'
        ]);
        return $broken + $cantfix + $linkbrokenafterchangehttps;
    }

    /**
     * @param int $courseid
     * @param int $resourceid
     * @param string $version
     * @return int
     * @throws dml_exception
     */
    public function get_numlinksnotvalid(int $courseid, int $resourceid, string $version) {
        global $DB;
        $linknotvalid = $DB->count_records('local_educa_resource_links', [
            'courseid' => $courseid,
            'resourceid' => $resourceid,
            'version' => $version,
            'action' => 'link_notvalid'
        ]);
        $linknotvalidactive = $DB->count_records('local_educa_resource_links', [
            'courseid' => $courseid,
            'resourceid' => $resourceid,
            'version' => $version,
            'action' => 'link_notvalid_active'
        ]);
        return $linknotvalid + $linknotvalidactive;
    }

    /**
     * @param int $courseid
     * @param int $resourceid
     * @param string $version
     * @param string $action
     * @param string $link
     * @param string $file
     * @param string|null $message
     * @param string|null $other
     * @return void
     * @throws coding_exception
     * @throws invalid_persistent_exception
     */
    public function create_resourcelink(int $courseid, int $resourceid, string $version, string $action, string $link, string $file, string $message = null, string $other = null) {
        $data = new stdClass();
        $data->courseid = $courseid;
        $data->resourceid = $resourceid;
        $data->version = $version;
        $data->action = $action;
        $data->link = str_replace(['<', '>'], '', filter_var($link, FILTER_SANITIZE_URL));
        $data->file = $file;
        $data->message = mb_detect_encoding($message) === 'ASCII' ? $message : iconv(mb_detect_encoding($message), 'ASCII', $message);
        $data->other = $other;
        $this->resourcelink = new educa_resource_links(0, $data);
        $this->resourcelink->create();
    }
}
