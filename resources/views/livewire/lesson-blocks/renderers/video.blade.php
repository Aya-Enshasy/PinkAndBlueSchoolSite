@php
    $content = $block['content'] ?? [];
    $media = $block['media'] ?? [];
    $url = $media['video_url'] ?? '';
    $embed = $url;
    if (preg_match('/(?:youtube\.com\/watch\?.*v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/shorts\/)([A-Za-z0-9_-]{6,})/', $url, $match)) {
        $embed = 'https://www.youtube.com/embed/'.$match[1];
    }
@endphp

<article class="student-preview-block preview-video" style="--preview-color: {{ $meta['color'] ?? '#f59e0b' }}">
    <div class="preview-kicker">فيديو مساعد</div>
    <h3>{{ $content['emoji'] ?? '▶' }} {{ $block['title'] ?: 'فيديو الدرس' }}</h3>
    <div class="preview-body">{!! $content['body'] ?: 'أضف رابط YouTube ليظهر الفيديو هنا.' !!}</div>
    @if ($url)
        <iframe src="{{ $embed }}" title="{{ $block['title'] ?: 'lesson video' }}" allowfullscreen loading="lazy"></iframe>
    @else
        <div class="preview-media-empty">لا يوجد رابط فيديو بعد.</div>
    @endif
</article>
