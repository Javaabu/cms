window.slug = function (str) {
    str = str.replace(/^\s+|\s+$/g, ''); // trim
    str = str.toLowerCase();

    // remove accents, swap ñ for n, etc
    var from = "ãàáäâẽèéëêìíïîõòóöôùúüûñç·/_,:;";
    var to = "aaaaaeeeeeiiiiooooouuuunc------";
    for (var i = 0, l = from.length; i < l; i++) {
        str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
    }

    str = str.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
        .replace(/\s+/g, '-') // collapse whitespace and replace by -
        .replace(/-+/g, '-'); // collapse dashes

    return str;
};

/*
	Transliterate Thaana
	This algorithm transliterates Thaana script to Roman characters (Latin)
	___________________
	Ayaz, 2014
	Based on the work of Kailash Nadh - http://nadh.in
	https://github.com/ayarse/Thaana-Transliterater
*/
window.transliterate2dv = function (input) {
    let _vowels = {
        "ަ": "a",
        "ާ": "aa",
        "ި": "i",
        "ީ": "ee",
        "ު": "u",
        "ޫ": "oo",
        "ެ": "e",
        "ޭ": "ey",
        "ޮ": "o",
        "ޯ": "oa",
        "ް": ""
    };

    let _compounds = {};

    let _alif_compounds = {
        "އަ": "a",
        "އާ": "aa",
        "އި": "i",
        "އީ": "ee",
        "އު": "u",
        "އޫ": "oo",
        "އެ": "e",
        "އޭ": "ey",
        "އޮ": "o",
        "އޯ": "oa"
    };

    let _consonants = {
        "ހ": "h",
        "ށ": "sh",
        "ނ": "n",
        "ރ": "r",
        "ބ": "b",
        "ޅ": "lh",
        "ކ": "k",
        "އ": "a",
        "ވ": "v",
        "މ": "m",
        "ފ": "f",
        "ދ": "dh",
        "ތ": "th",
        "ލ": "l",
        "ގ": "g",
        "ޏ": "y",
        "ސ": "s",
        "ޑ": "d",
        "ޒ": "z",
        "ޓ": "t",
        "ޔ": "y",
        "ޕ": "p",
        "ޖ": "j",
        "ޗ": "ch",
        "ޙ‎": "h",
        "ޚ‎": "kh",
        "ޛ‎": "z",
        "ޜ‎": "z",
        "ޝ‎": "sh",
        "ޝ": "sh"
    };

    let _punctuations = {
        "]": "[",
        "[": "]",
        "\\": "\\",
        "\'": "\'",
        "،": ",",
        ".": ".",
        "/": "/",
        "÷": "",
        "}": "{",
        "{": "}",
        "|": "|",
        ":": ":",
        "\"": "\"",
        ">": "<",
        "<": ">",
        "؟": "?",
        ")": ")",
        "(": "("
    };

    function transliterate(input) {
        // replace zero width non joiners
        input = input.replace(/[\u200B-\u200D\uFEFF]/g, '');


        let v = '';

        // replace words ending in shaviyani with 'ah'
        input = input.replace((/(އަށް)\B/ig), 'ah');
        input = input.replace((/(ށް)\B/ig), 'h');

        // replace thaa sukun with 'i'
        input = input.replace((/(ތް)\B/ig), 'i');

        // replace words ending in alif sukun with 'eh'
        input = input.replace((/(އެއް)\B/ig), 'eh');
        input = input.replace((/(ެއް)\B/ig), 'eh');
        input = input.replace((/(ިއް)\B/ig), 'ih');


        // replace alif compounds first so they don't get in the way
        for (var k in _alif_compounds) {
            if (!_alif_compounds.hasOwnProperty(k)) continue;
            v = _alif_compounds[k];
            input = input.replace(new RegExp(k, 'ig'), v);
        }

        // replace words ending in alif sukun with 'ah'
        input = input.replace((/(ައް)\B/ig), 'ah');

        // replace words ending ai bai fili
        input = input.replace((/(ައި)\B/ig), 'ai');

        // remaining consonants
        for (var k in _consonants) {
            if (!_consonants.hasOwnProperty(k)) continue;

            v = _consonants[k];
            input = input.replace(new RegExp(k, 'g'), v);
        }

        // vowels
        for (var k in _vowels) {
            if (!_vowels.hasOwnProperty(k)) continue;

            v = _vowels[k];

            input = input.replace(new RegExp(k, 'g'), v);
        }

        // capitalize first letter of sentence
        input = input.replace(/(^\s*\w|[\.\!\?]\s*\w)/g, function (c) {
            return c.toUpperCase();
        });

        for (var k in _punctuations) {
            let p = _punctuations[k];
            if (!_punctuations.hasOwnProperty(k)) continue;
            input = input.replace(k, p);
        }

        return input;
    }

    // _____ construct
    return transliterate(input);
};

