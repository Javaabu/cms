@foreach($media_items as $media)
    <x-forms::table.row :model="$media" :no-checkbox="! empty($no_checkbox)">

        <x-forms::table.cell :label="__('Title')">
            @can('view', $media)
                <a href="{{ $media->admin_url }}">
                    @endcan
                    <div title="{{ $media->name }}" class="square img-header"
                         style="background-image: url({{ $media->getUrl('preview') }})">
                        <div class="square-content">
                            @if($media->type_slug != 'image')
                                <i class="{{ $media->icon }} media-icon"></i>
                            @endif
                        </div>
                    </div>
                    @can('view', $media)
                </a>
            @endcan
        </x-forms::table.cell>

        <x-forms::table.cell :label="__('Description')">
            {!! $media->admin_link !!}
            <span class="d-block">{{ $media->file_name }}</span>
            <div class="table-actions actions">
                <a class="actions__item"><span>{{ _d('ID: :id', ['id' => $media->id]) }}</span></a>

                @can('update', $media)
                    <a class="actions__item zmdi zmdi-edit" href="{{ $media->url('edit') }}" title="Edit">
                        <span>{{ _d('Edit') }}</span>
                    </a>
                @endcan

                @can('delete', $media)
                    <a class="actions__item delete-link zmdi zmdi-delete" href="#"
                       data-request-url="{{ $media->url('destroy') }}"
                       data-redirect-url="{{ Request::fullUrl() }}" title="Delete">
                        <span>{{ _d('Delete') }}</span>
                    </a>
                @endcan
            </div>
        </x-forms::table.cell>

        <x-forms::table.cell :label="__('Model')">
            {!! $media->model ? $media->model->admin_link : '-' !!}
        </x-forms::table.cell>

        <x-forms::table.cell name="created_at" />

    </x-forms::table.row>
@endforeach


