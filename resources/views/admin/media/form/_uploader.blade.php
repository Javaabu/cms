@php use Javaabu\Cms\Media\AllowedMimeTypes; @endphp
<div class="card">
    <div class="card-body">
        <h4 class="card-title">{{ _d('Upload Media') }}</h4>

        @if(isset($type))
            <x-forms::hidden name="type" :value="$type" />
        @endif

        <h6 class="card-subtitle">
            {{ _d('Drop files here to upload.') }}
            @if($max_size = get_setting('max_upload_file_size'))
                {{ _d('You can upload up to maximum :size per file for non image files.', ['size' => format_file_size($max_size)]) }}
            @endif
            @if($max_size = get_setting('max_image_file_size'))
                <br>
                {{ _d('For image formats you can upload up to maximum :size per file.', ['size' => format_file_size($max_size)]) }}
            @endif
        </h6>

        <div id="files-drop" class="dropzone">
        </div>
    </div>
</div>

<div class="{{ $view == 'grid' ? '' : 'card' }} files-card" style="display: none">
    @if($view == 'grid')
        <div class="uploaded-files">
            <div class="row"></div>
        </div>
    @else
        <x-forms::table
            model="media"
            :no-bulk="true"
            :no-checkbox="true"
            :no-pagination="true"
            table-class="uploaded-files mb-0"
        >
            <x-slot:headers>
                <x-forms::table.heading colspan="2" :label="__('Name')" />
            </x-slot:headers>
        </x-forms::table>   
    @endif
</div>


