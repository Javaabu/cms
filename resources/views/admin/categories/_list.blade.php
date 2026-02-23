@foreach($categories as $category)
    <x-forms::table.row :model="$category" :no-checkbox="! empty($no_checkbox)">

        <x-forms::table.cell :label="__('Name')">
            <a href="{{ route('admin.categories.edit', [$type, $category]) }}">
                {{ $category->depth_name }}
            </a>
            <div class="table-actions actions">
                <a class="actions__item"><span>{{ __('ID: :id', ['id' => $category->id]) }}</span></a>

                @can('update', $category)
                    <a class="actions__item zmdi zmdi-edit" href="{{ route('admin.categories.edit', [$type, $category]) }}" title="{{ __('Edit') }}">
                        <span>{{ __('Edit') }}</span>
                    </a>
                @endcan

                @can('delete', $category)
                    <a class="actions__item delete-link zmdi zmdi-delete" href="#" data-request-url="{{ route('admin.categories.destroy', [$type, $category]) }}"
                       data-redirect-url="{{ Request::fullUrl() }}" title="{{ __('Delete') }}">
                        <span>{{ __('Delete') }}</span>
                    </a>
                @endcan
            </div>
        </x-forms::table.cell>

        <x-forms::table.cell name="slug" />

        <x-forms::table.cell :label="__('Parent')">
            @if($category->parent)
                <a href="{{ route('admin.categories.edit', [$type, $category->parent]) }}">
                    {{ $category->parent->name }}
                </a>
            @else
                <span class="text-muted">{{ __('Root Level') }}</span>
            @endif
        </x-forms::table.cell>

        <x-forms::table.cell :label="__('Posts')">
            @if($category->posts()->exists())
                {{ trans_choice(':count Post|:count Posts', $category->posts()->count()) }}
            @else
                <span class="text-muted">{{ __('No posts') }}</span>
            @endif
        </x-forms::table.cell>
    </x-forms::table.row>
@endforeach
