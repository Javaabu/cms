/**
 * Media Image Block
 */

//require('./MediaImage.css').toString();
import './MediaImage.css';

class MediaImage {
    constructor({data, api, config}) {
        this.api = api;
        this.config = config || {};
        this.data = data;
        this.data = {
            media: data.media || '',
            url: data.url || '',
            text: data.text || '',
            title: data.title || '',
            imagePosition: data.imagePosition !== undefined ? data.imagePosition : 'top',
        };

        this.wrapper = undefined;
        this.settings = [
            {
                name: 'left',
                icon: `<svg width="54" height="36" viewBox="0 0 54 36" xmlns="http://www.w3.org/2000/svg"><path class="fil0" d="M11 4l11 0c3,0 5,2 5,5l0 7 0 2 0 9c0,3 -2,5 -5,5l-11 0c-3,0 -5,-2 -5,-5l0 -7 0 -3 0 -8c0,-3 2,-5 5,-5zm20 25l17 0 0 3 -17 0 0 -3zm0 -19l17 0 0 3 -17 0 0 -3zm0 6l17 0 0 4 -17 0 0 -4zm0 7l17 0 0 3 -17 0 0 -3zm0 -19l17 0 0 3 -17 0 0 -3zm-9 20l-3 3 3 0 0 -3zm-7 3l7 -7 0 -5 -10 10 -1 0 0 2 4 0zm-4 -6l10 -10 1 0 0 -2 -4 0 -7 7 0 5zm0 -9l3 -3 -3 0 0 3z"/></svg>`
            },
            {
                name: 'right',
                icon: `<svg width="54" height="36" viewBox="0 0 54 36" xmlns="http://www.w3.org/2000/svg"><path class="fil0" d="M32 4l11 0c3,0 5,2 5,5l0 7 0 2 0 9c0,3 -2,5 -5,5l-11 0c-3,0 -5,-2 -5,-5l0 -7 0 -3 0 -8c0,-3 2,-5 5,-5zm-26 0l17 0 0 3 -17 0 0 -3zm0 19l17 0 0 3 -17 0 0 -3zm0 -7l17 0 0 4 -17 0 0 -4zm0 -6l17 0 0 3 -17 0 0 -3zm0 19l17 0 0 3 -17 0 0 -3zm37 -5l-3 3 3 0 0 -3zm-7 3l7 -7 0 -5 -10 10 -1 0 0 2 4 0zm-4 -6l10 -10 1 0 0 -2 -4 0 -7 7 0 5zm0 -9l3 -3 -3 0 0 3z"/></svg>`
            },
            {
                name: 'top',
                icon: `<svg width="54" height="36" viewBox="0 0 54 36" xmlns="http://www.w3.org/2000/svg"><path class="fil0" d="M11 4l7 0 4 0 5 0 4 0 5 0 4 0 3 0c3,0 5,2 5,5l0 0 0 5 0 7c0,2 -2,5 -5,5l-7 0 -4 0 -5 0 -4 0 -5 0 -4 0 -3 0c-3,0 -5,-3 -5,-5l0 -1 0 -4 0 -7c0,-3 2,-5 5,-5zm-5 25l42 0 0 3 -42 0 0 -3zm11 -20l-6 6 0 4 10 -10 -4 0zm-6 2l2 -2 -2 0 0 2zm17 9l11 -11 -4 0 -11 11 4 0zm15 -10l-10 10 4 0 6 -5 0 -5zm-24 10l11 -11 -4 0 -11 11 4 0zm24 -1l-2 1 2 0 0 -1z"/></svg>`
            },
            {
                name: 'divider',
                icon: `<svg width="54" height="36" viewBox="0 0 54 36" xmlns="http://www.w3.org/2000/svg"><path class="fil0" d="M13 4l8 0 5 0 4 0 4 0 5 0 1 0 1 0c4,0 7,3 7,7l0 1 0 1 -10 0 4 -4 -4 0 -4 4 -5 0 4 -4 -4 0 -4 4 -4 0 4 -4 -5 0 -4 4 -4 0 4 -4 -5 0 0 4 -5 0 0 -2c0,-4 3,-7 7,-7zm-4 12l36 0 0 4 -36 0 0 -4zm39 7l0 2c0,4 -3,7 -7,7l-8 0 -5 0 -4 0 -4 0 -5 0 -1 0 -1 0c-4,0 -7,-3 -7,-7l0 -1 0 -1 10 0 -4 4 4 0 4 -4 5 0 -4 4 4 0 4 -4 4 0 -4 4 5 0 4 -4 4 0 -4 4 5 0 0 -4 5 0z"/></svg>`
            }
        ];
    }

