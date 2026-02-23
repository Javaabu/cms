<div class="actions">
    @if(isset($media))
        @can('delete', $media)
            <a class="actions__item delete-link zmdi zmdi-delete" href="#"
               data-request-url="{{ translate_route('admin.media.destroy', $media) }}"
               data-redirect-url="{{ translate_route('admin.media.index') }}" title="Delete">
                <span>{{ _d('Delete') }}</span>
            </a>
        @endcan

        @can('viewLogs', $media)
            <a class="actions__item zmdi zmdi-assignment" href="{{ $media->log_url }}" target="_blank"
               title="View Logs">
                <span>{{ _d('View Logs') }}</span>
            </a>
        @endcan
    @endif

    @can('create', Javaabu\Cms\Media\Media::class)
        <a class="actions__item zmdi zmdi-plus" href="{{ translate_route('admin.media.create') }}" title="Add New">
            <span>{{ _d('Add New') }}</span>
        </a>
    @endcan

    @can('viewAny', Javaabu\Cms\Media\Media::class)
        <a class="actions__item zmdi zmdi-view-list-alt"
           href="{{ add_query_arg('view', 'list', translate_route('admin.media.index')) }}" title="List View">
            <span>{{ _d('View All') }}</span>
        </a>

        <a class="actions__item zmdi zmdi-apps"
           href="{{ add_query_arg('view', 'grid', translate_route('admin.media.index')) }}" title="Grid View">
            <span>{{ _d('View All') }}</span>
        </a>
    @endcan
</div>


