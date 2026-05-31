<div class="block-fields-grid block-form block-form-vocabulary">
    <label>
        الكلمة / المصطلح
        <input type="text" wire:model.live="blocks.{{ $index }}.title" placeholder="{{ $meta['label'] }}">
    </label>
    <label>
        المعنى / الترجمة
        <input type="text" wire:model.live="blocks.{{ $index }}.content.symbol" placeholder="مثال: كتاب / Book">
    </label>
    <label>
        تصنيف قصير
        <input type="text" wire:model.live="blocks.{{ $index }}.content.term" placeholder="noun / فعل / معنى">
    </label>
    <label>
        أيقونة
        <input type="text" wire:model.live="blocks.{{ $index }}.content.emoji">
    </label>

    @include('livewire.lesson-blocks.forms._rich-text', [
        'model' => "blocks.{$index}.content.body",
        'value' => $block['content']['body'] ?? '',
        'label' => 'جملة مثال أو شرح الكلمة',
    ])

    <label class="wide">
        تدريبات نطق / أمثلة
        <textarea rows="4" wire:model.live="blocks.{{ $index }}.content.items_text" placeholder="كل جملة أو تدريب بسطر"></textarea>
    </label>
    <label>
        رابط صورة
        <input type="url" wire:model.live="blocks.{{ $index }}.media.image_url" placeholder="اختياري">
    </label>
    <label>
        ارفاق صورة
        <input type="file" wire:model="uploads.{{ $index }}.image" accept="image/*">
    </label>
    <label>
        رابط صوت
        <input type="url" wire:model.live="blocks.{{ $index }}.media.audio_url" placeholder="اختياري">
    </label>
    <label>
        ارفاق صوت
        <input type="file" wire:model="uploads.{{ $index }}.audio" accept="audio/*">
    </label>
    <div class="audio-recorder-panel wide" x-data="lessonAudioRecorder('uploads.{{ $index }}.audio')">
        <div class="audio-recorder-copy">
            <strong>تسجيل صوت للكلمة</strong>
            <small>سجل نطق الكلمة أو جملة قصيرة، ثم احفظ الدرس بعد اكتمال الرفع.</small>
        </div>
        <div class="audio-recorder-actions">
            <button type="button" class="recorder-btn start" x-show="!isRecording" x-on:click="start" x-bind:disabled="!supported || isUploading">ابدأ التسجيل</button>
            <button type="button" class="recorder-btn stop" x-show="isRecording" x-on:click="stop">إيقاف التسجيل</button>
        </div>
        <div class="audio-recorder-progress" x-show="isUploading">
            <span x-bind:style="`width: ${progress}%`"></span>
        </div>
        <audio x-show="previewUrl" x-bind:src="previewUrl" controls></audio>
        <small class="audio-recorder-message" x-text="supported ? message : 'المتصفح لا يدعم التسجيل الصوتي.'"></small>
    </div>
</div>
