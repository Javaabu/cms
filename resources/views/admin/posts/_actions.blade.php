<div class="actions">
    @if(isset($post))
        @can('delete', $post)
        <a class="actions__item delete-link zmdi zmdi-delete" href="#"
            data-request-url="{{ route('admin.posts.destroy', [$type, $post]) }}"
            data-redirect-url="{{ route('admin.posts.index', $type) }}" title="Delete">
            <span>{{ __('Delete') }}</span>
        </a>
        @endcan

        @if(method_exists($post, 'permalink'))
            <a class="actions__item zmdi zmdi-open-in-new" href="{{ $post->permalink }}" target="_blank"
               title="View on Website">
                <span>{{ __('View on Website') }}</span>
            </a>
        @endif

        @can('viewLogs', $post)
        <a class="actions__item zmdi zmdi-assignment" href="{{ $post->log_url ?? '#' }}" target="_blank" title="View Logs">
            <span>{{ __('View Logs') }}</span>
        </a>
        @endcan
    @endif

    @can('create', [$type])
    <a class="actions__item zmdi zmdi-plus" href="{{ route('admin.posts.create', $type) }}" title="Add New">
        <span>{{ __('Add New') }}</span>
    </a>
    @endcan

    @can('viewAny', [$type])
    <a class="actions__item zmdi zmdi-view-list-alt" href="{{ route('admin.posts.index', $type) }}" title="List All">
        <span>{{ __('View All') }}</span>
    </a>
    @endcan

    @can('viewTrash', $type)
    <a class="actions__item zmdi zmdi-delete" href="{{ route('admin.posts.trash', $type) }}" title="Trash">
        <span>{{ __('Trash') }}</span>
    </a>
    @endcan
</div>
