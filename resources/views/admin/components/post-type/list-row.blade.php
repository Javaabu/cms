@php use Carbon\Carbon; @endphp
@php
    $name = $name ?? _d('Title')
@endphp
{{ $avatar ?? '' }}
<td data-col="{{ $name }}">
    {{ $before_link ?? '' }}
    {!! $model->admin_link !!}
    <div class="table-actions actions">
        <a class="actions__item"><span>{{ _d('ID: :id', ['id' => $model->id]) }}</span></a>

        @if(!isset($hide_permalink))
            <a class="actions__item zmdi zmdi-open-in-new" href="{{ $model->permalink }}" target="_blank" title="Open">
                <span>{{ _d('Open') }}</span>
            </a>
        @endif

        @if($model->trashed())
            @can('forceDelete', $model)
                <a class="actions__item delete-link zmdi zmdi-delete" href="#"
                   data-request-url="{{ $model->url('force-delete') }}"
                   data-redirect-url="{{ Request::fullUrl() }}" title="Delete Permanently">
                    <span>{{ _d('Delete Permanently') }}</span>
                </a>
            @endcan

            @can('restore', $model)
                <a class="actions__item restore-link zmdi zmdi-time-restore-setting" href="#"
                   data-post-url="{{ $model->url('restore') }}"
                   data-redirect-url="{{ Request::fullUrl() }}" title="Restore">
                    <span>{{ _d('Restore') }}</span>
                </a>
            @endcan
        @else
            {{--            @can('view', $model)--}}
            {{--                <a class="actions__item zmdi zmdi-open-in-new" href="{{ $model->permalink }}" title="View">--}}
            {{--                    <span>{{ _d('View') }}</span>--}}
            {{--                </a>--}}
            {{--            @endcan--}}

            @can('update', $model)
                <a class="actions__item zmdi zmdi-edit" href="{{ $model->url('edit') }}" title="Edit">
                    <span>{{ _d('Edit') }}</span>
                </a>
            @endcan

            @can('delete', $model)
                <a class="actions__item delete-link zmdi zmdi-delete" href="#"
                   data-request-url="{{ $model->url('destroy') }}"
                   data-redirect-url="{{ Request::fullUrl() }}" title="Delete">
                    <span>{{ _d('Delete') }}</span>
                </a>
            @endcan
        @endif
    </div>
</td>
{{ $before ?? '' }}
@component('admin.components.translations.json-translations-rows', [
            'model' => $model,
            'create_url' => $create_url,
        ])
@endcomponent
<td>
    @php
        $date_title = $model->published_at > Carbon::now() ? _d('Scheduled For') : _d('Published On');
        $date = $model->published_at;
    @endphp
    <strong class="d-block">{{ $date_title }}:</strong>
    {{ $date ? $date->format('j M Y H:i') : '-' }}
</td>
<td data-col="{{ _d('Status') }}">
    <span class="status solid {{ $model->status }}">
        {{ $model->status }}
    </span>
</td>
