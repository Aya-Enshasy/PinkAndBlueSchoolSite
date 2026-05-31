<div class="builder-field wide">
    <label>{{ $label ?? 'المحتوى الرئيسي' }}</label>
    <div class="rich-editor-wrap" wire:ignore
        x-data="lessonRichText(@js($value ?? ''), '{{ $model }}')"
        x-init="init()"
    >
        <div class="rich-toolbar">
            <button type="button" x-on:click="toggleBold">B</button>
            <button type="button" x-on:click="toggleItalic">I</button>
            <button type="button" x-on:click="toggleList">•</button>
        </div>
        <div class="tiptap-editor" x-ref="editor"></div>
    </div>
</div>
