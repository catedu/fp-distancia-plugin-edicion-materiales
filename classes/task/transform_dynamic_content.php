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

namespace local_educaaragon\task;

use cm_info;
use coding_exception;
use component_generator_base;
use context_user;
use core\task\scheduled_task;
use core_course_external;
use dml_exception;
use Exception;
use local_educaaragon\external\reprocessing_external;
use local_educaaragon\manage_editable_resource;
use local_educaaragon\manage_logs;
use local_educaaragon\processcourse;
use moodle_exception;
use phpunit_util;
use repository;
use repository_filesystem;
use stdClass;
use RuntimeException;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/course/externallib.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/local/educaaragon/lib.php');
require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');
require_once($CFG->dirroot . '/local/educaaragon/classes/manage_logs.php');
require_once($CFG->dirroot . '/lib/modinfolib.php');
require_once($CFG->dirroot . '/local/educaaragon/classes/external/reprocessing_external.php');


class transform_dynamic_content extends scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('transformdynamiccontent', 'local_educaaragon');
    }


    /**
     * @return bool
     * @throws dml_exception
     * @throws moodle_exception
     * @throws coding_exception
     */
    public function execute(): bool {
        if (get_config('local_educaaragon', 'activetask')) {
            $starexecute = microtime(true);
            $this->print_signature();
            $courses = $this->get_courses_to_process();
            mtrace(get_string('coursesfound', 'local_educaaragon', count($courses)));
            $repository = get_repository();
            $generator = phpunit_util::get_data_generator();
            $usercontext = context_user::instance(get_admin()->id)->id;
            if ($generator !== null) {
                $resourcegenerator = $generator->get_plugin_generator('mod_resource');
            } else {
                throw new RuntimeException(
                    get_string(
                        'no_resourcegenerator',
                        'local_educaaragon'
                    )
                );
            }
            foreach ($courses as $course) {
                mtrace(PHP_EOL . get_string('processcourse', 'local_educaaragon', ['shortname' => $course->shortname, 'courseid' => $course->id]));
                try {
                    $start = microtime(true);
                    $this->procces_course($course, $repository, $resourcegenerator, $usercontext);
                    mtrace(get_string('processresourcelinks', 'local_educaaragon', ['shortname' => $course->shortname, 'courseid' => $course->id]));
                    $this->procces_resource_links($course);
                    mtrace( get_string('course_processed', 'local_educaaragon') . round(microtime(true) - $start, 2) . 's' . PHP_EOL
                        . get_string('memory_used', 'local_educaaragon') . display_size(memory_get_usage())
                    );
                } catch (Exception $e) {
                    $manage_logs = new manage_logs();
                    $manage_logs->create_processed_course($course->id);
                    if ($e->getMessage() === 'error/invalidpersistenterror') {
                        reprocessing_external::reprocessing_course($course->id);
                        $manage_logs->update_proccesed_course(false, 'error/invalidpersistenterror');
                    }
                    if ($e->getMessage() === 'error/Invalid file requested.') {
                        reprocessing_external::reprocessing_course($course->id);
                        $manage_logs->update_proccesed_course(false, 'error/invalidfilerequested');
                    }
                    mtrace(get_string(
                        'errorprocesscourse_desc',
                        'local_educaaragon',
                        ['course' => $course->shortname, 'error' => $e->getMessage()]
                    ));
                }
            }
            mtrace( PHP_EOL
                . get_string('allcourses_processed', 'local_educaaragon') . round(microtime(true) - $starexecute, 2) . 's' . PHP_EOL
                . get_string('memory_used', 'local_educaaragon') . display_size(memory_get_usage()) . PHP_EOL
            );
            return true;
        }
        mtrace(get_string('notactivetask', 'local_educaaragon'));
        return false;
    }

    /**
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function get_courses_to_process(): array {
        global $DB;
        if (get_config('local_educaaragon', 'allcourses')) {
            $courses = get_courses();
            unset($courses[1]);
        } else {
            $categories = core_course_external::get_categories([
                ['key' => 'id', 'value' => get_config('local_educaaragon', 'category')],
                ['key' => 'visible', 'value' => 1]], 1);
            $courses = [];
            foreach ($categories as $category) {
                $coursesofcategory = get_courses($category['id']);
                foreach($coursesofcategory as $course) {
                    $courses[] = $course;
                }
            }
        }
        foreach ($courses as $key => $course) {
            $hasproccesed = $DB->get_record('local_educa_processedcourses', ['courseid' => $course->id], 'processed');
            if (!$hasproccesed) {
                continue;
            }
            if ((int)$hasproccesed->processed === 1) {
                unset($courses[$key]);
            }
        }
        return array_values($courses);
    }

    /**
     * @param stdClass $course
     * @param repository_filesystem $repository
     * @param component_generator_base $resourcegenerator
     * @param int $usercontext
     * @return void
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function procces_course(
        stdClass $course,
        repository_filesystem $repository,
        component_generator_base $resourcegenerator,
        int $usercontext) {
        $manage_logs = new manage_logs();
        $manage_logs->create_processed_course($course->id);
        $processcourse = new processcourse($course, $repository, $resourcegenerator, $usercontext);
        $repositoryfolder = $processcourse->get_related_folder();
        if (empty($repositoryfolder)) {
            $manage_logs->update_proccesed_course(false, 'no_associated_folder');
            throw new RuntimeException(
                get_string(
                    'no_associated_folder',
                    'local_educaaragon',
                    ['course' => $course->shortname, 'repository' => $repository->get_name()]
                )
            );
        }
        $contentsoffolder = $processcourse->get_contents_for_course($repositoryfolder['path']);
        /** @var cm_info[] $dynamiccontent */
        $dynamiccontent = $processcourse->get_scorms_and_imscp();
        if (count($dynamiccontent) > 0) {
            mtrace(get_string('dynamiccontent_found', 'local_educaaragon', count($dynamiccontent)));
            if (count($dynamiccontent) !== count($contentsoffolder)) {
                $processcourse->create_resources_without_association($contentsoffolder);
                $manage_logs->update_proccesed_course(true, 'correctly_processed_needassociation');
            } else {
                // Associate content with repos.
                $associations = $processcourse->get_content_associations($dynamiccontent, $contentsoffolder);
                if (empty($associations)) {
                    $processcourse->create_resources_without_association($contentsoffolder);
                    $manage_logs->update_proccesed_course(true, 'correctly_processed_needassociation');
                } else {
                    $processcourse->create_resources($associations);
                    $manage_logs->update_proccesed_course(true, 'correctly_processed');
                }
            }
        } else {
            $processcourse->create_resources_without_association($contentsoffolder);
            $manage_logs->update_proccesed_course(true, 'correctly_processed_needassociation');
        }
    }

    /**
     * @param stdClass $course
     * @return void
     * @throws moodle_exception
     */
    private function procces_resource_links(stdClass $course) {
        global $DB;
        $resources = $DB->get_records('local_educa_editables', ['courseid' => $course->id, 'type' => 'editable']);
        $courseinfo = get_fast_modinfo($course);
        $DB->delete_records('local_educa_resource_links', ['courseid' => $course->id]);
        if ($courseinfo !== null) {
            $i = 0;
            foreach ($resources as $resource) {
                $start = microtime(true);
                $cm = $DB->get_record('course_modules', ['course' => $course->id, 'instance' => $resource->resourceid], 'id');
                $cminfo = $courseinfo->get_cm($cm->id);
                $manageeditable = new manage_editable_resource($cminfo, 'original');
                $manageeditable->process_resource_links(true);
                $i++;
                mtrace(
                    get_string('processlink', 'local_educaaragon') .
                    $i . '/' . count($resources) . ' -> ' . round(microtime(true) - $start, 2) . 's'
                );
            }
        }
    }

    /**
     * @return void
     */
    private function print_signature() {
        $file = 'https://tresipunt.com/wp-content/uploads/2020/04/logo3ipunt.png';
        $img = imagecreatefromstring(file_get_contents($file));
        [$width, $height] = getimagesize($file);
        $scale = 10;
        $chars = array(
            ' ', '\'', '.', ':',
            '|', 'T',  'X', '0',
            '#',
        );
        $chars = array_reverse($chars);
        $c_count = count($chars);
        for($y = 0; $y <= $height - $scale - 1; $y += $scale) {
            for($x = 0; $x <= $width - ($scale / 2) - 1; $x += ($scale / 2)) {
                $rgb = imagecolorat($img, $x, $y);
                $r = (($rgb >> 16) & 0xFF);
                $g = (($rgb >> 8) & 0xFF);
                $b = ($rgb & 0xFF);
                $sat = ($r + $g + $b) / (255 * 3);
                echo $chars[ (int)( $sat * ($c_count - 1) ) ];
            }
            echo PHP_EOL;
        }
    }
}
