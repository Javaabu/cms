<div class="actions">
    @if(isset($category_type))
        @can('delete', $category_type)
        <a class="actions__item delete-link zmdi zmdi-delete" href="#"
            data-request-url="{{ route('admin.category-types.destroy', $category_type) }}"
            data-redirect-url="{{ route('admin.category-types.index') }}" title="{{ __('Delete') }}">
            <span>{{ __('Delete') }}</span>
        </a>
        @endcan
    @endif

    @can('create', \Javaabu\Cms\Models\CategoryType::class)
    <a class="actions__item zmdi zmdi-plus" href="{{ route('admin.category-types.create') }}" title="{{ __('Add New') }}">
        <span>{{ __('Add New') }}</span>
    </a>
    @endcan

    @can('viewAny', \Javaabu\Cms\Models\CategoryType::class)
    <a class="actions__item zmdi zmdi-view-list-alt" href="{{ route('admin.category-types.index') }}" title="{{ __('List All') }}">
        <span>{{ __('View All') }}</span>
    </a>
    @endcan
</div>
