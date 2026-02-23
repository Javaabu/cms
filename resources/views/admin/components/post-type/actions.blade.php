<div class="actions">
    @if(isset($model))
        @can('delete', $model)
            <a class="actions__item delete-link zmdi zmdi-delete" href="#"
               data-request-url="{{ $model->url('destroy') }}"
               data-redirect-url="{{ action($controller.'@index', $language) }}" title="Delete">
                <span>{{ _d('Delete') }}</span>
            </a>
        @endcan

        @can('viewLogs', $model)
            <a class="actions__item zmdi zmdi-assignment" href="{{ $model->log_url }}" target="_blank"
               title="View Logs">
                <span>{{ _d('View Logs') }}</span>
            </a>
        @endcan
    @endif

    @can('create', $model_class)
        <a class="actions__item zmdi zmdi-plus" href="{{ action($controller.'@create', $language) }}" title="Add New">
            <span>{{ _d('Add New') }}</span>
        </a>
    @endcan

    @can('trash', $model_class)
        <a class="{{ $model_class::onlyTrashed()->exists() ? 'indicating' : '' }} actions__item zmdi zmdi-time-restore-setting"
           href="{{ action($controller.'@trash', $language) }}" title="Trash">
            <span>{{ _d('Trash') }}</span>
        </a>
    @endcan

    @can('index', $model_class)
        <a class="actions__item zmdi zmdi-view-list-alt" href="{{ action($controller.'@index', $language) }}"
           title="List All">
            <span>{{ _d('View All') }}</span>
        </a>
    @endcan
</div>
