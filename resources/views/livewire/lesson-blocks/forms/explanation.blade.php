<div class="block-fields-grid block-form block-form-explanation">
    <label>
        عنوان الشرح
        <input type="text" wire:model.live="blocks.{{ $index }}.title" placeholder="{{ $meta['label'] }}">
    </label>
    <label>
        أيقونة
        <input type="text" wire:model.live="blocks.{{ $index }}.content.emoji">
    </label>
    <label>
        مصطلح / قاعدة
        <input type="text" wire:model.live="blocks.{{ $index }}.content.term" placeholder="اختياري">
    </label>
    <label>
        رمز / معنى قصير
        <input type="text" wire:model.live="blocks.{{ $index }}.content.symbol" placeholder="اختياري">
    </label>

    @include('livewire.lesson-blocks.forms._rich-text', [
        'model' => "blocks.{$index}.content.body",
        'value' => $block['content']['body'] ?? '',
        'label' => 'نص الشرح',
    ])

    <label class="wide">
        نقاط داعمة
        <textarea rows="3" wire:model.live="blocks.{{ $index }}.content.items_text" placeholder="كل نقطة بسطر. مناسبة للتعريف، الفكرة، القراءة، أو القاعدة."></textarea>
    </label>
    <label>
        خلاصة
        <input type="text" wire:model.live="blocks.{{ $index }}.content.result" placeholder="ما الذي يجب أن يتذكره الطالب؟">
    </label>
    <label>
        تلميح
        <input type="text" wire:model.live="blocks.{{ $index }}.content.hint" placeholder="اختياري">
    </label>
    <label>
        رابط صورة
        <input type="url" wire:model.live="blocks.{{ $index }}.media.image_url" placeholder="اختياري">
    </label>
    <label>
        ارفاق صورة
        <input type="file" wire:model="uploads.{{ $index }}.image" accept="image/*">
        <small wire:loading wire:target="uploads.{{ $index }}.image">جار رفع الصورة...</small>
    </label>
</div>
