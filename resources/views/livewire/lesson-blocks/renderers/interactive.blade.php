@php
    $content = $block['content'] ?? [];
    $options = collect(preg_split('/\r\n|\r|\n|,/', ($content['options_text'] ?? '') ?: ($content['items_text'] ?? '')) ?: [])->map(fn ($item) => trim($item))->filter()->values();
@endphp

<article class="student-preview-block preview-interactive" style="--preview-color: {{ $meta['color'] ?? '#16a085' }}">
    <div class="preview-kicker">تفاعل سريع</div>
    <h3>{{ $content['emoji'] ?? '☑️' }} {{ $content['question'] ?: ($block['title'] ?: 'اختر الإجابة') }}</h3>
    <div class="preview-body">{!! $content['body'] ?: 'اكتب السؤال أو التعليمات.' !!}</div>
    <div class="preview-choice-list">
        @forelse ($options as $option)
            <button type="button">{{ $option }}</button>
        @empty
            <button type="button">خيار 1</button>
            <button type="button">خيار 2</button>
        @endforelse
    </div>
    @if (! empty($content['answer']))
        <div class="preview-answer">الإجابة: {{ $content['answer'] }}</div>
    @endif
</article>
