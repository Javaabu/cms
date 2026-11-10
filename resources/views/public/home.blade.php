@extends($layout)

@if($page)
    @section('title', $page['title'])
@endif

@section($layout_section)
    @manifestRequire('cms::styles')
    @manifestRequire('post-types::styles')

    <div class="cms-home">
        @if($page)
            <article class="cms-home__page" data-post="{{ $page['id'] }}">
                <h1 class="cms-heading" dir="auto">{{ $page['title'] }}</h1>
                @if(($page['content'] ?? null) !== null)
                    <div class="post-types-content">
                        <x-block-editor::content :content="$page['content']" />
                    </div>
                @endif
            </article>
        @else
            <h1 class="cms-visually-hidden">{{ $site['name'] }}</h1>
        @endif

        @if($post_type)
            <section class="cms-home__latest" aria-labelledby="cms-home-latest">
                <header class="cms-home__latest-header">
                    <h2 id="cms-home-latest" class="cms-subheading">{{ $strings['latest'] }}</h2>
                    @if($archive_url)
                        <a href="{{ $archive_url }}" class="cms-link">{{ $strings['view_all'] }}</a>
                    @endif
                </header>

                @if($posts)
                    <div class="post-types-archive">
                        @foreach($posts as $post)
                            @include('post-types::partials.card', ['post' => $post, 'strings' => $strings])
                        @endforeach
                    </div>
                @else
                    <p class="cms-muted">{{ $strings['empty'] }}</p>
                @endif
            </section>
        @endif
    </div>
@endsection
