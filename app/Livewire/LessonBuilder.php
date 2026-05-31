<?php

namespace App\Livewire;

use App\Models\Lesson;
use App\Models\LessonBlock;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class LessonBuilder extends Component
{
    use WithFileUploads;

    public ?int $lessonId = null;
    public string $lessonPicker = '';
    public int $grade = 1;
    public string $subject = 'arabic';
    public int $unitNo = 1;
    public string $unitTitle = '';
    public string $lessonTitle = '';
    public string $status = 'published';
    public int $xpReward = 20;
    public array $blocks = [];
    public array $uploads = [];
    public string $notice = '';

    public function mount(): void
    {
        if ($this->blocks === []) {
            $this->addBlock($this->defaultTypeForSubject($this->subject));
        }

        $this->ensureBlocksShape();
    }

    public function hydrate(): void
    {
        $this->ensureBlocksShape();
    }

    public function render(): View
    {
        $this->ensureBlocksShape();

        return view('livewire.lesson-builder', [
            'subjects' => $this->subjects(),
            'grades' => $this->grades(),
            'units' => range(1, 9),
            'presets' => $this->presetsForSubject($this->subject),
            'guide' => $this->subjectGuide($this->subject),
            'stage' => $this->stageProfile($this->grade),
            'savedLessons' => Lesson::query()
                ->orderBy('grade')
                ->orderBy('subject')
                ->orderBy('unit_no')
                ->orderByDesc('updated_at')
                ->get(),
        ]);
    }

    public function updatedSubject(): void
    {
        $this->ensureBlocksShape();

        foreach ($this->blocks as $index => $block) {
            $this->blocks[$index]['subject'] = $this->subject;
            $this->blocks[$index]['settings']['category'] = $this->blockCategory($this->blocks[$index]['type']);
            $this->blocks[$index]['settings']['layout'] = $this->formTemplate($this->blocks[$index]['type']);
            $this->blocks[$index]['settings']['stage'] = $this->stageProfile($this->grade)['key'];
        }
    }

    public function updatedGrade(): void
    {
        $this->ensureBlocksShape();

        foreach ($this->blocks as $index => $block) {
            $this->blocks[$index]['settings']['stage'] = $this->stageProfile($this->grade)['key'];
        }
    }

    public function loadSelectedLesson(): void
    {
        if ($this->lessonPicker !== '') {
            $this->loadLesson((int) $this->lessonPicker);
        }
    }

    public function loadLesson(int $lessonId): void
    {
        $lesson = Lesson::query()->with('blocks')->findOrFail($lessonId);

        $this->lessonId = $lesson->id;
        $this->lessonPicker = (string) $lesson->id;
        $this->grade = $lesson->grade;
        $this->subject = $lesson->subject;
        $this->unitNo = $lesson->unit_no;
        $this->unitTitle = $lesson->unit_title ?: '';
        $this->lessonTitle = $lesson->title;
        $this->status = $lesson->status;
        $this->xpReward = $lesson->xp_reward;
        $this->blocks = $lesson->blocks
            ->map(fn (LessonBlock $block) => $this->blockFromModel($block))
            ->values()
            ->all();
        $this->uploads = [];
        $this->notice = 'تم تحميل الدرس للتعديل.';
    }

    public function newLesson(): void
    {
        $this->lessonId = null;
        $this->lessonPicker = '';
        $this->unitTitle = '';
        $this->lessonTitle = '';
        $this->status = 'published';
        $this->xpReward = 20;
        $this->blocks = [];
        $this->uploads = [];
        $this->addBlock($this->defaultTypeForSubject($this->subject));
        $this->notice = 'بدأت درسًا جديدًا.';
    }

    public function addBlock(?string $type = null): void
    {
        $this->blocks[] = $this->blankBlock($type ?: $this->defaultTypeForSubject($this->subject));
    }

    public function duplicateBlock(int $index): void
    {
        if (! isset($this->blocks[$index])) {
            return;
        }

        $copy = $this->blocks[$index];
        $copy['id'] = null;
        $copy['uid'] = (string) Str::uuid();
        $copy['title'] = trim(($copy['title'] ?? '').' نسخة');

        array_splice($this->blocks, $index + 1, 0, [$copy]);
    }

    public function removeBlock(int $index): void
    {
        if (count($this->blocks) <= 1) {
            $this->notice = 'اترك قسمًا واحدًا على الأقل داخل الدرس.';
            return;
        }

        if (isset($this->blocks[$index])) {
            array_splice($this->blocks, $index, 1);
        }
    }

    public function normalizeBlockType(int $index): void
    {
        $this->ensureBlocksShape();

        if (! isset($this->blocks[$index])) {
            return;
        }

        $type = $this->blocks[$index]['type'] ?? $this->defaultTypeForSubject($this->subject);
        $base = $this->blankBlock($type);
        $this->blocks[$index] = array_replace_recursive($base, $this->blocks[$index]);
        $this->blocks[$index]['subject'] = $this->subject;
        $this->blocks[$index]['settings']['category'] = $this->blockCategory($type);
        $this->blocks[$index]['settings']['layout'] = $this->formTemplate($type);
        $this->blocks[$index]['settings']['is_graded'] = $this->isGradedType($type);
    }

    public function reorderBlocks(array $orderedUids): void
    {
        $this->ensureBlocksShape();

        $current = collect($this->blocks)->keyBy(fn (array $block) => $block['uid']);
        $ordered = [];

        foreach ($orderedUids as $uid) {
            if ($current->has($uid)) {
                $ordered[] = $current->get($uid);
            }
        }

        foreach ($this->blocks as $block) {
            if (! in_array($block['uid'], $orderedUids, true)) {
                $ordered[] = $block;
            }
        }

        $this->blocks = array_values($ordered);
    }

    public function save(): void
    {
        $this->ensureBlocksShape();

        $this->validate([
            'grade' => ['required', 'integer', 'min:1', 'max:9'],
            'subject' => ['required', 'in:arabic,english,math,science'],
            'unitNo' => ['required', 'integer', 'min:1', 'max:9'],
            'lessonTitle' => ['required', 'string', 'max:180'],
            'status' => ['required', 'in:published,draft'],
            'xpReward' => ['required', 'integer', 'min:0', 'max:500'],
            'uploads.*.image' => ['nullable', 'image', 'max:4096'],
            'uploads.*.pdf' => ['nullable', 'file', 'mimes:pdf', 'max:12288'],
            'uploads.*.audio' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a,webm,weba', 'max:10240'],
        ]);

        $lesson = $this->lessonId
            ? Lesson::query()->findOrFail($this->lessonId)
            : new Lesson();

        $lesson->fill([
            'grade' => $this->grade,
            'subject' => $this->subject,
            'unit_no' => $this->unitNo,
            'unit_title' => $this->unitTitle ?: "الوحدة {$this->unitNo}",
            'title' => $this->lessonTitle,
            'status' => $this->status,
            'xp_reward' => $this->xpReward,
            'sort_order' => $lesson->sort_order ?: 0,
        ])->save();

        $this->lessonId = $lesson->id;
        $this->lessonPicker = (string) $lesson->id;

        $keptIds = [];

        foreach ($this->blocks as $index => $block) {
            $block = $this->prepareBlockForSave($block, $index);
            $model = isset($block['id']) && $block['id']
                ? LessonBlock::query()->where('lesson_id', $lesson->id)->find($block['id'])
                : new LessonBlock();

            $model ??= new LessonBlock();
            $model->fill([
                'lesson_id' => $lesson->id,
                'type' => $block['type'],
                'subject' => $this->subject,
                'category' => $this->blockCategory($block['type']),
                'subtype' => $this->formTemplate($block['type']),
                'title' => $block['title'] ?: $this->typeLabel($block['type']),
                'content' => $block['content'],
                'media' => $block['media'],
                'settings' => $block['settings'],
                'is_graded' => $this->isGradedType($block['type']),
                'sort_order' => $index,
            ])->save();

            $this->blocks[$index] = $this->blockFromModel($model, $block['uid'] ?? null);
            $keptIds[] = $model->id;
        }

        LessonBlock::query()
            ->where('lesson_id', $lesson->id)
            ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
            ->delete();

        $this->uploads = [];
        $this->notice = $this->status === 'draft'
            ? 'تم حفظ الدرس كمسودة للمعلم فقط.'
            : 'تم حفظ الدرس ونشره للطالب حسب الصف والمادة المحددة.';
    }

    public function saveDraft(): void
    {
        $this->status = 'draft';
        $this->save();
    }

    public function subjects(): array
    {
        return [
            'arabic' => ['label' => 'اللغة العربية', 'icon' => '', 'color' => '#d22d78'],
            'english' => ['label' => 'اللغة الإنجليزية', 'icon' => '', 'color' => '#215b9f'],
            'math' => ['label' => 'الحساب', 'icon' => '', 'color' => '#8b5cf6'],
            'science' => ['label' => 'العلوم', 'icon' => '', 'color' => '#0ea5e9'],
        ];
    }

    public function grades(): array
    {
        return [
            1 => 'الصف الأول',
            2 => 'الصف الثاني',
            3 => 'الصف الثالث',
            4 => 'الصف الرابع',
            5 => 'الصف الخامس',
            6 => 'الصف السادس',
            7 => 'الصف السابع',
            8 => 'الصف الثامن',
            9 => 'الصف التاسع',
        ];
    }

    public function presetsForSubject(string $subject): array
    {
        $colors = [
            'explain' => '#215b9f',
            'example' => '#d22d78',
            'interactive' => '#16a085',
            'media' => '#f59e0b',
            'tool' => '#8b5cf6',
        ];

        $presets = [
            'science' => [
                ['type' => 'observation', 'label' => 'ملاحظة ظاهرة', 'group' => 'شرح', 'icon' => '', 'color' => $colors['explain']],
                ['type' => 'experiment', 'label' => 'تجربة', 'group' => 'شرح', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'cause_explanation', 'label' => 'تفسير السبب', 'group' => 'شرح', 'icon' => '', 'color' => $colors['explain']],
                ['type' => 'conclusion', 'label' => 'استنتاج', 'group' => 'شرح', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'concept', 'label' => 'مفهوم علمي', 'group' => 'شرح', 'icon' => '', 'color' => $colors['explain']],
                ['type' => 'experiment_steps', 'label' => 'خطوات تجربة', 'group' => 'شرح', 'icon' => '1', 'color' => $colors['example']],
                ['type' => 'hypothesis', 'label' => 'فرضية', 'group' => 'شرح', 'icon' => '', 'color' => $colors['explain']],
                ['type' => 'result', 'label' => 'نتيجة', 'group' => 'شرح', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'compare', 'label' => 'قارن', 'group' => 'تحليل', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'analyze_image', 'label' => 'حلل صورة', 'group' => 'تحليل', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'diagram_explanation', 'label' => 'فسر الرسم', 'group' => 'تحليل', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'sequence_cycle', 'label' => 'رتب دورة', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'drag_parts', 'label' => 'اسحب أجزاء', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'match_term', 'label' => 'وصل المصطلح', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'true_false', 'label' => 'صح وخطأ', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'multiple_choice', 'label' => 'اختيار متعدد', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'video', 'label' => 'فيديو تجربة', 'group' => 'وسائط', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'audio', 'label' => 'صوت شرح', 'group' => 'وسائط', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'image', 'label' => 'صورة', 'group' => 'وسائط', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'gif', 'label' => 'GIF', 'group' => 'وسائط', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'pdf', 'label' => 'PDF', 'group' => 'وسائط', 'icon' => '', 'color' => $colors['media']],
            ],
            'math' => [
                ['type' => 'lesson_idea', 'label' => 'فكرة الدرس', 'group' => 'شرح', 'icon' => '', 'color' => $colors['explain']],
                ['type' => 'step_by_step', 'label' => 'شرح خطوة بخطوة', 'group' => 'شرح', 'icon' => '1', 'color' => $colors['explain']],
                ['type' => 'law', 'label' => 'قانون', 'group' => 'شرح', 'icon' => '', 'color' => $colors['explain']],
                ['type' => 'rule', 'label' => 'قاعدة', 'group' => 'شرح', 'icon' => '', 'color' => $colors['explain']],
                ['type' => 'solution_method', 'label' => 'طريقة الحل', 'group' => 'شرح', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'solution_steps', 'label' => 'خطوات الحل', 'group' => 'شرح', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'common_mistake', 'label' => 'خطأ شائع', 'group' => 'شرح', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'simplify', 'label' => 'تبسيط', 'group' => 'شرح', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'solved_example', 'label' => 'مثال محلول', 'group' => 'أمثلة', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'gradual_solution', 'label' => 'حل تدريجي', 'group' => 'أمثلة', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'complete_solution', 'label' => 'أكمل الحل', 'group' => 'أمثلة', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'missing_solution', 'label' => 'حل ناقص', 'group' => 'أمثلة', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'order_solution_steps', 'label' => 'رتب خطوات الحل', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'choose_operation', 'label' => 'اختر العملية الصحيحة', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'drag_number', 'label' => 'اسحب الرقم', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'count_items', 'label' => 'عدّ العناصر', 'group' => 'صفوف صغيرة', 'icon' => '', 'color' => $colors['tool']],
                ['type' => 'match_fraction', 'label' => 'طابق الكسر', 'group' => 'تفاعلي', 'icon' => '½', 'color' => $colors['interactive']],
                ['type' => 'visual_counter', 'label' => 'عداد بصري', 'group' => 'أداة', 'icon' => '', 'color' => $colors['tool']],
                ['type' => 'cubes', 'label' => 'مكعبات', 'group' => 'أداة', 'icon' => '', 'color' => $colors['tool']],
                ['type' => 'circles', 'label' => 'دوائر', 'group' => 'أداة', 'icon' => '', 'color' => $colors['tool']],
                ['type' => 'apples', 'label' => 'صور تفاح', 'group' => 'أداة', 'icon' => '', 'color' => $colors['tool']],
                ['type' => 'number_line', 'label' => 'خط أعداد', 'group' => 'أداة', 'icon' => '', 'color' => $colors['tool']],
                ['type' => 'calculator', 'label' => 'آلة حاسبة بسيطة', 'group' => 'أداة', 'icon' => '', 'color' => $colors['tool']],
                ['type' => 'shapes', 'label' => 'رسم أشكال', 'group' => 'أداة', 'icon' => '', 'color' => $colors['tool']],
                ['type' => 'grid', 'label' => 'شبكة مربعات', 'group' => 'أداة', 'icon' => '', 'color' => $colors['tool']],
            ],
            'arabic' => [
                ['type' => 'reading_text', 'label' => 'قراءة النص', 'group' => 'شرح', 'icon' => '', 'color' => $colors['explain']],
                ['type' => 'word_explanation', 'label' => 'شرح الكلمات', 'group' => 'شرح', 'icon' => '', 'color' => $colors['explain']],
                ['type' => 'main_idea', 'label' => 'الفكرة العامة', 'group' => 'شرح', 'icon' => '', 'color' => $colors['explain']],
                ['type' => 'moral', 'label' => 'المغزى', 'group' => 'شرح', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'grammar', 'label' => 'القواعد', 'group' => 'شرح', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'parsing', 'label' => 'إعراب', 'group' => 'شرح', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'language_practice', 'label' => 'تدريب لغوي', 'group' => 'تدريب', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'listen_text', 'label' => 'استمع للنص', 'group' => 'قراءة', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'read_after_audio', 'label' => 'اقرأ خلف الصوت', 'group' => 'قراءة', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'word_picture', 'label' => 'كلمة وصورة', 'group' => 'قراءة', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'order_sentence', 'label' => 'رتب الجملة', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'complete_word', 'label' => 'أكمل الكلمة', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'write_sentence', 'label' => 'اكتب جملة', 'group' => 'تعبير', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'describe_picture', 'label' => 'عبر عن الصورة', 'group' => 'تعبير', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'correct_mistake', 'label' => 'صحح الخطأ', 'group' => 'تعبير', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'build_word', 'label' => 'كوّن كلمة', 'group' => 'تعبير', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'match_meaning', 'label' => 'وصل الكلمة بمعناها', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'synonym', 'label' => 'مرادف', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'opposite', 'label' => 'ضد', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'order_words', 'label' => 'ترتيب كلمات', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'audio', 'label' => 'صوت', 'group' => 'وسائط', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'image', 'label' => 'صورة', 'group' => 'وسائط', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'pdf', 'label' => 'PDF', 'group' => 'وسائط', 'icon' => '', 'color' => $colors['media']],
            ],
            'english' => [
                ['type' => 'vocabulary_word', 'label' => 'كلمة جديدة', 'group' => 'مفردات', 'icon' => 'A', 'color' => $colors['explain']],
                ['type' => 'word_picture', 'label' => 'صورة الكلمة', 'group' => 'مفردات', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'listen_word', 'label' => 'استمع للكلمة', 'group' => 'مفردات', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'repeat_word', 'label' => 'كرر الكلمة', 'group' => 'مفردات', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'translation', 'label' => 'ترجمة', 'group' => 'مفردات', 'icon' => '', 'color' => $colors['explain']],
                ['type' => 'grammar_rule', 'label' => 'قاعدة', 'group' => 'قواعد', 'icon' => '', 'color' => $colors['explain']],
                ['type' => 'grammar_example', 'label' => 'مثال', 'group' => 'قواعد', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'fill_blank', 'label' => 'أكمل الفراغ', 'group' => 'قواعد', 'icon' => '___', 'color' => $colors['interactive']],
                ['type' => 'correct_sentence', 'label' => 'صحح الجملة', 'group' => 'قواعد', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'listen_repeat', 'label' => 'استمع وأعد', 'group' => 'محادثة', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'audio_recording', 'label' => 'تسجيل صوت', 'group' => 'محادثة', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'choose_heard', 'label' => 'اختر ما سمعت', 'group' => 'استماع', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'short_conversation', 'label' => 'محادثة قصيرة', 'group' => 'محادثة', 'icon' => '', 'color' => $colors['example']],
                ['type' => 'match_picture', 'label' => 'وصل الكلمة بالصورة', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'drag_word', 'label' => 'اسحب الكلمة', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'order_sentence_en', 'label' => 'رتب الجملة', 'group' => 'تفاعلي', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'choose_pronunciation', 'label' => 'اختر النطق الصحيح', 'group' => 'استماع', 'icon' => '', 'color' => $colors['interactive']],
                ['type' => 'video', 'label' => 'فيديو', 'group' => 'وسائط', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'audio', 'label' => 'صوت', 'group' => 'وسائط', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'image', 'label' => 'صورة', 'group' => 'وسائط', 'icon' => '', 'color' => $colors['media']],
                ['type' => 'pdf', 'label' => 'PDF', 'group' => 'وسائط', 'icon' => '', 'color' => $colors['media']],
            ],
        ];

        return $presets[$subject] ?? $presets['arabic'];
    }

    public function subjectGuide(string $subject): array
    {
        return match ($subject) {
            'science' => [
                'title' => 'العلوم تبدأ من ملاحظة ثم تجربة ثم استنتاج.',
                'text' => 'استخدم صورة أو فيديو عندما يكون المفهوم بصريًا، وخلي الطالب يلاحظ قبل أن يرى التفسير.',
                'chips' => ['ملاحظة', 'تجربة', 'تفسير', 'استنتاج'],
            ],
            'math' => [
                'title' => 'الحساب يحتاج خطوات مرئية لا نص طويل.',
                'text' => 'ابدأ بفكرة قصيرة، ثم مثال محلول، ثم خطوات مرقمة أو أداة بصرية مثل خط الأعداد.',
                'chips' => ['فكرة', 'قانون', 'خطوات', 'تحقق'],
            ],
            'english' => [
                'title' => 'الإنجليزي يعتمد على الصوت والصورة والتكرار.',
                'text' => 'استخدم كلمة مع صورة، صوت، محادثة قصيرة، وخيارات تحت بعضها مثل تجربة Duolingo.',
                'chips' => ['مفردات', 'استماع', 'تكرار', 'محادثة'],
            ],
            default => [
                'title' => 'العربي يحتاج قراءة وفهم ومعنى.',
                'text' => 'ابدأ بنص أو كلمة، ثم معنى في السياق، ثم مثال، ثم تدريب لغوي بسيط.',
                'chips' => ['قراءة', 'معنى', 'قواعد', 'تعبير'],
            ],
        };
    }

    public function stageProfile(int $grade): array
    {
        if ($grade <= 3) {
            return [
                'key' => 'early',
                'label' => 'صفوف 1-3',
                'text' => 'صور كبيرة، صوت، تفاعل سريع، ألوان وحركة أكثر من النص.',
            ];
        }

        if ($grade <= 6) {
            return [
                'key' => 'middle',
                'label' => 'صفوف 4-6',
                'text' => 'شرح أوضح، أمثلة، وأسئلة فهم قصيرة.',
            ];
        }

        return [
            'key' => 'upper',
            'label' => 'صفوف 7-9',
            'text' => 'تحليل، استنتاج، أسئلة مفتوحة، وحل مشكلات.',
        ];
    }

    public function typeLabel(string $type): string
    {
        return collect($this->presetsForSubject($this->subject))->firstWhere('type', $type)['label'] ?? Str::headline($type);
    }

    public function typeMeta(string $type): array
    {
        $meta = collect($this->presetsForSubject($this->subject))->firstWhere('type', $type)
            ?? ['label' => Str::headline($type), 'icon' => '', 'color' => '#215b9f', 'group' => 'مخصص'];

        return array_replace($meta, [
            'category' => $this->blockCategory($type),
            'layout' => $this->formTemplate($type),
            'is_graded' => $this->isGradedType($type),
        ]);
    }

    public function blockCategory(string $type): string
    {
        if (Str::contains($type, ['video', 'pdf', 'audio', 'image', 'gif', 'listen', 'repeat', 'recording'])) {
            return 'media';
        }

        if (Str::contains($type, ['choice', 'match', 'order', 'drag', 'true_false', 'blank', 'correct', 'complete', 'choose'])) {
            return 'interactive';
        }

        if (Str::contains($type, ['quiz', 'question', 'assessment'])) {
            return 'assessment';
        }

        return 'explanation';
    }

    public function formTemplate(string $type): string
    {
        if (Str::contains($type, ['video', 'pdf', 'audio', 'image', 'gif', 'listen', 'repeat', 'recording'])) {
            return 'media';
        }

        if (Str::contains($type, ['match', 'synonym', 'opposite'])) {
            return 'matching';
        }

        if (Str::contains($type, ['order', 'drag'])) {
            return 'ordering';
        }

        if (Str::contains($type, ['choice', 'true_false', 'blank', 'correct', 'complete', 'choose'])) {
            return 'choice';
        }

        if (Str::contains($type, ['example', 'solution', 'steps', 'experiment'])) {
            return 'steps';
        }

        if (Str::contains($type, ['vocabulary', 'word_picture', 'word_explanation', 'translation'])) {
            return 'vocabulary';
        }

        return 'explanation';
    }

    public function isGradedType(string $type): bool
    {
        return Str::contains($type, [
            'choice',
            'true_false',
            'blank',
            'correct',
            'complete',
            'choose',
            'quiz',
            'question',
            'match',
            'order',
            'drag',
        ]);
    }

    public function renderTemplate(string $type): string
    {
        if (Str::contains($type, ['video'])) {
            return 'video';
        }

        if (Str::contains($type, ['pdf'])) {
            return 'pdf';
        }

        if (Str::contains($type, ['audio', 'listen', 'repeat', 'recording'])) {
            return 'audio';
        }

        if (Str::contains($type, ['experiment'])) {
            return 'experiment';
        }

        if (Str::contains($type, ['observation', 'analyze_image', 'diagram'])) {
            return 'observation';
        }

        if (Str::contains($type, ['match', 'synonym', 'opposite'])) {
            return 'matching';
        }

        if (Str::contains($type, ['order', 'drag'])) {
            return 'ordering';
        }

        if (Str::contains($type, ['example', 'solution'])) {
            return 'example';
        }

        if (Str::contains($type, ['vocabulary', 'word_picture', 'listen_word', 'translation'])) {
            return 'vocabulary';
        }

        if (Str::contains($type, ['grammar', 'rule', 'law', 'concept', 'definition'])) {
            return 'definition';
        }

        if (Str::contains($type, ['reading', 'text', 'moral'])) {
            return 'reading';
        }

        if (Str::contains($type, ['choice', 'match', 'order', 'drag', 'true_false', 'blank', 'correct'])) {
            return 'interactive';
        }

        return 'default';
    }

    private function defaultTypeForSubject(string $subject): string
    {
        return match ($subject) {
            'science' => 'observation',
            'math' => 'lesson_idea',
            'english' => 'vocabulary_word',
            default => 'reading_text',
        };
    }

    private function ensureBlocksShape(): void
    {
        if ($this->blocks === []) {
            $this->blocks[] = $this->blankBlock($this->defaultTypeForSubject($this->subject));
            return;
        }

        foreach ($this->blocks as $index => $block) {
            $type = is_array($block) ? ($block['type'] ?? $this->defaultTypeForSubject($this->subject)) : $this->defaultTypeForSubject($this->subject);
            $base = $this->blankBlock($type);
            $uid = is_array($block) && ! empty($block['uid']) ? $block['uid'] : (string) Str::uuid();

            $this->blocks[$index] = array_replace_recursive($base, is_array($block) ? $block : []);
            $this->blocks[$index]['uid'] = $uid;
            $this->blocks[$index]['type'] = $type;
            $this->blocks[$index]['subject'] = $this->subject;
            $this->blocks[$index]['settings']['category'] = $this->blockCategory($type);
            $this->blocks[$index]['settings']['layout'] = $this->formTemplate($type);
            $this->blocks[$index]['settings']['is_graded'] = $this->isGradedType($type);
            $this->blocks[$index]['settings']['stage'] = $this->stageProfile($this->grade)['key'];
        }

        $this->blocks = array_values($this->blocks);
    }

    private function blankBlock(string $type): array
    {
        $meta = $this->typeMeta($type);
        $stage = $this->stageProfile($this->grade);

        return [
            'uid' => (string) Str::uuid(),
            'id' => null,
            'type' => $type,
            'subject' => $this->subject,
            'title' => '',
            'content' => [
                'emoji' => $meta['icon'] ?? '',
                'body' => '',
                'term' => '',
                'symbol' => '',
                'prompt' => '',
                'items_text' => '',
                'options_text' => '',
                'left_items_text' => '',
                'right_items_text' => '',
                'question' => '',
                'answer' => '',
                'result' => '',
                'hint' => '',
                'score' => 1,
            ],
            'media' => [
                'image_url' => '',
                'image_alt' => '',
                'video_url' => '',
                'pdf_url' => '',
                'audio_url' => '',
                'file_name' => '',
            ],
            'settings' => [
                'allow_audio' => in_array($this->subject, ['english', 'arabic'], true),
                'show_step_numbers' => in_array($this->subject, ['math', 'science'], true),
                'interaction' => $this->blockCategory($type) === 'interactive' ? 'active' : 'read',
                'category' => $this->blockCategory($type),
                'layout' => $this->formTemplate($type),
                'is_graded' => $this->isGradedType($type),
                'stage' => $stage['key'],
            ],
        ];
    }

    private function blockFromModel(LessonBlock $block, ?string $uid = null): array
    {
        $base = $this->blankBlock($block->type);
        $base['uid'] = $uid ?: (string) Str::uuid();
        $base['id'] = $block->id;
        $base['subject'] = $block->subject;
        $base['title'] = $block->title ?: '';
        $base['content'] = array_replace($base['content'], $block->content ?: []);
        $base['media'] = array_replace($base['media'], $block->media ?: []);
        $base['settings'] = array_replace($base['settings'], $block->settings ?: [], [
            'category' => $block->category ?: $this->blockCategory($block->type),
            'layout' => $block->subtype ?: $this->formTemplate($block->type),
            'is_graded' => $block->is_graded,
        ]);

        return $base;
    }

    private function prepareBlockForSave(array $block, int $index): array
    {
        $block = array_replace_recursive($this->blankBlock($block['type'] ?? $this->defaultTypeForSubject($this->subject)), $block);
        $block['subject'] = $this->subject;
        $block['content']['items'] = $this->lines($block['content']['items_text'] ?? '');
        $block['content']['options'] = $this->lines($block['content']['options_text'] ?? '');
        $leftItems = $this->lines($block['content']['left_items_text'] ?? '');
        $rightItems = $this->lines($block['content']['right_items_text'] ?? '');
        $block['content']['pairs'] = collect($leftItems)
            ->map(fn (string $left, int $pairIndex) => [
                'left' => $left,
                'right' => $rightItems[$pairIndex] ?? '',
            ])
            ->filter(fn (array $pair) => $pair['left'] !== '' || $pair['right'] !== '')
            ->values()
            ->all();
        $block['settings']['category'] = $this->blockCategory($block['type']);
        $block['settings']['layout'] = $this->formTemplate($block['type']);
        $block['settings']['is_graded'] = $this->isGradedType($block['type']);

        foreach (['image' => 'image_url', 'pdf' => 'pdf_url', 'audio' => 'audio_url'] as $slot => $mediaKey) {
            $file = data_get($this->uploads, "{$index}.{$slot}");

            if ($file) {
                $path = $file->store("lesson-media/{$slot}s", 'public');
                $block['media'][$mediaKey] = Storage::disk('public')->url($path);
                $block['media']['file_name'] = $file->getClientOriginalName();
            }
        }

        return $block;
    }

    private function lines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n|,/', $value) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
