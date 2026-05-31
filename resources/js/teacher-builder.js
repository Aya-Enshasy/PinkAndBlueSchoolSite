import Sortable from 'sortablejs';
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

window.Sortable = Sortable;

window.createLessonTiptap = (element, content, onUpdate) => new Editor({
    element,
    extensions: [StarterKit],
    content: content || '<p></p>',
    editorProps: {
        attributes: {
            class: 'tiptap-prose',
            dir: 'auto',
        },
    },
    onUpdate: ({ editor }) => onUpdate(editor.getHTML()),
});

window.lessonRichText = (content, modelPath) => ({
    editor: null,
    init() {
        if (!this.$refs.editor || this.$refs.editor.dataset.ready === '1') return;

        this.$refs.editor.dataset.ready = '1';
        this.editor = window.createLessonTiptap(this.$refs.editor, content, (html) => {
            this.$wire.set(modelPath, html, false);
        });
    },
    toggleBold() {
        this.editor?.chain().focus().toggleBold().run();
    },
    toggleItalic() {
        this.editor?.chain().focus().toggleItalic().run();
    },
    toggleList() {
        this.editor?.chain().focus().toggleBulletList().run();
    },
});

window.attachLessonSortable = (element, wire) => {
    if (!window.Sortable || element.dataset.sortableReady === '1') return;

    element.dataset.sortableReady = '1';
    new window.Sortable(element, {
        handle: '.drag-handle',
        animation: 180,
        ghostClass: 'is-dragging',
        chosenClass: 'is-chosen',
        onEnd: () => {
            const order = [...element.querySelectorAll('[data-uid]')].map((item) => item.dataset.uid);
            wire.reorderBlocks(order);
        },
    });
};

window.lessonAudioRecorder = (uploadPath) => ({
    supported: typeof navigator !== 'undefined'
        && !!navigator.mediaDevices
        && typeof navigator.mediaDevices.getUserMedia === 'function'
        && typeof window.MediaRecorder !== 'undefined',
    recorder: null,
    stream: null,
    chunks: [],
    previewUrl: '',
    message: '',
    progress: 0,
    isRecording: false,
    isUploading: false,

    async start() {
        if (!this.supported || this.isRecording || this.isUploading) return;

        this.message = '';
        this.progress = 0;

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.chunks = [];
            this.recorder = new window.MediaRecorder(this.stream);

            this.recorder.addEventListener('dataavailable', (event) => {
                if (event.data?.size) this.chunks.push(event.data);
            });

            this.recorder.addEventListener('stop', () => {
                const type = this.recorder?.mimeType || 'audio/webm';
                const blob = new Blob(this.chunks, { type });
                this.stopTracks();
                this.upload(blob, type);
            }, { once: true });

            this.recorder.start();
            this.isRecording = true;
            this.message = 'التسجيل يعمل الآن...';
        } catch (error) {
            this.stopTracks();
            this.message = 'تعذر تشغيل الميكروفون. تأكد من السماح للتسجيل.';
        }
    },

    stop() {
        if (!this.recorder || this.recorder.state === 'inactive') return;
        this.isRecording = false;
        this.message = 'جار تجهيز التسجيل...';
        this.recorder.stop();
    },

    stopTracks() {
        this.stream?.getTracks?.().forEach((track) => track.stop());
        this.stream = null;
    },

    upload(blob, type) {
        if (!blob?.size) {
            this.message = 'لم يتم التقاط صوت. حاول التسجيل مرة أخرى.';
            return;
        }

        if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
        this.previewUrl = URL.createObjectURL(blob);
        this.isUploading = true;
        this.progress = 1;

        const extension = type.includes('ogg') ? 'ogg' : 'webm';
        const file = new File([blob], `teacher-recording-${Date.now()}.${extension}`, { type });

        this.$wire.upload(
            uploadPath,
            file,
            () => {
                this.isUploading = false;
                this.progress = 100;
                this.message = 'تم رفع التسجيل. احفظ الدرس حتى يظهر للطالب.';
            },
            () => {
                this.isUploading = false;
                this.message = 'تعذر رفع التسجيل. حاول مرة أخرى.';
            },
            (event) => {
                this.progress = event.detail?.progress ?? this.progress;
            },
        );
    },
});
