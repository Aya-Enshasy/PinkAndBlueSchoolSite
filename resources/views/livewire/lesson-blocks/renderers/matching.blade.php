@php
    $content = $block['content'] ?? [];
    $left = collect(preg_split('/\r\n|\r|\n|,/', $content['left_items_text'] ?? '') ?: [])->map(fn ($item) => trim($item))->filter()->values();
    $right = collect(preg_split('/\r\n|\r|\n|,/', $content['right_items_text'] ?? '') ?: [])->map(fn ($item) => trim($item))->filter()->values();
@endphp

<article class="student-preview-block preview-matching" style="--preview-color: {{ $meta['color'] ?? '#16a085' }}">
    <div class="preview-kicker">وصل بين العناصر</div>
    <h3>{{ $content['emoji'] ?? '🔗' }} {{ $content['question'] ?: ($block['title'] ?: 'صل الصحيح') }}</h3>
    <div class="matching-preview-grid">
        <div>
            @forelse ($left as $item)
                <span>{{ $item }}</span>
            @empty
                <span>تفاحة</span>
                <span>برتقال</span>
            @endforelse
        </div>
        <div>
            @forelse ($right as $item)
                <span>{{ $item }}</span>
            @empty
                <span>Apple</span>
                <span>Orange</span>
            @endforelse
        </div>
    </div>
    @if (! empty($content['hint']))
        <div class="preview-result"><span>{{ $content['hint'] }}</span></div>
    @endif
</article>
