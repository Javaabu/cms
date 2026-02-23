@php
    $disabled = ! empty($disabled);
    $collection = $collection ?? $input_name;
    $selected_media = isset($model) ? $model->getAttachmentMedia($collection) : null;
    $media_type = $media_type ?? 'image';
@endphp
<div class="dropzone {{ $disabled ? 'disabled' : 'dz-clickable' }}"
     @if(! $disabled)
         data-deletable=".attachment-thumb"
     {{--data-sortable=".attachment-thumb"--}}
     data-attachment-gallery="{{ $input_name }}"
     data-attachment-type="{{ $media_type }}"
    @endif
>
    <div class="dz-default dz-message attachment-gallery-placeholder"
         @if($selected_media && $selected_media->isNotEmpty())
             style="display: none"
        @endif
    >
        <span>{{ __('Click here to select :media_type', ['media_type' => Str::plural($media_type)]) }}</span>
    </div>

    @if($selected_media && $selected_media->isNotEmpty())
        @foreach($selected_media as $media)
            <div data-attachment="{{ $media->id }}" class="attachment-thumb dz-preview dz-file-preview">
                @if($media->type_slug == 'image')
                    <div class="dz-image">
                        <img src="{{ $media->getUrl('thumb') }}" alt="">
                    </div>
                @else
                    <div class="dz-image d-flex justify-content-around align-items-center">
                        <i class="{{ $media->icon }} zmdi-hc-4x"></i>
                    </div>
                @endif
                @if(! $disabled)
                    <a href="" class="dz-remove" data-delete=".attachment-thumb"></a>
                @endif
                <p class="pt-2 text-center text-muted">{{ $media->short_name }}</p>
                <x-forms::hidden :name="$input_name.'[]'" :default="$media->id" />
            </div>
        @endforeach
    @endif

    <x-forms::hidden :name="'sync_'.$input_name" :default="1" />
</div>
