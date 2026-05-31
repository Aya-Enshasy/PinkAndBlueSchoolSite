@php
    $content = $block['content'] ?? [];
    $media = $block['media'] ?? [];
    $steps = collect(preg_split('/\r\n|\r|\n|,/', $content['items_text'] ?? '') ?: [])->map(fn ($item) => trim($item))->filter()->values();
@endphp

<article class="student-preview-block preview-experiment" style="--preview-color: {{ $meta['color'] ?? '#d22d78' }}">
    <div class="preview-kicker">تجربة مصغرة</div>
    <h3>{{ $content['emoji'] ?? '🧪' }} {{ $block['title'] ?: 'تجربة' }}</h3>
    <div class="preview-body">{!! $content['body'] ?: 'اكتب وصف التجربة وما الذي سيشاهده الطالب.' !!}</div>
    <div class="preview-step-track">
        @forelse ($steps as $step)
            <div><b>{{ $loop->iteration }}</b><span>{{ $step }}</span></div>
        @empty
            <div><b>1</b><span>أضف خطوات التجربة، كل خطوة بسطر.</span></div>
        @endforelse
    </div>
    @if (! empty($content['result']))
        <div class="preview-result"><strong>الاستنتاج</strong><span>{{ $content['result'] }}</span></div>
    @endif
    @if (! empty($media['video_url']))
        <a class="preview-media-link" href="{{ $media['video_url'] }}" target="_blank" rel="noreferrer">فتح فيديو التجربة</a>
    @endif
</article>
