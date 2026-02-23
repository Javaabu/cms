@foreach($posts as $post)
    <x-forms::table.row :model="$post" :no-checkbox="! empty($no_checkbox)">

        <x-forms::table.cell :label="__('Title')">
            <a href="{{ route('admin.posts.edit', [$type, $post]) }}">{{ $post->title }}</a>
            <div class="table-actions actions">
                <a class="actions__item"><span>{{ __('ID: :id', ['id' => $post->id]) }}</span></a>

                @can('view', $post)
                    <a class="actions__item zmdi zmdi-eye" href="{{ route('admin.posts.show', [$type, $post]) }}" title="View">
                        <span>{{ __('View') }}</span>
                    </a>
                @endcan

                @can('update', $post)
                    <a class="actions__item zmdi zmdi-edit" href="{{ route('admin.posts.edit', [$type, $post]) }}" title="Edit">
                        <span>{{ __('Edit') }}</span>
                    </a>
                @endcan

                @can('delete', $post)
                    <a class="actions__item delete-link zmdi zmdi-delete" href="#" data-request-url="{{ route('admin.posts.destroy', [$type, $post]) }}"
                       data-redirect-url="{{ Request::fullUrl() }}" title="Delete">
                        <span>{{ __('Delete') }}</span>
                    </a>
                @endcan

                @if(method_exists($post, 'permalink'))
                    <a class="actions__item zmdi zmdi-open-in-new" href="{{ $post->permalink }}" target="_blank" title="View on Website">
                        <span>{{ __('View on Website') }}</span>
                    </a>
                @endif
            </div>
        </x-forms::table.cell>

        <x-forms::table.cell :label="__('Status')">
            @if($post->status)
                <span class="badge badge-{{ \Javaabu\Cms\Enums\PostStatus::from($post->status)->color() }}">
                    {{ \Javaabu\Cms\Enums\PostStatus::from($post->status)->label() }}
                </span>
            @endif
        </x-forms::table.cell>

        @if($type->hasFeature(\Javaabu\Cms\Enums\PostTypeFeatures::CATEGORIES) && $type->category_type_id)
            <x-forms::table.cell :label="__('Categories')">
                @if($post->categories && $post->categories->isNotEmpty())
                    {{ $post->categories->pluck('name')->join(', ') }}
                @else
                    <span class="text-muted">{{ __('None') }}</span>
                @endif
            </x-forms::table.cell>
        @endif

        <x-forms::table.cell :label="__('Published At')">
            @if($post->published_at)
                {{ $post->published_at->format('d M Y H:i') }}
            @else
                <span class="text-muted">{{ __('Not published') }}</span>
            @endif
        </x-forms::table.cell>

        <x-forms::table.cell :label="__('Created At')">
            {{ $post->created_at->format('d M Y H:i') }}
        </x-forms::table.cell>

    </x-forms::table.row>
@endforeach
