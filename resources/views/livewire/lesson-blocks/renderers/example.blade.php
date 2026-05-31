@php
    $content = $block['content'] ?? [];
    $items = collect(preg_split('/\r\n|\r|\n|,/', $content['items_text'] ?? '') ?: [])->map(fn ($item) => trim($item))->filter()->values();
@endphp

<article class="student-preview-block preview-example" style="--preview-color: {{ $meta['color'] ?? '#d22d78' }}">
    <div class="preview-kicker">مثال تطبيقي</div>
    <h3>{{ $content['emoji'] ?? '✅' }} {{ $block['title'] ?: 'مثال' }}</h3>
    <div class="preview-body">{!! $content['body'] ?: 'اكتب نص المثال أو السؤال.' !!}</div>
    <div class="preview-step-track">
        @forelse ($items as $item)
            <div><b>{{ $loop->iteration }}</b><span>{{ $item }}</span></div>
        @empty
            <div><b>1</b><span>أضف خطوات الحل أو نقاط المثال.</span></div>
        @endforelse
    </div>
    @if (! empty($content['answer']) || ! empty($content['result']))
        <div class="preview-result">
            <strong>{{ $content['answer'] ?: $content['result'] }}</strong>
            @if (! empty($content['hint'])) <span>{{ $content['hint'] }}</span> @endif
        </div>
    @endif
</article>
