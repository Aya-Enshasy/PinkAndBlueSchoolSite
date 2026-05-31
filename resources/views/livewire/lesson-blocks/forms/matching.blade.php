<div class="block-fields-grid block-form block-form-matching">
    <label class="wide">
        تعليمات الوصل
        <input type="text" wire:model.live="blocks.{{ $index }}.content.question" placeholder="مثال: صِل الكلمة بمعناها">
    </label>
    <label>
        عنوان النشاط
        <input type="text" wire:model.live="blocks.{{ $index }}.title" placeholder="{{ $meta['label'] }}">
    </label>
    <label>
        درجة النشاط
        <input type="number" min="1" max="100" wire:model.live="blocks.{{ $index }}.content.score">
    </label>
    <label>
        الطرف الأول
        <textarea rows="5" wire:model.live="blocks.{{ $index }}.content.left_items_text" placeholder="تفاحة&#10;برتقال"></textarea>
    </label>
    <label>
        الطرف الثاني
        <textarea rows="5" wire:model.live="blocks.{{ $index }}.content.right_items_text" placeholder="Apple&#10;Orange"></textarea>
    </label>
    <label class="wide">
        تلميح
        <input type="text" wire:model.live="blocks.{{ $index }}.content.hint" placeholder="اختياري">
    </label>
</div>
