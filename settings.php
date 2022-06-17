<?php
// This file is part of Moodle Workplace https://moodle.com/workplace based on Moodle
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
//
// Moodle Workplace Code is dual-licensed under the terms of both the
// single GNU General Public Licence version 3.0, dated 29 June 2007
// and the terms of the proprietary Moodle Workplace Licence strictly
// controlled by Moodle Pty Ltd and its certified premium partners.
// Wherever conflicting terms exist, the terms of the MWL are binding
// and shall prevail.

/**
 * @package local_educaaragon
 * @author 3iPunt <https://www.tresipunt.com/>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 3iPunt <https://www.tresipunt.com/>
 */

use core\notification;

defined('MOODLE_INTERNAL') || die;
global $ADMIN, $CFG;

$ADMIN->add('courses', new admin_category('local_educaaragon', get_string('pluginname', 'local_educaaragon')));

$context = context_system::instance();
$settings = new theme_boost_admin_settingspage_tabs('localsettingeducaaragon', get_string('pluginname', 'local_educaaragon'), 'local/educaaragon:manageall', false, $context);
$page = new admin_settingpage('local_educaaragon_general', get_string('generalsettings', 'theme_boost'), 'local/educaaragon:manageall', false, $context);

if ($hassiteconfig) {
    $setting = new admin_setting_heading('local_educaaragon_generalconfig_header', get_string('generalconfig', 'local_educaaragon'), '');
    $page->add($setting);

    $setting = new admin_setting_configcheckbox('local_educaaragon/activetask', get_string('activetask', 'local_educaaragon'), get_string('activetask_desc', 'local_educaaragon'), 0);
    $page->add($setting);

    /** @var repository_filesystem[] $instances */
    $instances = repository::get_instances(['type' => 'filesystem']);
    if (empty($instances)) {
        notification::error(get_string('no_repository_exists', 'local_educaaragon'));
    } else {
        $repositories = [];
        foreach ($instances as $instance) {
            $repositories[$instance->id] = $instance->get_name();
        }
        $setting = (new admin_setting_configselect('local_educaaragon/repository', get_string('repository', 'local_educaaragon'), get_string('repository_desc', 'local_educaaragon'), 0, $repositories));
        $page->hide_if('local_educaaragon/repository', 'local_educaaragon/activetask');
        $page->add($setting);
    }

    $setting = new admin_setting_configcheckbox('local_educaaragon/allcourses', get_string('allcourses', 'local_educaaragon'), get_string('allcourses_desc', 'local_educaaragon'), 0);
    $page->hide_if('local_educaaragon/allcourses', 'local_educaaragon/activetask');
    $page->add($setting);

    $cats = core_course_category::get_all();
    $choices = [];
    foreach ($cats as $cat) {
        $parent = empty( $cat->get_parent_coursecat()->name) ? '' : $cat->get_parent_coursecat()->name . '/';
        $choices[$cat->id] = $parent . $cat->name;
    }

    $setting = (new admin_setting_configselect('local_educaaragon/category', get_string('category', 'local_educaaragon'), get_string('category_desc', 'local_educaaragon'), 1, $choices));
    $page->hide_if('local_educaaragon/category', 'local_educaaragon/activetask');
    $page->hide_if('local_educaaragon/category', 'local_educaaragon/allcourses', 'checked');
    $page->add($setting);

    $settings->add($page);

    $ADMIN->add('local_educaaragon', $page);
}

$ADMIN->add('local_educaaragon', new admin_externalpage('processedcourses',
    get_string('processedcourses', 'local_educaaragon'),
    "$CFG->wwwroot/local/educaaragon/processedcourses.php", 'local/educaaragon:manageall', false, $context));
$ADMIN->add('local_educaaragon', new admin_externalpage('editables',
    get_string('editables', 'local_educaaragon'),
    "$CFG->wwwroot/local/educaaragon/editables.php", 'local/educaaragon:manageall', false, $context));
$ADMIN->add('local_educaaragon', new admin_externalpage('registereditions',
    get_string('registereditions', 'local_educaaragon'),
    "$CFG->wwwroot/local/educaaragon/registereditions.php", 'local/educaaragon:manageall', false, $context));
$ADMIN->add('local_educaaragon', new admin_externalpage('editresource',
    get_string('editingresource', 'local_educaaragon'),
    "$CFG->wwwroot/local/educaaragon/editresource.php", ['moodle/site:config'], true));
$ADMIN->add('local_educaaragon', new admin_externalpage('resourcelinks',
    get_string('link_report', 'local_educaaragon'),
    "$CFG->wwwroot/local/educaaragon/resourcelinks.php", ['moodle/site:config'], true));
