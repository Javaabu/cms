@php
    $selected_galleries = isset($model) ? $model->relatedGalleries->pluck('id') : old('related_galleries', []);
    $galleries = \App\Models\Post::whereType($type->slug)->whereIn('id', $selected_galleries)->get();
    $galleries_for_select = (clone $galleries)->pluck('title', 'id');
    $option_attributes = (clone $galleries)->mapWithKeys(function ($gallery) {
                            return [$gallery->id => ['data-image' => $gallery->square_thumb ?? '', 'data-text' => $gallery->title ?? '']];
                        })->all(); //TODO
@endphp
<x-forms::card>
    <x-forms::select2 name="related_galleries[]" :label="__('Related Galleries')" :options="$galleries_for_select"
                      :default="$selected_galleries" class="select2-ajax-thumb"
                      :ajax-url="api_action([\App\Http\Controllers\Api\PostsController::class, 'index'], [\App\Models\PostType::whereSlug('galleries')->first()])"
                      allow-clear :required="!empty($required)" :disabled="!empty($disabled)" multiple
                      name-field="title" {{--:option_attributes="$option_attributes"--}}/>
    @if($model)
        <x-forms::hidden name="sync_related_galleries" :default="1" />
    @endif
</x-forms::card>
