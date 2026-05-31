@php
    $content = $block['content'] ?? [];
    $media = $block['media'] ?? [];
    $items = collect(preg_split('/\r\n|\r|\n|,/', $content['items_text'] ?? '') ?: [])->map(fn ($item) => trim($item))->filter()->values();
@endphp

<article class="student-preview-block preview-observation" style="--preview-color: {{ $meta['color'] ?? '#0ea5e9' }}">
    <div class="preview-kicker">لاحظ ثم فكر</div>
    <div class="preview-mascot-row">
        <span class="preview-mascot">{{ $content['emoji'] ?? '🔎' }}</span>
        <div class="preview-bubble">
            <small>{{ $meta['label'] ?? 'ملاحظة' }}</small>
            <h3>{{ $block['title'] ?: 'ماذا تلاحظ؟' }}</h3>
            <div>{!! $content['body'] ?: 'اكتب الملاحظة أو الظاهرة التي يبدأ منها الطالب.' !!}</div>
        </div>
    </div>
    @if (! empty($media['image_url']))
        <img class="preview-image" src="{{ $media['image_url'] }}" alt="{{ $media['image_alt'] ?? '' }}">
    @endif
    <div class="preview-option-stack">
        @forelse ($items as $item)
            <div><i>🔍</i><span>{{ $item }}</span></div>
        @empty
            <div><i>🔍</i><span>أضف أسئلة ملاحظة أو نقاط تحليل من حقل العناصر.</span></div>
        @endforelse
    </div>
</article>
