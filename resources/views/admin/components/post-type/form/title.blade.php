@php
    $title = $title ?? 'title';
    $value = $value ?? old($title);
@endphp

<x-forms::card>
    <x-forms::text :name="$title" :default="$value" :value="$value" class="lang" id="title" :placeholder="__('Enter '.$title.' here')" required/>

    @unless(isset($hide_slug))
        <div class="form-group">
            @include('cms::admin.components.slug', [
                'url_prefix' => $url_prefix,
                'slug' => isset($model) ? $model->slug : old('slug'),
                'required' => true,
            ])
            @include('errors._list', ['error' => $errors->get('slug')])
        </div>
    @endif
</x-forms::card>

@unless(isset($hide_slug))
    @push('scripts')
        <script type="text/javascript">
            $(document).ready(function () {
                @if(empty($model))
                $('.slug').hide();
                var slugified = false;

                $('#title').change(function () {
                    var str = $(this).val();

                    if (str && !slugified) {
                        $('.slug').show().trigger('slug.change', transliterate2dv(str))
                    }
                }).trigger('change');
                @endif
            });
        </script>
    @endpush
@endif
