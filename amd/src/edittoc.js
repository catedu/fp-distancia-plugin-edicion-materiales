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
/* eslint-disable jsdoc/require-param-type */
/* eslint-disable camelcase */
/* eslint-disable no-unused-vars */
/* eslint-disable no-console */
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

    let ACTION = {
        SAVECHANGES: '[data-action="save-changes"]',
        ADDNODE: '[data-action="add-new-node"]'
    };

    let SERVICES = {
        SAVECHANGES: 'local_educaaragon_savetocchanges',
    };

    let REGIONS = {
        CONTENTEDITABLE: 'a[contenteditable=true]',
        TOCLISTCONTENT: '.toc-list-content',
        EDITTITLE: 'i.edit-title',
        DELETENODE: 'i.delete-node',
        NEWNODE: '.new-node',
        NEWNODEEDIT: '.new-node i.edit-title',
        NEWNODEDELETE: '.new-node i.delete-node',
        NEWNODECONTENTEDITABLE: '.new-node a[contenteditable=true]',
        HASCHANGES: '.haschanges',
        CONTENT_CONTROLS: '[data-region="educaaragon-editresourcetoc"]',
        OVERLAY: '[data-region="overlay-icon-container"]',
        DRAGGING: 'ul.sortable.dragaware'
    };

    let TEMPLATES = {
        LOADING: 'core/overlay_loading',
        SUCCESS: 'core/notification_success',
        ERROR: 'core/notification_error'
    };

    /**
     * @constructor
     * @param el
     * @param options
     */
    function Sortable(el, options) {
        let self = this,
            $sortable = $(el),
            container_type = $sortable[0].nodeName,
            node_type = (container_type === 'OL' || container_type === 'UL') ? 'LI' : 'DIV',
            defaults = {
                handle: false,
                container: container_type,
                container_type: container_type,
                same_depth: false,
                make_unselectable: false,
                nodes: node_type,
                nodes_type: node_type,
                placeholder_class: null,
                auto_container_class: 'sortable_container',
                autocreate: false,
                group: false,
                scroll: false,
                update: null
            };
        self.$sortable = $sortable.data('sortable', self);
        self.options = $.extend({}, defaults, options);
        self.init();
    }

    Sortable.prototype.invoke = function(command) {
        let self = this;
        if (command === 'destroy') {
            return self.destroy();
        } else if (command === 'serialize') {
            return self.serialize(self.$sortable);
        }
    };

    let hasChanges = false;
    let hasChangesStringsKeys = [
        {key: 'changesnotsaved', component: 'local_educaaragon'},
        {key: 'changesnotsaved_desc', component: 'local_educaaragon'},
        {key: 'savechanges', component: 'local_educaaragon'}
    ];
    let deleteNode = [
        {key: 'delete_node', component: 'local_educaaragon'},
        {key: 'delete_node_desc', component: 'local_educaaragon'},
        {key: 'delete_node', component: 'local_educaaragon'}
    ];
    let hasChangesStrings;
    let deleteNodeStrings;
    let modalChanges;
    let modalDelete;
    let currentNodeToDelete;
    let alreadyLoaded = false;

    Sortable.prototype.init = function() {
        let self = this,
            $clone,
            $placeholder,
            origin;
        if (self.options.make_unselectable) {
            $('html').unselectable();
        }
        self.$sortable
            .addClass('sortable')
            .on('destroy.sortable', function() {
                self.destroy();
            });

        /**
         *
         * @param $node
         * @param offset
         */
        function find_insert_point($node, offset) {
            let containers,
                best,
                depth;

            if (!offset) {
                return;
            }

            containers = self.$sortable
                .add(self.$sortable.find(self.options.container))
                .not($node.find(self.options.container))
                .not($clone.find(self.options.container))
                .not(self.find_nodes());

            if (self.options.same_depth) {
                depth = $node.parent().nestingDepth('ul');
                containers = containers.filter(function() {
                    return $(this).nestingDepth('ul') == depth;
                });
            }

            $placeholder.hide();
            containers.each(function(ix, container) {
                let $trailing = $(self.create_placeholder()).appendTo(container),
                    $children = $(container).children(self.options.nodes).not('.sortable_clone'),
                    $candidate,
                    n,
                    dist;

                for (n = 0; n < $children.length; n++) {
                    $candidate = $children.eq(n);
                    dist = self.square_dist($candidate.offset(), offset);
                    if (!best || best.dist > dist) {
                        best = {container: container, before: $candidate[0], dist: dist};
                    }
                }

                $trailing.remove();
            });
            $placeholder.show();

            return best;
        }

        /**
         *
         * @param $element
         * @param best
         */
        function insert($element, best) {
            let $container = $(best.container);
            if (best.before && best.before.closest('html')) {
                $element.insertBefore(best.before);
            } else {
                $element.appendTo($container);
            }
        }

        self.$sortable.dragaware($.extend({}, self.options, {
            delegate: self.options.nodes,
            dragstart: function() {
                let $node = $(this);

                $clone = $node.clone()
                    .removeAttr('id')
                    .addClass('sortable_clone')
                    .css({position: 'absolute'})
                    .insertAfter($node)
                    .offset($node.offset());
                $placeholder = self.create_placeholder()
                    .css({height: $node.outerHeight(), width: $node.outerWidth()})
                    .insertAfter($node);
                $node.hide();

                origin = new PositionHelper($clone.offset());

                if (self.options.autocreate) {
                    self.find_nodes().filter(function(ix, el) {
                        return $(el).find(self.options.container).length === 0;
                    }).append('<' + self.options.container_type + ' class="' + self.options.auto_container_class + '"/>');
                }
            },

            /**
             * Drag - reposition clone, check for best insert position, move placeholder in dom accordingly.
             * @param evt
             * @param pos
             */
            drag: function(evt, pos) {
                let $node = $(this),
                    offset = origin.absolutize(pos),
                    best = find_insert_point($node, offset);

                $clone.offset(offset);
                insert($placeholder, best);
            },

            /**
             * Drag stop - clean up.
             * @param evt
             * @param pos
             */
            dragstop: function(evt, pos) {
                let $node = $(this),
                    offset = origin.absolutize(pos),
                    best = find_insert_point($node, offset);

                if (best) {
                    insert($node, best);
                }
                $node.show();

                if ($clone) {
                    $clone.remove();
                }
                if ($placeholder) {
                    $placeholder.remove();
                }
                $clone = null;
                $placeholder = null;

                if (best && self.options.update) {
                    hasChanges = true;
                    self.options.update.call(self.$sortable, evt, self);
                }
                self.$sortable.trigger('update');
            }
        }));

        /* Links controls */
        $(REGIONS.TOCLISTCONTENT).find('a').each(function() {
            $(this).wrap('<div class="content-title d-flex flex-nowrap align-items-center justify-content-start"></div>');
            if ($(this).attr('href') === 'index.html') {
                $(this).removeAttr('class');
                $(this).addClass('main-node');
                $(this).after('<i class="edit-title icon fa fa-pencil fa-fw float-right ml-6" title="Edit Title" role="img"' +
                    ' aria-label="Edit Title"></i>');
            } else {
                $(this).after('<i class="edit-title icon fa fa-pencil fa-fw float-right ml-6" title="Edit Title" role="img"' +
                    ' aria-label="Edit Title"></i>' +
                    '<i class="delete-node icon fa fa-trash fa-fw float-right ml-6" title="Delete Node" role="img"' +
                    ' aria-label="Delete Node"></i>');
            }
            $(this).attr('contenteditable', true);
            if ($(this).attr('href') === 'index.html') {
                $(this).css({'font-weight': 'bold', 'color': 'red'});
                let liElement = $(this).closest('li');
                liElement.addClass('index-element').closest('ul').before(liElement);
            }
        });
        $(REGIONS.EDITTITLE).off('touchstart.dragaware mousedown.dragaware click');
        $(REGIONS.EDITTITLE).on('touchstart.dragaware mousedown.dragaware click', function(e) {
            e.stopPropagation();
            let editableElement = $(this).prev('a').attr('contenteditable', 'true').focus();
            moveCursorToEnd(editableElement[0]);
        });
        $(REGIONS.DELETENODE).off('touchstart.dragaware mousedown.dragaware click');
        $(REGIONS.DELETENODE).on('touchstart.dragaware mousedown.dragaware click', function(e) {
            e.stopPropagation();
            currentNodeToDelete = $(this).closest('li');
            modalDelete.show();
        });
        $(REGIONS.CONTENTEDITABLE).off('keydown');
        $(REGIONS.CONTENTEDITABLE).on('keydown', function(e) {
            hasChanges = true;
            $(REGIONS.HASCHANGES).css('display', 'block');
            if (e.key === "Enter") {
                e.preventDefault();
                $(this).blur();
            }
        });
        $(REGIONS.CONTENTEDITABLE).off('blur, focusout');
        $(REGIONS.CONTENTEDITABLE).on('blur, focusout', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if ($(this).text().trim() === '') {
                $(this).attr('placeholder', 'El título no puede estar vacío');
                $(this).focus();
                $(this).css('background-color', '#ffbfaf');
                $(this).empty();
                moveCursorToEnd(this);
            } else {
                $(this).css('background-color', 'unset');
            }
        });
        $(document).off('paste');
        $(document).on('paste', '[contenteditable]', function(e) {
            e.preventDefault();
            let text = (e.originalEvent || e).clipboardData.getData('text/plain');
            document.execCommand('insertText', false, text);
        });
        $(document).off('mousedown');
        $(document).on('mousedown', function(e) {
            if (e.target.id !== 'write_comment') {
                e.preventDefault();
                e.stopPropagation();
                $(REGIONS.CONTENTEDITABLE).blur();
            }
        });
        $(document).off('keydown');
        $(document).on('keydown', function(event) {
            if (event.key === 'Tab') {
                event.preventDefault();
                $(REGIONS.CONTENTEDITABLE).blur();
            }
        });

        if (alreadyLoaded === false) {
            Sortable.prototype.createModalChanges();
            Sortable.prototype.createModalDelete();
            document.querySelectorAll(
                'a:not([target="_blank"]), .content-controls button:not([data-action="save-changes"])'
            ).forEach(link => {
                link.addEventListener('click', (e) => {
                    if (hasChanges) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    Sortable.prototype.detectChanges(e);
                });
            });
            window.onbeforeunload = function(e) {
                Sortable.prototype.detectChanges(e);
                if (hasChanges) {
                    e.preventDefault();
                }
            };
            $(ACTION.SAVECHANGES).on('click', Sortable.prototype.saveChanges);
            $(ACTION.ADDNODE).on('click', Sortable.prototype.addNode);
        }

        alreadyLoaded = true;

        /**
         *
         * @param el
         */
        function moveCursorToEnd(el) {
            if (typeof window.getSelection != "undefined"
                && typeof document.createRange != "undefined") {
                let range = document.createRange();
                range.selectNodeContents(el);
                range.collapse(false);
                let sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            } else if (typeof document.body.createTextRange != "undefined") {
                let textRange = document.body.createTextRange();
                textRange.moveToElementText(el);
                textRange.collapse(false);
                textRange.select();
            }
        }
    };

    Sortable.prototype.destroy = function() {
        let self = this;

        if (self.options.make_unselectable) {
            $('html').unselectable('destroy');
        }

        self.$sortable
            .removeClass('sortable')
            .off('.sortable')
            .dragaware('destroy');
    };

    Sortable.prototype.serialize = function(container) {
        let self = this;
        return container.children(self.options.nodes).not(self.options.container).map(function(ix, el) {
            let $el = $(el),
                text = $el.clone().children().remove().end().text().trim(), // Text only without children
                id = $el.attr('id'),
                node = {id: id || text};
            if ($el.find(self.options.nodes).length) {
                node.children = self.serialize($el.children(self.options.container));
            }
            return node;
        }).get();
    };

    Sortable.prototype.find_nodes = function() {
        let self = this;
        return self.$sortable.find(self.options.nodes).not(self.options.container);
    };

    Sortable.prototype.create_placeholder = function() {
        let self = this;
        return $('<' + self.options.nodes_type + '/>')
            .addClass('sortable_placeholder')
            .addClass(self.options.placeholder_class);
    };

    Sortable.prototype.square_dist = function(pos1, pos2) {
        return Math.pow(pos2.left - pos1.left, 2) + Math.pow(pos2.top - pos1.top, 2);
    };

    Sortable.prototype.createModalChanges = function() {
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

    Sortable.prototype.createModalDelete = function() {
        Str.get_strings(deleteNode).then(function(langStrings) {
            deleteNodeStrings = langStrings;
            let title = deleteNodeStrings[0];
            let confirmMessage = deleteNodeStrings[1];
            let buttonText = deleteNodeStrings[2];
            ModalFactory.create({
                title: title,
                body: confirmMessage,
                type: ModalFactory.types.SAVE_CANCEL
            }).then(function(modal) {
                modal.setSaveButtonText(buttonText);
                modal.getRoot().on(ModalEvents.save, function() {
                    currentNodeToDelete.remove();
                    modal.hide();
                });
                modalDelete = modal;
            });
            return true;
        });
    };

    Sortable.prototype.detectChanges = function() {
        if (hasChanges) {
            modalChanges.show();
        }
    };

    Sortable.prototype.saveChanges = function(e) {
        e.preventDefault();
        e.stopPropagation();
        let courseid = $(e.currentTarget).attr('data-courseid');
        let resourceid = $(e.currentTarget).attr('data-resourceid');
        let versionname = $(e.currentTarget).attr('data-versionname');
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

            let liElement = $('.index-element');
            liElement.removeClass('index-element').prependTo($('.toc-list-content').find('ul').first());
            let htmlContent = $('.toc-list-content ul:first-child').html();
            let html = $.parseHTML(htmlContent);
            $(html).find(REGIONS.EDITTITLE).each(function() {
                $(this).remove();
            });
            $(html).find(REGIONS.DELETENODE).each(function() {
                $(this).remove();
            });
            $(html).find(REGIONS.CONTENTEDITABLE).each(function() {
                $(this).removeAttr('contenteditable style').unwrap();
            });
            $(html).find('.sortable_container').each(function() {
                $(this).removeAttr('class');
            });
            $(html).each(function() {
                $(this).removeAttr('id style');
                $(this).find('ul').each(function() {
                    if ($(this).has('li').length === 0) {
                        $(this).remove();
                    }
                });
                $(this).find('li').each(function() {
                    $(this).removeAttr('id style');
                });
            });
            let cleanedHtml = $('<div>').append(html).html();
            liElement.addClass('index-element').insertBefore(liElement.closest('ul'));
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
                            html: cleanedHtml,
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
                                    $(REGIONS.CONTENT_CONTROLS).prepend(htmlsuccess);
                                });
                                hasChanges = false;
                                $(REGIONS.NEWNODE).removeAttr('class');
                                $(REGIONS.HASCHANGES).css('display', 'none');
                                if (response.tochtml) {
                                    $(REGIONS.TOCLISTCONTENT).html(response.tochtml);
                                    $(function() {
                                        $('.toc-list-content ul:first-child').sortable({
                                            autocreate: true,
                                            update: function(evt) {
                                                hasChanges = true;
                                                $('.haschanges').css('display', 'block');
                                            }
                                        });
                                    });
                                }
                            } else {
                                Templates.render(TEMPLATES.ERROR, {message: langStrings[4],
                                    closebutton: true,
                                    announce: true}).done(function(htmlerror) {
                                    $(REGIONS.CONTENT_CONTROLS).prepend(htmlerror);
                                });
                            }
                            $(REGIONS.OVERLAY).remove();
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
            $('#write_comment').focus();
        }).fail(Notification.exception);
    };

    Sortable.prototype.addNode = function(e) {
        let htmlLi = '<li class="new-node">' +
            '<div class="content-title d-flex flex-nowrap align-items-center justify-content-start">' +
            '<a href="" class="no-ch" contenteditable="true">Nuevo Elemento</a>' +
            '<i class="edit-title icon fa fa-pencil fa-fw float-right ml-6" ' +
            'title="Edit Title" role="img" aria-label="Edit Title"></i>' +
            '<i class="delete-node icon fa fa-trash fa-fw float-right ml-6" ' +
            'title="Delete Node" role="img" aria-label="Delete Node"></i>' +
            '</div></li>';
        if ($(e.currentTarget).hasClass('forprev')) {
            let targetLi = $(REGIONS.DRAGGING).find('a[href="index.html"]').closest('li');
            if (targetLi.length) {
                $(htmlLi).insertAfter(targetLi);
            } else {
                $(REGIONS.DRAGGING).prepend(htmlLi);
            }
        } else {
            $(REGIONS.DRAGGING).append(htmlLi);
        }
        hasChanges = true;
        $('.haschanges').css('display', 'block');
        $(REGIONS.NEWNODEEDIT).off('touchstart.dragaware mousedown.dragaware click');
        $(REGIONS.NEWNODEDELETE).off('touchstart.dragaware mousedown.dragaware click');
        $(REGIONS.NEWNODECONTENTEDITABLE).off('keydown');
        $(REGIONS.NEWNODECONTENTEDITABLE).off('blur, focusout');
        $(REGIONS.NEWNODEEDIT).on('touchstart.dragaware mousedown.dragaware click', function(e) {
            e.stopPropagation();
            let editableElement = $(this).prev('a').attr('contenteditable', 'true').focus();
            if (typeof window.getSelection != "undefined"
                && typeof document.createRange != "undefined") {
                let range = document.createRange();
                range.selectNodeContents(editableElement[0]);
                range.collapse(false);
                let sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            } else if (typeof document.body.createTextRange != "undefined") {
                let textRange = document.body.createTextRange();
                textRange.moveToElementText(editableElement[0]);
                textRange.collapse(false);
                textRange.select();
            }
        });
        $(REGIONS.NEWNODEDELETE).on('touchstart.dragaware mousedown.dragaware click', function(e) {
            e.stopPropagation();
            currentNodeToDelete = $(this).closest('li');
            modalDelete.show();
        });
        $(REGIONS.NEWNODECONTENTEDITABLE).on('keydown', function(e) {
            hasChanges = true;
            $(REGIONS.HASCHANGES).css('display', 'block');
            if (e.key === "Enter") {
                e.preventDefault();
                $(this).blur();
            }
        });
        $(REGIONS.NEWNODECONTENTEDITABLE).on('blur, focusout', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if ($(this).text().trim() === '') {
                $(this).attr('placeholder', 'El título no puede estar vacío');
                $(this).focus();
                $(this).css('background-color', '#ffbfaf');
                $(this).empty();
                if (typeof window.getSelection != "undefined"
                    && typeof document.createRange != "undefined") {
                    let range = document.createRange();
                    range.selectNodeContents(this);
                    range.collapse(false);
                    let sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(range);
                } else if (typeof document.body.createTextRange != "undefined") {
                    let textRange = document.body.createTextRange();
                    textRange.moveToElementText(this);
                    textRange.collapse(false);
                    textRange.select();
                }
            } else {
                $(this).css('background-color', 'unset');
            }
        });
        if ($(e.currentTarget).hasClass('forprev')) {
            $(REGIONS.NEWNODEEDIT).first().click();
        } else {
            $(REGIONS.NEWNODEEDIT).last().click();
        }
    };


    /**
     *
     * @param el
     * @param options
     */
    function Draggable(el, options) {
        let self = this,
            defaults = {
                handle: false,
                delegate: false,
                revert: false,
                placeholder: false,
                droptarget: false,
                container: false,
                scroll: false,
                update: null,
                drop: null
            };

        self.$draggable = $(el).data('draggable', self);
        self.options = $.extend({}, defaults, options);

        self.init();
    }

    Draggable.prototype.init = function() {
        let self = this,
            $clone,
            origin;

        self.$draggable
            .addClass('draggable')
            .on('destroy.draggable', function() {
                self.destroy();
            });

        /**
         *
         * @param pos
         */
        function check_droptarget(pos) {
            let $over;

            $('.hovering').removeClass('hovering');

            $clone.hide();
            $over = $(document.elementFromPoint(pos.clientX, pos.clientY)).closest(self.options.droptarget);
            $clone.show();

            if ($over.length) {
                $over.addClass('hovering');
                return $over;
            }
        }

        self.$draggable.dragaware($.extend({}, self.options, {
            /**
             * Drag start - create clone, keep drag start origin.
             */
            dragstart: function() {
                let $this = $(this);
                if (self.options.placeholder || self.options.revert) {
                    $clone = $this.clone()
                        .removeAttr('id')
                        .addClass('draggable_clone')
                        .css({position: 'absolute'})
                        .appendTo(self.options.container || $this.parent())
                        .offset($this.offset());
                    if (!self.options.placeholder) {
                        $(this).invisible();
                    }
                } else {
                    $clone = $this;
                }

                origin = new PositionHelper($clone.offset());
            },

            /**
             * Drag - reposition clone.
             * @param evt
             * @param pos
             */
            drag: function(evt, pos) {
                // eslint-disable-next-line no-unused-vars
                let $droptarget = check_droptarget(pos);
                $clone.offset(origin.absolutize(pos));
            },

            /**
             * Drag stop - clean up.
             * @param evt
             * @param pos
             */
            dragstop: function(evt, pos) {
                let $this = $(this),
                    $droptarget = check_droptarget(pos);

                if (self.options.revert || self.options.placeholder) {
                    $this.visible();
                    if (!self.options.revert) {
                        $this.offset(origin.absolutize(pos));
                    }
                    $clone.remove();
                }

                $clone = null;

                if (self.options.update) {
                    hasChanges = true;
                    self.options.update.call($this, evt, self);
                }

                $this.trigger('update');

                if ($droptarget) {
                    if (self.options.drop) {
                        self.options.drop.call($this, evt, $droptarget[0]);
                    }
                    $droptarget.trigger('drop', [$this]);
                    $droptarget.removeClass('hovering');
                } else {
                    if (self.options.onrevert) {
                        self.options.onrevert.call($this, evt);
                    }
                }
            }
        }));
    };

    Draggable.prototype.destroy = function() {
        let self = this;

        self.$draggable
            .dragaware('destroy')
            .removeClass('draggable')
            .off('.draggable');
    };


    /**
     *
     * @param el
     * @param options
     */
    function Droppable(el, options) {
        let self = this,
            defaults = {
                accept: false,
                drop: null
            };

        self.$droppable = $(el).data('droppable', self);
        self.options = $.extend({}, defaults, options);

        self.init();
    }

    Droppable.prototype.init = function() {
        let self = this;

        self.$droppable
            .addClass('droppable')
            .on('drop', function(evt, $draggable) {
                if (self.options.accept && !$draggable.is(self.options.accept)) {
                    return;
                }
                if (self.options.drop) {
                    self.options.drop.call(self.$droppable, evt, $draggable);
                }
            })
            .on('destroy.droppable', function() {
                self.destroy();
            });
    };

    Droppable.prototype.destroy = function() {
        let self = this;

        self.$droppable
            .removeClass('droppable')
            .off('.droppable');
    };


    /**
     *
     * @param el
     * @param options
     */
    function Dragaware(el, options) {
        let $dragaware = $(el),
            $reference = null,
            origin = null,
            lastpos = null,
            defaults = {
                handle: null,
                delegate: null,
                scroll: false,
                scrollspeed: 15,
                scrolltimeout: 50,
                dragstart: null,
                drag: null,
                dragstop: null
            },
            scrolltimeout;

        options = $.extend({}, defaults, options);

        /**
         * Returns the event position
         * dX, dY relative to drag start
         * pageX, pageY relative to document
         * clientX, clientY relative to browser window
         * @param evt
         */
        function evtpos(evt) {
            evt = window.hasOwnProperty('event') ? window.event : evt;
            // Extract touch event if present
            if (evt.type.substring(0, 5) === 'touch') {
                evt = evt.hasOwnProperty('originalEvent') ? evt.originalEvent : evt;
                evt = evt.touches[0];
            }

            return {
                pageX: evt.pageX,
                pageY: evt.pageY,
                clientX: evt.clientX,
                clientY: evt.clientY,
                dX: origin ? evt.pageX - origin.pageX : 0,
                dY: origin ? evt.pageY - origin.pageY : 0
            };
        }

        /**
         *
         * @param pos
         */
        function autoscroll(pos) {
            // TODO: allow window scrolling.
            // TODO: handle nested scroll containers.
            let sp = $dragaware.scrollParent(),
                mouse = {x: pos.pageX, y: pos.pageY},
                offset = sp.offset(),
                scrollLeft = sp.scrollLeft(),
                scrollTop = sp.scrollTop(),
                width = sp.width(),
                height = sp.height();

            window.clearTimeout(scrolltimeout);

            if (scrollLeft > 0 && mouse.x < offset.left) {
                sp.scrollLeft(scrollLeft - options.scrollspeed);
            } else if (scrollLeft < sp.prop('scrollWidth') - width && mouse.x > offset.left + width) {
                sp.scrollLeft(scrollLeft + options.scrollspeed);
            } else if (scrollTop > 0 && mouse.y < offset.top) {
                sp.scrollTop(scrollTop - options.scrollspeed);
            } else if (scrollTop < sp.prop('scrollHeight') - height && mouse.y > offset.top + height) {
                sp.scrollTop(scrollTop + options.scrollspeed);
            } else {
                return;
            }

            scrolltimeout = window.setTimeout(function() {
                autoscroll(pos);
            }, options.scrolltimeout);
        }

        /**
         *
         * @param evt
         */
        function start(evt) {
            let $target = $(evt.target);

            $reference = options.delegate ? $target.closest(options.delegate) : $dragaware;

            if ($target.closest(options.handle || '*').length && (evt.type == 'touchstart' || evt.button == 0)) {
                origin = lastpos = evtpos(evt);
                if (options.dragstart) {
                    options.dragstart.call($reference, evt, lastpos);
                }

                $reference.addClass('dragging');
                $reference.trigger('dragstart');

                // Late binding of event listeners
                $(document)
                    .on('touchend.dragaware mouseup.dragaware click.dragaware', end)
                    .on('touchmove.dragaware mousemove.dragaware', move);
                return false;
            }
        }

        /**
         *
         * @param evt
         */
        function move(evt) {
            lastpos = evtpos(evt);
            $(REGIONS.CONTENTEDITABLE).blur();
            if (options.scroll) {
                autoscroll(lastpos);
            }

            $reference.trigger('dragging');

            if (options.drag) {
                options.drag.call($reference, evt, lastpos);
                return false;
            }
        }

        /**
         *
         * @param evt
         */
        function end(evt) {
            $(REGIONS.CONTENTEDITABLE).blur();
            window.clearTimeout(scrolltimeout);

            if (options.dragstop) {
                options.dragstop.call($reference, evt, lastpos);
            }

            $reference.removeClass('dragging');
            $reference.trigger('dragstop');

            origin = false;
            lastpos = false;
            $reference = false;

            $(document)
                .off('.dragaware');

            return false;
        }

        $dragaware
            .addClass('dragaware')
            .on('touchstart.dragaware mousedown.dragaware', options.delegate, start);

        $dragaware.on('destroy.dragaware', function() {
            $dragaware
                .removeClass('dragaware')
                .off('.dragaware');
        });
    }


    /**
     *
     * @param origin
     */
    function PositionHelper(origin) {
        this.origin = origin;
    }
    PositionHelper.prototype.absolutize = function(pos) {
        if (!pos) {
            return this.origin;
        }
        return {top: this.origin.top + pos.dY, left: this.origin.left + pos.dX};
    };


    /**
     * Sortable plugin.
     * @param options
     */
    $.fn.sortable = function(options) {
        let filtered = this.not(function() {
            return $(this).is('.sortable') || $(this).closest('.sortable').length;
        });

        if (this.data('sortable') && typeof options === 'string') {
            return this.data('sortable').invoke(options);
        }

        if (filtered.length && options && options.group) {
            new Sortable(filtered, options);
        } else {
            filtered.each(function(ix, el) {
                new Sortable(el, options);
            });
        }
        return this;
    };


    /**
     * Draggable plugin.
     * @param options
     */
    $.fn.draggable = function(options) {
        if (options === 'destroy') {
            this.trigger('destroy.draggable');
        } else {
            this.not('.draggable').each(function(ix, el) {
                new Draggable(el, options);
            });
        }
        return this;
    };


    /**
     * Droppable plugin.
     * @param options
     */
    $.fn.droppable = function(options) {
        if (options === 'destroy') {
            this.trigger('destroy.droppable');
        } else {
            this.not('.droppable').each(function(ix, el) {
                new Droppable(el, options);
            });
        }
        return this;
    };


    /**
     * Dragaware plugin.
     * @param options
     */
    $.fn.dragaware = function(options) {
        if (options === 'destroy') {
            this.trigger('destroy.dragaware');
        } else {
            this.not('.dragaware').each(function(ix, el) {
                new Dragaware(el, options);
            });
        }
        return this;
    };


    /**
     * Disables mouse selection.
     * @param command
     */
    $.fn.unselectable = function(command) {

        /**
         *
         */
        function disable() {
            return false;
        }

        if (command === 'destroy') {
            return this
                .removeClass('unselectable')
                .removeAttr('unselectable')
                .off('selectstart.unselectable');
        } else {
            return this
                .addClass('unselectable')
                .attr('unselectable', 'on')
                .on('selectstart.unselectable', disable);
        }
    };


    $.fn.invisible = function() {
        return this.css({visibility: 'hidden'});
    };


    $.fn.visible = function() {
        return this.css({visibility: 'visible'});
    };


    $.fn.scrollParent = function() {
        return this.parents().addBack().filter(function() {
            let p = $(this);
            return (/(scroll|auto)/).test(p.css("overflow-x") + p.css("overflow-y") + p.css("overflow"));
        });
    };

    $.fn.nestingDepth = function(selector) {
        let parent = this.parent().closest(selector || '*');
        if (parent.length) {
            return parent.nestingDepth(selector) + 1;
        } else {
            return 0;
        }
    };

});
