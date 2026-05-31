@php
    $content = $block['content'] ?? [];
    $media = $block['media'] ?? [];
    $url = $media['audio_url'] ?? '';
@endphp

<article class="student-preview-block preview-audio" style="--preview-color: {{ $meta['color'] ?? '#f59e0b' }}">
    <div class="preview-kicker">صوت ونطق</div>
    <div class="preview-mascot-row">
        <span class="preview-mascot">{{ $content['emoji'] ?? '🔊' }}</span>
        <div class="preview-bubble">
            <small>{{ $meta['label'] ?? 'صوت' }}</small>
            <h3>{{ $block['title'] ?: 'استمع ثم أعد' }}</h3>
            <div>{!! $content['body'] ?: 'أضف ملف صوت أو رابط صوت للطالب.' !!}</div>
        </div>
    </div>
    @if ($url)
        <audio controls src="{{ $url }}"></audio>
    @else
        <div class="preview-media-empty">لم يتم إرفاق صوت بعد.</div>
    @endif
</article>
