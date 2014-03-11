if (typeof RedactorPlugins === 'undefined') var RedactorPlugins = {};

/* Generic draft support for osTicket. The plugins supports draft retrieval
 * automatically, along with draft autosave, and image uploading.
 *
 * Configuration:
 * draft_namespace: namespace for the draft retrieval
 * draft_object_id: extension to the namespace for draft retrieval
 *
 * Caveats:
 * Login (staff only currently) is required server-side for drafts and image
 * uploads. Furthermore, the id of the staff is considered for the drafts,
 * so one user will not retrieve drafts for another user.
 */
RedactorPlugins.draft = {
    init: function() {
        if (!this.opts.draft_namespace)
            return;

        this.opts.changeCallback = this.hideDraftSaved;
        var autosave_url = 'ajax.php/draft/' + this.opts.draft_namespace;
        if (this.opts.draft_object_id)
            autosave_url += '.' + this.opts.draft_object_id;
        this.opts.autosave = autosave_url;
        this.opts.autosaveInterval = 10;
        this.opts.autosaveCallback = this.setupDraftUpdate;
        this.opts.initCallback = this.recoverDraft;
    },
    recoverDraft: function() {
        var self = this;
        $.ajax(this.opts.autosave, {
            dataType: 'json',
            statusCode: {
                200: function(json) {
                    self.draft_id = json.draft_id;
                    // Replace the current content with the draft, sync, and make
                    // images editable
                    self.setupDraftUpdate(json);
                    if (!json.body) return;
                    self.set(json.body, false);
                    self.observeStart();
                },
                205: function() {
                    // Save empty draft immediately;
                    var ai = self.opts.autosaveInterval;

                    // Save immediately -- capture the created autosave
                    // interval and clear it as soon as possible. Note that
                    // autosave()ing doesn't happen immediately. It happens
                    // async after the autosaveInterval expires.
                    self.opts.autosaveInterval = 0;
                    self.autosave();
                    var interval = self.autosaveInterval;
                    setTimeout(function() {
                        clearInterval(interval);
                    }, 1);

                    // Reinstate previous autosave interval timing
                    self.opts.autosaveInterval = ai;
                }
            }
        });
    },
    setupDraftUpdate: function(data) {
        this.$box.parent().find('.draft-saved').show();

        // Slight workaround. Signal the 'keyup' event normally signaled
        // from typing in the <textarea>
        if ($.autoLock && this.opts.draft_namespace == 'ticket.response')
            if (this.get())
                $.autoLock.handleEvent();

        if (typeof data != 'object')
            data = $.parseJSON(data);

        if (!data || !data.draft_id)
            return;

        $('input[name=draft_id]', this.$box.closest('form'))
            .val(data.draft_id);
        this.draft_id = data.draft_id;

        var self = this;
        getConfig().then(function(c) {
            if (c.allow_attachments) {
                self.opts.clipboardUploadUrl =
                self.opts.imageUpload =
                    'ajax.php/draft/'+data.draft_id+'/attach';
                self.opts.imageUploadErrorCallback = self.displayError;
            }
        });
        this.opts.original_autosave = this.opts.autosave;
        this.opts.autosave = 'ajax.php/draft/'+data.draft_id;
    },

    displayError: function(json) {
        alert(json.error);
    },

    hideDraftSaved: function() {
        this.$box.parent().find('.draft-saved').hide();
    },

    deleteDraft: function() {
        if (!this.draft_id)
            // Nothing to delete
            return;
        var self = this;
        $.ajax('ajax.php/draft/'+this.draft_id, {
            type: 'delete',
            async: false,
            success: function() {
                self.draft_id = undefined;
                self.hideDraftSaved();
                self.set('', false, false);
                self.opts.autosave = self.opts.original_autosave;
            }
        });
    }
};

