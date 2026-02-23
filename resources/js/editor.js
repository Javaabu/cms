/**
 * Editor JS
 */

import EditorJS from '@editorjs/editorjs';
import Header from '@editorjs/header';
//const LinkTool = require('@editorjs/link');
import RawTool from '@editorjs/raw';
//const SimpleImage = require('@editorjs/simple-image');
import SimpleImage from '@editorjs/simple-image';
//const List = require('@editorjs/list');
import List from '@editorjs/list';
//const Embed = require('@editorjs/embed');
import Embed from '@editorjs/embed';
//const ImageTool = require('@editorjs/image');
//const Table = require('@editorjs/table');
//const Quote = require('@editorjs/quote');
import Quote from '@editorjs/quote';
import Table from '@editorjs/table';
/*const MediaImage = require('./blocks/MediaImage/MediaImage');
const ActionCard = require('./blocks/ActionCard/ActionCard');
const Related = require('./blocks/Related/Related');
const Document = require('./blocks/MediaDocument/MediaDocument');
const Statistics = require('./blocks/StatisticsSelector/StatisticsSelector');*/
import MediaImage from './blocks/MediaImage/MediaImage';
import ActionCard from './blocks/ActionCard/ActionCard';
import Related from './blocks/Related/Related';
import Document from './blocks/MediaDocument/MediaDocument';
import Thumbnail from './blocks/MediaThumb/MediaThumb';
import Statistics from './blocks/StatisticsSelector/StatisticsSelector';
import MediaThumb from "./blocks/MediaThumb/MediaThumb";
import Delimiter from '@editorjs/delimiter';
import editorjsColumns  from '@calumk/editorjs-columns'


$('[data-editor-js]').each(function () {
    let _this = this;
    let target_form = $(_this).closest('form');
    let input_el = $($(this).data('editor-js') || '#content-edit');
    let holder_id = $(this).data('holder-id') || 'codex-editor';
    let upload_url = $(this).data('upload-url');
    let upload_types = $(this).data('upload-types') || 'image/*';

    if (!input_el.length) {
        // abort
        return;
    }

    let raw_data = input_el.val();
    let json_data = {};

    try {
        json_data = JSON.parse(raw_data);
    } catch (ex) {
        // fallback to a raw block
        if (raw_data) {
            json_data = {
                blocks: [
                    {
                        type: 'raw',
                        data: {
                            html: raw_data
                        }
                    }
                ]
            }
        }

        console.error('Failed to parse editor json');
    }


    const editor = new EditorJS({
        holderId: holder_id,
        data: json_data,
        placeholder: 'Start writing...',
        tools: {
            header: {
                class: Header,
                shortcut: 'CMD+SHIFT+H',
            },
            raw: RawTool,
            image: {
                class: SimpleImage,
                inlineToolbar: true
            },
            list: {
                class: List,
                inlineToolbar: true,
            },
            embed: {
                class: Embed,
                config: {
                    services: {
                        youtube: true,
                        vimeo: true
                    }
                }
            },
            quote: Quote,
            table: {
                class: Table,
                inlineToolbar: true,
                config: {
                    rows: 2,
                    cols: 2,
                },
            },
            media_image: {
                class: MediaImage,
                inlineToolbar: true
            },
            action_card: {
                class: ActionCard,
                inlineToolbar: true
            },
            related: Related,
            document: Document,
            thumbnail: MediaThumb,
            statistics: Statistics,
            delimiter: {
                class: Delimiter,
            },
            columns : {
                class : editorjsColumns,
                config : {
                    tools : {
                        header: Header,
                        image: SimpleImage,
                        media_image: MediaImage,
                        action_card: ActionCard,
                        embed: Embed,
                        list: List,
                        related: Related
                    },
                    EditorJsLibrary : EditorJS //ref EditorJS - This means only one global thing
                }
            },
        },
    });

    target_form.submit(function (e) {
        e.preventDefault();

        // https://stackoverflow.com/questions/5721724/jquery-how-to-get-which-button-was-clicked-upon-form-submission
        // handle submit button action including
        // remove any existing hidden elements
        target_form.find('.editor-js-submit').remove();

        let active_el = $(document.activeElement);

        if (active_el.length && active_el.attr('name')) {
            $('<input />').attr('type', 'hidden')
                .attr('name', active_el.attr('name'))
                .attr('value', active_el.val())
                .attr('class', 'editor-js-submit')
                .appendTo(target_form);
        }

        editor.save().then((output_data) => {
            input_el.val(JSON.stringify(output_data));

            e.currentTarget.submit();
        }).catch((error) => {
            notify('', 'Error saving editor content!', 'danger');
            e.currentTarget.submit();
        });
    });
});