$(document).ready(function () {
    $('[data-edit-iframe-modal]').on('click', function (e) {
        e.preventDefault();

        var url = $(this).prop('href');
        var modal = $($(this).data('edit-iframe-modal'));
        var iframe = modal.find('iframe');

        iframe.unbind('load');
        iframe.prop('src', url);


        modal.modal('show');
    });

    $('[data-save-iframe]').on('click', function (e) {
        e.preventDefault();

        var _this = this;
        var iframe = $($(this).data('save-iframe'));
        var iframe_contents = iframe.contents();
        var form = iframe_contents.find('form');
        var confirm_save = $(this).data('confirm-save') || false;
        var redirect_url = $(this).data('redirect-url');

        if (confirm_save) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Just checking if you wanted to really do this.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, continue!',
            }).then(function (result) {
                if (result.value) {
                    saveIframe();
                }
            });
        } else {
            saveIframe();
        }

        function saveIframe() {
            toggleLoading($(_this), true);

            iframe.unbind('load');

            iframe.on('load', function () {
                toggleLoading($(_this), false);

                // check for any validation errors
                if (iframe.contents().find('.is-invalid').length < 1) {
                    notify('Success! ', 'Your inputs have been saved.', 'success');

                    // Uncomment if you want to reload whole page instead of only the iframe
                    redirectPage(redirect_url);
                }
            });

            form.submit();
        }
    });

    // append to html
    $('[data-append]').on('click', function (e) {
        e.preventDefault();
        var elem = $($(this).data('append'));
        var html = $(this).data('append-html');
        var id = elem.data('next-id') || 0;

        html = html.replace(/:id/g, id);
        html = html.replace(/:no/g, id + 1);
        elem.append(JSON.parse(html));

        id++;
        elem.data('next-id', id);
    });

    /**
     * Media Picker
     */
    $('.media-picker').on('click', '[data-select-media]', function (e) {
        e.preventDefault();

        var media_picker = $(this).closest('.media-picker');
        var single = media_picker.data('single') || false;
        var media_thumb = $(this).closest('.media-thumb');

        var url = $(this).prop('href');
        var id = $(this).data('select-media');

        var selected = media_thumb.hasClass('selected');

        if (selected) {
            // remove from the selected
            media_thumb.removeClass('selected');
        } else {
            // if single, clear the current selections
            if (single) {
                media_picker.find('.media-thumb.selected').removeClass('selected');
            }

            // add the selection
            media_thumb.addClass('selected');
        }
    });

    /**
     * Attachment Input
     */
    $('[data-attachment-input]').each(function () {
        var _this = this;
        var type = $(_this).data('attachment-input');
        var background_preview = $(_this).data('background-preview') || false;
        var media_picker_url = window.Laravel.mediaPicker + '?single=1';

        if (type) {
            media_picker_url += '&type=' + type;
        }

        $(_this).find('[data-triggers="remove-file"]').on('click', function (e) {
            e.preventDefault();

            // abort
            if ($(_this).find('input[type="hidden"]').prop('disabled')) {
                return;
            }

            $(_this).find('input[type="hidden"]').val('');

            if (type == 'image') {
                $(_this).find('.fileinput-preview').html('')
                    .css('background-image', 'none');
            } else {
                $(_this).find('.fileinput-filename').html('');
            }

            $(_this).removeClass('fileinput-exists');
            $(_this).addClass('fileinput-new');
        });

        $(_this).find('[data-triggers="select-file"]').on('click', function (e) {
            e.preventDefault();

            // abort
            if ($(_this).find('input[type="hidden"]').prop('disabled')) {
                return;
            }

            var dialog = bootbox.dialog({
                title: 'Media Library',
                message: '<p><i class="loading"></i> Loading...</p>',
                size: 'xl',
                buttons: {
                    cancel: {
                        label: 'Cancel',
                        className: 'btn-light',
                        callback: function () {
                            //
                        }
                    },
                    ok: {
                        label: 'Select File',
                        className: 'btn-primary',
                        callback: function () {
                            var iframe = $(this).find('iframe').contents();
                            var media_picker = iframe.find('.media-picker');
                            var selected = media_picker.find('.media-thumb.selected').first();

                            if (!selected.length) {
                                $(this).find('.alert').show();
                                return false;
                            } else {
                                var selected_media = selected.find('[data-select-media]');
                                var file_id = selected_media.data('select-media');
                                var file_url = selected_media.prop('href');
                                var preview_url = selected_media.data('preview');

                                $(_this).find('input[type="hidden"]').val(file_id);

                                if (type == 'image') {
                                    if (background_preview) {
                                        $(_this).find('.fileinput-preview').css('background-image', 'url(' + file_url + ')');
                                    } else {
                                        $(_this).find('.fileinput-preview').html('<img src="' + preview_url + '">');
                                    }
                                } else {
                                    $(_this).find('.fileinput-filename').html('<a href="' + file_url + '" target="_blank">' + file_url + '</a>');
                                }

                                $(this).find('.alert').hide();
                                $(_this).removeClass('fileinput-new');
                                $(_this).addClass('fileinput-exists');
                            }
                        }
                    }
                }
            });

            dialog.init(function () {
                var html =
                    '<div class="alert alert-danger" style="display: none">Please select a file!</div>' +
                    '<div class="embed-responsive embed-responsive-16by9">' +
                    '<iframe class="embed-responsive-item" src="' + media_picker_url + '"></iframe>' +
                    '</div>';

                dialog.find('.bootbox-body').html(html);
            });

        });
    });

    /**
     * Attachment Gallery
     */
    $('[data-attachment-gallery]').each(function () {
        var _this = this;
        var input_name = $(_this).data('attachment-gallery');
        var type = $(_this).data('attachment-type') || 'image';
        var media_picker_url = window.Laravel.mediaPicker + '?single=0';

        if (type) {
            media_picker_url += '&type=' + type;
        }

        $(_this).on('js-deleted', function (e) {
            // check how many remaining
            var selected_media_ids = $(_this).find('input').serialize();

            if (!selected_media_ids) {
                $(_this).find('.attachment-gallery-placeholder').show();
            }
        });

        $(_this).on('click', function (e) {
            // check if a direct click or click on placeholder
            if (e.target !== this && (!$(e.target).closest('.attachment-gallery-placeholder').length)) {
                return;
            }

            e.preventDefault();

            var selected_media_ids = $(_this).find('input').serialize();
            selected_media_ids = selected_media_ids.replace(new RegExp(input_name, 'g'), 'selected');

            if (selected_media_ids) {
                media_picker_url += '&' + selected_media_ids;
            }

            var dialog = bootbox.dialog({
                title: 'Media Library',
                message: '<p><i class="loading"></i> Loading...</p>',
                size: 'xl',
                buttons: {
                    cancel: {
                        label: 'Cancel',
                        className: 'btn-light',
                        callback: function () {
                            //
                        }
                    },
                    ok: {
                        label: 'Select Files',
                        className: 'btn-primary',
                        callback: function () {
                            var iframe = $(this).find('iframe').contents();
                            var media_picker = iframe.find('.media-picker');
                            var selected = media_picker.find('.media-thumb.selected');

                            if (!selected.length) {
                                $(this).find('.alert').show();
                                return false;
                            } else {
                                $(selected).each(function (e) {
                                    var selected_media = $(this).find('[data-select-media]');

                                    var file_id = selected_media.data('select-media');
                                    var file_url = selected_media.prop('href');
                                    var preview_url = selected_media.data('thumb');
                                    var file_name = selected_media.data('file-name');
                                    var media_icon = selected_media.data('media-icon');
                                    var media_type = selected_media.data('media-type');

                                    // check if the file is already selected
                                    if ($(_this).find('[data-attachment="' + file_id + '"]').length) {
                                        // abort
                                        return
                                    }

                                    if (media_type !== 'image') {
                                        var html =
                                            '<div data-attachment="' + file_id + '" class="attachment-thumb dz-preview dz-file-preview">' +
                                            '<div class="dz-image d-flex justify-content-around align-items-center">' +
                                            `<i class="${media_icon} zmdi-hc-4x"></i>` +
                                            '</div>' +
                                            '<a href="" class="dz-remove" data-delete=".attachment-thumb"></a>' +
                                            `<p class="pt-2 text-center text-muted">${file_name}</p>` +
                                            '<input type="hidden" name="' + input_name + '[]" value="' + file_id + '">' +
                                            '</div>';
                                    } else {
                                        var html =
                                            '<div data-attachment="' + file_id + '" class="attachment-thumb dz-preview dz-file-preview">' +
                                            '<div class="dz-image">' +
                                            '<img src="' + preview_url + '" alt="">' +
                                            `<i class="${media_icon} media-icon"></i>` +
                                            '</div>' +
                                            '<a href="" class="dz-remove" data-delete=".attachment-thumb"></a>' +
                                            `<p class="pt-2 text-center text-muted">${file_name}</p>` +
                                            '<input type="hidden" name="' + input_name + '[]" value="' + file_id + '">' +
                                            '</div>';
                                    }

                                    $(_this).append(html);
                                });


                                $(this).find('.alert').hide();
                                $(_this).find('.attachment-gallery-placeholder').hide();
                            }
                        }
                    }
                }
            });

            dialog.init(function () {
                var html =
                    '<div class="alert alert-danger" style="display: none">Please select at least 1 file!</div>' +
                    '<div class="embed-responsive embed-responsive-16by9">' +
                    '<iframe class="embed-responsive-item" src="' + media_picker_url + '"></iframe>' +
                    '</div>';

                dialog.find('.bootbox-body').html(html);
            });

        });
    });

    /**
     * Document Attachment Gallery
     */
    $('[data-document-attachment-gallery]').each(function () {
        var _this = this;
        var input_name = $(_this).data('document-attachment-gallery');
        var type = $(_this).data('attachment-type') || 'image';
        var media_picker_url = window.Laravel.mediaPicker + '?single=0';

        if (type) {
            media_picker_url += '&type=' + type;
        }

        $(_this).on('js-deleted', function (e) {
            // check how many remaining
            var selected_media_ids = $(_this).find('input').serialize();

            if (!selected_media_ids) {
                $(_this).find('.attachment-gallery-placeholder').show();
            }
        });

        $(_this).on('click', function (e) {
            // check if a direct click or click on placeholder
            if (e.target !== this && (!$(e.target).closest('.attachment-gallery-placeholder').length)) {
                return;
            }

            e.preventDefault();

            var selected_media_ids = $(_this).find('input').serialize();
            selected_media_ids = selected_media_ids.replace(new RegExp(input_name, 'g'), 'selected');

            if (selected_media_ids) {
                media_picker_url += '&' + selected_media_ids;
            }

            var dialog = bootbox.dialog({
                title: 'Media Library',
                message: '<p><i class="loading"></i> Loading...</p>',
                size: 'xl',
                buttons: {
                    cancel: {
                        label: 'Cancel',
                        className: 'btn-light',
                        callback: function () {
                            //
                        }
                    },
                    ok: {
                        label: 'Select Files',
                        className: 'btn-primary',
                        callback: function () {
                            var iframe = $(this).find('iframe').contents();
                            var media_picker = iframe.find('.media-picker');
                            var selected = media_picker.find('.media-thumb.selected');

                            if (!selected.length) {
                                $(this).find('.alert').show();
                                return false;
                            } else {
                                $(selected).each(function (e) {
                                    var selected_media = $(this).find('[data-select-media]');

                                    var file_id = selected_media.data('select-media');
                                    var file_url = selected_media.prop('href');
                                    var preview_url = selected_media.data('thumb');
                                    var file_name = selected_media.data('file-name');
                                    var media_icon = selected_media.data('media-icon');
                                    var media_type = selected_media.data('media-type');

                                    // check if the file is already selected
                                    if ($(_this).find('[data-attachment="' + file_id + '"]').length) {
                                        // abort
                                        return
                                    }

                                    if (media_type !== 'image') {
                                        var html =
                                            '<div data-attachment="' + file_id + '" class="row attachment-row w-75">' +
                                            '<div class="col-md-3">' +
                                            '<div class="attachment-thumb dz-preview dz-file-preview m-0 w-100">' +
                                            '<div class="text-center">' +
                                            `<i class="${media_icon} zmdi-hc-4x mt-4"></i>` +
                                            '</div>' +
                                            '<a href="" class="dz-remove" data-delete=".attachment-thumb"></a>' +
                                            '</div>' +
                                            '<div class="col-md-9">' +
                                            `<p class="pt-4 dz-filename">${file_name}</p>` +
                                            '<input type="hidden" name="' + input_name + '[]" value="' + file_id + '">' +
                                            '</div>' +
                                            '</div>';
                                    } else {
                                        var html =
                                            '<div class="row attachment-row w-75" data-attachment="' + file_id + '">' +
                                            '<div class="col-md-3">' +
                                            '<div class="attachment-thumb dz-preview dz-file-preview m-0 w-100">' +
                                            '<img class="img-fluid" src="' + preview_url + '" alt="' + file_name + '">' +
                                            '<a href="" class="dz-remove" data-delete=".attachment-thumb"></a>' +
                                            '</div>' +
                                            '</div>' +
                                            '<div class="col-md-9">' +
                                            `<p class="pt-4 dz-filename">${file_name}</p>` +
                                            '</div>' +
                                            '<input type="hidden" name="' + input_name + '[]" value="' + file_id + '">' +
                                            '</div>';
                                    }

                                    $(_this).append(html);
                                });


                                $(this).find('.alert').hide();
                                $(_this).find('.attachment-gallery-placeholder').hide();
                            }
                        }
                    }
                }
            });

            dialog.init(function () {
                var html =
                    '<div class="alert alert-danger" style="display: none">Please select at least 1 file!</div>' +
                    '<div class="embed-responsive embed-responsive-16by9">' +
                    '<iframe class="embed-responsive-item" src="' + media_picker_url + '"></iframe>' +
                    '</div>';

                dialog.find('.bootbox-body').html(html);
            });

        });
    });
});

Dropzone.autoDiscover = false;
