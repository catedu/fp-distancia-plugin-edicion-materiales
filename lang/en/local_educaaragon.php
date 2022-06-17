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

$string['pluginname'] = 'Educa Aragón';
$string['educaaragon:manageall'] = 'Manage plugin local_educaaragon';
$string['educaaragon:editresources'] = 'Edit editable resources in the course';

$string['generalconfig'] = 'General configuration';
$string['activetask'] = 'Activate scheduled task to transform resources';
$string['activetask_desc'] = 'If enabled, a scheduled Moodle cron job will run through the courses looking for SCORM and IMS content to transform it.';
$string['repository'] = 'Content repository';
$string['repository_desc'] = 'Select the repository of type "filesystem" where all the dynamic contents in HTML format are stored. If none exists, you will have to create one and store the contents in it. The contents of each course should be stored in folders named with the short name of the course to make the relation.';
$string['no_repository_exists'] = 'There is no filesystem repository. A repository with HTML course contents is needed. Consult a developer.';
$string['no_repository_select'] = 'No repository is selected in the plugin configuration. Select a repository before you can run the task.';
$string['allcourses'] = 'Apply to all courses';
$string['allcourses_desc'] = 'If enabled, the scheduled task will apply to all courses on the platform';
$string['category'] = 'Category';
$string['category_desc'] = 'Select the category where the SCORMS and IMS transformation to resources will be applied. All courses contained in this category will be affected, including those in sub-categories.';
$string['transformdynamiccontent'] = 'Dynamic content transformation task';
$string['course_processed'] = 'Course processed. Time spent: ';
$string['memory_used'] = 'Memory used: ';
$string['allcourses_processed'] = 'All courses processed. Time spent: ';
$string['printable'] = 'printable';

$string['transform_dynamic_content_desc'] = 'Task to transform SCORMS and IMS into HTML resources and their print version. After passing this task, the affected course contents can be edited.';
$string['notactivetask'] = 'Scheduled task has been deactivated from configuration. No course will be modified.';
$string['coursesfound'] = 'To be processed {$a} courses';
$string['processcourse'] = 'Processing course {$a->shortname} with ID {$a->courseid}';
$string['errorprocesscourse'] = 'Error processing course. Check the contents of the course in the repository';
$string['errorprocesscourse_desc'] = 'Error processing course {$a->course}: {$a->error}';
$string['error/invalidpersistenterror'] = 'There are invalid character errors in the links<br>error/invalidpersistenterror';
$string['error/invalidfilerequested'] = 'There are resources that contain directories, or invalid files in their content<br>error/invalidfilerequested';
$string['dynamiccontent_found'] = 'Found {$a} dynamic contents';
$string['no_resourcegenerator'] = 'There is no resource generator in this environment, so the task cannot continue. Contact a developer.';
$string['no_associated_folder'] = 'There is no folder associated with the course {$a->course} in the repository {$a->repository}';
$string['elements_does_not_match'] = 'The number of dynamic resources in the course {$a->course} does not match the number of associated resources in the repository {$a->repository}. This process will not change anything in the course until this is resolved.';
$string['elements_cant_associate'] = 'Could not associate the contents of the course {$a->course} with the contents of the repository {$a->repository}. Please check the titles of the resources and the nomenclature of the repository content. The numbering should be 01, 02, 03, etc.';
$string['error_copy_files'] = 'Error copying course files {$a->course}. Origin: {$a->origen} - Destination: {$a->destiny}. Resolve this before re-running the task.';
$string['no_index_file'] = 'No index.html file found on the resource {$a->cmname} of the course {$a->course}. The process will not continue for this course.';
$string['correctly_processed'] = 'Correctly processed course';
$string['correctly_processed_needassociation'] = 'Course processed correctly. Needs manual sorting of editable resources';
$string['selected_for_reprocessing'] = 'Selected to be reprocessed at the next execution of the task';
$string['resource_deleted'] = 'One or more editable resources have been deleted from this course. Reprocessing is recommended.';
$string['processresource'] = 'Content created in progress ';
$string['processlink'] = 'Resource link processing ';

// Tables
$string['processedcourses'] = 'Processed courses';
$string['processedcourses_help'] = 'List of courses processed by the task <b>local_educaaragon\task\transform_dynamic_content</b>.<br>From this panel you can manage the courses that you need to be reprocessed in the next execution of the task.';
$string['courseid'] = 'Course ID';
$string['coursename'] = 'Full name';
$string['shortname'] = 'Short name';
$string['processed'] = 'Processed';
$string['message'] = 'Message';
$string['usermodified'] = 'User';
$string['timemodified'] = 'Date of modification';
$string['actions'] = 'Actions';
$string['reprocessing'] = 'Reprocess course on next run';
$string['reprocessingmsg'] = '<p>This action will mark this course for reprocessing at the next execution of the scheduled task <b>local_educaaragon\task\transform_dynamic_content</b>.</p><h4>ATTENTION!</h4><h5>Please note that marking this course for re-processing will remove the resources that were previously generated by the task, to avoid duplication.</h5>';
$string['reprocess'] = 'Reprocess';
$string['editableresources'] = 'Show list of generated editable resources';
$string['editables'] = 'Editable resources';
$string['editables_help'] = 'List of resources available for editing.<br>You can filter the results by course by adding the "courseid" parameter to the url.';
$string['resourceid'] = 'Resource ID';
$string['resourcename'] = 'Name of the resource';
$string['viewcourse'] = 'View course';
$string['backversions'] = 'Back to version selector';
$string['relatedcmid'] = 'Related resource';
$string['revieweditableresource'] = 'View resource';
$string['editresource'] = 'Edit resource';
$string['viewprintresource'] = 'View printable version';

