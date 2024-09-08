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

use cm_info;
use coding_exception;
use component_generator_base;
use core\invalid_persistent_exception;
use dml_exception;
use DOMDocument;
use DOMException;
use file_exception;
use RuntimeException;
use moodle_exception;
use repository_exception;
use repository_filesystem;
use stdClass;
use stored_file_creation_exception;

require_once(__DIR__ . '/../../../config.php');
global $CFG;

require_once($CFG->dirroot . '/lib/weblib.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot . '/lib/resourcelib.php');
require_once($CFG->dirroot . '/mod/resource/lib.php');


/**
 * @package local_educaaragon
 * @author 3iPunt <https://www.tresipunt.com/>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 3iPunt <https://www.tresipunt.com/>
 */

class processcourse {

    private $course;
    private $repository;
    private $info;
    private $resourcegenerator;
    private $usercontextid;
    private $processingroute;


    /**
     * @param stdClass $course
     * @param repository_filesystem $repository
     * @param component_generator_base $resourcegenerator
     * @param int $usercontextid
     * @throws moodle_exception
     */
    public function __construct(
        stdClass $course,
        repository_filesystem $repository,
        component_generator_base $resourcegenerator,
        int $usercontextid) {
        global $CFG;
        $this->course = $course;
        $this->repository = $repository;
        $this->info = get_fast_modinfo($this->course);
        $this->resourcegenerator = $resourcegenerator;
        $this->usercontextid = $usercontextid;
        $this->processingroute = $CFG->dirroot . '/local/educaaragon/fileprocessing/';
    }

    /**
     * @return array
     */
    public function get_scorms_and_imscp(): array {
        $cms = $this->info->get_cms();
        /** @var cm_info[] $scorms */
        $scorms = [];
        /** @var cm_info[] $ims */
        $ims = [];
        foreach($cms as $cm) {
            // TODO the dynamic contents of section 0 are not transformed. Confirm with customer
            if ($cm->modname === 'scorm' && $cm->section !== 0) {
                $scorms[$cm->id] = $cm;
            }
            if ($cm->modname === 'imscp' && $cm->section !== 0) {
                $ims[$cm->id] = $cm;
            }
        }
        return array_merge($scorms, $ims);
    }

    /**
     * @return array
     */
    public function get_related_folder(): array {
         $listing = $this->repository->get_listing('/');
         foreach ($listing['list'] as $folder) {
             if ($this->course->shortname === $folder['title']) {
                 return $folder;
             }
         }
         return [];
     }

    /**
     * @param string $path
     * @return array
     */
    public function get_contents_for_course(string $path): array {
        return $this->repository->get_listing($path)['list'];
    }

    /**
     * @param array $dynamiccontent
     * @param array $contentsoffolder
     * @return array
     */
    public function get_content_associations(array $dynamiccontent, array $contentsoffolder): array {
        $associations = [];
        foreach ($dynamiccontent as $cm) {
            $dynamicorder = preg_replace( '/\D/', "",  remove_accents($cm->name));
            if ($dynamicorder === '' || strlen($dynamicorder) > 2) {
                return [];
            }
            if (strlen($dynamicorder) < 2) {
                $dynamicorder = '0' . $dynamicorder;
            }
            foreach ($contentsoffolder as $newcontent) {
                if (strcmp($dynamicorder, $newcontent['title']) === 0) {
                    $associations[$dynamicorder]['cm'] = $cm;
                    $associations[$dynamicorder]['folder'] = $newcontent;
                    break;
                }
            }
        }
        return $associations;
    }

    /**
     * @param array $associations
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws repository_exception
     * @throws stored_file_creation_exception
     * @throws invalid_persistent_exception
     */
    public function create_resources(array $associations) {
        global $DB;
        $manage_logs = new manage_logs();
        $i = 0;
        foreach ($associations as $association) {
            $start = microtime(true);
            assert($association['cm'] instanceof cm_info);
            $cm = $association['cm'];
            $folder = $association['folder'];
            $editableinstance = $this->create_editable_resource($cm, $folder);
            $previouscm = $DB->get_record('course_modules', ['id' => $cm->id, 'course' => $cm->course]);
            $editablecm = $DB->get_record('course_modules', ['instance' => $editableinstance->id, 'course' => $cm->course]);
            $this->order_resource($previouscm, $editablecm);

            $manage_logs->create_editable($editableinstance->id, $cm->course, 'editable', 'original');
            $manage_logs->update_editable($previouscm->id, 'original');

            $printableinstance = $this->create_pintable_resource($cm, $folder);
            $printablecm = $DB->get_record('course_modules', ['instance' => $printableinstance->id, 'course' => $cm->course]);
            $this->order_resource($editablecm, $printablecm, false);

            $manage_logs->create_editable($printableinstance->id, $cm->course, 'printable', 'original');
            $manage_logs->update_editable($editablecm->id, 'original');
            $i++;
            mtrace(
                get_string('processresource', 'local_educaaragon') .
                $i . '/' . count($associations) . ' -> ' . round(microtime(true) - $start, 2) . 's'
            );
        }
    }

    /**
     * @param array $contentsoffolder
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws repository_exception
     * @throws stored_file_creation_exception
     */
    public function create_resources_without_association(array $contentsoffolder) {
        $section = course_create_section($this->course);
        $manage_logs = new manage_logs();
        $i = 0;
        foreach ($contentsoffolder as $contentoffolder) {
            $start = microtime(true);
            $editableinstance = $this->create_editable_resource_without_association($contentoffolder, $section);
            $this->info = get_fast_modinfo($this->course);
            $cm = $this->info->get_cm($editableinstance->cmid);
            $manage_logs->create_editable($editableinstance->id, $cm->course, 'editable', 'original');

            $printableinstance = $this->create_pintable_resource($cm, $contentoffolder);

            $manage_logs->create_editable($printableinstance->id, $cm->course, 'printable', 'original');
            $manage_logs->update_editable($editableinstance->cmid, 'original');
            $i++;
            mtrace(
                get_string('processresource', 'local_educaaragon') .
                $i . '/' . count($contentsoffolder) . ' -> ' . round(microtime(true) - $start, 2) . 's'
            );
        }
    }

    /**
     * @param cm_info $cm
     * @param array $folder
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws repository_exception
     * @throws stored_file_creation_exception
     */
    private function create_editable_resource(cm_info $cm, array $folder): stdClass {
        global $DB;
        $record = [
            'course' => $this->course,
            'name' => $cm->name,
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'printintro' => 0,
            'files' => file_get_unused_draft_itemid(),
            'completion' => COMPLETION_TRACKING_NONE,
            'completionview' => COMPLETION_VIEW_NOT_REQUIRED,
            'display' => RESOURCELIB_DISPLAY_OPEN,
        ];
        $options = ['section' => $cm->sectionnum];
        $files = $this->repository->get_listing($folder['path'])['list'];
        $fs = get_file_storage();
        foreach ($files as $file) {
            $fileinfo = [
                'component' => 'user',
                'filearea' => 'draft',
                'contextid' => $this->usercontextid,
                'itemid' => $record['files'],
                'filename' => $file['title'],
                'filepath' => '/',
            ];
            $filepath = $this->repository->get_file($file['path'])['path'];
            $fs->create_file_from_pathname($fileinfo, $filepath);
        }
        $indexhtml = $fs->get_file($this->usercontextid, 'user', 'draft', $record['files'], '/', 'index.html');
        if ($indexhtml === false) {
            throw new RuntimeException(
                get_string(
                    'no_index_file',
                    'local_educaaragon',
                    ['cmname' => $cm->name, 'course' => $cm->course]
                )
            );
        }
        $filepath = file_correct_filepath('/');
        file_reset_sortorder($this->usercontextid, 'user', 'draft', $indexhtml->get_itemid());
        file_set_sortorder($this->usercontextid, 'user', 'draft', $indexhtml->get_itemid(), $filepath, $indexhtml->get_filename(), 1);

        foreach($this->info->get_cms() as $cmcourse) {
            if ($cmcourse->name === $cm->name) {
                $hidecm = new stdClass();
                $hidecm->id = $cmcourse->id;
                $hidecm->visible = 0;
                $DB->update_record('course_modules', $hidecm);
            }
        }

        $instance = $this->resourcegenerator->create_instance($record, $options);
        $instance->intro = '';
        $DB->update_record('resource', $instance);
        return $instance;
    }

    /**
     * @param cm_info $cm
     * @param array $folder
     * @return stdClass
     * @throws DOMException
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws repository_exception
     * @throws stored_file_creation_exception
     */
    private function create_pintable_resource(cm_info $cm, array $folder): stdClass {
        global $DB;
        $files = $this->repository->get_listing($folder['path'])['list'];
        foreach ($files as $file) {
            $filepath = $this->repository->get_file($file['path'])['path'];
            if (!copy($filepath, $this->processingroute . $file['title'])) {
                $this->empty_processingroute();
                throw new RuntimeException(
                    get_string(
                        'error_copy_files',
                        'local_educaaragon',
                        ['course' => $cm->course, 'origen' => $filepath, 'destiny' => $this->processingroute . $file['title']]
                    )
                );
            }
        }
        $localfiles = scandir($this->processingroute);
        $htmls = [];
        foreach ($localfiles as $localfile) {
            if (strpos($localfile, '.html') !== false) {
                $htmls[] = $localfile;
            }
        }
        $index = array_search('index.html', $htmls, false);
        if ($index === false) {
            throw new RuntimeException(
                get_string(
                    'no_index_file',
                    'local_educaaragon',
                    ['cmname' => $cm->name, 'course' => $cm->course]
                )
            );
        }
        $this->unify_files();

        $fs = get_file_storage();
        $record = [
            'course' => $this->course,
            'name' => $cm->name . ' (' . get_string('printable', 'local_educaaragon') . ')',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'printintro' => 0,
            'files' => file_get_unused_draft_itemid(),
            'completion' => COMPLETION_TRACKING_NONE,
            'completionview' => COMPLETION_VIEW_NOT_REQUIRED,
            'display' => RESOURCELIB_DISPLAY_OPEN,
        ];
        $options = ['section' => $cm->sectionnum];
        $files = scandir($this->processingroute);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $fileinfo = [
                    'component' => 'user',
                    'filearea' => 'draft',
                    'contextid' => $this->usercontextid,
                    'itemid' => $record['files'],
                    'filename' => $file,
                    'filepath' => '/',
                ];
                $fs->create_file_from_pathname($fileinfo, $this->processingroute . $file);
            }
        }
        $indexhtml = $fs->get_file($this->usercontextid, 'user', 'draft', $record['files'], '/', 'index.html');
        if ($indexhtml === false) {
            throw new RuntimeException(
                get_string(
                    'no_index_file',
                    'local_educaaragon',
                    ['cmname' => $cm->name, 'course' => $cm->course]
                )
            );
        }
        $filepath = file_correct_filepath('/');
        file_reset_sortorder($this->usercontextid, 'user', 'draft', $indexhtml->get_itemid());
        file_set_sortorder($this->usercontextid, 'user', 'draft', $indexhtml->get_itemid(), $filepath, $indexhtml->get_filename(), 1);

        foreach($this->info->get_cms() as $cmcourse) {
            if ($cmcourse->name === $record['name']) {
                $hidecm = new stdClass();
                $hidecm->id = $cmcourse->id;
                $hidecm->visible = 0;
                $DB->update_record('course_modules', $hidecm);
            }
        }

        $instance = $this->resourcegenerator->create_instance($record, $options);
        $instance->intro = '';
        $DB->update_record('resource', $instance);
        $this->empty_processingroute();
        return $instance;
    }

    /**
     * @param array $folder
     * @param stdClass $section
     * @return stdClass
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws repository_exception
     * @throws stored_file_creation_exception
     */
    private function create_editable_resource_without_association(array $folder, stdClass $section): stdClass {
        global $DB;
        $title = $this->get_resource_title($folder);
        $record = [
            'course' => $this->course,
            'name' => $folder['title'] . ' - ' . $title,
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'printintro' => 0,
            'files' => file_get_unused_draft_itemid(),
            'completion' => COMPLETION_TRACKING_NONE,
            'completionview' => COMPLETION_VIEW_NOT_REQUIRED,
            'display' => RESOURCELIB_DISPLAY_OPEN,
        ];
        $options = ['section' => $section->section];
        $files = $this->repository->get_listing($folder['path'])['list'];
        $fs = get_file_storage();
        foreach ($files as $file) {
            $fileinfo = [
                'component' => 'user',
                'filearea' => 'draft',
                'contextid' => $this->usercontextid,
                'itemid' => $record['files'],
                'filename' => $file['title'],
                'filepath' => '/',
            ];
            $filepath = $this->repository->get_file($file['path'])['path'];
            $fs->create_file_from_pathname($fileinfo, $filepath);
        }
        $indexhtml = $fs->get_file($this->usercontextid, 'user', 'draft', $record['files'], '/', 'index.html');
        if ($indexhtml === false) {
            throw new RuntimeException(
                get_string(
                    'no_index_file',
                    'local_educaaragon',
                    ['cmname' => $title, 'course' => $this->course->id]
                )
            );
        }
        $filepath = file_correct_filepath('/');
        file_reset_sortorder($this->usercontextid, 'user', 'draft', $indexhtml->get_itemid());
        file_set_sortorder($this->usercontextid, 'user', 'draft', $indexhtml->get_itemid(), $filepath, $indexhtml->get_filename(), 1);

        foreach($this->info->get_cms() as $cmcourse) {
            if ($cmcourse->name === $record['name']) {
                $hidecm = new stdClass();
                $hidecm->id = $cmcourse->id;
                $hidecm->visible = 0;
                $DB->update_record('course_modules', $hidecm);
            }
        }

        $instance = $this->resourcegenerator->create_instance($record, $options);
        $instance->intro = '';
        $DB->update_record('resource', $instance);
        return $instance;
    }

    /**
     * @return void
     * @throws DOMException
     */
    private function unify_files(): void {
        // libxml_use_internal_errors(true); // TODO manage errors.
        $indexdoc = new DOMDocument();
        $indexdoc->loadHTMLFile($this->processingroute . 'index.html');
        $nav = $indexdoc->getElementById('siteNav');
        if ($nav !== null && $nav->childNodes) {
            $links = $nav->getElementsByTagName('a');
            $linkshref = [];
            foreach ($links as $link) {
                if ($link->getAttribute('href') !== 'index.html') {
                    $linkshref[] = $link->getAttribute('href');
                }
            }
            $nav->parentNode->removeChild($nav);
        }
        $toppagination = $indexdoc->getElementById('topPagination');
        if ($toppagination !== null) {
            $toppagination->parentNode->removeChild($toppagination);
        }
        $bottompagination = $indexdoc->getElementById('bottomPagination');
        if ($bottompagination !== null) {
            $bottompagination->parentNode->removeChild($bottompagination);
        }
        $indexmain = $indexdoc->getElementById('main'); // TODO id and name attributes are treated equally in DOMDocument, they cannot be repeated, throws a warning.
        if ($indexmain !== null && isset($linkshref) && count($linkshref) > 0) {
            foreach ($linkshref as $link) {
                $html = new DOMDocument();
                $html->loadHTMLFile($this->processingroute . $link);
                $htmlmain = $html->getElementById('main');
                if ($htmlmain !== null && $htmlmain->childNodes) {
                    $childnodes = [];
                    foreach ($htmlmain->childNodes as $childnode) {
                        $childnodes[] = $childnode;
                    }
                    foreach ($childnodes as $childnode) {
                        $node = $indexdoc->importNode($childnode, true);
                        $indexmain->appendChild($node);
                    }
                }
                $indexdoc->saveHTMLFile($this->processingroute . 'index.html');
            }
        }
        $heads = $indexdoc->getElementsByTagName('head');
        $css = $indexdoc->createElement('style',
            '#nav-toggler, a#main {
                display: none;
            }
            section > header:first-child,
            div#main {
                break-before: avoid !important;
            }
            article + header,
            #nodeDecoration h1#nodeTitle {
                break-before: page;
                break-inside: avoid !important;
            }
            article,
            a[name="main"] + div#nodeDecoration {
                break-before: avoid;
                break-after: avoid;
                break-inside: avoid !important;
            }
            tbody tr,
            div.iDevice_wrapper {
                break-inside: avoid !important;
            }
            article[class$="autoevaluacionfpd"],
            div[class$="autoevaluacionfpd"] {
                break-before: page;
                break-after: page;
            }');
        $metas = $indexdoc->getElementById('metacachehttp');
        if ($metas === null) {
            $metacache = $indexdoc->createElement('meta', '');
            $metacacheid = $indexdoc->createAttribute('id');
            $metacacheid->value = 'metacachehttp';
            $metacache->appendChild($metacacheid);
            $metacachehttp = $indexdoc->createAttribute('http-equiv');
            $metacachehttp->value = 'Cache-Control';
            $metacache->appendChild($metacachehttp);
            $metacachecontent = $indexdoc->createAttribute('content');
            $metacachecontent->value = 'no-cache, no-store, must-revalidate';
            $metacache->appendChild($metacachecontent);

            $metapragma = $indexdoc->createElement('meta', '');
            $metapragmahttp = $indexdoc->createAttribute('http-equiv');
            $metapragmahttp->value = 'Pragma';
            $metapragma->appendChild($metapragmahttp);
            $metapragmacontent = $indexdoc->createAttribute('content');
            $metapragmacontent->value = 'no-cache';
            $metapragma->appendChild($metapragmacontent);

            $metaexpires = $indexdoc->createElement('meta', '');
            $metaexpireshttp = $indexdoc->createAttribute('http-equiv');
            $metaexpireshttp->value = 'Expires';
            $metaexpires->appendChild($metaexpireshttp);
            $metaexpirescontent = $indexdoc->createAttribute('content');
            $metaexpirescontent->value = '0';
            $metaexpires->appendChild($metaexpirescontent);
            foreach ($heads as $head) {
                $head->appendChild($metacache);
                $head->appendChild($metapragma);
                $head->appendChild($metaexpires);
                $head->appendChild($css);
            }
        }
        $body = $indexdoc->getElementsByTagName('body')->item(0);
        if ($body !== null) {
            $body->setAttribute('class', $body->getAttribute('class') . ' no-nav');
            $indexdoc->saveHTMLFile($this->processingroute . 'index.html');
        }
    }

    /**
     * @param stdClass $previouscm
     * @param stdClass $newcm
     * @param bool $hideprevious
     * @return void
     * @throws dml_exception
     */
    private function order_resource(stdClass $previouscm, stdClass $newcm, bool $hideprevious = true) {
        global $DB;
        $section = $DB->get_record('course_sections', ['id' => $previouscm->section, 'course' => $previouscm->course]);
        $sequence = $section->sequence;
        $sequence = str_replace($newcm->id, '', $sequence);
        $position = strpos($sequence, $previouscm->id) + strlen($previouscm->id) + 1;
        $sequence = substr_replace($sequence, $newcm->id . ',', $position, 0);
        if (substr($sequence, -1) === ',') {
            $sequence = rtrim($sequence, ',');
        }
        $section->sequence = $sequence;
        $DB->update_record('course_sections', $section);
        if ($hideprevious) {
            $previouscm->visible = 0;
            $DB->update_record('course_modules', $previouscm);
        }
        $newcm->indent = $previouscm->indent;
        $DB->update_record('course_modules', $newcm);
    }

    /**
     * @return void
     */
    private function empty_processingroute(): void {
        $files = glob($this->processingroute . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * @param array $folder
     * @return string
     * @throws repository_exception
     */
    private function get_resource_title(array $folder): string {
        $files = $this->repository->get_listing($folder['path'])['list'];
        foreach ($files as $file) {
            if ($file['title'] === 'index.html') {
                $filepath = $this->repository->get_file($file['path'])['path'];
                copy($filepath, $this->processingroute . $file['title']);
            }
        }
        $indexdoc = new DOMDocument();
        $indexdoc->loadHTMLFile($this->processingroute . 'index.html');
        $title = $indexdoc->getElementsByTagName('title')->item(0);
        $this->empty_processingroute();
        if ($title !== null) {
            return $title->nodeValue;
        }
        return $folder['title'];
    }
}
