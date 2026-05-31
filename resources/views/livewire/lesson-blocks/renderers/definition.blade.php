@php
    $content = $block['content'] ?? [];
    $items = collect(preg_split('/\r\n|\r|\n|,/', $content['items_text'] ?? '') ?: [])->map(fn ($item) => trim($item))->filter()->values();
@endphp

<article class="student-preview-block preview-definition" style="--preview-color: {{ $meta['color'] ?? '#215b9f' }}">
    <div class="preview-kicker">{{ $meta['label'] ?? 'تعريف' }}</div>
    <div class="definition-tile-preview">
        <span>{{ $content['emoji'] ?? '💡' }}</span>
        <div>
            <small>{{ $content['symbol'] ?: 'مفهوم' }}</small>
            <h3>{{ $block['title'] ?: ($content['term'] ?: 'مصطلح جديد') }}</h3>
        </div>
    </div>
    <div class="preview-body">{!! $content['body'] ?: 'اكتب التعريف أو القاعدة هنا.' !!}</div>
    @if ($items->isNotEmpty())
        <div class="preview-option-stack">
            @foreach ($items as $item)
                <div><i>{{ $loop->iteration }}</i><span>{{ $item }}</span></div>
            @endforeach
        </div>
    @endif
</article>
