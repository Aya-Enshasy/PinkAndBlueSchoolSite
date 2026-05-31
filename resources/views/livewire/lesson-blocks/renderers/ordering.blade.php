@php
    $content = $block['content'] ?? [];
    $items = collect(preg_split('/\r\n|\r|\n|,/', $content['items_text'] ?? '') ?: [])->map(fn ($item) => trim($item))->filter()->values();
@endphp

<article class="student-preview-block preview-ordering" style="--preview-color: {{ $meta['color'] ?? '#16a085' }}">
    <div class="preview-kicker">رتب بالترتيب الصحيح</div>
    <h3>{{ $content['emoji'] ?? '🔁' }} {{ $content['question'] ?: ($block['title'] ?: 'رتب العناصر') }}</h3>
    <div class="ordering-preview-list">
        @forelse ($items as $item)
            <div><b>{{ $loop->iteration }}</b><span>{{ $item }}</span><i>☰</i></div>
        @empty
            <div><b>1</b><span>أضف العناصر بالترتيب الصحيح</span><i>☰</i></div>
        @endforelse
    </div>
    @if (! empty($content['hint']))
        <div class="preview-result"><span>{{ $content['hint'] }}</span></div>
    @endif
</article>
