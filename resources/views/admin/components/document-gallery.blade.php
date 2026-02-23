@php
    $disabled = ! empty($disabled);
    $collection = $collection ?? $input_name;
    $selected_media = isset($model) ? $model->getAttachmentMedia($collection) : null;
    $media_type = $media_type ?? 'image';
@endphp
<div class="dropzone {{ $disabled ? 'disabled' : 'dz-clickable' }}"
     @if(! $disabled)
         data-deletable=".attachment-thumb"
     {{--data-sortable=".attachment-thumb"--}} {{-- TODO fix sorting--}}
     data-document-attachment-gallery="{{ $input_name }}"
     data-attachment-type="{{ $media_type }}"
    @endif
>
    <div class="dz-default dz-message attachment-gallery-placeholder"
         @if($selected_media && $selected_media->isNotEmpty())
             style="display: none"
        @endif
    >
        <span>{{ __('Click here to select images') }}</span>
    </div>

    @if($selected_media && $selected_media->isNotEmpty())
        @foreach($selected_media as $media)
            <div class="row attachment-row w-75" data-attachment="{{ $media->id }}">
                <div class="col-md-3">
                    <div class="attachment-thumb dz-preview dz-file-preview m-0 w-100">
                        @if($media->type_slug == 'image')
                            <img class="img-fluid" src="{{ $media->getUrl('thumb') }}" alt="{{ $media->name }}">
                        @else
                            <div class="text-center">
                                <i class="{{ $media->icon }} zmdi-hc-4x mt-4"></i>
                            </div>
                        @endif
                        @if(! $disabled)
                            <a href="" class="dz-remove" data-delete=".attachment-row"></a>
                        @endif
                    </div>
                </div>

                <div class="col-md-9">
                    <p class="pt-4 dz-filename">{{ $media->name }}</p>
                </div>
                <x-forms::hidden :name="$input_name.'[]'" :default="$media->id" />
            </div>
        @endforeach
    @endif

    <x-forms::hidden :name="'sync_'.$input_name" :default="1" />
</div>
