@if(filled($settings['title'] ?? null))
<h2 class="widget-title">{{ $settings['title'] }}</h2>
@endif
@if($terms)
<ul class="cms-widget-list">
@foreach($terms as $term)
<li>
@if($term['url'])<a href="{{ $term['url'] }}" dir="auto">{{ $term['name'] }}</a>@else<span dir="auto">{{ $term['name'] }}</span>@endif
@if($show_counts)
<span class="cms-widget-count">({{ \Illuminate\Support\Number::format($term['count']) }})</span>
@endif
</li>
@endforeach
</ul>
@endif