RedactorPlugins.signature = {
    init: function() {
        var $el = $(this.$element.get(0)),
            inner = $('<div class="inner"></div>');
        if ($el.data('signatureField')) {
            this.$signatureBox = $('<div class="selected-signature"></div>')
                .append(inner)
                .appendTo(this.$box);
            if ($el.data('signature'))
                inner.html($el.data('signature'));
            else
                this.$signatureBox.hide();
            $('input[name='+$el.data('signatureField')+']', $el.closest('form'))
                .on('change', false, false, $.proxy(this.updateSignature, this))
            if ($el.data('deptField'))
                $(':input[name='+$el.data('deptField')+']', $el.closest('form'))
                    .on('change', false, false, $.proxy(this.updateSignature, this))
            // Expand on hover
            var outer = this.$signatureBox,
                inner = $('.inner', this.$signatureBox).get(0),
                originalHeight = outer.height(),
                hoverTimeout = undefined,
                originalShadow = this.$signatureBox.css('box-shadow');
            this.$signatureBox.hover(function() {
                hoverTimeout = setTimeout($.proxy(function() {
                    originalHeight = Math.max(originalHeight, outer.height());
                    $(this).animate({
                        'height': inner.offsetHeight
                    }, 'fast');
                    $(this).css('box-shadow', 'none', 'important');
                }, this), 250);
            }, function() {
                clearTimeout(hoverTimeout);
                $(this).stop().animate({
                    'height': Math.min(inner.offsetHeight, originalHeight)
                }, 'fast');
                $(this).css('box-shadow', originalShadow);
            });
        }
    },
    updateSignature: function(e) {
        var $el = $(this.$element.get(0));
            selected = $(':input:checked[name='+$el.data('signatureField')+']', $el.closest('form')).val(),
            type = $(e.target).val(),
            dept = $(':input[name='+$el.data('deptField')+']', $el.closest('form')).val(),
            url = 'ajax.php/content/signature/',
            inner = $('.inner', this.$signatureBox);
        e.preventDefault && e.preventDefault();
        if (selected == 'dept' && $el.data('deptId'))
            url += 'dept/' + $el.data('deptId');
        else if (selected == 'dept' && $el.data('deptField')) {
            if (dept)
                url += 'dept/' + dept
            else
                return inner.empty().parent().hide();
        }
        else if (type == 'none')
           return inner.empty().parent().hide();
        else
            url += selected

        inner.load(url).parent().show();
    }
};

/* Redactor richtext init */
$(function() {
    var captureImageSizes = function(html) {
        $('img', this.$box).each(function(i, img) {
            // TODO: Rewrite the entire <img> tag. Otherwise the @width
            // and @height attributes will begin to accumulate
            before = img.outerHTML;
            if (img.clientWidth && img.clientHeight)
                $(img).attr('width', img.clientWidth)
                      .attr('height',img.clientHeight);
            html = html.replace(before, img.outerHTML);
        });
        // Drop <inline> elements if found in the text (shady mojo happening
        // inside the Redactor editor)
        // DELME: When this is fixed upstream in Redactor
        html = html.replace(/<inline /, '<span ').replace(/<\/inline>/, '</span>');
        return html;
    },
    redact = function(el) {
        var el = $(el),
            options = {
                'air': el.hasClass('no-bar'),
                'airButtons': ['formatting', '|', 'bold', 'italic', 'underline', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|', 'image'],
                'buttons': ['html', '|', 'formatting', '|', 'bold',
                    'italic', 'underline', 'deleted', '|', 'unorderedlist',
                    'orderedlist', 'outdent', 'indent', '|', 'image', 'video',
                    'file', 'table', 'link', '|', 'alignment', '|',
                    'horizontalrule'],
                'autoresize': !el.hasClass('no-bar'),
                'minHeight': el.hasClass('small') ? 75 : 150,
                'focus': false,
                'plugins': ['fontcolor','fontfamily', 'signature'],
                'imageGetJson': 'ajax.php/draft/images/browse',
                'syncBeforeCallback': captureImageSizes,
                'linebreaks': true,
                'tabFocus': false
            };
        if (el.data('redactor')) return;
        var reset = $('input[type=reset]', el.closest('form'));
        if (reset) {
            reset.click(function() {
                if (el.hasClass('draft'))
                    el.redactor('deleteDraft');
                else
                    el.redactor('set', '', false, false);
            });
        }
        if (el.hasClass('draft')) {
            var draft_saved = $('<span>')
                .addClass("pull-right draft-saved faded")
                .css({'position':'relative','top':'-1.8em','right':'1em'})
                .hide()
                .append($('<span>')
                    .css({'position':'relative', 'top':'0.17em'})
                    .text('Draft Saved'));
            el.closest('form').append($('<input type="hidden" name="draft_id"/>'));
            if (el.hasClass('draft-delete')) {
                draft_saved.append($('<span>')
                    .addClass('action-button')
                    .click(function() {
                        el.redactor('deleteDraft');
                        return false;
                    })
                    .append($('<i>')
                        .addClass('icon-trash')
                    )
                );
            }
            draft_saved.insertBefore(el);
            options['plugins'].push('draft');
            if (el.data('draftNamespace'))
                options['draft_namespace'] = el.data('draftNamespace');
            if (el.data('draftObjectId'))
                options['draft_object_id'] = el.data('draftObjectId');
        }
        el.redactor(options);
    },
    findRichtextBoxes = function() {
        $('.richtext').each(function(i,el) {
            if ($(el).hasClass('ifhtml'))
                // Check if html_thread is enabled first
                getConfig().then(function(c) {
                    if (c.html_thread)
                        redact(el);
                });
            else
                // Make a rich text editor immediately
                redact(el);
        });
    };
    findRichtextBoxes();
    $(document).ajaxStop(findRichtextBoxes);
});
