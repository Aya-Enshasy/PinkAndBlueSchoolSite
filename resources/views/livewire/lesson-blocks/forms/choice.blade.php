<div class="block-fields-grid block-form block-form-choice">
    <label class="wide">
        السؤال
        <input type="text" wire:model.live="blocks.{{ $index }}.content.question" placeholder="اكتب السؤال الذي سيظهر للطالب">
    </label>

    @include('livewire.lesson-blocks.forms._rich-text', [
        'model' => "blocks.{$index}.content.body",
        'value' => $block['content']['body'] ?? '',
        'label' => 'تعليمات قصيرة قبل السؤال',
    ])

    <label class="wide">
        الخيارات
        <textarea rows="4" wire:model.live="blocks.{{ $index }}.content.options_text" placeholder="كل خيار بسطر. مثال: نعم / لا / ربما"></textarea>
    </label>
    <label>
        الإجابة الصحيحة
        <input type="text" wire:model.live="blocks.{{ $index }}.content.answer" placeholder="اكتب الخيار الصحيح تمامًا">
    </label>
    <label>
        درجة / XP السؤال
        <input type="number" min="1" max="100" wire:model.live="blocks.{{ $index }}.content.score">
    </label>
    <label>
        تلميح عند الخطأ
        <input type="text" wire:model.live="blocks.{{ $index }}.content.hint" placeholder="اختياري">
    </label>
    <label>
        صورة اختيارية
        <input type="file" wire:model="uploads.{{ $index }}.image" accept="image/*">
    </label>
</div>
