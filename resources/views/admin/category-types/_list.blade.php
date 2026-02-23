@foreach($category_types as $category_type)
    <x-forms::table.row :model="$category_type" :no-checkbox="! empty($no_checkbox)">

        <x-forms::table.cell :label="__('Name')">
            <a href="{{ route('admin.category-types.edit', $category_type) }}">
                {{ $category_type->name }}
            </a>
            <div class="table-actions actions">
                <a class="actions__item"><span>{{ __('ID: :id', ['id' => $category_type->id]) }}</span></a>

                @can('update', $category_type)
                    <a class="actions__item zmdi zmdi-edit" href="{{ route('admin.category-types.edit', $category_type) }}" title="{{ __('Edit') }}">
                        <span>{{ __('Edit') }}</span>
                    </a>
                @endcan

                @can('view', [\Javaabu\Cms\Models\Category::class, $category_type])
                    <a class="actions__item zmdi zmdi-view-list" href="{{ route('admin.categories.index', $category_type) }}" title="{{ __('View Categories') }}">
                        <span>{{ __('View Categories') }}</span>
                    </a>
                @endcan

                @can('delete', $category_type)
                    <a class="actions__item delete-link zmdi zmdi-delete" href="#" data-request-url="{{ route('admin.category-types.destroy', $category_type) }}"
                       data-redirect-url="{{ Request::fullUrl() }}" title="{{ __('Delete') }}">
                        <span>{{ __('Delete') }}</span>
                    </a>
                @endcan
            </div>
        </x-forms::table.cell>

        <x-forms::table.cell name="slug" />

        <x-forms::table.cell :label="__('Categories')">
            @if($category_type->categories()->exists())
                <a href="{{ route('admin.categories.index', $category_type) }}">
                    {{ trans_choice(':count Category|:count Categories', $category_type->categories()->count()) }}
                </a>
            @else
                <span class="text-muted">{{ __('No categories') }}</span>
            @endif
        </x-forms::table.cell>

        <x-forms::table.cell :label="__('Created')">
            {{ $category_type->created_at->format('M d, Y') }}
        </x-forms::table.cell>
    </x-forms::table.row>
@endforeach
