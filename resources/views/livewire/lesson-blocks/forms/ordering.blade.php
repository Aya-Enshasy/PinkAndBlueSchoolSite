<div class="block-fields-grid block-form block-form-ordering">
    <label class="wide">
        تعليمات الترتيب
        <input type="text" wire:model.live="blocks.{{ $index }}.content.question" placeholder="مثال: رتّب خطوات الحل">
    </label>
    <label>
        عنوان النشاط
        <input type="text" wire:model.live="blocks.{{ $index }}.title" placeholder="{{ $meta['label'] }}">
    </label>
    <label>
        درجة النشاط
        <input type="number" min="1" max="100" wire:model.live="blocks.{{ $index }}.content.score">
    </label>
    <label class="wide">
        العناصر بالترتيب الصحيح
        <textarea rows="6" wire:model.live="blocks.{{ $index }}.content.items_text" placeholder="كل عنصر بسطر. الطالب سيحاول ترتيبها بنفس التسلسل."></textarea>
    </label>
    <label class="wide">
        تلميح
        <input type="text" wire:model.live="blocks.{{ $index }}.content.hint" placeholder="اختياري">
    </label>
</div>
