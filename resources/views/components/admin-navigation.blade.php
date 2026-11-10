<nav {{ $attributes->merge(['class' => 'cms-nav']) }} aria-label="{{ __('Admin') }}">
    @foreach($groups as $group)
        <div class="cms-nav__group">
            @if($group['label'])
                <p class="cms-nav__heading" id="cms-nav-{{ $group['key'] }}">{{ $group['label'] }}</p>
            @endif
            <ul class="cms-nav__list" @if($group['label']) aria-labelledby="cms-nav-{{ $group['key'] }}" @endif>
                @foreach($group['items'] as $item)
                    @include('cms::components.partials.navigation-item', ['item' => $item])
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
