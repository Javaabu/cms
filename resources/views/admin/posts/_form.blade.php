@php
    $section = (isset($post) ? $post->section : null);
    $data = [
        'model' => $post ?? null,
        'model_class' => $post ?? $type,
        'model_route' => 'admin.posts',
        'create_url' => $type->slug. '/create',
        'url_prefix' => app()->getLocale(). ($type->slug != 'pages' ? '/'.$type->slug : ''),
        'type' => $type
    ];
    extract($data);
    $lang = $model->lang ?? null;
    $langValue = $lang instanceof \BackedEnum ? $lang->value : $lang;
    $is_translation = isset($model) && $langValue && $langValue != app()->getLocale();
@endphp
<div class="row">
    <div class="col-md-8">
        @include('cms::admin.components.post-type.form.title')
        @include('cms::admin.components.post-type.form.content', ['content' => $model?->content ?: null])
        @include('cms::admin.components.post-type.form.excerpt')

        @if($type->hasFeature(Javaabu\Cms\Enums\PostTypeFeatures::DOCUMENTS))
            @include('cms::admin.posts.form._documents')
        @endif

        @if($type->hasFeature(Javaabu\Cms\Enums\PostTypeFeatures::IMAGE_GALLERY))
            @include('cms::admin.posts.form._image_gallery')
        @endif

        @if($type->hasFeature(Javaabu\Cms\Enums\PostTypeFeatures::FORMAT))
            @include('cms::admin.posts.form._format-sections')
        @endif

        @if( !$type->hasFeature(Javaabu\Cms\Enums\PostTypeFeatures::FORMAT) &&
              $type->hasFeature(Javaabu\Cms\Enums\PostTypeFeatures::VIDEO_LINK)
        )
            @include('cms::admin.posts.form._video-link')
        @endif

        @if($type->hasFeature(Javaabu\Cms\Enums\PostTypeFeatures::RELATED_GALLERIES))
            @include('cms::admin.posts.form._related-galleries')
        @endif
    </div>
    <div class="col-md-4">
        @component('cms::admin.components.post-type.form.publish', $data)
            @slot('before')
                @if(config('cms.should_translate'))
                    @include('cms::admin.components.post-type.form.translations')
                @endif

            @if(\Illuminate\Support\Facades\Schema::hasTable('departments'))
                @include('cms::admin.posts.form._department-selector')
            @endif

                @if($type->hasFeature(Javaabu\Cms\Enums\PostTypeFeatures::DOCUMENT_NUMBER))
                    @component('cms::admin.components.post-type.form.text-input', [
                            'title' => $type->getFeatureName(Javaabu\Cms\Enums\PostTypeFeatures::DOCUMENT_NUMBER),
                            'name' => 'document_no',
                            'disabled' => $is_translation,
                        ])
                    @endcomponent
                @endif

                @if($type->hasFeature(Javaabu\Cms\Enums\PostTypeFeatures::GAZETTE_LINK))
                    <x-forms::url name="gazette_link" :label="__('Gazette Link')" :default="old('gazette_link')" placeholder="https://gazette.gov.mv/..." :disabled="$is_translation" />
                @endif

                @if($type->hasFeature(Javaabu\Cms\Enums\PostTypeFeatures::REF_NO))
                    @component('cms::admin.components.post-type.form.text-input', [
                            'title' => $type->getFeatureName(Javaabu\Cms\Enums\PostTypeFeatures::REF_NO),
                            'name' => 'ref_no',
                            'disabled' => $is_translation,
                        ])
                    @endcomponent
                @endif

                @if($type->hasFeature(Javaabu\Cms\Enums\PostTypeFeatures::EXPIREABLE))
                    @include('cms::admin.posts.form._expireable')
                @endif

            @if(\Illuminate\Support\Facades\Schema::hasTable('menus'))
                @if($type->hasFeature(Javaabu\Cms\Enums\PostTypeFeatures::PAGE_STYLE))
                    @include('cms::admin.posts.form._page-style')
                @endif
            @endif

                @isset($post)
                    <x-forms::checkbox name="recently_updated" :default="$post->recently_updated">
                        <small>{{ __('Use this to indicate that this :post_type was recently updated', ['post_type' => $type->lower_singular_name]) }}</small>
                    </x-forms::checkbox>
                @endisset
            @endslot
        @endcomponent

        @if($type->hasFeature(Javaabu\Cms\Enums\PostTypeFeatures::FORMAT))
            @include('cms::admin.posts.form._format')
        @endif

        @include('cms::admin.components.post-type.form.featured-image')

        {{--@include('cms::admin.components.tags-card', [
            'model' => $model ?? null,
            'disabled' => $is_translation
        ])--}}

        @if($type->hasFeature(Javaabu\Cms\Enums\PostTypeFeatures::CATEGORIES))
            @if($category_type = $type->categoryType)
                @include('cms::admin.components.categories-card', [
                    'type' => \Javaabu\Cms\Models\CategoryType::whereSlug($category_type->slug)->first(),
                    'model' => $model ?? null,
                    'disabled' => $is_translation
                ])
            @endif
        @endif
    </div>
</div>

@include('cms::admin.components.floating-submit')