// Edit resource
$string['editingresource'] = 'Editing resource';
$string['resourcenoteditable'] = 'This resource is not editable';
$string['versionnoteditable'] = 'This version cannot be edited. Select a different version.';
$string['selectversion'] = 'Select the version';
$string['selectsection'] = 'Select the section to edit';
$string['createnewversion'] = 'Create a new version';
$string['createnewversion_desc'] = 'Are you sure you want to create a new version to edit?<br>The name you have given to the version will be modified to remove special characters and replace spaces with -. If you have left it empty, the date will be set in Unix format as the version name.';
$string['confirm'] = 'Confirm';
$string['versionname'] = 'Name';
$string['loadversion'] = 'Editar version';
$string['deleteversion'] = 'Delete version';
$string['deleteversion_desc'] = 'Are you sure you want to delete the selected version?<br>Please note that if this version is applied for display, it will still be shown to users even if you delete it. To fix this, apply another version';
$string['asofversion'] = 'as of version';
$string['versionalreadyexist'] = 'A version with that name already exists';
$string['errorcreateversion'] = 'An error occurred while creating a new version. Check that the name is not repeated or contains special characters and try again by reloading this page. If the problem persists, please contact an administrator.';
$string['save_changes'] = 'Save changes';
$string['save_changes_desc'] = 'Are you sure you want to save the changes applied to this version? The changes will be saved on the version, they will not be applied to the existing resource in the course..';
$string['changes_saved'] = 'Changes saved correctly: ';
$string['not_saved'] = 'The changes could not be saved, please try again: ';
$string['apply_version'] = 'Apply version';
$string['apply_version_desc'] = 'Are you sure you want to apply the version that is selected to the resource that students will see?';
$string['version_saved'] = 'The version has been applied correctly: ';
$string['version_not_saved'] = 'The version could not be applied, please try again: ';
$string['versionprintable_saved'] = 'The print version has been applied correctly from the edited version: ';
$string['versionprintable_not_saved'] = 'The print version could not be applied, please try again: ';

// Edited resource
$string['registereditions'] = 'Register of editions';
$string['version_created'] = 'New version created';
$string['version_created_asofversion'] = 'Creada a partir de la versión: ';
$string['version_deleted'] = 'Deleted version';
$string['version_changes_saved'] = 'Saved changes';
$string['version_changes_saved_file'] = 'Affected file: ';
$string['version_applied'] = 'Version applied to the resource';
$string['version_printable_applied'] = 'Printable version applied to the resource';
$string['version_original_created'] = 'Original version created';
$string['action'] = 'Event';
$string['other'] = 'Additional information';
$string['version'] = 'Version';
$string['edit_comments'] = 'Editor\'s comments: ';
$string['write_comment'] = 'Additional information on the edition: ';

// Links
$string['link_report'] = 'Link report';
$string['link_report_desc'] = 'In this report you can see information about the links contained in a particular version of an editable resource.';
$string['processresourcelinks'] = 'Searching for broken links and flash content in resources';
$string['link_case'] = 'Case';
$string['link'] = 'Link';
$string['video'] = 'Video';
$string['iframe'] = 'Iframe';
$string['file'] = 'File';
$string['link_type'] = 'Type of link:';
$string['link_text'] = 'Link text: ';
$string['link_active'] = 'Active link';
$string['link_broken'] = 'Broken link';
$string['link_broken_cantfix'] = 'Broken link. Not solved with https';
$string['link_fixed'] = 'Link fixed with https';
$string['link_flash'] = 'Flash content';
$string['link_notvalid'] = 'The URL appears to be invalid, and does not work';
$string['link_notvalid_active'] = 'The URL looks invalid, but it works';
$string['link_youtube'] = 'Valid youtube link';
$string['link_youtube_fixed'] = 'Youtube link fixed';
$string['link_youtube_broken'] = 'Broken youtube link';
$string['showactivelinks'] = 'Show active links';
$string['hideactivelinks'] = 'Hide active links';
$string['link_broken_afterchangehttps'] = 'Broken link after applying https, works with http';
$string['process_resource_links'] = 'Processed resource links';
$string['process_version_links'] = 'Process version links';
$string['process_version_links_desc'] = 'All links in this version will be processed to detect or fix links that do not work.<br>When the process is finished, you will be redirected to the link report for this version, but the version will not be applied and you will have to apply it manually when you review it.<br>This process can take several minutes, and will not stop even if you close the tab (if you close the tab you will not be redirected when it finishes).<br>All previously generated link processing records for this version will be deleted for re-creation.<h5>Are you sure you can process the links for this version?</h5>';
$string['view_version_links'] = 'View record of processed links';
$string['processed_resource_links'] = 'Links processed correctly: ';
$string['not_processed_resource_links'] = 'The links could not be processed, please try again later: ';
$string['numfiles'] = 'Files: ';
$string['numlinks'] = 'Links: ';
$string['numlinksactive'] = 'Assets: ';
$string['numlinksfixed'] = 'Fixed: ';
$string['numlinksbroken'] = 'Broken: ';
$string['numlinksnotvalid'] = 'No valid: ';
$string['numprocessed'] = 'Processed: ';
$string['numprocessedcorrectly'] = 'Correct: ';
$string['numprocessederror'] = 'Errors: ';
$string['numprocessedwarning'] = 'No folder: ';
$string['nofolder'] = 'No folder';
