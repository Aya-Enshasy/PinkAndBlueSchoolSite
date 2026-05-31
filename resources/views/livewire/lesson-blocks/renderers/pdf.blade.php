@php
    $content = $block['content'] ?? [];
    $media = $block['media'] ?? [];
    $url = $media['pdf_url'] ?? '';
@endphp

<article class="student-preview-block preview-pdf" style="--preview-color: {{ $meta['color'] ?? '#f59e0b' }}">
    <div class="preview-kicker">ملف مساعد</div>
    <h3>{{ $content['emoji'] ?? '📄' }} {{ $block['title'] ?: 'PDF' }}</h3>
    <div class="preview-body">{!! $content['body'] ?: 'ارفق PDF أو أضف رابطه حتى يظهر للطالب.' !!}</div>
    @if ($url)
        <object data="{{ $url }}" type="application/pdf">
            <a href="{{ $url }}" target="_blank" rel="noreferrer">فتح PDF</a>
        </object>
        <a class="preview-media-link" href="{{ $url }}" target="_blank" rel="noreferrer">فتح الملف</a>
    @else
        <div class="preview-media-empty">لم يتم إرفاق PDF بعد.</div>
    @endif
</article>
