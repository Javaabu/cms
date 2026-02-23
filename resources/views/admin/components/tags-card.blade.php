@php
    $name = $name ?? 'tags';
    $title = $title ?? _d('Tags');
    $disabled = ! empty($disabled);

    $selected_tags = isset($model) ? $model->tagWords()
                                           ->select('tags.id', 'tags.name')
                                           ->pluck('name')->all() : old('tags', []);

    if (! is_array($selected_tags)) {
        $selected_tags = [$selected_tags];
    }

    $tags = App\Models\Tag::whereIn('name', $selected_tags)
                          ->orderBy('name')
                          ->pluck('name', 'name');

    $tags_id = $name.'-list';
@endphp

<x-forms::card :title="$title">
    <x-forms::select2 :name="$name.'[]'" :options="$tags" :default="$selected_tags" :ajax-url="translate_route('api.tags.index')"
                      :placeholder="__('Enter tag words..')" alow-clear tags id-field="name"
                      :disabled="$disabled" multiple />
    @if(! $disabled)
        <x-forms::hidden :name="'sync_'.$name" :default="1" />
    @endif
</x-forms::card>
