@php
    $content = $block['content'] ?? [];
    $media = $block['media'] ?? [];
    $items = collect(preg_split('/\r\n|\r|\n|,/', $content['items_text'] ?? '') ?: [])->map(fn ($item) => trim($item))->filter()->values();
@endphp

<article class="student-preview-block preview-default" style="--preview-color: {{ $meta['color'] ?? '#215b9f' }}">
    <div class="preview-mascot-row">
        <span class="preview-mascot">{{ $content['emoji'] ?? ($meta['icon'] ?? '✨') }}</span>
        <div class="preview-bubble">
            <small>{{ $meta['label'] ?? 'شرح' }}</small>
            <h3>{{ $block['title'] ?: ($content['term'] ?: 'قسم جديد') }}</h3>
            @if (! empty($content['body']))
                <div>{!! $content['body'] !!}</div>
            @else
                <p>أضف محتوى هذا القسم من لوحة المعلم.</p>
            @endif
        </div>
    </div>

    @if (! empty($media['image_url']))
        <img class="preview-image" src="{{ $media['image_url'] }}" alt="{{ $media['image_alt'] ?? '' }}">
    @endif

    @if ($items->isNotEmpty())
        <div class="preview-option-stack">
            @foreach ($items as $item)
                <div><i>{{ $loop->iteration }}</i><span>{{ $item }}</span></div>
            @endforeach
        </div>
    @endif

    @if (! empty($content['result']) || ! empty($content['hint']))
        <div class="preview-result">
            @if (! empty($content['result'])) <strong>{{ $content['result'] }}</strong> @endif
            @if (! empty($content['hint'])) <span>{{ $content['hint'] }}</span> @endif
        </div>
    @endif
</article>
