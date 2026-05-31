<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\LessonBlock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradeSixScienceMicroscopeSeeder extends Seeder
{
    private const SOURCE = 'grade-six-science-microscope';

    public function run(): void
    {
        DB::transaction(function (): void {
            Lesson::query()
                ->where('grade', 6)
                ->where('subject', 'science')
                ->where('unit_no', 1)
                ->where(function ($query): void {
                    $query
                        ->where('title', 'المجهر الضوئي (المركب) وأجزاؤه')
                        ->orWhereHas('blocks', fn ($blocks) => $blocks->where('settings->source', self::SOURCE));
                })
                ->get()
                ->each
                ->delete();

            $lesson = Lesson::query()->create([
                'grade' => 6,
                'subject' => 'science',
                'unit_no' => 1,
                'unit_title' => 'الوحدة 1: أدوات البحث العلمي',
                'title' => 'المجهر الضوئي (المركب) وأجزاؤه',
                'status' => 'published',
                'xp_reward' => 95,
                'sort_order' => 1,
            ]);

            foreach ($this->blocks() as $index => $block) {
                LessonBlock::query()->create(array_merge($block, [
                    'lesson_id' => $lesson->id,
                    'subject' => 'science',
                    'sort_order' => $index,
                ]));
            }
        });
    }

    private function blocks(): array
    {
        return [
            $this->block('observation', 'المجهر الضوئي (المركب) وأجزاؤه', [
                'emoji' => '🔬',
                'body' => 'المجهر الضوئي (المركب) جهاز يستخدم لرؤية الأشياء الصغيرة جداً التي لا نستطيع رؤيتها بالعين المجردة، مثل الخلايا والكائنات الحية الدقيقة. يتكون المجهر من عدة أجزاء، ولكل جزء وظيفة محددة تساعدنا على رؤية العينة بوضوح.',
                'items_text' => implode("\n", [
                    'العدسة العينية: ننظر من خلالها إلى العينة.',
                    'العدسات الشيئية: تقوم بتكبير العينة بدرجات مختلفة.',
                    'المنضدة: توضع عليها الشريحة.',
                    'مثبت الشريحة: يثبت الشريحة في مكانها.',
                    'الضابطان الكبيران: يستخدمان للتركيز الأولي للصورة.',
                    'الضابطان الصغيران: يستخدمان لتوضيح الصورة بدقة أكبر.',
                    'المكثف: يجمع الضوء ويوجهه نحو العينة.',
                    'مصدر الضوء: يضيء العينة ليسهل رؤيتها.',
                    'الذراع: يستخدم لحمل المجهر.',
                    'القاعدة: تثبت المجهر وتحافظ على توازنه.',
                ]),
                'result' => 'المجهر جهاز يستخدم لتكبير الأجسام الصغيرة، وتتعاون أجزاؤه لإظهار العينة بصورة واضحة.',
                'hint' => 'ركز على اسم كل جزء ووظيفته.',
                'image_url' => '/assets/demo-science/microscope.svg',
                'image_alt' => 'رسم توضيحي لمجهر ضوئي مركب',
            ]),

            $this->block('concept', 'مفردات الدرس', [
                'emoji' => '💡',
                'term' => 'كلمات علمية',
                'symbol' => 'مصطلحات',
                'body' => 'تعرف على أهم الكلمات الواردة في درس المجهر الضوئي.',
                'items_text' => implode("\n", [
                    'المجهر: جهاز لتكبير الأجسام الصغيرة.',
                    'العينة: الشيء المراد فحصه أو مشاهدته.',
                    'الشريحة: قطعة زجاجية توضع عليها العينة.',
                    'العدسة: جزء زجاجي يساعد على التكبير.',
                    'التكبير: جعل الجسم يبدو أكبر من حجمه الحقيقي.',
                    'المكثف: جزء يجمع الضوء ويوجهه نحو العينة.',
                    'مصدر الضوء: الجزء الذي يوفر الإضاءة.',
                ]),
                'result' => 'فهم معاني الكلمات يساعدنا على فهم أجزاء المجهر ووظائفها بسهولة.',
                'image_url' => '/assets/demo-science/microscope.svg',
            ]),

            $this->block('main_idea', 'الفكرة العامة للدرس', [
                'emoji' => '🧠',
                'body' => 'يتحدث الدرس عن المجهر الضوئي المركب، وأهم أجزائه، ووظيفة كل جزء، والطريقة الصحيحة لاستخدامه في فحص العينات الدقيقة.',
                'result' => 'المجهر أداة علمية مهمة تساعدنا على رؤية عالم الكائنات الدقيقة ودراسته.',
            ]),

            $this->block('experiment_steps', 'كيف نستخدم المجهر؟', [
                'emoji' => '🧪',
                'body' => 'لاستخدام المجهر بطريقة صحيحة نتبع خطوات مرتبة حتى نحصل على صورة واضحة للعينة.',
                'items_text' => implode("\n", [
                    'نحمل المجهر من الذراع والقاعدة.',
                    'نضع المجهر على سطح مستو.',
                    'نثبت الشريحة على المنضدة.',
                    'نوجه الضوء نحو العينة.',
                    'ننظر من خلال العدسة العينية.',
                    'نستخدم الضابطين الكبيرين للحصول على صورة أولية.',
                    'نستخدم الضابطين الصغيرين لتوضيح الصورة بدقة.',
                ]),
                'result' => 'يجب استخدام المجهر بحذر وترتيب للحصول على صورة واضحة للعينة.',
                'image_url' => '/assets/demo-science/microscope.svg',
            ]),

            $this->block('result', 'أهمية المجهر', [
                'emoji' => '⭐',
                'body' => 'ساعد اختراع المجهر العلماء على اكتشاف الكثير من الكائنات الدقيقة والخلايا التي لا يمكن رؤيتها بالعين المجردة. كما ساهم في تطور العلوم والطب ودراسة الكائنات الحية.',
                'items_text' => implode("\n", [
                    'ساعد العلماء في الاكتشافات العلمية.',
                    'يستخدم في المختبرات والمدارس.',
                    'يساعد في دراسة الكائنات الحية الدقيقة.',
                ]),
                'result' => 'المجهر من أهم الأدوات العلمية التي ساعدت الإنسان على فهم العالم من حوله.',
            ]),

            $this->choice('أكمل الكلمة 1', 'يوضع الجسم المراد فحصه على __________.', ['الشريحة', 'القاعدة', 'الذراع'], 'الشريحة'),
            $this->choice('أكمل الكلمة 2', 'ننظر إلى العينة من خلال العدسة __________.', ['العينية', 'الشيئية', 'القاعدة'], 'العينية'),
            $this->choice('أكمل الكلمة 3', 'تستخدم العدسات الشيئية في __________ العينة.', ['تكبير', 'تثبيت', 'حمل'], 'تكبير'),
            $this->choice('أكمل الكلمة 4', 'يثبت المجهر على __________.', ['القاعدة', 'الشريحة', 'المكثف'], 'القاعدة'),
            $this->choice('أكمل الكلمة 5', 'يوفر __________ الإضاءة اللازمة لرؤية العينة.', ['مصدر الضوء', 'مثبت الشريحة', 'الذراع'], 'مصدر الضوء'),

            $this->matching('وصل الكلمة بمعناها', 'صل الكلمة بمعناها الصحيح', [
                'المجهر' => 'جهاز لتكبير الأجسام الصغيرة',
                'العينة' => 'الشيء المراد فحصه',
                'الشريحة' => 'قطعة زجاجية توضع عليها العينة',
                'التكبير' => 'جعل الجسم يبدو أكبر',
                'المكثف' => 'يجمع الضوء ويوجهه للعينة',
            ]),

            $this->matching('مرادف', 'صل الكلمة بمرادفها', [
                'فحص' => 'معاينة',
                'واضحة' => 'جلية',
                'تثبيت' => 'ربط',
                'مشاهدة' => 'رؤية',
                'دقيق' => 'صغير جداً',
            ], 'synonym'),

            $this->choice('صحح المعلومة 1', 'أي جملة تصحح الخطأ: العدسة العينية توضع عليها الشريحة؟', [
                'الشريحة توضع على المنضدة.',
                'القاعدة ننظر من خلالها.',
                'المكثف يحمل المجهر.',
            ], 'الشريحة توضع على المنضدة.', 'correct_mistake'),

            $this->choice('صحح المعلومة 2', 'أي جملة تصحح الخطأ: مصدر الضوء يستخدم لتثبيت الشريحة؟', [
                'مثبت الشريحة يستخدم لتثبيت الشريحة.',
                'الذراع يكبر العينة.',
                'العدسة العينية تجمع الضوء.',
            ], 'مثبت الشريحة يستخدم لتثبيت الشريحة.', 'correct_mistake'),

            $this->choice('صحح المعلومة 3', 'أي جملة تصحح الخطأ: الضابطان الصغيران للتكبير؟', [
                'العدسات الشيئية للتكبير.',
                'مصدر الضوء يثبت الشريحة.',
                'القاعدة تنظر إلى العينة.',
            ], 'العدسات الشيئية للتكبير.', 'correct_mistake'),

            $this->choice('صحح المعلومة 4', 'أي جملة تصحح الخطأ: القاعدة ننظر من خلالها إلى العينة؟', [
                'العدسة العينية ننظر من خلالها إلى العينة.',
                'المنضدة تحمل المجهر باليد.',
                'المكثف هو العينة.',
            ], 'العدسة العينية ننظر من خلالها إلى العينة.', 'correct_mistake'),

            $this->block('describe_picture', 'صف ما تراه', [
                'emoji' => '🖼️',
                'question' => 'انظر إلى صورة المجهر ثم اكتب جملة أو جملتين تصف فيها اسم الجهاز وفائدته.',
                'body' => 'اكتب بأسلوبك: ماذا ترى؟ ما اسم الجهاز؟ ما فائدته؟',
                'hint' => 'مثال: أرى مجهراً ضوئياً يستخدم لتكبير الأجسام الصغيرة.',
                'score' => 6,
                'image_url' => '/assets/demo-science/microscope.svg',
            ], true),

            $this->ordering('كوّن كلمة: مجهر', 'رتب الحروف لتكوين كلمة علمية', ['م', 'ج', 'ه', 'ر'], 'build_word'),
            $this->ordering('كوّن كلمة: عينة', 'رتب الحروف لتكوين كلمة علمية', ['ع', 'ي', 'ن', 'ة'], 'build_word'),
            $this->ordering('كوّن كلمة: شريحة', 'رتب الحروف لتكوين كلمة علمية', ['ش', 'ر', 'ي', 'ح', 'ة'], 'build_word'),
            $this->ordering('كوّن كلمة: ضوء', 'رتب الحروف لتكوين كلمة علمية', ['ض', 'و', 'ء'], 'build_word'),

            $this->ordering('ترتيب كلمات 1', 'رتب الكلمات لتكوين جملة صحيحة', ['ننظر', 'إلى', 'العينة', 'من', 'خلال', 'العدسة', 'العينية'], 'order_words'),
            $this->ordering('ترتيب كلمات 2', 'رتب الكلمات لتكوين جملة صحيحة', ['توضع', 'الشريحة', 'على', 'المنضدة'], 'order_words'),
            $this->ordering('ترتيب كلمات 3', 'رتب الكلمات لتكوين جملة صحيحة', ['تكبر', 'العدسات', 'الشيئية', 'العينة'], 'order_words'),
        ];
    }

    private function choice(string $title, string $question, array $options, string $answer, string $type = 'complete_word'): array
    {
        return $this->block($type, $title, [
            'emoji' => '✅',
            'question' => $question,
            'options_text' => implode("\n", $options),
            'answer' => $answer,
            'hint' => 'اقرأ الجملة بهدوء، ثم اختر الكلمة التي تكمل المعنى.',
            'score' => 5,
        ], true);
    }

    private function matching(string $title, string $question, array $pairs, string $type = 'match_term'): array
    {
        return $this->block($type, $title, [
            'emoji' => '🔗',
            'question' => $question,
            'left_items_text' => implode("\n", array_keys($pairs)),
            'right_items_text' => implode("\n", array_values($pairs)),
            'hint' => 'اضغط على كلمة من العمود الأول ثم معناها الصحيح من العمود الثاني.',
            'score' => 8,
        ], true);
    }

    private function ordering(string $title, string $question, array $items, string $type): array
    {
        return $this->block($type, $title, [
            'emoji' => '↕️',
            'question' => $question,
            'items_text' => implode("\n", $items),
            'hint' => 'استخدم الأسهم أو السحب حتى يصبح الترتيب صحيحاً.',
            'score' => 6,
        ], true);
    }

    private function block(string $type, string $title, array $content, bool $graded = false): array
    {
        $media = [
            'image_url' => $content['image_url'] ?? '',
            'image_alt' => $content['image_alt'] ?? '',
            'video_url' => $content['video_url'] ?? '',
            'pdf_url' => $content['pdf_url'] ?? '',
            'audio_url' => $content['audio_url'] ?? '',
            'file_name' => $content['file_name'] ?? '',
        ];

        foreach (array_keys($media) as $key) {
            unset($content[$key]);
        }

        return [
            'type' => $type,
            'category' => $this->category($type),
            'subtype' => $this->subtype($type),
            'title' => $title,
            'content' => array_merge([
                'emoji' => $content['emoji'] ?? '✨',
                'score' => $content['score'] ?? 1,
            ], $content),
            'media' => $media,
            'settings' => [
                'demo' => true,
                'source' => self::SOURCE,
                'stage' => 'middle',
            ],
            'is_graded' => $graded,
        ];
    }

    private function category(string $type): string
    {
        if (str_contains($type, 'match') || str_contains($type, 'synonym') || str_contains($type, 'order') || str_contains($type, 'build_word') || str_contains($type, 'correct') || str_contains($type, 'complete') || str_contains($type, 'choice') || str_contains($type, 'describe')) {
            return 'interactive';
        }

        return 'explanation';
    }

    private function subtype(string $type): string
    {
        if (str_contains($type, 'match') || str_contains($type, 'synonym')) {
            return 'matching';
        }

        if (str_contains($type, 'order') || str_contains($type, 'build_word')) {
            return 'ordering';
        }

        if (str_contains($type, 'choice') || str_contains($type, 'complete') || str_contains($type, 'correct')) {
            return 'choice';
        }

        if (str_contains($type, 'describe')) {
            return 'writing';
        }

        if (str_contains($type, 'steps') || str_contains($type, 'experiment')) {
            return 'steps';
        }

        return 'explanation';
    }
}
