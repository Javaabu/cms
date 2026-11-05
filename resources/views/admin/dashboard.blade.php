@extends($layout)

@section('title', $strings['title'])

@section($layout_section)
    @manifestRequire('cms::styles')

    <div class="cms-dashboard">
        <h1 class="cms-heading">{{ $strings['title'] }}</h1>

        <section class="cms-dashboard__section" aria-labelledby="cms-dashboard-content">
            <h2 id="cms-dashboard-content" class="cms-subheading">{{ $strings['content'] }}</h2>

            @if($post_types)
                <div class="cms-dashboard__cards">
                    @foreach($post_types as $type)
                        <article class="cms-card" data-post-type="{{ $type['key'] }}">
                            <header class="cms-card__header">
                                <h3 class="cms-card__title" data-icon="{{ $type['icon'] }}">{{ $type['label'] }}</h3>
                                @if($type['create_url'])
                                    <a class="cms-button cms-button--small" href="{{ $type['create_url'] }}">{{ str_replace(':type', mb_strtolower($type['singular_label']), $strings['add']) }}</a>
                                @endif
                            </header>
                            <dl class="cms-card__counts">
                                @foreach(['published', 'scheduled', 'draft', 'pending'] as $status)
                                    <div class="cms-card__count" data-status="{{ $status }}">
                                        <dt>{{ $strings[$status] }}</dt>
                                        <dd>{{ \Illuminate\Support\Number::format($type['counts'][$status]) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                            @if($type['index_url'])
                                <a class="cms-card__link" href="{{ $type['index_url'] }}">{{ $strings['view_all'] }}</a>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <p class="cms-muted">{{ $strings['no_types'] }}</p>
            @endif
        </section>

        @if($post_types)
            <section class="cms-dashboard__section" aria-labelledby="cms-dashboard-recent">
                <h2 id="cms-dashboard-recent" class="cms-subheading">{{ $strings['recent'] }}</h2>

                @if($recent)
                    <ul class="cms-recent">
                        @foreach($recent as $post)
                            <li class="cms-recent__item" data-post="{{ $post['id'] }}">
                                @if($post['edit_url'])
                                    <a class="cms-recent__title" href="{{ $post['edit_url'] }}" dir="auto">{{ $post['title'] }}</a>
                                @else
                                    <span class="cms-recent__title" dir="auto">{{ $post['title'] }}</span>
                                @endif
                                <span class="cms-recent__meta">
                                    {{ $post['post_type_label'] }}
                                    · <span class="cms-badge cms-badge--{{ $post['status_color'] }}">{{ $post['status'] }}</span>
                                    @if($post['updated_at'])
                                        · <time datetime="{{ $post['updated_at'] }}">{{ str_replace(':date', \Illuminate\Support\Carbon::parse($post['updated_at'])->diffForHumans(), $strings['updated']) }}</time>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="cms-muted">{{ $strings['empty'] }}</p>
                @endif
            </section>
        @endif
    </div>
@endsection
