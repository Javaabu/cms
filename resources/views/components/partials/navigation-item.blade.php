<li class="cms-nav__item{{ $item['active'] ? ' cms-nav__item--active' : '' }}" data-key="{{ $item['key'] }}">
    @if($item['url'])
        <a class="cms-nav__link" href="{{ $item['url'] }}" @if($item['active']) aria-current="page" @endif @if($item['icon']) data-icon="{{ $item['icon'] }}" @endif>{{ $item['label'] }}</a>
    @else
        <span class="cms-nav__link" @if($item['icon']) data-icon="{{ $item['icon'] }}" @endif>{{ $item['label'] }}</span>
    @endif
    @if($item['children'])
        <ul class="cms-nav__children">
            @foreach($item['children'] as $child)
                @include('cms::components.partials.navigation-item', ['item' => $child])
            @endforeach
        </ul>
    @endif
</li>