    static get toolbox() {
        return {
            title: 'Image',
            icon: '<svg width="17" height="15" viewBox="0 0 336 276" xmlns="http://www.w3.org/2000/svg"><path d="M291 150V79c0-19-15-34-34-34H79c-19 0-34 15-34 34v42l67-44 81 72 56-29 42 30zm0 52l-43-30-56 30-81-67-66 39v23c0 19 15 34 34 34h178c17 0 31-13 34-29zM79 0h178c44 0 79 35 79 79v118c0 44-35 79-79 79H79c-44 0-79-35-79-79V79C0 35 35 0 79 0z"/></svg>'
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
        title.value = this.data.title || '';
        title.placeholder = 'Enter a title here...';
        input_wrapper.appendChild(title);

        const text = document.createElement('div');
        text.contentEditable = true;
        text.innerHTML = this.data.text || '';
        input_wrapper.appendChild(text);

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
                                let file_url = _this._stripHostFromLink(selected_media.prop('href'));
                                let large_url = _this._stripHostFromLink(selected_media.data('large'));

                                // assign image
                                _this.data.media = file_id;
                                _this.data.url = file_url;

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
        const image = this.wrapper.querySelector('img');
        image.src = url;

        this._acceptImagePositionView();
    }

    save(blockContent) {
        const image = blockContent.querySelector('img');
        const text = blockContent.querySelector('[contenteditable]');
        const title = blockContent.querySelector('input');

        var file_url = this._stripHostFromLink(image.src);

        return Object.assign(this.data, {
            title: title.value || '',
            url: file_url,
            text: text.innerHTML || ''
        });
    }

    validate(savedData) {
        if (!(savedData.url.trim() || savedData.title.trim() || savedData.text.trim())) {
            return false;
        }

        let allowed_positions = ['top', 'left', 'right', 'divider'];

        return allowed_positions.includes(savedData.imagePosition);
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
                this._toggleImagePosition(position.name);

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

    /**
     * @private
     * Click on the Settings Button
     * @param {string} position — tune name from this.settings
     */
    _toggleImagePosition(position) {
        this.data.imagePosition = position;
        this._acceptImagePositionView();
    }

    /**
     * Add specified class corresponds with activated image position
     * @private
     */
    _acceptImagePositionView() {
        this.settings.forEach(position => {
            this.wrapper.classList.toggle(position.name, this.data.imagePosition === position.name);
        });
    }

    _stripHostFromLink(link) {
        // Strip site domain from links if internal link
        let site_domain = window.Laravel.admin_domain;
        let internal_link = link.indexOf(site_domain, 0) === 0 || link.indexOf('http') !== 0;

        if (internal_link) {
            link = link.replace(site_domain, '')
        } else if (this._isSubdomain(site_domain)) {
            link = link.replace(window.Laravel.public_domain, '')
        }

        return link;
    }

    _isSubdomain(url) {
        url = url || window.Laravel.site_domain; // just for the example
        var regex = new RegExp(/^([a-z]+:\/{2})?([\w-]+\.[\w-]+\.\w+)$/);

        return !!url.match(regex); // make sure it returns boolean
    }
}

export default MediaImage;
