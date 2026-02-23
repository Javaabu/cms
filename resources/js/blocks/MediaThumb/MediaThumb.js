/**
 * Media Document Block
 */

//require('./MediaDocument.css').toString();

import './MediaThumb.css';

class MediaThumb {
    constructor({data, api, config}) {
        this.api = api;
        this.config = config || {};
        this.data = data;
        this.data = {
            media: data.media || '',
            url: data.url || '',
            text: data.text || '',
            title: data.title || '',
        };

        this.wrapper = undefined;
        this.settings = [];
    }

    static get toolbox() {
        return {
            title: 'Embed Media Thumbnail',
            icon: '<svg width="17" height="15" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg" >\n' +
                '<path d="M224 136V0H24C10.7 0 0 10.7 0 24v464c0 13.3 10.7 24 24 24h336c13.3 0 24-10.7 24-24V160H248c-13.2 0-24-10.8-24-24zm64 236c0 6.6-5.4 12-12 12H108c-6.6 0-12-5.4-12-12v-8c0-6.6 5.4-12 12-12h168c6.6 0 12 5.4 12 12v8zm0-64c0 6.6-5.4 12-12 12H108c-6.6 0-12-5.4-12-12v-8c0-6.6 5.4-12 12-12h168c6.6 0 12 5.4 12 12v8zm0-72v8c0 6.6-5.4 12-12 12H108c-6.6 0-12-5.4-12-12v-8c0-6.6 5.4-12 12-12h168c6.6 0 12 5.4 12 12zm96-114.1v6.1H256V0h6.1c6.4 0 12.5 2.5 17 7l97.9 98c4.5 4.5 7 10.6 7 16.9z"/></svg>'
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
        this.wrapper.classList.add('media-image');
        var _this = this;

        const image_wrapper = document.createElement('div');
        image_wrapper.classList.add('image-wrapper');

        const document_link = document.createElement('a');
        image_wrapper.appendChild(document_link);

        const media_btn = document.createElement('div');
        media_btn.classList.add(this.api.styles.button);
        media_btn.innerHTML = this.data && this.data.url ? 'Change Media' : 'Select Media';
        image_wrapper.appendChild(media_btn);

        this.wrapper.appendChild(image_wrapper);

        const input_wrapper = document.createElement('div');
        input_wrapper.classList.add('input-wrapper');

        const title = document.createElement('input');
        title.classList.add(this.api.styles.input);
        title.value = this.data.title || '';
        title.placeholder = 'Enter a title here...';
        input_wrapper.appendChild(title);

        this.wrapper.appendChild(input_wrapper);

        var media_picker_url = window.Laravel.mediaPicker + '?single=1&type=document';

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
                                _this.data.url = large_url;

                                media_btn.innerHTML = 'Change Image';
                                _this._createImage(_this.data.url);

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

        if (this.data && this.data.url) {
            this._createImage(this.data.url);
        }

        return this.wrapper;
    }

    _createImage(url) {
        const document_title = this.wrapper.querySelector('a');
        document_title.href = url;
        document_title.text = url;
    }

    save(blockContent) {
        const image = blockContent.querySelector('a');
        const title = blockContent.querySelector('input');

        return Object.assign(this.data, {
            title: title.value || '',
            url: image.href,
        });
    }

    validate(savedData) {
        if (!(savedData.url.trim() || savedData.title.trim() || savedData.text.trim())) {
            return false;
        }

        return true;
    }

    renderSettings() {
        const wrapper = document.createElement('div');
        var buttons = {};

        this.settings.forEach(position => {
            let button = document.createElement('div');

            button.classList.add(this.api.styles.settingsButton);
            button.classList.toggle(this.api.styles.settingsButtonActive, this.data.imagePosition === position.name);
            button.innerHTML = position.icon;
            button.classList.add('btn-' + position.name);
            wrapper.appendChild(button);

            button.addEventListener('click', () => {
                // toggle the buttons
                this.settings.forEach(setting => {
                    let btn = buttons[setting.name];
                    btn.classList.toggle(this.api.styles.settingsButtonActive, this.data.imagePosition === setting.name);
                });
            });

            buttons[position.name] = button;
        });

        return wrapper;
    }
}

export default  MediaThumb;
