@php
    $editor_type = $editor_type ?? 'editorjs';
    $content = $content ?? old('content');
    $thaana_enabled = $thaana_enabled ?? false;
@endphp
<x-forms::card :title="__('Content')">
    @if($editor_type == 'wysiwyg')
        <x-forms::textarea name="content" class="wysiwyg lang" id="content-body" />
    @else
        <div class="form-group mb-0 w-100 {{ $thaana_enabled ? 'thaana' : '' }}" data-editor-js="#content-body">
            <div class="lang align-text w-100" id="codex-editor"></div>
            <x-forms::hidden name="content" :default="$content" id="content-body" data-directionality="{{ $thaana_enabled ? 'rtl' : 'ltr' }}" data-lang="{{ $thaana_enabled ? 'dv' : 'en' }}"/>
        </div>
    @endif
</x-forms::card>

@push('scripts')
    @if($editor_type == 'wysiwyg')
        @include('admin.partials.wysiwyg')
    @else
        @vite(['vendor/javaabu/cms/resources/js/editor.js'])
        <script src="{{ asset('vendors/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>
        <script>
            $(document).ready(function () {
                var tinyMCEContent;
                $("li[data-tool='raw']").click(function () {
                    tinymce.init({
                        selector: 'textarea.ce-rawtool__textarea'
                    });

                    iframe = $('iframe')
                    tinyMCEContent = $('#tinymce p', iframe.contents())
                });
            });
        </script>
    @endif
@endpush
