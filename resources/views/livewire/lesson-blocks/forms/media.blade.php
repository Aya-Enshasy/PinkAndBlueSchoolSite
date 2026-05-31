@php($type = $block['type'] ?? '')
<div class="block-fields-grid block-form block-form-media">
    <label>
        عنوان الوسيط
        <input type="text" wire:model.live="blocks.{{ $index }}.title" placeholder="{{ $meta['label'] }}">
    </label>
    <label>
        أيقونة
        <input type="text" wire:model.live="blocks.{{ $index }}.content.emoji">
    </label>

    @include('livewire.lesson-blocks.forms._rich-text', [
        'model' => "blocks.{$index}.content.body",
        'value' => $block['content']['body'] ?? '',
        'label' => 'تعليمات للطالب',
    ])

    @if (str_contains($type, 'video'))
        <label class="wide">
            رابط YouTube / فيديو
            <input type="url" wire:model.live="blocks.{{ $index }}.media.video_url" placeholder="https://youtube.com/watch?v=...">
        </label>
    @endif

    @if (str_contains($type, 'pdf'))
        <label>
            رابط PDF
            <input type="url" wire:model.live="blocks.{{ $index }}.media.pdf_url" placeholder="اختياري">
        </label>
        <label>
            ارفاق PDF
            <input type="file" wire:model="uploads.{{ $index }}.pdf" accept="application/pdf">
            <small wire:loading wire:target="uploads.{{ $index }}.pdf">جار رفع PDF...</small>
        </label>
    @endif

    @if (str_contains($type, 'audio') || str_contains($type, 'listen') || str_contains($type, 'repeat') || str_contains($type, 'recording'))
        <label>
            رابط صوت
            <input type="url" wire:model.live="blocks.{{ $index }}.media.audio_url" placeholder="اختياري">
        </label>
        <label>
            ارفاق صوت
            <input type="file" wire:model="uploads.{{ $index }}.audio" accept="audio/*">
            <small wire:loading wire:target="uploads.{{ $index }}.audio">جار رفع الصوت...</small>
        </label>
        <div class="audio-recorder-panel wide" x-data="lessonAudioRecorder('uploads.{{ $index }}.audio')">
            <div class="audio-recorder-copy">
                <strong>تسجيل صوت مباشر</strong>
                <small>سجل شرحًا قصيرًا من الميكروفون، ثم احفظ الدرس بعد اكتمال الرفع.</small>
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
    @endif

    @if (str_contains($type, 'image') || str_contains($type, 'gif'))
        <label>
            رابط صورة / GIF
            <input type="url" wire:model.live="blocks.{{ $index }}.media.image_url" placeholder="اختياري">
        </label>
        <label>
            ارفاق صورة
            <input type="file" wire:model="uploads.{{ $index }}.image" accept="image/*">
        </label>
    @endif
</div>
