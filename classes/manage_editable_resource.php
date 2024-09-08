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
use context_module;
use core\invalid_persistent_exception;
use dml_exception;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use DOMXPath;
use file_exception;
use html_writer;
use moodle_exception;
use moodle_url;
use repository_exception;
use RuntimeException;
use stdClass;
use stored_file_creation_exception;

require_once(__DIR__ . '/../../../config.php');
global $CFG;

require_once($CFG->dirroot . '/local/educaaragon/lib.php');
require_once($CFG->dirroot . '/mod/resource/locallib.php');

class manage_editable_resource {

    private $cm;
    private $course;
    private $repository;
    private $editfolder;
    private $version;
    private $versions;
    private $versionloaded;

    /**
     * @param cm_info $cm
     * @param string $version
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_persistent_exception
     * @throws repository_exception
     */
    public function __construct(cm_info $cm, string $version = '') {
        $this->cm = $cm;
        $this->course = $this->cm->get_course();
        $this->repository = get_repository();
        $this->editfolder = $this->get_editfolder();
        $this->get_original_content();
        $this->versions = $this->get_versions();
        if ($version !== '') {
            $this->version = $version;
            $this->versionloaded = $this->get_versionloaded($version);
        }
    }

    /**
     * @param string $version
     * @return array
     */
    private function get_versionloaded(string $version): array {
        $listing = $this->repository->get_listing($this->editfolder['path']);
        foreach($listing['list'] as $repositoryversion) {
            if ($repositoryversion['title'] === $version) {
                return $repositoryversion;
            }
        }
        return [];
    }

    /**
     * @return array
     * @throws repository_exception
     */
    private function get_editfolder(): array {
        $listing = $this->repository->get_listing('/');
        foreach ($listing['list'] as $folder) {
            if ('editions' === $folder['title']) {
                $coursesfolder = $this->repository->get_listing($folder['path']);
                foreach ($coursesfolder['list'] as $coursefolder) {
                    if ($this->course->shortname === $coursefolder['title']) {
                        $resourcesfolder = $this->repository->get_listing($coursefolder['path']);
                        foreach ($resourcesfolder['list'] as $resourcefolder) {
                            if ((int)$this->cm->instance === (int)$resourcefolder['title']) {
                                return $resourcefolder;
                            }
                        }
                        if (!mkdir($concurrentDirectory = $this->repository->get_rootpath() . 'editions/' . $this->course->shortname . '/' . $this->cm->instance, 0777, false) && !is_dir($concurrentDirectory)) {
                            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                        }
                    }
                }
                if (!mkdir($concurrentDirectory = $this->repository->get_rootpath() . 'editions/' . $this->course->shortname, 0777, false) && !is_dir($concurrentDirectory)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }
                return $this->get_editfolder();
            }
        }
        return [];
    }

    /**
     * @return array
     */
    public function get_versions(): array {
        $versions = [];
        $listing = $this->repository->get_listing($this->editfolder['path']);
        foreach($listing['list'] as $version) {
            $versions[] = $version['title'];
        }
        return $versions;
    }