@push('scripts')
    <script type="text/javascript">
        Dropzone.autoDiscover = false;
        var mediaLibraryUrl = '{{ translate_route('admin.media.index') }}';
        var uploadedFiles = [];

        $(document).ready(function () {
            var mediaLibraryDropzone = new Dropzone('#files-drop', {
                url: mediaLibraryUrl,
                dictResponseError: '{{ _d('Error uploading file!') }}',
                dictDefaultMessage: '{{ _d('Drop file here to upload') }}',
                dictFileTooBig: '{{ _d('File is too big (\{\{filesize\}\} MB). Max filesize: \{\{maxFilesize\}\} MB') }}',
                dictInvalidFileType: '{{ _d('You cannot upload files of this type.') }}',
                maxFilesize: {{ floor(get_setting('max_upload_file_size') / 1024) }},
                uploadMultiple: false,
                parallelUploads: 50,
                timeout: 600000,
                acceptedFiles: '{{ AllowedMimeTypes::getAllowedMimeTypesString($type ?? '') }}',
                error: function (file, response) {
                    var message = '';

                    if (response.hasOwnProperty('errors')) {
                        var errors = response.errors;

                        if (errors.hasOwnProperty('file')) {
                            message = errors.file[0];
                        }
                    }

                    if (!message) {
                        message = ($.type(response) === "string") ? response
                            : '{{ _d('Error uploading file!') }}';
                    }

                    file.previewElement.classList.add('dz-error');
                    _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]');
                    _results = [];
                    for (_i = 0, _len = _ref.length; _i < _len; _i++) {
                        node = _ref[_i];
                        _results.push(node.textContent = message);
                    }
                    return _results;
                },
                init: function () {
                    this.on('sending', function (file, xhr, form_data) {
                        var append_data = getJsonFormData($('#append-data').find('select, input'));

                        $.each(append_data, function (key, value) {
                            if (Array.isArray(value)) {
                                for (var i = 0; i < value.length; i++) {
                                    form_data.append(key, value[i]);
                                }
                            } else {
                                form_data.append(key, value);
                            }
                        });
                    });

                    this.on('success', function (dropzone_file, file) {
                        if (uploadedFiles.length < 1) {
                            $('.files-card').show();
                        }

                        // add to uploaded files
                        uploadedFiles.push(file);

                        var file_name = $('<div />').text(file.file_name).html(); // escape html
                        var file_title = $('<div />').text(file.name).html(); // escape html
                        var tags_html = '';

                        var file_elem_id = 'file-' + file.id;
                        var delete_link = '';
                        var icon = file.type_slug != 'image' ? '<i class="media-icon ' + file.icon + '"></i>' : '';

                        @if($view == 'grid')
                        var html =
                            '<div class="col-lg-2 col-md-3 col-6">' +
                            '<div id="' + file_elem_id + '" title="' + file_title + '" class="card media-thumb square img-header" style="background-image: url(' + file.preview + ')">' +
                            '<div class="square-content card-body">' +
                            icon +
                            '<a href="' + file.location + '" ' +
                            'data-thumb="' + file.thumb + '" ' +
                            'data-preview="' + file.preview + '" ' +
                            'data-large="' + file.large + '" ' +
                            'data-select-media="' + file.id + '" ' +
                            // 'data-file-name="' + file.name + '" ' +
                            // 'data-media-icon="' + file.icon + '" ' +
                            // 'data-media-type="' + file.type_slug + '" ' +
                            'class="view-overlay"></a>' +
                            '</div>' +
                            '</div>' +
                            '</div>';

                        $('.uploaded-files > .row').append(html);
                        @else
                        if (file.tags) {
                            tags_html = '<div class="tags">';

                            $.each(file.tags, function (key, value) {
                                var tag_name = $('<div />').text(value).html(); // escape html
                                tags_html += '<a>' + tag_name + '</a>';
                            });

                            tags_html += '</div>';
                        }

                        @can('delete_media')
                        var delete_link_id = 'delete-file-' + file.id;
                        delete_link =
                            '<a id="' + delete_link_id + '" class="actions__item zmdi zmdi-delete" href="#" title="Delete">' +
                            ' <span>{{ _d('Delete') }}</span>' +
                            '</a>';
                        @endcan

                        var html =
                            '<tr id=' + file_elem_id + '>' +
                            '<td class="avatar">' +
                            '<a target="_blank" href="' + file.edit_url + '">' +
                            '<div title="' + file_title + '" class="square img-header" style="background-image: url(' + file.preview + ')">' +
                            '<div class="square-content">' +
                            icon +
                            '</div>' +
                            '</div>' +
                            '</a>' +
                            '</td>' +
                            '<td data-col="{{ _d('Name') }}">' +
                            '<a target="_blank" href="' + file.edit_url + '">' + file.name + '</a>' +
                            '<span class="d-block">' + file_name + '</span>' +
                            '<div class="table-actions actions">' +
                            '<a class="actions__item"><span>ID: ' + file.id + '</span></a> ' +

                            '<a class="actions__item zmdi zmdi-edit" target="_blank" href="' + file.edit_url + '" title="Edit">' +
                            ' <span>{{ _d('Edit') }}</span>' +
                            '</a> ' +

                            delete_link +
                            '</div>' +
                            '</td>' +
                            '</tr>';

                        $('.uploaded-files > tbody').append(html);


                        @can('delete_media')
                        // Listen to the click event
                        $('.files-card').on('click', '#' + delete_link_id, function (e) {
                            // Make sure the button click doesn't submit the form:
                            e.preventDefault();
                            e.stopPropagation();

                            swal({
                                title: '{{ _d('Are you sure you want to remove this file?') }}',
                                text: '{{ _d('You will not be able to undo this delete operation!') }}',
                                type: 'warning',
                                showCancelButton: true,
                                confirmButtonText: '{{ _d('Yes, Delete!') }}',
                                cancelButtonText: '{{ _d('Cancel') }}',
                            }).then(function (result) {
                                if (result.value) {
                                    //remove file extension
                                    var id = file.id;
                                    $.ajax({
                                        type: 'DELETE',
                                        url: mediaLibraryUrl + '/' + id,
                                        success: function (data) {
                                            removeElement(uploadedFiles, file);
                                            $('#' + file_elem_id).remove();

                                            notify(
                                                '{{ _d('Success!') }} ',
                                                '{{ _d('File') }} ' + file.name + ' {{ _d('removed successfully.') }}',
                                                'success'
                                            );

                                            // hide table if 0 files
                                            if (uploadedFiles.length < 1) {
                                                $('.files-card').hide();
                                            }
                                        },
                                        error: function (jqXHR, textStatus, errorThrow) {
                                            if (jqXHR.status != 404) {
                                                notify(
                                                    '{{ _d('Error!') }} ',
                                                    '{{ _d('Error while removing file') }} ' + file.name,
                                                    'error'
                                                );
                                            }
                                        }
                                    });
                                }
                            });

                        });
                        @endcan
                        @endif

                        // remove file from drop zone
                        this.removeFile(dropzone_file);
                    });
                }
            });

        });
    </script>
@endpush


