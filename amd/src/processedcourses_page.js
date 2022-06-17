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

define([
    'jquery',
    'core/str',
    'core/ajax',
    'core/modal_factory',
    'core/modal_events',
    'core/templates'
], function($, Str, Ajax, ModalFactory, ModalEvents, Templates) {
    "use strict";

    /**
     *
     * @property {string} REPROCESSING_COURSE - Selector for select course for reprocessing
     */
    let ACTION = {
        REPROCESSING_COURSE: '[data-action="reprocessing_course"]',
    };

    /**
     *
     * @property {string} PROCESSEDTABLE - Service for generate Table
     * @property {string} REPROCESSING - Service for reprocessing course
     */
    let SERVICES = {
        PROCESSEDTABLE: 'local_educaaragon_processed_table',
        REPROCESSING: 'local_educaaragon_reprocessing'
    };

    /**
     *
     * @property {string} CONTENT_TABLE - Region for all reload
     * @property {string} TABLE - Region for table reload
     */
    let REGION = {
        CONTENT_TABLE: '[data-region="educaaragon-processedcourses"]',
        TABLE: '[data-region="processedcourses-table"]'
    };

    let TEMPLATES = {
        TABLE: 'local_educaaragon/processedcourses_table',
        LOADING: 'core/overlay_loading'
    };

    /**
     * @constructor
     * @param {String} selector The selector for the page region containing the page.
     */
    function Table(selector) {
        this.node = $(selector);
        this.initReprocessingCourse();
    }

    /** @type {jQuery} The jQuery node for the page region. */
    Table.prototype.node = null;

    /**
     * Register event listeners of clicks.
     */
    Table.prototype.initReprocessingCourse = function() {
        this.node.find(ACTION.REPROCESSING_COURSE).on('click', this.reprocessingCourse);
    };

    Table.prototype.reprocessingCourse = function(e) {
        e.preventDefault();
        e.stopPropagation();
        let courseid = $(e.currentTarget).attr('data-courseid');
        let stringkeys = [
            {key: 'reprocessing', component: 'local_educaaragon'},
            {key: 'reprocessingmsg', component: 'local_educaaragon'},
            {key: 'reprocess', component: 'local_educaaragon'}
        ];
        Str.get_strings(stringkeys).then(function(langStrings) {
            let title = langStrings[0];
            let confirmMessage = langStrings[1];
            let buttonText = langStrings[2];
            return ModalFactory.create({
                title: title,
                body: confirmMessage,
                type: ModalFactory.types.SAVE_CANCEL
            }).then(function(modal) {
                modal.setSaveButtonText(buttonText);
                modal.getRoot().on(ModalEvents.save, function() {
                    let identifier = $(REGION.TABLE);
                    Templates.render(TEMPLATES.LOADING, {visible: true}).done(function(html) {
                        identifier.append(html);
                        let request = {
                            methodname: SERVICES.REPROCESSING,
                            args: {
                                courseid: courseid
                            }
                        };
                        Ajax.call([request])[0].done(function() {
                            let request = {
                                methodname: SERVICES.PROCESSEDTABLE,
                                args: {
                                }
                            };
                            Ajax.call([request])[0].done(function(response) {
                                let template = TEMPLATES.TABLE;
                                Templates.render(template, response).done(function(html, js) {
                                    identifier.html(html);
                                    Templates.runTemplateJS(js);
                                    location.reload();
                                });
                            }).fail(Notification.exception);
                        }).fail(Notification.exception);
                    });
                });
                modal.getRoot().on(ModalEvents.hidden, function() {
                    modal.destroy();
                });
                return modal;
            });
        }).done(function(modal) {
            modal.show();
        }).fail(Notification.exception);
    };

    return {
        /**
         * Factory method returning instance of the Table
         * @param {String} selector The selector for the table region containing the table.
         * @return {Table}
         */
        initClicks: function(selector) {
            return new Table(selector);
        }
    };
});
