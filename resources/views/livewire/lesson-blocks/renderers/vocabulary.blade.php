@php
    $content = $block['content'] ?? [];
    $media = $block['media'] ?? [];
    $items = collect(preg_split('/\r\n|\r|\n|,/', $content['items_text'] ?? '') ?: [])->map(fn ($item) => trim($item))->filter()->values();
@endphp

<article class="student-preview-block preview-vocabulary" style="--preview-color: {{ $meta['color'] ?? '#215b9f' }}">
    <div class="preview-kicker">English Vocabulary</div>
    <div class="word-card">
        @if (! empty($media['image_url']))
            <img src="{{ $media['image_url'] }}" alt="{{ $media['image_alt'] ?? '' }}">
        @endif
        <div>
            <small>{{ $content['symbol'] ?: 'meaning' }}</small>
            <h3>{{ $block['title'] ?: ($content['term'] ?: 'New word') }}</h3>
            <div>{!! $content['body'] ?: 'Add a short sentence or meaning.' !!}</div>
        </div>
    </div>
    @if (! empty($media['audio_url']))
        <audio controls src="{{ $media['audio_url'] }}"></audio>
    @endif
    <div class="preview-option-stack english-options">
        @forelse ($items as $item)
            <div><i>🔊</i><span>{{ $item }}</span></div>
        @empty
            <div><i>🔊</i><span>Listen, repeat, then use the word.</span></div>
        @endforelse
    </div>
</article>
