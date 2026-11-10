@if(filled($settings['title'] ?? null))
<h2 class="widget-title">{{ $settings['title'] }}</h2>
@endif
@if($posts)
<ul class="cms-widget-list">
@foreach($posts as $post)
<li>
@if($post['url'])<a href="{{ $post['url'] }}" dir="auto">{{ $post['title'] }}</a>@else<span dir="auto">{{ $post['title'] }}</span>@endif
@if(($settings['show_date'] ?? true) && $post['date'])
<time datetime="{{ $post['date'] }}">{{ $post['date_label'] }}</time>
@endif
</li>
@endforeach
</ul>
@if($archive_url)
<a class="cms-widget-more" href="{{ $archive_url }}">{{ $archive_label }}</a>
@endif
@else
<p class="cms-muted">{{ __('Nothing has been published here yet.') }}</p>
@endif
