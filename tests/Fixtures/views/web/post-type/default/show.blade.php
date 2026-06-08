<h1>{{ $post->title }}</h1>
<section data-documents-count="{{ $post_documents->count() }}"></section>
<section data-related-count="{{ $related_posts->count() }}">
    @foreach ($related_posts as $related_post)
        <article data-related-id="{{ $related_post->id }}">{{ $related_post->title }}</article>
    @endforeach
</section>
