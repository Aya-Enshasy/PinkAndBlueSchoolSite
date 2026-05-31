@php
    $content = $block['content'] ?? [];
    $items = collect(preg_split('/\r\n|\r|\n|,/', $content['items_text'] ?? '') ?: [])->map(fn ($item) => trim($item))->filter()->values();
@endphp

<article class="student-preview-block preview-reading" style="--preview-color: {{ $meta['color'] ?? '#d22d78' }}">
    <div class="preview-kicker">قراءة وفهم</div>
    <div class="preview-mascot-row">
        <span class="preview-mascot">{{ $content['emoji'] ?? '📖' }}</span>
        <div class="preview-bubble">
            <small>{{ $meta['label'] ?? 'قراءة' }}</small>
            <h3>{{ $block['title'] ?: 'اقرأ النص' }}</h3>
        </div>
    </div>
    <div class="reading-paper">{!! $content['body'] ?: 'أضف نص القراءة أو الفكرة العامة.' !!}</div>
    @if ($items->isNotEmpty())
        <div class="preview-option-stack">
            @foreach ($items as $item)
                <div><i>📌</i><span>{{ $item }}</span></div>
            @endforeach
        </div>
    @endif
</article>