    /**
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_persistent_exception
     * @throws repository_exception
     */
    private function get_original_content(): array {
        $listing = $this->repository->get_listing($this->editfolder['path']);
        foreach($listing['list'] as $version) {
            if ($version['title'] === 'original') {
                return $version;
            }
        }
        if (!mkdir($concurrentDirectory = $this->repository->get_rootpath() . 'editions/' . $this->course->shortname . '/' . $this->cm->instance . '/original', 0777, false) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        $context = context_module::instance($this->cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
        foreach ($files as $file) {
            $file->copy_content_to($concurrentDirectory . '/' . $file->get_filename());
        }

        $manage_logs = new manage_logs();
        $manage_logs->create_edited($this->course->id, $this->cm->instance, 'version_original_created', null, 'original');

        return $this->get_original_content();
    }

    /**
     * @param string $versionname
     * @param string $asofversion
     * @return bool
     * @throws coding_exception
     * @throws invalid_persistent_exception
     * @throws repository_exception
     */
    public function create_version(string $versionname, string $asofversion = 'original'): bool {
        foreach ($this->versions as $version) {
            if ($version === $versionname) {
                return false;
            }
        }
        copy_folder(
            $this->repository->get_rootpath() . 'editions/' . $this->course->shortname . '/' . $this->cm->instance . '/' . $asofversion,
            $this->repository->get_rootpath() . 'editions/' . $this->course->shortname . '/' . $this->cm->instance . '/' . $versionname
        );

        $manage_logs = new manage_logs();
        $other = new stdClass();
        $other->version_created_asofversion = $asofversion;
        $manage_logs->create_edited($this->course->id, $this->cm->instance, 'version_created', $other, $versionname);

        return true;
    }

    /**
     * @param string $versionname
     * @return bool
     * @throws coding_exception
     * @throws invalid_persistent_exception
     * @throws repository_exception
     */
    public function delete_version(string $versionname): bool {
        if ($versionname === 'original')  {
           return false;
        }
        delete_folder($this->repository->get_rootpath() . 'editions/' . $this->course->shortname . '/' . $this->cm->instance . '/' . $versionname);

        $manage_logs = new manage_logs();
        $manage_logs->create_edited($this->course->id, $this->cm->instance, 'version_deleted', null, $versionname);

        return true;
    }

    /**
     * @return string
     * @throws coding_exception
     * @throws repository_exception
     */
    public function get_css(): string {
        if (!isset($this->versionloaded)) {
            $version = required_param('version', PARAM_RAW);
            $this->versionloaded = $this->get_versionloaded($version);
        }
        $files = $this->repository->get_listing($this->versionloaded['path'])['list'];
        $css = '';
        foreach ($files as $file) {
            if ($file['title'] === 'base.css' ||
                $file['title'] === 'content.css' ||
                $file['title'] === 'nav.css') {
                $filepath = $this->repository->get_file($file['path'])['path'];
                $css .= file_get_contents($filepath);
            }
        }
        return $css;
    }

    /**
     * @return string
     * @throws DOMException
     * @throws moodle_exception
     * @throws coding_exception
     * @throws repository_exception
     */
    public function get_html_nav(): string {
        if (!isset($this->versionloaded)) {
            $version = required_param('version', PARAM_RAW);
            $this->versionloaded = $this->get_versionloaded($version);
        }
        $fileloaded = optional_param('file', 'index.html', PARAM_RAW);
        $files = $this->repository->get_listing($this->versionloaded['path'])['list'];
        foreach ($files as $file) {
            if ($file['title'] === 'index.html') {
                $filepath = $this->repository->get_file($file['path'])['path'];
                $indexdoc = new DOMDocument();
                libxml_use_internal_errors(true);
                $indexdoc->loadHTMLFile($filepath);
                libxml_clear_errors();
                $nav = $indexdoc->getElementById('siteNav');
                if ($nav !== null) {
                    $navdoc = new DOMDocument();
                    libxml_use_internal_errors(true);
                    $navdoc->loadHTML($nav->ownerDocument->saveXML($nav),  LIBXML_NOXMLDECL);
                    libxml_clear_errors();
                    $links = $navdoc->getElementsByTagName('a');
                    foreach ($links as $link) {
                        $href = $link->getAttribute('href');
                        $value = $link->nodeValue;
                        $newlink = $navdoc->createElement('a', utf8_decode($value));
                        $newhref = new moodle_url('/local/educaaragon/editresource.php', ['resourceid' => $this->cm->instance, 'version' => $this->versionloaded['title'], 'file' => $href]);
                        $newlink->setAttribute(
                            'href',
                            $newhref->out(false)
                        );
                        if ($fileloaded === $href) {
                            $newlink->setAttribute(
                                'style',
                                'font-weight: bold'
                            );
                        }
                        $link->parentNode->replaceChild($newlink, $link);
                    }
                    return $navdoc->saveHTML($navdoc);
                }
            }
        }
        return '';
    }

    /**
     * @param string $httmlfile
     * @return void
     * @throws DOMException
     * @throws coding_exception
     * @throws repository_exception
     */
    public function get_html_for_edit(string $httmlfile): string {
        if (!isset($this->versionloaded)) {
            $version = required_param('version', PARAM_RAW);
            $this->versionloaded = $this->get_versionloaded($version);
        }
        $files = $this->repository->get_listing($this->versionloaded['path'])['list'];
        foreach ($files as $file) {
            if ($file['title'] === $httmlfile) {
                $filepath = $this->repository->get_file($file['path'])['path'];
                $doc = new DOMDocument();
                libxml_use_internal_errors(true);
                $doc->loadHTMLFile($filepath);
                libxml_clear_errors();
                $images = $doc->getElementsByTagName('img');
                foreach ($images as $img) {
                    $src = $img->getAttribute('src');
                    foreach ($files as $fileimg) {
                        if ($fileimg['title'] === $src) {
                            $modulecontext = context_module::instance($this->cm->id);
                            $imagepath = moodle_url::make_pluginfile_url($modulecontext->id, 'mod_resource', 'content', 0, '/', $src);
                            $img->setAttribute(
                                'src',
                                $imagepath->out(false)
                            );
                            break;
                        }
                    }
                }
                $videos = $doc->getElementsByTagName('source');
                foreach ($videos as $video) {
                    $src = $video->getAttribute('src');
                    foreach ($files as $fileimg) {
                        if ($fileimg['title'] === $src) {
                            $modulecontext = context_module::instance($this->cm->id);
                            $videopath = moodle_url::make_pluginfile_url($modulecontext->id, 'mod_resource', 'content', 0, '/', $src);
                            $video->setAttribute(
                                'src',
                                $videopath->out(false)
                            );
                            break;
                        }
                    }
                }
                $main = $doc->getElementById('main');
                if ($main !== null) {
                    return $main->ownerDocument->saveHTML($main);
                }
                break;
            }
        }
        return '';
    }

    /**
     * @return string
     * @throws coding_exception
     * @throws repository_exception
     */
    public function get_toc_for_edit(): string {
        if (!isset($this->versionloaded)) {
            $version = required_param('version', PARAM_RAW);
            $this->versionloaded = $this->get_versionloaded($version);
        }
        $files = $this->repository->get_listing($this->versionloaded['path'])['list'];
        foreach ($files as $file) {
            if ($file['title'] === 'index.html') {
                $filepath = $this->repository->get_file($file['path'])['path'];
                $indexdoc = new DOMDocument();
                libxml_use_internal_errors(true);
                $indexdoc->loadHTMLFile(mb_convert_encoding($filepath, 'HTML-ENTITIES', 'UTF-8'));
                libxml_clear_errors();
                $nav = $indexdoc->getElementById('siteNav');
                if ($nav !== null) {
                    $navdoc = new DOMDocument();
                    libxml_use_internal_errors(true);
                    $navdoc->loadHTML(mb_convert_encoding($nav->ownerDocument->saveXML($nav), 'HTML-ENTITIES', 'UTF-8'));
                    libxml_clear_errors();
                    $links = $navdoc->getElementsByTagName('ul');
                    $tempdom = new DOMDocument();
                    $tempimported = $tempdom->importNode($links->item(0), true);
                    $tempdom->appendChild($tempimported);
                    $newHtml = $tempdom->saveHTML();
                    return utf8_decode($newHtml);
                }
            }
        }
        return '';
    }

    /**
     * @param string $filename
     * @param string $html
     * @param string $comments
     * @return bool
     * @throws DOMException
     * @throws coding_exception
     * @throws invalid_persistent_exception
     * @throws repository_exception
     */
    public function savechanges(string $filename, string $html, string $comments = ''): bool {
        global $CFG;
        if (!isset($this->versionloaded)) {
            $version = required_param('version', PARAM_RAW);
            $this->versionloaded = $this->get_versionloaded($version);
        }
        $files = $this->repository->get_listing($this->versionloaded['path'])['list'];
        foreach ($files as $file) {
            if ($file['title'] === $filename) {
                $filepath = $this->repository->get_file($file['path'])['path'];
                $doc = new DOMDocument();
                $doc->loadHTMLFile($filepath, LIBXML_HTML_NODEFDTD | LIBXML_SCHEMA_CREATE | LIBXML_NOXMLDECL);
                $doc->encoding = 'UTF-8';
                $this->manageMetaTags(false, $doc);
                $main = $doc->getElementById('main');
                if ($main !== null) {
                    $htmltoutf8 = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
                    $loadhtml = new DOMDocument();
                    $loadhtml->loadHTML($htmltoutf8, LIBXML_HTML_NODEFDTD | LIBXML_SCHEMA_CREATE | LIBXML_NOXMLDECL);

                    $imgs = $loadhtml->getElementsByTagName('img');
                    foreach ($imgs as $img) {
                        $src = $img->getAttribute('src');
                        if (strpos($src, $CFG->wwwroot) !== false) {
                            $srcfile = basename($src);
                            $img->setAttribute('src', $srcfile);
                        }
                    }
                    $videos = $loadhtml->getElementsByTagName('source');
                    foreach ($videos as $video) {
                        $src = $video->getAttribute('src');
                        if (strpos($src, $CFG->wwwroot) !== false) {
                            $srcfile = basename($src);
                            $video->setAttribute('src', $srcfile);
                        }
                    }

                    $newmain = $doc->createElement('section');
                    $newmain->setAttribute('id', 'main');
                    foreach ($loadhtml->getElementById('main')->childNodes as $node) {
                        $node = $doc->importNode($node, true);
                        $newmain->appendChild($node);
                    }
                    $main->parentNode->replaceChild($newmain, $main);

                    if ($doc->saveHTMLFile($this->repository->get_file($file['path'])['path']) === false) {
                        return false;
                    }

                    $manage_logs = new manage_logs();
                    $other = new stdClass();
                    $other->version_changes_saved_file = $filename;
                    if ($comments !== '') {
                        $other->edit_comments = $comments;
                    }
                    $manage_logs->create_edited($this->course->id, $this->cm->instance, 'version_changes_saved', $other, $this->versionloaded['title']);

                    return true;
                }
                return false;
            }
        }

        return false;
    }

    /**
     * @param string $html
     * @param string $comments
     * @return bool
     * @throws DOMException
     * @throws coding_exception
     */
    public function savetocchanges(string $html, string $comments = ''): bool {
        if (!isset($this->versionloaded)) {
            $version = required_param('version', PARAM_RAW);
            $this->versionloaded = $this->get_versionloaded($version);
        }
        try {
            $htmltoutf8 = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
            $html = $this->processNewNodes($htmltoutf8, $this->version);
            $htmltoutf8 = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
            $files = $this->repository->get_listing($this->versionloaded['path'])['list'];
            foreach ($files as $file) {
                $loadhtml = new DOMDocument();
                $loadhtml->loadHTML('<ul>' . $htmltoutf8 . '</ul>', LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED | LIBXML_NOXMLDECL);
                $filepath = $this->repository->get_file($file['path'])['path'];
                $currentFileName = basename($filepath);
                $doc = new DOMDocument();
                libxml_use_internal_errors(true);
                $doc->loadHTMLFile(mb_convert_encoding($filepath, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED | LIBXML_NOXMLDECL);
                $doc->encoding = 'UTF-8';
                libxml_clear_errors();

                $nav = $doc->getElementById('siteNav');
                if ($nav !== null) {
                    $oldul = $nav->getElementsByTagName('ul')->item(0);
                    if ($oldul) {
                        $newul = $doc->createElement('ul');
                        foreach ($loadhtml->documentElement->childNodes as $childNode) {
                            if ($childNode->nodeType === XML_ELEMENT_NODE && $childNode->nodeName === 'li') {
                                $this->processNodeForActive($childNode, $doc, $currentFileName);
                                $this->processCurrentPageParent($childNode, $doc, $currentFileName);
                                $this->processDaddyNoch($childNode, $doc, $currentFileName);
                                $this->processOtherSection($childNode, $doc, $currentFileName);
                                $this->removeClassesForActiveLinks($childNode);
                            }

                            $importedNode = $doc->importNode($childNode, true);
                            $newul->appendChild($importedNode);
                        }
                        $oldul->parentNode->replaceChild($newul, $oldul);
                        if (isset($newul)) {
                            $this->managePagination($newul, $doc, $filepath);
                        }
                        $this->manageMetaTags(false, $doc);
                        $doc->saveHTMLFile($filepath);
                    }
                }
            }
            $manage_logs = new manage_logs();
            $other = new stdClass();
            $other->version_changes_saved_file = 'TOC';
            if ($comments !== '') {
                $other->edit_comments = $comments;
            }
            $manage_logs->create_edited($this->course->id, $this->cm->instance, 'version_changes_saved', $other, $this->versionloaded['title']);
            return true;
        } catch (moodle_exception $e) {
            debugging($e->getMessage());
            return false;
        }
    }

    /**
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws invalid_persistent_exception
     * @throws repository_exception
     * @throws stored_file_creation_exception
     */
    public function applyversion(): bool {
        if (!isset($this->versionloaded)) {
            $version = required_param('version', PARAM_RAW);
            $this->versionloaded = $this->get_versionloaded($version);
        }
        $files = $this->repository->get_listing($this->versionloaded['path'])['list'];
        $fs = get_file_storage();
        $context = context_module::instance($this->cm->id)->id;
        foreach ($files as $file) {
            if ($fs->file_exists($context, 'mod_resource', 'content', 0, '/', $file['title'])) {
                $fs->delete_area_files($context, 'mod_resource', 'content', 0);
            }
            $fileinfo = [
                'component' => 'mod_resource',
                'filearea' => 'content',
                'contextid' => $context,
                'itemid' => 0,
                'filename' => $file['title'],
                'filepath' => '/',
            ];
            $filepath = $this->repository->get_file($file['path'])['path'];
            $fs->create_file_from_pathname($fileinfo, $filepath);
        }
        $indexhtml = $fs->get_file($context, 'mod_resource', 'content', 0, '/', 'index.html');
        $filepath = file_correct_filepath('/');
        file_reset_sortorder($context, 'mod_resource', 'content', $indexhtml->get_itemid());
        file_set_sortorder($context, 'mod_resource', 'content', $indexhtml->get_itemid(), $filepath, $indexhtml->get_filename(), 1);

        $manage_logs = new manage_logs();
        $manage_logs->create_edited($this->course->id, $this->cm->instance, 'version_applied', null, $this->versionloaded['title']);
        $manage_logs->create_editable($this->cm->instance, $this->course->id, 'editable', $this->versionloaded['title']);
        $manage_logs->update_editable((int)$manage_logs->get_relatedcmid(), $this->versionloaded['title']);
        return true;
    }

    /**
     * @return bool
     * @throws DOMException
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws invalid_persistent_exception
     * @throws moodle_exception
     * @throws repository_exception
     * @throws stored_file_creation_exception
     */
    public function apllyversionprintable(): bool {
        global $DB;
        if (!isset($this->versionloaded)) {
            $version = required_param('version', PARAM_RAW);
            $this->versionloaded = $this->get_versionloaded($version);
        }
        $resourceprintable = $DB->get_record('local_educa_editables', ['type' => 'printable', 'relatedcmid' => $this->cm->id]);
        if ($resourceprintable === false) {
            return false;
        }
        $cmprintable = $DB->get_record('course_modules', ['instance' => $resourceprintable->resourceid, 'course' => $this->course->id]);
        if ($cmprintable === false) {
            return false;
        }
        if (!mkdir($folderprintable = $this->repository->get_rootpath() . 'editions/' . $this->course->shortname . '/' . $this->cm->instance . '/' . $this->versionloaded['title'] . '/printable', 0777, false) && !is_dir($folderprintable)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $folderprintable));
        }
        $cminfo = get_fast_modinfo($this->course)->get_cm($cmprintable->id);
        $files = $this->repository->get_listing($this->versionloaded['path'])['list'];
        foreach ($files as $file) {
            if ($file['title'] === 'printable') {
                continue;
            }
            $filepath = $this->repository->get_file($file['path'])['path'];
            if (!copy($filepath, $folderprintable . '/' . $file['title'])) {
                $this->delete_folder($folderprintable . '/');
                return false;
            }
        }
        $localfiles = scandir($folderprintable . '/');
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
                    ['cmname' => $cminfo->name, 'course' => $cminfo->course]
                )
            );
        }

        $this->unify_files($folderprintable . '/');

        $fs = get_file_storage();
        $context = context_module::instance($cminfo->id)->id;
        foreach ($localfiles as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if ($fs->file_exists($context, 'mod_resource', 'content', 0, '/', $file)) {
                $fs->delete_area_files($context, 'mod_resource', 'content', 0);
            }
            $fileinfo = [
                'component' => 'mod_resource',
                'filearea' => 'content',
                'contextid' => $context,
                'itemid' => 0,
                'filename' => $file,
                'filepath' => '/',
            ];
            $fs->create_file_from_pathname($fileinfo, $folderprintable . '/' . $file);
        }
        $indexhtml = $fs->get_file($context, 'mod_resource', 'content', 0, '/', 'index.html');
        $filepath = file_correct_filepath('/');
        file_reset_sortorder($context, 'mod_resource', 'content', $indexhtml->get_itemid());
        file_set_sortorder($context, 'mod_resource', 'content', $indexhtml->get_itemid(), $filepath, $indexhtml->get_filename(), 1);
        $this->delete_folder($folderprintable . '/');

        $manage_logs = new manage_logs();
        $manage_logs->create_edited($this->course->id, $cmprintable->instance, 'version_printable_applied', null, $this->versionloaded['title']);
        $manage_logs->create_editable($this->cm->instance, $this->course->id, 'editable', $this->versionloaded['title']);
        $manage_logs->update_editable((int)$manage_logs->get_relatedcmid(), $this->versionloaded['title']);
        return true;
    }

    /**
     * @param string $folderprintable
     * @return void
     * @throws DOMException
     */
    private function unify_files(string $folderprintable): void {
        // libxml_use_internal_errors(true);
        $indexdoc = new DOMDocument();
        $indexdoc->loadHTMLFile($folderprintable . 'index.html');
        $nav = $indexdoc->getElementById('siteNav');
        if ($nav !== null && $nav->childNodes) {
            $links = $nav->getElementsByTagName('a');
            $linkshref = [];
            foreach ($links as $link) {
                $linkshref[] = $link->getAttribute('href');
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
        $indexmain = $indexdoc->getElementById('main');
        if ($indexmain !== null && isset($linkshref) && count($linkshref) > 0) {
            $indexhtmlnodes = [];
            foreach ($indexmain->childNodes as $indexChildNode) {
                $indexhtmlnodes[] = $indexChildNode;
            }
            while ($indexmain->hasChildNodes()) {
                $indexmain->removeChild($indexmain->firstChild);
            }
            foreach ($linkshref as $link) {
                if ($link === 'index.html') {
                    foreach ($indexhtmlnodes as $indexchildnode) {
                        $indexnode = $indexdoc->importNode($indexchildnode, true);
                        $indexmain->appendChild($indexnode);
                    }
                } else {
                    $html = new DOMDocument();
                    $html->loadHTMLFile($folderprintable . $link);
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
                }
                $indexdoc->saveHTMLFile($folderprintable . 'index.html');
            }
        }
        $this->manageMetaTags(true, $indexdoc);
        $body = $indexdoc->getElementsByTagName('body')->item(0);
        if ($body !== null) {
            $body->setAttribute('class', $body->getAttribute('class') . ' no-nav');
            $indexdoc->saveHTMLFile($folderprintable . 'index.html');
        }
    }

    /**
     * @param string $path
     * @return void
     */
    private function delete_folder(string $path): void {
        $files = glob($path . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($path)) {
            rmdir($path);
        }
    }

    /**
     * @param bool $applyversion
     * @return void
     * @throws DOMException
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws invalid_persistent_exception
     * @throws moodle_exception
     * @throws repository_exception
     * @throws stored_file_creation_exception
     */
    public function process_resource_links(bool $applyversion = false): void {
        global $DB;
        $DB->delete_records('local_educa_resource_links', ['courseid' => $this->course->id, 'resourceid' => $this->cm->instance, 'version' => $this->versionloaded['title']]);
        $managelogs = new manage_logs();
        $files = $this->repository->get_listing($this->versionloaded['path'])['list'];
        $numfiles = 0;
        $numlinks = 0;
        foreach ($files as $file) {
            if (strpos($file['title'], '.html') !== false) {
                $filepath = $this->repository->get_file($file['path'])['path'];
                $html = new DOMDocument();
                $html->loadHTMLFile($filepath, LIBXML_HTML_NODEFDTD | LIBXML_SCHEMA_CREATE | LIBXML_NOXMLDECL);
                $links = $html->getElementsByTagName('a');
                foreach ($links as $link) {
                    $href = $link->getAttribute('href');
                    $other = new stdClass();
                    $text = $link->nodeValue;
                    $text = str_replace(['<', '>'], '', $text);
                    $other->link_text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
                    $other->link_type = get_string('link', 'local_educaaragon');
                    $this->process_link($href, $other, $file, $link, 'link', $managelogs);
                    if (is_link_external($href)) {
                        $numlinks++;
                    }
                }

                $iframes = $html->getElementsByTagName('iframe');
                foreach ($iframes as $iframe) {
                    $href = $iframe->getAttribute('src');
                    $other = new stdClass();
                    $other->link_type = get_string('iframe', 'local_educaaragon');
                    $this->process_link($href, $other, $file, $iframe, 'iframe', $managelogs);
                    $numlinks++;
                }

                $videos = $html->getElementsByTagName('source');
                foreach ($videos as $video) {
                    $href = $video->getAttribute('src');
                    $other = new stdClass();
                    $other->link_type = get_string('video', 'local_educaaragon');
                    $this->process_link($href, $other, $file, $video, 'video', $managelogs);
                    $numlinks++;
                }

                $html->saveHTMLFile($filepath);
                $numfiles++;
            }
        }
        if ($applyversion) {
            $this->applyversion();
            $this->apllyversionprintable();
        }
        $other = new stdClass();
        $other->numfiles = $numfiles;
        $other->numlinks = $numlinks;
        $other->numlinksactive = $managelogs->get_numlinksactive($this->course->id, $this->cm->instance, $this->versionloaded['title']);
        $other->numlinksfixed = $managelogs->get_numlinksfixed($this->course->id, $this->cm->instance, $this->versionloaded['title']);
        $other->numlinksbroken = $managelogs->get_numlinksbroken($this->course->id, $this->cm->instance, $this->versionloaded['title']);
        $other->numlinksnotvalid = $managelogs->get_numlinksnotvalid($this->course->id, $this->cm->instance, $this->versionloaded['title']);
        $managelogs->create_edited($this->course->id, $this->cm->instance, 'process_resource_links', $other, $this->versionloaded['title']);
    }

    /**
     * @param string $href
     * @param stdClass $other
     * @param array $file
     * @param DOMNode $node
     * @param string $type
     * @param manage_logs $managelogs
     * @return void
     * @throws coding_exception
     * @throws invalid_persistent_exception
     */
    private function process_link(string $href, stdClass $other, array $file, DOMNode $node, string $type, manage_logs $managelogs): void {
        ini_set('default_socket_timeout', 10);
        //$headers = @get_headers($href);
        $headers = $this->get_headers_curl($href);
        // validate url
        if (filter_var($href, FILTER_VALIDATE_URL) === false) {
            // Not validate url
            // validate local content
            if (is_link_external($href)) {
                // no valid, check run
                if (strpos($href, '//') === 0) {
                    $fixhref = 'https:' . $href;
                    //$fixheaders = @get_headers($fixhref);
                    $fixheaders = $this->get_headers_curl($fixhref);
                    if (!$fixheaders || invalid_http_responses($fixheaders[0])) {
                        $managelogs->create_resourcelink(
                            $this->course->id,
                            $this->cm->instance,
                            $this->versionloaded['title'],
                            'link_broken_cantfix',
                            $fixhref,
                            $file['title'],
                            $fixheaders[0],
                            json_encode($other)
                        );
                    } else {
                        if ($type === 'link') {
                            $node->setAttribute('href', $fixhref);
                        }
                        if ($type === 'iframe' || $type === 'video') {
                            $node->setAttribute('src', $fixhref);
                        }
                        $managelogs->create_resourcelink(
                            $this->course->id,
                            $this->cm->instance,
                            $this->versionloaded['title'],
                            'link_fixed',
                            $fixhref,
                            $file['title'],
                            $fixheaders[0],
                            json_encode($other)
                        );
                    }
                } else if (!$headers || invalid_http_responses($headers[0])) {
                    $managelogs->create_resourcelink(
                        $this->course->id,
                        $this->cm->instance,
                        $this->versionloaded['title'],
                        'link_notvalid',
                        $href,
                        $file['title'],
                        $headers[0],
                        json_encode($other)
                    );
                } else {
                    // not valid but run
                    $managelogs->create_resourcelink(
                        $this->course->id,
                        $this->cm->instance,
                        $this->versionloaded['title'],
                        'link_notvalid_active',
                        $href,
                        $file['title'],
                        $headers[0],
                        json_encode($other)
                    );
                }
            }
        } else if (strpos($href, '.swf') !== false || strpos($href, '.flv') !== false || strpos($href, '.f4v') !== false) {
            // validate flash
            $managelogs->create_resourcelink(
                $this->course->id,
                $this->cm->instance,
                $this->versionloaded['title'],
                'link_flash',
                $href, $file['title'],
                $headers[0],
                json_encode($other)
            );
        } else if (!$headers || invalid_http_responses($headers[0])) {
            // validate link active
            if (strpos($href, 'http:') !== false) {
                // validate http
                $fixhref = preg_replace("/^http:/i", 'https:', $href);
                //$fixheaders = @get_headers($fixhref);
                $fixheaders = $this->get_headers_curl($fixhref);
                if (!$fixheaders || invalid_http_responses($fixheaders[0])) {
                    $managelogs->create_resourcelink(
                        $this->course->id,
                        $this->cm->instance,
                        $this->versionloaded['title'],
                        'link_broken_cantfix',
                        $fixhref,
                        $file['title'],
                        $fixheaders[0],
                        json_encode($other)
                    );
                } else {
                    if ($type === 'link') {
                        $node->setAttribute('href', $fixhref);
                    }
                    if ($type === 'iframe' || $type === 'video') {
                        $node->setAttribute('src', $fixhref);
                    }
                    $managelogs->create_resourcelink(
                        $this->course->id,
                        $this->cm->instance,
                        $this->versionloaded['title'],
                        'link_fixed',
                        $fixhref,
                        $file['title'],
                        $fixheaders[0],
                        json_encode($other)
                    );
                }
            } else {
                $managelogs->create_resourcelink(
                    $this->course->id,
                    $this->cm->instance,
                    $this->versionloaded['title'],
                    'link_broken',
                    $href,
                    $file['title'],
                    $headers[0],
                    json_encode($other)
                );
            }
        } else if (strpos($href, 'http:') !== false) {
            // Link valid but http
            $changehttps = preg_replace("/^http:/i", 'https:', $href);
            //$changehttpsheaders = @get_headers($changehttps);
            $changehttpsheaders = $this->get_headers_curl($changehttps);
            if (!$changehttpsheaders || invalid_http_responses($changehttpsheaders[0])) {
                $managelogs->create_resourcelink(
                    $this->course->id,
                    $this->cm->instance,
                    $this->versionloaded['title'],
                    'link_broken_afterchangehttps',
                    $href,
                    $file['title'],
                    $changehttpsheaders[0],
                    json_encode($other)
                );
            } else {
                if ($type === 'link') {
                    $node->setAttribute('href', $changehttps);
                }
                if ($type === 'iframe' || $type === 'video') {
                    $node->setAttribute('src', $changehttps);
                }
                $managelogs->create_resourcelink(
                    $this->course->id,
                    $this->cm->instance,
                    $this->versionloaded['title'],
                    'link_fixed',
                    $changehttps,
                    $file['title'],
                    $changehttpsheaders[0],
                    json_encode($other));
            }
        } else {
            // link valid
            $managelogs->create_resourcelink($this->course->id, $this->cm->instance, $this->versionloaded['title'], 'link_active', $href, $file['title'], $headers[0], json_encode($other));
        }
    }

    /**
     * @param string $url
     * @return array
     */
    private function get_headers_curl(string $url): array {
        $useragent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        /*curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);*/
        $response = curl_exec($ch);
        if ($response === false) {
            return [];
        }
        $headers = [];
        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));
        foreach (explode("\r\n", $header_text) as $i => $line) {
            if ($i === 0) {
                $headers[0] = $line;
            } else {
                [$key, $value] = explode(': ', $line);
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    /**
     * @param bool $forprint
     * @param DOMDocument $indexdoc
     * @return void
     * @throws DOMException
     */
    private function manageMetaTags(bool $forprint, DOMDocument $indexdoc) {
        $heads = $indexdoc->getElementsByTagName('head');
        if ($forprint === true) {
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
        }
        $js = $indexdoc->createElement('script',
    'let navigationEntries = performance.getEntriesByType("navigation");
            let navigationType = navigationEntries.length > 0 ? navigationEntries[0].type : null;
            if (navigationType === "reload") {
                console.log("non-cached version");
            } else {
                window.location.reload(true);
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
                if ($forprint) {
                    $head->appendChild($css);
                }
                $head->appendChild($js);
            }
        } else if ($forprint) {
            foreach ($heads as $head) {
                $head->appendChild($css);
            }
        }
    }

    /* START MANAGE TOC ENGIINE */

    /**
     * @param string $newlist
     * @param string $version
     * @return string
     * @throws repository_exception
     */
    private function processNewNodes(string $newlist, string $version): string {
        $folder = $this->repository->get_rootpath() . 'editions/' . $this->course->shortname . '/' . $this->cm->instance . '/' . $version  . '/';
        $loadhtml = new DOMDocument();
        $loadhtml->loadHTML('<ul>' . $newlist . '</ul>', LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED | LIBXML_NOXMLDECL);
        $xpath = new DOMXPath($loadhtml);
        $newNodes = $xpath->query("//li[contains(@class, 'new-node')]");
        foreach ($newNodes as $childNode) {
            $anchorNode = $childNode->getElementsByTagName('a')->item(0);
            if ($anchorNode) {
                $nodeValue = mb_strtolower(trim($anchorNode->nodeValue), 'UTF-8');
                $baseFilename = $folder . '/' . clean_string(mb_convert_encoding($nodeValue, 'UTF-8')) . '.html';
                $filename = $baseFilename;
                $counter = 1;
                while (file_exists($filename)) {
                    $filename = $folder . '/' . clean_string(mb_convert_encoding($nodeValue, 'UTF-8')) . '_' . $counter . '.html';
                    $counter++;
                }
                $indexFilePath = $folder . '/index.html';
                $newFilePath = $filename;
                if (!copy($indexFilePath, $newFilePath)) {
                    die("Failed to copy $indexFilePath to $newFilePath");
                }
                $newHtml = new DOMDocument();
                $newHtml->loadHTMLFile($newFilePath, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED | LIBXML_NOXMLDECL);
                $mainSection = $newHtml->getElementById('main');
                if ($mainSection) {
                    $header = $newHtml->getElementById('nodeDecoration');
                    if ($header) {
                        $title = $header->getElementsByTagName('h1')->item(0);
                        if ($title) {
                            $title->nodeValue = $nodeValue;
                        }
                    }
                    foreach (iterator_to_array($mainSection->childNodes) as $child) {
                        if ($child->nodeType === XML_ELEMENT_NODE && $child->getAttribute('id') !== 'nodeDecoration') {
                            $mainSection->removeChild($child);
                        }
                    }
                    $mainSection->normalize();
                    $newHtml->saveHTMLFile($newFilePath);
                }
                $anchorNode->setAttribute('href', basename($filename));
            }
            $childNode->removeAttribute('class');
        }
        $output = '';
        foreach ($loadhtml->getElementsByTagName('ul')->item(0)->childNodes as $child) {
            $output .= $loadhtml->saveHTML($child);
        }
        return $output;
    }

    /**
     * @param DOMNode $node
     * @param DOMDocument $doc
     * @param string $currentFileName
     * @return void
     */
    private function processNodeForActive(DOMNode $node, DOMDocument $doc, string $currentFileName): void {
        $this->removeActiveIdRecursively($node);
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                if ($child->nodeName === 'a') {
                    $href = $child->getAttribute('href');
                    if ($href === $currentFileName) {
                        $node->setAttribute('id', 'active');
                        $child->setAttribute('class', 'active');
                    } else {
                        $child->setAttribute('class', '');
                    }
                }
                if ($child->hasChildNodes()) {
                    $this->processNodeForActive($child, $doc, $currentFileName);
                }
            }
        }
    }

    /**
     * @param DOMNode $node
     * @param DOMDocument $doc
     * @param string $currentFileName
     * @return void
     */
    private function processCurrentPageParent(DOMNode $node, DOMDocument $doc, string $currentFileName): void {
        foreach ($node->childNodes as $childNode) {
            if ($childNode->nodeType === XML_ELEMENT_NODE && $childNode->nodeName === 'li') {
                $foundActive = false;
                if ($this->containsActive($childNode)) {
                    $foundActive = true;
                }
                if ($foundActive) {
                    if ($childNode->getAttribute('id') !== 'active') {
                        $existingClass = $childNode->getAttribute('class');
                        if (strpos($existingClass, 'current-page-parent') === false) {
                            $newClass = $existingClass ? $existingClass . ' current-page-parent' : 'current-page-parent';
                            $childNode->setAttribute('class', $newClass);
                        }
                    }
                    $currentUl = $childNode->parentNode && $childNode->parentNode->nodeName === 'ul'
                        ? $childNode->parentNode
                        : null;
                    if ($currentUl) {
                        $parentLi = $currentUl->parentNode && $currentUl->parentNode->nodeName === 'li'
                            ? $currentUl->parentNode
                            : null;
                        if ($parentLi && $parentLi->getAttribute('id') !== 'active') {
                            $existingLiClass = $parentLi->getAttribute('class');
                            if (strpos($existingLiClass, 'current-page-parent') === false) {
                                $newLiClass = $existingLiClass ? $existingLiClass . ' current-page-parent' : 'current-page-parent';
                                $parentLi->setAttribute('class', $newLiClass);
                            }
                            foreach ($parentLi->childNodes as $parentLiChildNode) {
                                if ($parentLiChildNode->nodeType === XML_ELEMENT_NODE && $parentLiChildNode->nodeName === 'a') {
                                    $existingAClass = $parentLiChildNode->getAttribute('class');
                                    if (strpos($existingAClass, 'current-page-parent') === false) {
                                        $newAClass = $existingAClass ? $existingAClass . ' current-page-parent' : 'current-page-parent';
                                        $parentLiChildNode->setAttribute('class', $newAClass);
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            if ($childNode->hasChildNodes()) {
                $this->processCurrentPageParent($childNode, $doc, $currentFileName);
            }
        }
    }

    /**
     * @param DOMNode $node
     * @return bool
     */
    private function containsActive(DOMNode $node): bool {
        if ($node->nodeType === XML_ELEMENT_NODE && $node->nodeName === 'li' && $node->getAttribute('id') === 'active') {
            return true;
        }
        foreach ($node->childNodes as $childNode) {
            if ($this->containsActive($childNode)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param DOMNode $node
     * @param DOMDocument $doc
     * @param string $currentFileName
     * @return void
     */
    private function processDaddyNoch(DOMNode $node, DOMDocument $doc, string $currentFileName): void {
        foreach ($node->childNodes as $childNode) {
            if ($childNode->nodeType === XML_ELEMENT_NODE && $childNode->nodeName === 'a') {
                $hasUlSibling = false;
                foreach ($childNode->parentNode->childNodes as $siblingNode) {
                    if ($siblingNode->nodeType === XML_ELEMENT_NODE && $siblingNode->nodeName === 'ul') {
                        $hasUlSibling = true;
                        break;
                    }
                }
                $existingClass = $childNode->getAttribute('class');
                if ($hasUlSibling && strpos($childNode->getAttribute('class'), 'daddy') === false) {
                    $newClass = $existingClass ? $existingClass . ' daddy' : 'daddy';
                    $childNode->setAttribute('class', $newClass);
                } else if (!$hasUlSibling && strpos($childNode->getAttribute('class'), 'no-ch') === false) {
                    $newClass = $existingClass ? $existingClass . ' no-ch' : 'no-ch';
                    $childNode->setAttribute('class', $newClass);
                }
            }
            if ($childNode->hasChildNodes()) {
                $this->processDaddyNoch($childNode, $doc, $currentFileName);
            }
        }
    }

    /**
     * @param DOMNode $node
     * @param DOMDocument $doc
     * @param string $currentFileName
     * @return void
     */
    private function processOtherSection(DOMNode $node, DOMDocument $doc, string $currentFileName): void {
        foreach ($node->childNodes as $childNode) {
            if ($childNode->nodeType === XML_ELEMENT_NODE && $childNode->nodeName === 'a') {
                $hasDaddyClass = strpos($childNode->getAttribute('class'), 'daddy') !== false;
                $isActive = strpos($childNode->getAttribute('class'), 'active') !== false;
                foreach ($childNode->parentNode->childNodes as $nestedChildNode) {
                    if ($nestedChildNode->nodeType === XML_ELEMENT_NODE && $nestedChildNode->nodeName === 'ul') {
                        if ($hasDaddyClass && !$isActive) {
                            $existingClass = $nestedChildNode->getAttribute('class');
                            $newClass = $existingClass ? $existingClass . ' other-section' : 'other-section';
                            $nestedChildNode->setAttribute('class', $newClass);
                        }
                        $this->processOtherSection($nestedChildNode, $doc, $currentFileName);
                    }
                }
            } elseif ($childNode->nodeType === XML_ELEMENT_NODE && $childNode->nodeName === 'ul') {
                if (strpos($node->getAttribute('class'), 'other-section') !== false) {
                    $existingClass = $childNode->getAttribute('class');
                    $newClass = $existingClass ? $existingClass . ' other-section' : 'other-section';
                    $childNode->setAttribute('class', $newClass);
                }
                $this->processOtherSection($childNode, $doc, $currentFileName);
            }
            if ($childNode->hasChildNodes()) {
                $this->processOtherSection($childNode, $doc, $currentFileName);
            }
        }
    }


    /**
     * @param DOMNode $node
     * @return void
     */
    private function removeClassesForActiveLinks(DOMNode $node): void {
        foreach ($node->childNodes as $childNode) {
            if ($childNode->nodeType === XML_ELEMENT_NODE && $childNode->nodeName === 'a') {
                if (strpos($childNode->getAttribute('class'), 'active') !== false) {
                    $this->removeClassFromContainedUl($childNode);
                    $this->removeClassFromAncestorUl($childNode);
                    $this->removeClassFromSiblingUl($childNode);
                }
            }
            if ($childNode->hasChildNodes()) {
                $this->removeClassesForActiveLinks($childNode);
            }
        }
    }

    /**
     * @param DOMNode $node
     * @return void
     */
    private function removeClassFromContainedUl(DOMNode $node): void {
        foreach ($node->childNodes as $childNode) {
            if ($childNode->nodeType === XML_ELEMENT_NODE && $childNode->nodeName === 'ul') {
                $childNode->removeAttribute('class');
            }
            if ($childNode->hasChildNodes()) {
                $this->removeClassFromContainedUl($childNode);
            }
        }
    }

    /**
     * @param DOMNode $node
     * @return void
     */
    private function removeClassFromAncestorUl(DOMNode $node): void {
        $parentNode = $node->parentNode;
        while ($parentNode && $parentNode->nodeType === XML_ELEMENT_NODE) {
            if ($parentNode->nodeName === 'ul') {
                $parentNode->removeAttribute('class');
            }
            $parentNode = $parentNode->parentNode;
        }
    }

    /**
     * @param DOMNode $node
     * @return void
     */
    private function removeClassFromSiblingUl(DOMNode $node): void {
        $siblingNode = $node->nextSibling;
        while ($siblingNode) {
            if ($siblingNode->nodeType === XML_ELEMENT_NODE && $siblingNode->nodeName === 'ul') {
                $siblingNode->removeAttribute('class');
            }
            $siblingNode = $siblingNode->nextSibling;
        }
    }

    /**
     * @param DOMNode $node
     * @return void
     */
    private function removeActiveIdRecursively(DOMNode $node): void {
        if ($node->nodeType === XML_ELEMENT_NODE && $node->hasAttribute('id') && $node->getAttribute('id') === 'active') {
            $node->removeAttribute('id');
        }
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $this->removeActiveIdRecursively($child);
            }
        }
    }

    /* END MANAGE TOC ENGIINE */

    /**
     * @param DOMElement $newul
     * @param DOMDocument $doc
     * @param string $filepath
     * @return void
     * @throws DOMException
     */
    private function managePagination(DOMElement $newul, DOMDocument $doc, string $filepath): void {
        $toppagination = $doc->getElementById('topPagination');
        if ($toppagination !== null) {
            $this->processPagination($toppagination, $newul, $doc, $filepath);
        }
        $bottompagination = $doc->getElementById('bottomPagination');
        if ($bottompagination !== null) {
            $this->processPagination($bottompagination, $newul, $doc, $filepath);
        }
    }

    /**
     * @param DOMElement $paginationElement
     * @param DOMElement $newul
     * @param DOMDocument $doc
     * @param string $filepath
     * @return void
     * @throws DOMException
     */
    private function processPagination(DOMElement $paginationElement, DOMElement $newul, DOMDocument $doc, string $filepath): void {
        $currentLink = null;
        $previousLink = null;
        $nextLink = null;
        $allLinks = $newul->getElementsByTagName('a');
        $totalPages = $allLinks->length;
        $currentPage = 1;
        for ($i = 0; $i < $allLinks->length; $i++) {
            $link = $allLinks->item($i);
            if ($link !== null && strpos($link->getAttribute('href'), basename($filepath)) !== false) {
                $currentLink = $link;
                $currentPage = $i + 1;
                if ($i + 1 < $allLinks->length) {
                    $nextLink = $allLinks->item($i + 1);
                }
                break;
            }
            $previousLink = $link;
        }
        if (!$currentLink && $allLinks->length > 0) {
            $nextLink = $allLinks->item(1);
        }
        $paginationNav = null;
        foreach ($paginationElement->getElementsByTagName('*') as $element) {
            if (strpos($element->getAttribute('class'), 'pagination') !== false) {
                $paginationNav = $element;
                break;
            }
        }
        if (!$paginationNav) {
            return;
        }
        $prevLink = null;
        $nextLinkElement = null;
        $pageCounterElement = null;
        foreach ($paginationNav->getElementsByTagName('a') as $a) {
            if (strpos($a->getAttribute('class'), 'prev') !== false) {
                $prevLink = $a;
            }
            if (strpos($a->getAttribute('class'), 'next') !== false) {
                $nextLinkElement = $a;
            }
        }
        foreach ($paginationNav->getElementsByTagName('span') as $span) {
            if (strpos($span->getAttribute('class'), 'page-counter') !== false) {
                $pageCounterElement = $span;
                break;
            }
        }
        if ($previousLink) {
            if ($prevLink !== null) {
                $prevLink->setAttribute('href', $previousLink->getAttribute('href'));
                $prevLink->setAttribute('class', 'prev');
                $prevLink->textContent = '« Anterior';
            } else {
                $newPrevLink = $doc->createElement('a', '« Anterior');
                $newPrevLink->setAttribute('href', $previousLink->getAttribute('href'));
                $newPrevLink->setAttribute('class', 'prev');
                if ($paginationNav->firstChild) {
                    $paginationNav->insertBefore($newPrevLink, $paginationNav->firstChild);
                } else {
                    $paginationNav->appendChild($newPrevLink);
                }
            }
        }
        if ($pageCounterElement !== null) {
            while ($pageCounterElement->firstChild) {
                $pageCounterElement->removeChild($pageCounterElement->firstChild);
            }
            $pageCounterElement->appendChild($doc->createTextNode('Página '));
            $currentStrong = $doc->createElement('strong', $currentPage);
            $pageCounterElement->appendChild($currentStrong);
            $pageCounterElement->appendChild($doc->createTextNode(' de '));
            $totalStrong = $doc->createElement('strong', $totalPages);
            $pageCounterElement->appendChild($totalStrong);
            if ($prevLink && $prevLink->nextSibling !== $pageCounterElement) {
                $paginationNav->insertBefore($pageCounterElement, $prevLink->nextSibling);
            }
        }
        if ($nextLink) {
            if ($nextLinkElement !== null) {
                $nextLinkElement->setAttribute('href', $nextLink->getAttribute('href'));
                $nextLinkElement->setAttribute('class', 'next');
                $nextLinkElement->textContent = 'Siguiente »';
            } else {
                $newNextLink = $doc->createElement('a', 'Siguiente »');
                $newNextLink->setAttribute('href', $nextLink->getAttribute('href'));
                $newNextLink->setAttribute('class', 'next');
                $paginationNav->appendChild($newNextLink);
            }
        } else {
            if ($nextLinkElement !== null) {
                $paginationNav->removeChild($nextLinkElement);
            }
        }
        if ($pageCounterElement && $pageCounterElement->nextSibling instanceof DOMElement && strpos($pageCounterElement->nextSibling->getAttribute('class'), 'sep') === false) {
            $sepAfterCounter = $doc->createElement('span', '| ');
            $sepAfterCounter->setAttribute('class', 'sep');
            $paginationNav->insertBefore($sepAfterCounter, $pageCounterElement->nextSibling);
        }
    }
}
