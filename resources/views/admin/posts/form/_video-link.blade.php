<x-forms::card :title="__('Video Link')">
    @component('admin.components.post-type.form.text-input', [
        'name' => 'video_url',
        'title' => __('Video Link'),
        'placeholder' => __('Video Link'),
        'disabled' => $is_translation
    ])
    @endcomponent
</x-forms::card>
