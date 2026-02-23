@include('admin.components.translations.db-non-translatables-notice')

<div class="row">
    <div class="col-md-8">
        @include('admin.components.post-type.form.title')
        @include('admin.components.post-type.form.content')
        @include('admin.components.post-type.form.excerpt')

        {{ $main ?? '' }}
    </div>
    <div class="col-md-4">
        @include('admin.components.post-type.form.language')
        @include('admin.components.post-type.form.publish')
        @include('admin.components.post-type.form.featured-image')
        @include('admin.components.post-type.form.header-image')

        {{ $side_main ?? '' }}

        @include('admin.components.categories-card', [
            'type' => $model_class::categoryType(),
            'model' => isset($lang_parent) ? $lang_parent : ($model ?? null),
            'disabled' => isset($lang_parent),
        ])
        @include('admin.components.tags-card', [
            'model' => isset($lang_parent) ? $lang_parent : ($model ?? null),
            'disabled' => isset($lang_parent),
        ])

        {{ $side ?? '' }}
    </div>
</div>

<button class="btn btn-success btn--action" type="submit"
        title="Save">
    <i class="zmdi zmdi-floppy"></i>
</button>

