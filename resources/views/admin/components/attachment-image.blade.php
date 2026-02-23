@php
    $type = $type ?? 'image';
    $disabled = ! empty($disabled);
    $collection = $collection ?? $file_input_id;
    $selected_media = isset($model) ? $model->getFirstAttachmentMedia($collection) : null;
    $file_url = $selected_media ? $selected_media->getUrl($type == 'image' ? 'preview' : null) : null;
    $selected_file = $selected_media ? $selected_media->id : null;
@endphp
<div class="fileinput {{ ! empty($file_url) ? 'fileinput-exists' : 'fileinput-new' }}"
     data-attachment-input="{{ $type }}">
    <div id="{{ $file_input_id.'-preview' }}" class="fileinput-preview thumbnail">
        @if($type == 'image' && ! empty($file_url))
            <img src="{{ $file_url }}">
        @else
            {{ $file_url ?? '' }}
        @endif
    </div>
    <div>
        <span class="btn btn-info btn-file {{ $disabled ? 'disabled' : '' }} mb-1" data-triggers="select-file">
            <span class="fileinput-new btn--icon-text">
                <i class="zmdi zmdi-{{ $type == 'image' ? 'image' : 'file' }}"></i>&nbsp;
                {{ $type == 'image' ? _d('Select image') : _d('Select file') }}
            </span>
            <span class="fileinput-exists btn--icon-text">
                <i class="zmdi zmdi-folder"></i> {{ _d('Change') }}
            </span>
        </span>
        <x-forms::hidden :name="$file_input_id" :default="old($file_input_id, $selected_file ?? '')" :disabled="$disabled" />
        <a href="#" class="mb-1 btn btn-danger btn--icon-text fileinput-exists {{ $disabled ? 'disabled' : '' }}"
           data-triggers="remove-file">
            <i class="zmdi zmdi-close"></i> {{ _d('Remove') }}
        </a>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            $('[data-triggers="remove-file"]').each(function() {
                $(this).on('click', function(event) {
                    event.preventDefault();

                    if ($('#clear_file').length === 0) {
                        $('<input>', {
                            type: 'hidden',
                            name: 'clear_file',
                            id: 'clear_file',
                            value: '1'
                        }).appendTo($(this).closest('form'));
                    }
                });
            });

            // Handle select file button click
            $('[data-triggers="select-file"]').each(function() {
                $(this).on('click', function() {
                    // Remove the hidden input field if it exists
                    if ($('#clear_file').length > 0) {
                        $('#clear_file').remove();
                    }
                });
            });
        });
    </script>
@endpush
