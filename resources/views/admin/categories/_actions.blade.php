<div class="actions">
    @if(isset($category))
        @can('delete', $category)
        <a class="actions__item delete-link zmdi zmdi-delete" href="#"
            data-request-url="{{ route('admin.categories.destroy', [$type, $category]) }}"
            data-redirect-url="{{ route('admin.categories.index', $type) }}" title="{{ __('Delete') }}">
            <span>{{ __('Delete') }}</span>
        </a>
        @endcan
    @endif

    @can('create', [\Javaabu\Cms\Models\Category::class, $type])
    <a class="actions__item zmdi zmdi-plus" href="{{ route('admin.categories.create', $type) }}" title="{{ __('Add New') }}">
        <span>{{ __('Add New') }}</span>
    </a>
    @endcan

    @can('viewAny', [\Javaabu\Cms\Models\Category::class, $type])
    <a class="actions__item zmdi zmdi-view-list-alt" href="{{ route('admin.categories.index', $type) }}" title="{{ __('List All') }}">
        <span>{{ __('View All') }}</span>
    </a>
    @endcan
</div>
