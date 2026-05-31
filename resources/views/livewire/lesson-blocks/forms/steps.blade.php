<div class="block-fields-grid block-form block-form-steps">
    <label>
        عنوان الخطوات
        <input type="text" wire:model.live="blocks.{{ $index }}.title" placeholder="{{ $meta['label'] }}">
    </label>
    <label>
        أيقونة
        <input type="text" wire:model.live="blocks.{{ $index }}.content.emoji">
    </label>

    @include('livewire.lesson-blocks.forms._rich-text', [
        'model' => "blocks.{$index}.content.body",
        'value' => $block['content']['body'] ?? '',
        'label' => 'السؤال أو وصف المثال',
    ])

    <label class="wide">
        الخطوات بالترتيب
        <textarea rows="5" wire:model.live="blocks.{{ $index }}.content.items_text" placeholder="كل خطوة بسطر. مثال: اكتب المعطيات / طبّق القانون / تحقق من الناتج"></textarea>
    </label>
    <label>
        الناتج / الإجابة
        <input type="text" wire:model.live="blocks.{{ $index }}.content.answer" placeholder="اختياري">
    </label>
    <label>
        الخلاصة
        <input type="text" wire:model.live="blocks.{{ $index }}.content.result" placeholder="اختياري">
    </label>
    <label>
        تلميح
        <input type="text" wire:model.live="blocks.{{ $index }}.content.hint" placeholder="اختياري">
    </label>
    <label>
        صورة مساعدة
        <input type="file" wire:model="uploads.{{ $index }}.image" accept="image/*">
    </label>
</div>
