<h1>{{ $title }}</h1>
<div data-post-type="{{ $post_type->slug }}">
    @foreach ($posts as $post)
        <article data-post-id="{{ $post->id }}">{{ $post->title }}</article>
    @endforeach
</div>
