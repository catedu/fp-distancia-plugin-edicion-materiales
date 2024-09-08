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
    'core/templates',
    'core/notification'
], function($, Str, Ajax, ModalFactory, ModalEvents, Templates, Notification) {
    "use strict";
    /* eslint-disable no-console */
    let ACTION = {
        CREATEVERSION: '[data-action="createversion"]',
        DELETEVERSION: '[data-action="deleteversion"]',
        LOADVERSION: '[data-action="loadversion"]',
        EDITTOC: '[data-action="edittoc"]',
        SAVECHANGES: '[data-action="save-changes"]',
        APPLYVERSION: '[data-action="apply-version"]',
        CHANGEVERSION: '[data-action="change-version"]',
        PROCESSVERSIONLINKS: '[data-action="process-version-links"]',
        VIEWVERSIONLINKS: '[data-action="viewversionlinks"]',
        VIEWVERSIONLINKSEDIT: '[data-action="viewversionlinks_link"]'
    };

    let SERVICES = {
        CREATEVERSION: 'local_educaaragon_createversion',
        DELETEVERSION: 'local_educaaragon_deleteversion',
        SAVECHANGES: 'local_educaaragon_savechanges',
        APPLYVERSION: 'local_educaaragon_applyversion',
        PROCESSVERSIONLINKS: 'local_educaaragon_processresourcelinks'
    };

    let REGION = {
        CONTENT_CONTROLS: '[data-region="educaaragon-editresource"]',
        CONTENT_ATTO_HTML: '.editor_atto_content',
        OVERLAY: '[data-region="overlay-icon-container"]',
        HASCHANGES: '.haschanges'
    };

    let TEMPLATES = {
        LOADING: 'core/overlay_loading',
        SUCCESS: 'core/notification_success',
        ERROR: 'core/notification_error'
    };

    /**
     * @constructor
     * @param {String} selector The selector for the page region containing the page.
     */
    function EditResource(selector) {
        this.node = $(selector);
        this.initEditResource();
    }

    let hasChanges = false;
    let hasChangesStringsKeys = [
        {key: 'changesnotsaved', component: 'local_educaaragon'},
        {key: 'changesnotsaved_desc', component: 'local_educaaragon'},
        {key: 'savechanges', component: 'local_educaaragon'}
    ];
    let hasChangesStrings;
    let modalChanges;

    /** @type {jQuery} The jQuery node for the page region. */
    EditResource.prototype.node = null;

    EditResource.prototype.initEditResource = function() {
        this.node.find(ACTION.CREATEVERSION).on('click', this.createVersion);
        this.node.find(ACTION.DELETEVERSION).on('click', this.deleteVersion);
        this.node.find(ACTION.LOADVERSION).on('click', this.loadVersion);
        this.node.find(ACTION.EDITTOC).on('click', this.editToc);
        this.node.find(ACTION.SAVECHANGES).on('click', this.saveChanges);
        this.node.find(ACTION.APPLYVERSION).on('click', this.applyVersion);
        this.node.find(ACTION.PROCESSVERSIONLINKS).on('click', this.processVersionLinks);
        this.node.find(ACTION.VIEWVERSIONLINKS).on('click', this.viewVersionLinks);
        this.changeVersion();
        $(document).on('change', ACTION.CHANGEVERSION, this.changeVersion);
        this.createModalChanges();
        document.querySelector(REGION.CONTENT_ATTO_HTML).addEventListener('input', function() {
            hasChanges = true;
            $(REGION.HASCHANGES).css('display', 'block');
        });
        $('.editor_atto_toolbar [type="button"]:not(.atto_collapse_button)').on('click', function(){
            hasChanges = true;
            $(REGION.HASCHANGES).css('display', 'block');
        });
        document.querySelectorAll(
            'a:not([target="_blank"]), .content-controls button:not([data-action="save-changes"])'
        ).forEach(link => {
            link.addEventListener('click', (e) => {
                if (hasChanges) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                EditResource.prototype.detectChanges(e);
            });
        });

        window.onbeforeunload = function(e) {
            EditResource.prototype.detectChanges(e);
            if (hasChanges) {
                e.preventDefault();
            }
        };
    };

    EditResource.prototype.createVersion = function(e) {
        e.preventDefault();
        e.stopPropagation();
        let courseid = $(e.currentTarget).attr('data-courseid');
        let resourceid = $(e.currentTarget).attr('data-resourceid');
        let versionname = $('#newversion').val();
        let asofversion = $('[data-region="select-asofversion"]').val();
        if (asofversion === undefined) {
            asofversion = 'original';
        }
        let stringkeys = [
            {key: 'createnewversion', component: 'local_educaaragon'},
            {key: 'createnewversion_desc', component: 'local_educaaragon'},
            {key: 'confirm', component: 'local_educaaragon'},
            {key: 'errorcreateversion', component: 'local_educaaragon'}
        ];
        Str.get_strings(stringkeys).then(function(langStrings) {
            let title = langStrings[0];
            let confirmMessage = langStrings[1];
            let buttonText = langStrings[2];
            let error = langStrings[3];
            return ModalFactory.create({
                title: title,
                body: confirmMessage,
                type: ModalFactory.types.SAVE_CANCEL
            }).then(function(modal) {
                modal.setSaveButtonText(buttonText);
                modal.getRoot().on(ModalEvents.save, function() {
                    let request = {
                        methodname: SERVICES.CREATEVERSION,
                        args: {
                            courseid: courseid,
                            resourceid: resourceid,
                            versionname: versionname,
                            asofversion: asofversion,
                        }
                    };
                    Templates.render(TEMPLATES.LOADING, {visible: true}).done(function(html) {
                        let identifier = $('body');
                        identifier.append(html);
                        Ajax.call([request])[0].done(function(response) {
                            if (response.response === true) {
                                let url = window.location.href;
                                if (url.indexOf('?') > -1) {
                                    url += '&version=' + response.versionname;
                                } else {
                                    url += '?version=' + response.versionname;
                                }
                                window.location.href = url;
                            }
                            if (response.response === false) {
                                identifier.html('<div class="alert alert-danger">' + error + '</div>');
                            }
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

    EditResource.prototype.deleteVersion = function(e) {
        e.preventDefault();
        e.stopPropagation();
        let courseid = $(e.currentTarget).attr('data-courseid');
        let resourceid = $(e.currentTarget).attr('data-resourceid');
        let versionname = $('[data-region="select-version"]').val();
        let stringkeys = [
            {key: 'changesnotsaved', component: 'local_educaaragon'},
            {key: 'changesnotsaved_desc', component: 'local_educaaragon'},
            {key: 'confirm', component: 'local_educaaragon'}
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
                    let request = {
                        methodname: SERVICES.DELETEVERSION,
                        args: {
                            courseid: courseid,
                            resourceid: resourceid,
                            versionname: versionname
                        }
                    };
                    Templates.render(TEMPLATES.LOADING, {visible: true}).done(function(html) {
                        let identifier = $('body');
                        identifier.append(html);
                        Ajax.call([request])[0].done(function(response) {
                            if (response.response === true) {
                                location.reload();
                            }
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

    EditResource.prototype.loadVersion = function(e) {
        e.preventDefault();
        e.stopPropagation();
        let versionname = $('[data-region="select-version"]').val();
        let url = window.location.href;
        if (url.indexOf('?') > -1) {
            url += '&version=' + versionname;
        } else {
            url += '?version=' + versionname;
        }
        window.location.href = url;
    };

    EditResource.prototype.editToc = function(e) {
        e.preventDefault();
        e.stopPropagation();
        let versionname = $('[data-region="select-version"]').val();
        let url = window.location.href;
        url = url.replace('editresource.php', 'editresourcetoc.php');
        if (url.indexOf('?') > -1) {
            url += '&version=' + versionname;
        } else {
            url += '?version=' + versionname;
        }
        window.location.href = url;
    };

    EditResource.prototype.saveChanges = function(e) {
        e.preventDefault();
        e.stopPropagation();
        let courseid = $(e.currentTarget).attr('data-courseid');
        let resourceid = $(e.currentTarget).attr('data-resourceid');
        let versionname = $(e.currentTarget).attr('data-versionname');
        let filename = $(e.currentTarget).attr('data-filename');
        let html = $(REGION.CONTENT_ATTO_HTML).html();
        let stringkeys = [
            {key: 'save_changes', component: 'local_educaaragon'},
            {key: 'save_changes_desc', component: 'local_educaaragon'},
            {key: 'confirm', component: 'local_educaaragon'},
            {key: 'changes_saved', component: 'local_educaaragon'},
            {key: 'not_saved', component: 'local_educaaragon'},
            {key: 'write_comment', component: 'local_educaaragon'}
        ];
        Str.get_strings(stringkeys).then(function(langStrings) {
            let title = langStrings[0];
            let confirmMessage = langStrings[1];
            let buttonText = langStrings[2];
            let htmlForBody = '<p>' + confirmMessage + '</p>' +
                '<div class="col-xl-12">' +
                '<div class="form-group purple-border mb-5">' +
                '<label for="write_comment" class="">' + langStrings[5] + '</label>' +
                '<textarea maxlength="300" class="form-control" id="write_comment" rows="3"></textarea>' +
                '</div>' +
                '</div>';
            return ModalFactory.create({
                title: title,
                body: htmlForBody,
                type: ModalFactory.types.SAVE_CANCEL
            }).then(function(modal) {
                modal.setSaveButtonText(buttonText);
                modal.getRoot().on(ModalEvents.save, function(e) {
                    e.preventDefault();
                    let comment = $('#write_comment').val();
                    if (comment.length > 300) {
                        comment = comment.substring(0, 300);
                    }
                    modal.hide();
                    let request = {
                        methodname: SERVICES.SAVECHANGES,
                        args: {
                            courseid: courseid,
                            resourceid: resourceid,
                            versionname: versionname,
                            filename: filename,
                            html: html,
                            other: btoa(comment)
                        }
                    };
                    Templates.render(TEMPLATES.LOADING, {visible: true}).done(function(html) {
                        let identifier = $('body');
                        identifier.append(html);
                        Ajax.call([request])[0].done(function(response) {
                            const d = new Date();
                            if (response.response === true) {
                                Templates.render(TEMPLATES.SUCCESS, {message: langStrings[3] + d.toTimeString().split(' ')[0],
                                    closebutton: true,
                                    // eslint-disable-next-line max-nested-callbacks
                                    announce: true}).done(function(htmlsuccess) {
                                    $(REGION.CONTENT_CONTROLS).prepend(htmlsuccess);
                                });
                                hasChanges = false;
                                $(REGION.HASCHANGES).css('display', 'none');
                            } else {
                                Templates.render(TEMPLATES.ERROR, {message: langStrings[4],
                                    closebutton: true,
                                    // eslint-disable-next-line max-nested-callbacks
                                    announce: true}).done(function(htmlerror) {
                                    $(REGION.CONTENT_CONTROLS).prepend(htmlerror);
                                });
                            }
                            $(REGION.OVERLAY).remove();
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

    EditResource.prototype.applyVersion = function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (hasChanges) {
            EditResource.prototype.detectChanges(e);
        } else {
            let courseid = $(e.currentTarget).attr('data-courseid');
            let resourceid = $(e.currentTarget).attr('data-resourceid');
            let versionname = $(e.currentTarget).attr('data-versionname');
            if (versionname === undefined) {
                versionname = $('[data-region="select-version"]').val();
            }
            let stringkeys = [
                {key: 'apply_version', component: 'local_educaaragon'},
                {key: 'apply_version_desc', component: 'local_educaaragon'},
                {key: 'confirm', component: 'local_educaaragon'},
                {key: 'version_saved', component: 'local_educaaragon'},
                {key: 'version_not_saved', component: 'local_educaaragon'},
                {key: 'versionprintable_saved', component: 'local_educaaragon'},
                {key: 'versionprintable_not_saved', component: 'local_educaaragon'}
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
                        let request = {
                            methodname: SERVICES.APPLYVERSION,
                            args: {
                                courseid: courseid,
                                resourceid: resourceid,
                                versionname: versionname
                            }
                        };
                        Templates.render(TEMPLATES.LOADING, {visible: true}).done(function(html) {
                            let identifier = $('body');
                            identifier.append(html);
                            Ajax.call([request])[0].done(function(response) {
                                const d = new Date();
                                if (response.response === true) {
                                    Templates.render(TEMPLATES.SUCCESS, {message: langStrings[3] + d.toTimeString().split(' ')[0],
                                        closebutton: true,
                                        // eslint-disable-next-line max-nested-callbacks
                                        announce: true}).done(function(htmlsuccess) {
                                        $(REGION.CONTENT_CONTROLS).prepend(htmlsuccess);
                                    });
                                } else {
                                    Templates.render(TEMPLATES.ERROR, {message: langStrings[4],
                                        closebutton: true,
                                        // eslint-disable-next-line max-nested-callbacks
                                        announce: true}).done(function(htmlerror) {
                                        $(REGION.CONTENT_CONTROLS).prepend(htmlerror);
                                    });
                                }
                                if (response.responseprintable === true) {
                                    Templates.render(TEMPLATES.SUCCESS, {message: langStrings[5] + d.toTimeString().split(' ')[0],
                                        closebutton: true,
                                        // eslint-disable-next-line max-nested-callbacks
                                        announce: true}).done(function(htmlsuccess) {
                                        $(REGION.CONTENT_CONTROLS).prepend(htmlsuccess);
                                    });
                                } else {
                                    Templates.render(TEMPLATES.ERROR, {message: langStrings[6],
                                        closebutton: true,
                                        // eslint-disable-next-line max-nested-callbacks
                                        announce: true}).done(function(htmlerror) {
                                        $(REGION.CONTENT_CONTROLS).prepend(htmlerror);
                                    });
                                }
                                $(REGION.OVERLAY).remove();
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
        }
    };

    EditResource.prototype.changeVersion = function() {
        let versionname = $('[data-region="select-version"]').val();
        if (versionname === 'original') {
            $(ACTION.DELETEVERSION).prop('disabled', true);
            $(ACTION.LOADVERSION).prop('disabled', true);
            $(ACTION.EDITTOC).prop('disabled', true);
            $(ACTION.PROCESSVERSIONLINKS).prop('disabled', true);
        } else {
            $(ACTION.DELETEVERSION).prop('disabled', false);
            $(ACTION.LOADVERSION).prop('disabled', false);
            $(ACTION.EDITTOC).prop('disabled', false);
            $(ACTION.PROCESSVERSIONLINKS).prop('disabled', false);
        }
    };

    EditResource.prototype.processVersionLinks = function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (hasChanges) {
            EditResource.prototype.detectChanges(e);
        } else {
            let courseid = $(e.currentTarget).attr('data-courseid');
            let resourceid = $(e.currentTarget).attr('data-resourceid');
            let versionname = $(e.currentTarget).attr('data-versionname');
            if (versionname === undefined) {
                versionname = $('[data-region="select-version"]').val();
            }
            let stringkeys = [
                {key: 'process_version_links', component: 'local_educaaragon'},
                {key: 'process_version_links_desc', component: 'local_educaaragon'},
                {key: 'confirm', component: 'local_educaaragon'},
                {key: 'processed_resource_links', component: 'local_educaaragon'},
                {key: 'not_processed_resource_links', component: 'local_educaaragon'},
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
                        let request = {
                            methodname: SERVICES.PROCESSVERSIONLINKS,
                            args: {
                                courseid: courseid,
                                resourceid: resourceid,
                                versionname: versionname
                            }
                        };
                        Templates.render(TEMPLATES.LOADING, {visible: true}).done(function(html) {
                            let identifier = $('body');
                            identifier.append(html);
                            Ajax.call([request])[0].done(function(response) {
                                const d = new Date();
                                if (response.response === true) {
                                    Templates.render(TEMPLATES.SUCCESS, {message: langStrings[3] + d.toTimeString().split(' ')[0],
                                        closebutton: true,
                                        // eslint-disable-next-line max-nested-callbacks
                                        announce: true}).done(function(htmlsuccess) {
                                        $(REGION.CONTENT_CONTROLS).prepend(htmlsuccess);
                                        let resourceid = $(e.currentTarget).attr('data-resourceid');
                                        if ($(ACTION.VIEWVERSIONLINKS).length > 0) {
                                            let href = $(ACTION.VIEWVERSIONLINKS).attr('data-href');
                                            let versionname = $('[data-region="select-version"]').val();
                                            let url = href + '?resourceid=' + resourceid + '&version=' + versionname;
                                            let n = window.open(url, '_blank');
                                            if (n === null) {
                                                window.location.href = url;
                                            }
                                        }
                                        if ($(ACTION.VIEWVERSIONLINKSEDIT).length > 0) {
                                            let url = $(ACTION.VIEWVERSIONLINKSEDIT).attr('href');
                                            let n = window.open(url, '_blank');
                                            if (n === null) {
                                                window.location.href = url;
                                            }
                                        }
                                    });
                                } else {
                                    Templates.render(TEMPLATES.ERROR, {message: langStrings[4],
                                        closebutton: true,
                                        // eslint-disable-next-line max-nested-callbacks
                                        announce: true}).done(function(htmlerror) {
                                        $(REGION.CONTENT_CONTROLS).prepend(htmlerror);
                                    });
                                }
                                $(REGION.OVERLAY).remove();
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
        }
    };

    EditResource.prototype.viewVersionLinks = function(e) {
        e.preventDefault();
        e.stopPropagation();
        let resourceid = $(e.currentTarget).attr('data-resourceid');
        let href = $(e.currentTarget).attr('data-href');
        let versionname = $('[data-region="select-version"]').val();
        let url = href + '?resourceid=' + resourceid + '&version=' + versionname;
        window.open(url);
    };

    EditResource.prototype.detectChanges = function() {
        if (hasChanges) {
            modalChanges.show();
        }
    };

    EditResource.prototype.createModalChanges = function() {
        Str.get_strings(hasChangesStringsKeys).then(function(langStrings) {
            hasChangesStrings = langStrings;
            let title = hasChangesStrings[0];
            let confirmMessage = hasChangesStrings[1];
            let buttonText = hasChangesStrings[2];
            ModalFactory.create({
                title: title,
                body: confirmMessage,
                type: ModalFactory.types.SAVE_CANCEL
            }).then(function(modal) {
                modal.setSaveButtonText(buttonText);
                modal.getRoot().on(ModalEvents.save, function() {
                    $(ACTION.SAVECHANGES).click();
                    modal.hide();
                });
                modalChanges = modal;
            });
            return true;
        });
    };

    return {
        /**
         * Factory method returning instance of the Table
         * @param {String} selector The selector for the table region containing the table.
         * @return {EditResource}
         */
        initEditResource: function(selector) {
            return new EditResource(selector);
        }
    };

});
