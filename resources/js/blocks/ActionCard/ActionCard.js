/**
 * Action Card Block
 */

//require('./ActionCard.css').toString();
import './ActionCard.css';

class ActionCard {
    constructor({data, api, config}) {
        this.api = api;
        this.config = config || {};
        this.data = data;
        this.data = {
            media: data.media || '',
            image_url: data.image_url || '',
            url: data.url || '',
            text: data.text || '',
            title: data.title || '',
            btn_text: data.btn_text || ''
        };

        this.wrapper = undefined;
    }

    static get toolbox() {
        return {
            title: 'Action Card',
            icon: '<svg width="23" height="15" viewBox="0 0 23 15" xmlns="http://www.w3.org/2000/svg"><path class="fil0" d="M4 0l7 0 8 0c2,0 4,2 4,4l0 7c0,2 -2,4 -4,4l-8 0 -7 0c-2,0 -4,-2 -4,-4l0 -3 0 -1 0 -3c0,-2 2,-4 4,-4zm10 4l5 0 0 4 -5 0 0 -4zm0 5l4 0c0,0 1,1 1,1l0 0c0,1 -1,1 -1,1l-4 0c0,0 -1,0 -1,-1l0 0c0,0 1,-1 1,-1zm-3 -7l0 11 8 0c1,0 2,-1 2,-2l0 -7c0,-1 -1,-2 -2,-2l-8 0zm-2 9l-2 1 2 0 0 -1zm-4 1l4 -4 0 -2 -6 5 0 0 0 1 2 0zm-2 -3l6 -5 0 0 0 -1 -2 0 -4 4 0 2zm0 -5l2 -1 -2 0 0 1z"/></svg>'
        };
    }

    /**
     * Automatic sanitize config
     */
    static get sanitize() {
        return {
            title: false,
            media: false,
            url: false, // disallow HTML
            image_url: false,
            btn_text: false,
            text: {
                br: true,
                b: true,
                a: {
                    href: true
                },
                i: true
            }
        }
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('action-card');
        this.wrapper.classList.add('left');
        var _this = this;

        const image_wrapper = document.createElement('div');
        image_wrapper.classList.add('image-wrapper');

        const image = document.createElement('img');
        image_wrapper.appendChild(image);

        const media_btn = document.createElement('div');
        media_btn.classList.add(this.api.styles.button);
        media_btn.innerHTML = this.data && this.data.url ? 'Change Image' : 'Select Image';
        image_wrapper.appendChild(media_btn);

        this.wrapper.appendChild(image_wrapper);

        const input_wrapper = document.createElement('div');
        input_wrapper.classList.add('input-wrapper');

        const title = document.createElement('input');
        title.classList.add(this.api.styles.input);
        title.classList.add('title-input');
        title.value = this.data.title || '';
        title.placeholder = 'Enter a title here...';
        input_wrapper.appendChild(title);

        const text = document.createElement('div');
        text.classList.add(this.api.styles.input);
        text.dataset.placeholder = 'Enter card text here...';
        text.contentEditable = true;
        text.innerHTML = this.data.text || '';
        input_wrapper.appendChild(text);

        const url = document.createElement('input');
        url.classList.add(this.api.styles.input);
        url.classList.add('url-input');
        url.value = this.data.url || '';
        url.placeholder = 'Button url...';
        input_wrapper.appendChild(url);

        const btn_text = document.createElement('input');
        btn_text.classList.add(this.api.styles.input);
        btn_text.classList.add('btn-text-input');
        btn_text.value = this.data.btn_text || '';
        btn_text.placeholder = 'Read More';
        input_wrapper.appendChild(btn_text);

        this.wrapper.appendChild(input_wrapper);

        var media_picker_url = window.Laravel.mediaPicker + '?single=1&type=image';

        media_btn.addEventListener('click', (e) => {
            e.preventDefault();

            let dialog = bootbox.dialog({
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
                            let iframe = $(this).find('iframe').contents();
                            let media_picker = iframe.find('.media-picker');
                            let selected = media_picker.find('.media-thumb.selected').first();

                            if (!selected.length) {
                                $(this).find('.alert').show();
                                return false;
                            } else {
                                let selected_media = selected.find('[data-select-media]');
                                let file_id = selected_media.data('select-media');
                                let file_url = selected_media.prop('href');
                                let large_url = selected_media.data('large');

                                // assign image
                                _this.data.media = file_id;
                                _this.data.image_url = large_url;

                                media_btn.innerHTML = 'Change Image';
                                _this._createImage(_this.data.image_url);

                                $(this).find('.alert').hide();
                            }
                        }
                    }
                }
            });

            dialog.init(function () {
                let html =
                    '<div class="alert alert-danger" style="display: none">Please select a file!</div>' +
                    '<div class="embed-responsive embed-responsive-16by9">' +
                    '<iframe class="embed-responsive-item" src="' + media_picker_url + '"></iframe>' +
                    '</div>';

                dialog.find('.bootbox-body').html(html);
            });

        });

        if (this.data && this.data.image_url) {
            this._createImage(this.data.image_url);
        }

        return this.wrapper;
    }

    _createImage(image_url) {
        const image = this.wrapper.querySelector('img');
        image.src = image_url;
    }

    save(blockContent) {
        const image = blockContent.querySelector('img');
        const text = blockContent.querySelector('[contenteditable]');
        const title = blockContent.querySelector('.title-input');
        const btn_text = blockContent.querySelector('.btn-text-input');
        const url = blockContent.querySelector('.url-input');

        return Object.assign(this.data, {
            title: title.value || '',
            btn_text: btn_text.value || '',
            url: url.value || '',
            image_url: image.src,
            text: text.innerHTML || ''
        });
    }

    validate(savedData) {
        if (!(savedData.image_url.trim() || savedData.url.trim() || savedData.title.trim() || savedData.text.trim())) {
            return false;
        }

        return true;
    }
}

export default ActionCard;
