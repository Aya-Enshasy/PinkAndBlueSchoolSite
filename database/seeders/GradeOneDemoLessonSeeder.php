<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\LessonBlock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradeOneDemoLessonSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            Lesson::query()
                ->whereHas('blocks', fn ($query) => $query->where('settings->source', 'grade-one-demo'))
                ->get()
                ->each
                ->delete();

            $this->seedArabic();
            $this->seedMath();
            $this->seedEnglish();
        });
    }

    private function seedArabic(): void
    {
        $this->lesson([
            'subject' => 'arabic',
            'unit_no' => 1,
            'unit_title' => 'رحلة القراءة الأولى',
            'title' => 'درس شامل: القطة الصغيرة والكرة الزرقاء',
            'xp_reward' => 80,
            'sort_order' => 1,
        ], [
            $this->block('reading_text', 'قراءة النص', [
                'emoji' => '📖',
                'body' => "القِطَّةُ الصَّغِيرَةُ تَلْعَبُ بِالكُرَةِ الزَّرْقَاءِ.\nهِيَ سَعِيدَةٌ وَنَشِيطَةٌ.",
                'items_text' => "أقرأ الجملة الأولى بهدوء\nأضع إصبعي تحت كل كلمة\nألاحظ: من يلعب؟ وبماذا تلعب؟",
                'result' => 'القطة الصغيرة تلعب بالكرة الزرقاء.',
                'hint' => 'اقرأ جملة واحدة كل مرة، ثم اضغط التالي.',
                'image_url' => '/assets/demo-arabic/cat-reading.svg',
                'image_alt' => 'قطة تقرأ وتلعب',
            ]),
            $this->block('word_explanation', 'شرح الكلمات', [
                'emoji' => '💬',
                'term' => 'نشِيطة',
                'symbol' => 'كثيرة الحركة',
                'body' => 'نشيطة تعني أنها تتحرك وتلعب بحماس. نفهم الكلمة من الجملة: القطة تلعب، إذن هي نشيطة.',
                'items_text' => "صغيرة: حجمها قليل\nالكرة الزرقاء: كرة لونها أزرق\nسعيدة: تشعر بالفرح",
                'result' => 'أفهم معنى الكلمة من الجملة والصورة.',
                'image_url' => '/assets/demo-arabic/word-card.svg',
            ]),
            $this->block('main_idea', 'الفكرة العامة', [
                'emoji' => '🧠',
                'body' => 'النص يخبرنا عن قطة صغيرة تلعب وتفرح. الفكرة العامة هي اللعب النشيط والفرح.',
                'items_text' => "الشخصية: القطة الصغيرة\nالشيء: الكرة الزرقاء\nالشعور: السعادة",
                'result' => 'أعرف الفكرة العامة عندما أسأل: عن ماذا يتحدث النص؟',
                'image_url' => '/assets/demo-arabic/cat-reading.svg',
            ]),
            $this->block('moral', 'المغزى', [
                'emoji' => '⭐',
                'body' => 'اللعب جميل عندما يكون آمنا ونشيطا. نلعب ونحافظ على أغراضنا ونشارك أصدقاءنا.',
                'items_text' => "ألعب بهدوء\nأشارك الكرة\nأحافظ على المكان",
                'result' => 'اللعب مع النظام يجعلنا سعداء.',
                'image_url' => '/assets/demo-arabic/reward-star.svg',
            ]),
            $this->block('grammar', 'القواعد', [
                'emoji' => '📌',
                'term' => 'الجملة الاسمية',
                'symbol' => 'اسم + وصف',
                'body' => 'في الجملة: القطة صغيرة. بدأنا باسم ثم وصفنا القطة بكلمة صغيرة.',
                'items_text' => "القطة: اسم\nصغيرة: صفة\nالكرة: اسم\nزرقاء: صفة",
                'result' => 'الصفة تخبرنا شكل الاسم أو لونه أو حجمه.',
                'image_url' => '/assets/demo-arabic/word-card.svg',
            ]),
            $this->block('parsing', 'إعراب مبسط', [
                'emoji' => '✏️',
                'body' => 'لصف أول نستخدم إعرابا بسيطا: نحدد الكلمة ونقول هل هي اسم أم فعل.',
                'items_text' => "القطة: اسم\nتلعب: فعل\nالكرة: اسم\nالزرقاء: صفة",
                'result' => 'أميّز الاسم والفعل من معنى الكلمة في الجملة.',
            ]),
            $this->block('language_practice', 'تدريب لغوي', [
                'emoji' => '🎯',
                'body' => 'نقرأ الجملة ثم نبحث عن كلمة تصف اللون أو الحركة.',
                'items_text' => "كلمة تدل على اللون: الزرقاء\nكلمة تدل على الحركة: تلعب\nكلمة تدل على الشعور: سعيدة",
                'result' => 'أستخرج كلمة مناسبة من النص.',
            ]),
            $this->block('listen_text', 'استمع للنص', [
                'emoji' => '🔊',
                'body' => 'استمع إلى النص، ثم حاول أن تقرأه بصوت واضح.',
                'items_text' => "القطة الصغيرة تلعب بالكرة الزرقاء\nهي سعيدة ونشيطة",
                'options_text' => "القطة\nالمدرسة\nالقلم",
                'answer' => 'القطة',
                'score' => 4,
                'hint' => 'ما الكلمة التي سمعتها في بداية النص؟',
                'audio_url' => $this->demoAudio(),
            ], true),
            $this->block('read_after_audio', 'اقرأ خلف الصوت', [
                'emoji' => '🎙️',
                'body' => 'اضغط تشغيل، ثم اقرأ الجملة بعد الصوت.',
                'items_text' => "القطة الصغيرة\nتلعب بالكرة\nهي سعيدة",
                'hint' => 'قل الجملة ببطء وبصوت مسموع.',
                'audio_url' => $this->demoAudio(),
            ]),
            $this->block('word_picture', 'كلمة وصورة', [
                'emoji' => '🖼️',
                'term' => 'قطة',
                'symbol' => 'حيوان أليف',
                'body' => 'نربط الكلمة بالصورة حتى نتذكر معناها بسرعة.',
                'items_text' => "قطة\nكرة\nلون أزرق",
                'result' => 'الصورة تساعدني أفهم الكلمة.',
                'image_url' => '/assets/demo-arabic/cat-reading.svg',
                'audio_url' => $this->demoAudio(),
            ]),
            $this->block('order_sentence', 'رتب الجملة', [
                'question' => 'رتب الكلمات لتكوين جملة صحيحة',
                'items_text' => "القطة\nتلعب\nبالكرة",
                'score' => 5,
                'hint' => 'ابدأ باسم الشخصية: القطة.',
                'image_url' => '/assets/demo-arabic/sentence-order.svg',
            ], true),
            $this->block('complete_word', 'أكمل الكلمة', [
                'question' => 'أكمل الكلمة: قـ ـة',
                'options_text' => "ط\nب\nم",
                'answer' => 'ط',
                'score' => 4,
                'hint' => 'الكلمة هي قطة.',
            ], true),
            $this->block('write_sentence', 'اكتب جملة', [
                'emoji' => '✍️',
                'question' => 'اكتب جملة قصيرة فيها كلمة قطة',
                'body' => 'اكتب جملة من ثلاث كلمات أو أكثر. مثال: القطة تلعب.',
                'answer' => 'القطة تلعب',
                'score' => 5,
                'hint' => 'ابدأ بكلمة القطة.',
                'image_url' => '/assets/demo-arabic/expression-picture.svg',
            ], true),
            $this->block('describe_picture', 'عبر عن الصورة', [
                'emoji' => '🖍️',
                'question' => 'ماذا ترى في الصورة؟',
                'body' => 'انظر إلى الصورة، ثم اكتب جملة تصفها.',
                'answer' => 'أرى قطة تلعب',
                'score' => 5,
                'hint' => 'استخدم: أرى...',
                'image_url' => '/assets/demo-arabic/cat-reading.svg',
            ], true),
            $this->block('correct_mistake', 'صحح الخطأ', [
                'question' => 'اختر الجملة الصحيحة',
                'options_text' => "القطة تلعب بالكرة\nالقطة يقرأ كتاب\nالكرة سعيدة تقرأ",
                'answer' => 'القطة تلعب بالكرة',
                'score' => 4,
                'hint' => 'ابحث عن الجملة التي معناها واضح.',
            ], true),
            $this->block('build_word', 'كوّن كلمة', [
                'question' => 'رتب الحروف لتكوين كلمة',
                'items_text' => "ق\nط\nة",
                'answer' => 'قطة',
                'score' => 4,
                'hint' => 'الكلمة اسم حيوان أليف.',
            ], true),
            $this->block('match_meaning', 'وصل الكلمة بمعناها', [
                'question' => 'صل كل كلمة بمعناها الصحيح',
                'left_items_text' => "قطة\nكرة\nسعيدة\nنشيطة",
                'right_items_text' => "حيوان أليف\nلعبة مستديرة\nفرحة\nكثيرة الحركة",
                'score' => 5,
                'hint' => 'اختر كلمة من اليمين ثم معناها من اليسار.',
            ], true),
            $this->block('synonym', 'مرادف', [
                'question' => 'صل الكلمة بمرادفها',
                'left_items_text' => "سعيدة\nصغيرة\nنشيطة",
                'right_items_text' => "فرحة\nقليلة الحجم\nكثيرة الحركة",
                'score' => 5,
                'hint' => 'المرادف كلمة قريبة في المعنى.',
            ], true),
            $this->block('opposite', 'ضد', [
                'question' => 'صل الكلمة بضدها',
                'left_items_text' => "صغيرة\nسعيدة\nنشيطة",
                'right_items_text' => "كبيرة\nحزينة\nهادئة",
                'score' => 5,
                'hint' => 'الضد هو عكس المعنى.',
            ], true),
            $this->block('order_words', 'ترتيب كلمات', [
                'question' => 'رتب الكلمات لتصبح جملة مفيدة',
                'items_text' => "هي\nسعيدة\nونشيطة",
                'score' => 4,
                'hint' => 'ابدأ بكلمة: هي.',
            ], true),
            $this->block('audio', 'صوت', [
                'emoji' => '🎧',
                'body' => 'هذا نموذج صوتي داخل الدرس. في لوحة المعلم يمكنك إرفاق صوت أو تسجيل صوت جديد.',
                'items_text' => "استمع\nكرر\nاضغط التالي",
                'audio_url' => $this->demoAudio(),
            ]),
            $this->block('image', 'صورة', [
                'emoji' => '🌈',
                'body' => 'انظر إلى الصورة وتحدث عنها بجملة قصيرة.',
                'items_text' => "ألاحظ الشخصية\nألاحظ اللون\nأقول جملة",
                'image_url' => '/assets/demo-arabic/expression-picture.svg',
                'image_alt' => 'طفل يصف صورة',
            ]),
            $this->block('pdf', 'PDF', [
                'emoji' => '📄',
                'body' => 'مكان ملف PDF المساعد. يستطيع المعلم رفع ورقة عمل أو ملخص هنا.',
                'items_text' => "افتح الملف عند الحاجة\nراجع النقاط\nارجع للدرس",
                'pdf_url' => '/assets/demo-arabic/reading-summary.pdf',
                'file_name' => 'reading-summary.pdf',
            ]),
        ]);
    }

    private function seedMath(): void
    {
        $this->lesson([
            'subject' => 'math',
            'unit_no' => 1,
            'unit_title' => 'الأعداد والجمع',
            'title' => 'درس شامل: الجمع حتى 10',
            'xp_reward' => 40,
            'sort_order' => 1,
        ], [
            $this->block('visual_model', 'نموذج بصري', [
                'emoji' => '🍎',
                'body' => 'تخيّل أن أمامك تفاحتين، ثم أضفنا إليهما ثلاث تفاحات. نعد الكل معًا.',
                'items_text' => "🍎 🍎\n+ 🍎 🍎 🍎\nنعد: 1، 2، 3، 4، 5",
                'hint' => 'استعمل العد بالأصابع أو الرسومات إذا احتجت.',
            ]),
            $this->block('lesson_idea', 'فكرة الدرس', [
                'emoji' => '🧮',
                'body' => 'الجمع يعني أن نضم مجموعتين مع بعض لنعد العدد الكلي.',
                'items_text' => "2 تفاحات + 1 تفاحة = 3 تفاحات\n3 أقلام + 2 أقلام = 5 أقلام",
                'result' => 'الجمع هو ضم الأشياء ثم عدّها.',
            ]),
            $this->block('math_rule', 'قاعدة الجمع', [
                'emoji' => '💡',
                'term' => 'الجمع',
                'symbol' => '+',
                'body' => 'علامة + تعني نضيف أو نجمع. الناتج يأتي بعد علامة =.',
                'items_text' => "العدد الأول\nعلامة الجمع +\nالعدد الثاني\nعلامة يساوي =\nالناتج",
                'result' => 'مثال: 2 + 3 = 5',
            ]),
            $this->block('solved_example', 'مثال محلول', [
                'emoji' => '🍎',
                'body' => 'لدينا 2 تفاحات، أضفنا 3 تفاحات. كم أصبح المجموع؟',
                'items_text' => "نعد أول مجموعة: 2\nنعد ثاني مجموعة: 3\nنجمع: 2 + 3 = 5",
                'answer' => 'المجموع 5 تفاحات.',
                'hint' => 'تحقق من الناتج بإعادة العد من البداية.',
            ]),
            $this->block('common_mistake', 'خطأ شائع', [
                'emoji' => '⚠️',
                'body' => 'أحيانًا ننسى عدّ كل العناصر، فنحصل على ناتج ناقص.',
                'items_text' => "لا تبدأ العد من الصفر\nلا تنسَ المجموعة الثانية\nضع علامة على كل عنصر عدَدته",
                'result' => 'العد الهادئ يقلل الأخطاء.',
            ]),
            $this->block('match_term', 'وصل العملية بالنتيجة', [
                'question' => 'صل كل عملية بالنتيجة الصحيحة',
                'left_items_text' => "2 + 1\n3 + 2\n4 + 0",
                'right_items_text' => "3\n5\n4",
                'score' => 5,
                'hint' => 'عد الأعداد بهدوء.',
            ], true),
            $this->block('order_solution_steps', 'رتب خطوات الحل', [
                'question' => 'رتب خطوات حل 4 + 2',
                'items_text' => "نكتب العملية 4 + 2\nنعد من 4 خطوتين\nنصل إلى 6\nنكتب الناتج 6",
                'score' => 4,
            ], true),
            $this->block('multiple_choice', 'اختيار النتيجة', [
                'question' => 'ما ناتج 5 + 2؟',
                'options_text' => "6\n7\n8",
                'answer' => '7',
                'score' => 3,
                'hint' => 'ابدأ من 5 ثم عد خطوتين.',
            ], true),
        ]);

        $this->lesson([
            'subject' => 'math',
            'unit_no' => 1,
            'unit_title' => 'الأعداد والجمع',
            'title' => 'المقارنة بين الأعداد',
            'xp_reward' => 20,
            'sort_order' => 2,
        ], [
            $this->block('lesson_idea', 'أكبر وأصغر', [
                'emoji' => '🔢',
                'body' => 'نقارن بين عددين لنحدد أيهما أكبر وأيهما أصغر.',
                'items_text' => "العدد الأكبر معه أشياء أكثر\n7 أكبر من 4\n2 أصغر من 6\n5 يساوي 5",
            ]),
            $this->block('math_rule', 'رموز المقارنة', [
                'emoji' => '💡',
                'term' => 'مقارنة',
                'symbol' => '> < =',
                'body' => 'نستخدم > للأكبر، < للأصغر، = للتساوي.',
                'items_text' => "8 > 5\n3 < 6\n4 = 4",
            ]),
            $this->block('solved_example', 'مثال مقارنة', [
                'emoji' => '✅',
                'body' => 'قارن بين 8 و 5.',
                'items_text' => "ننظر إلى العددين\n8 يأتي بعد 5\nإذن 8 أكبر من 5",
                'answer' => '8 أكبر من 5.',
            ]),
            $this->block('multiple_choice', 'اختر العدد الأكبر', [
                'question' => 'أي عدد أكبر؟',
                'options_text' => "3\n9\n5",
                'answer' => '9',
                'score' => 3,
            ], true),
            $this->block('match_term', 'وصل المقارنة', [
                'question' => 'صل العبارة بالرمز المناسب',
                'left_items_text' => "7 أكبر من 2\n4 أصغر من 9\n5 يساوي 5",
                'right_items_text' => ">\n<\n=",
                'score' => 5,
            ], true),
        ]);
    }

    private function seedEnglish(): void
    {
        $this->lesson([
            'subject' => 'english',
            'unit_no' => 1,
            'unit_title' => 'كلماتي الأولى',
            'title' => 'درس شامل: التحية والكلمات الأولى',
            'xp_reward' => 40,
            'sort_order' => 1,
        ], [
            $this->block('warmup_question', 'سؤال تمهيدي', [
                'emoji' => '👋',
                'body' => 'عندما تقابل صديقًا في الصباح، ماذا تقول؟ اليوم سنتعلم كلمات تحية بسيطة بالإنجليزي.',
                'items_text' => "Hello\nGood morning\nGoodbye",
                'hint' => 'استمع للكلمة ثم كررها بصوت واضح.',
            ]),
            $this->block('vocabulary_word', 'كلمة جديدة', [
                'emoji' => '🔊',
                'term' => 'Hello',
                'symbol' => 'مرحبا',
                'body' => 'Hello تعني مرحبا. نستخدمها عندما نبدأ الكلام مع شخص.',
                'items_text' => "Hello, teacher\nHello, friend\nHello, my name is Omar",
                'hint' => 'قل Hello بابتسامة قصيرة.',
            ]),
            $this->block('usage_idea', 'فكرة الاستعمال', [
                'emoji' => '🧠',
                'body' => 'الكلمة تصبح أسهل عندما نضعها في موقف حقيقي، مثل الدخول إلى الصف أو مقابلة صديق.',
                'items_text' => "أسمع الكلمة\nأكررها\nأستخدمها في جملة",
                'result' => 'الكلمة تعيش داخل جملة.',
            ]),
            $this->block('short_conversation', 'محادثة قصيرة', [
                'emoji' => '💬',
                'body' => "A: Hello!\nB: Hello!\nA: How are you?\nB: I am fine.",
                'items_text' => "Hello\nHow are you?\nI am fine\nGoodbye",
                'result' => 'نبدأ بـ Hello وننهي بـ Goodbye.',
            ]),
            $this->block('pronunciation_tip', 'ملاحظة نطق', [
                'emoji' => '⭐',
                'body' => 'كلمة Hello تبدأ بصوت h خفيف، ثم نقول: هِ-لو.',
                'items_text' => "استمع\nكرر\nاستخدمها في جملة",
                'result' => 'النطق يتحسن بالتكرار القصير.',
            ]),
            $this->block('solved_example', 'مثال استعمال', [
                'emoji' => '⭐',
                'body' => 'Use Hello when you meet someone. نستخدم Hello عند مقابلة شخص.',
                'items_text' => "Look at the person\nSay Hello\nSmile",
                'answer' => 'Hello, teacher!',
            ]),
            $this->block('match_meaning', 'وصل الكلمة بالمعنى', [
                'question' => 'صل كل كلمة إنجليزية بمعناها العربي',
                'left_items_text' => "Hello\nGoodbye\nThank you",
                'right_items_text' => "مرحبا\nمع السلامة\nشكرا",
                'score' => 5,
            ], true),
            $this->block('order_sentence_en', 'رتب الجملة', [
                'question' => 'رتب الكلمات لتكوين جملة صحيحة',
                'items_text' => "I\nam\nfine",
                'score' => 4,
            ], true),
            $this->block('multiple_choice', 'اختر المعنى', [
                'question' => 'ما معنى Hello؟',
                'options_text' => "مرحبا\nكتاب\nباب",
                'answer' => 'مرحبا',
                'score' => 3,
            ], true),
        ]);

        $this->lesson([
            'subject' => 'english',
            'unit_no' => 1,
            'unit_title' => 'كلماتي الأولى',
            'title' => 'الألوان',
            'xp_reward' => 20,
            'sort_order' => 2,
        ], [
            $this->block('vocabulary_word', 'كلمات الألوان', [
                'emoji' => '🎨',
                'term' => 'Colors',
                'symbol' => 'ألوان',
                'body' => 'نتعلم ثلاثة ألوان: red, blue, green.',
                'items_text' => "Red = أحمر\nBlue = أزرق\nGreen = أخضر",
            ]),
            $this->block('solved_example', 'مثال لون', [
                'emoji' => '🖍️',
                'body' => 'The apple is red. التفاحة لونها أحمر.',
                'items_text' => "Look at the apple\nChoose the color\nSay red",
                'answer' => 'Red means أحمر.',
            ]),
            $this->block('match_picture', 'وصل الألوان', [
                'question' => 'صل اللون بمعناه العربي',
                'left_items_text' => "Red\nBlue\nGreen",
                'right_items_text' => "أحمر\nأزرق\nأخضر",
                'score' => 5,
            ], true),
            $this->block('multiple_choice', 'اختر اللون', [
                'question' => 'ما لون السماء؟',
                'options_text' => "Blue\nRed\nGreen",
                'answer' => 'Blue',
                'score' => 3,
            ], true),
        ]);
    }

    private function lesson(array $lessonData, array $blocks): void
    {
        $lesson = Lesson::query()->create(array_merge([
            'grade' => 1,
            'status' => 'published',
            'xp_reward' => 20,
        ], $lessonData));

        foreach ($blocks as $index => $block) {
            LessonBlock::query()->create(array_merge($block, [
                'lesson_id' => $lesson->id,
                'subject' => $lesson->subject,
                'sort_order' => $index,
            ]));
        }
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

        foreach (array_keys($media) as $mediaKey) {
            unset($content[$mediaKey]);
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
                'source' => 'grade-one-demo',
            ],
            'is_graded' => $graded,
        ];
    }

    private function demoAudio(): string
    {
        return '/assets/demo-arabic/demo-tone.wav';
    }

    private function category(string $type): string
    {
        if (str_contains($type, 'match') || str_contains($type, 'order') || str_contains($type, 'choice') || str_contains($type, 'true_false') || str_contains($type, 'blank') || str_contains($type, 'complete') || str_contains($type, 'correct') || str_contains($type, 'write') || str_contains($type, 'describe') || str_contains($type, 'build_word')) {
            return 'interactive';
        }

        return 'explanation';
    }

    private function subtype(string $type): ?string
    {
        if (str_contains($type, 'match')) {
            return 'matching';
        }

        if (str_contains($type, 'order') || str_contains($type, 'build_word')) {
            return 'ordering';
        }

        if (str_contains($type, 'choice') || str_contains($type, 'multiple') || str_contains($type, 'complete') || str_contains($type, 'correct')) {
            return 'choice';
        }

        if (str_contains($type, 'write') || str_contains($type, 'describe')) {
            return 'writing';
        }

        if (str_contains($type, 'example')) {
            return 'steps';
        }

        if (str_contains($type, 'vocabulary') || str_contains($type, 'word')) {
            return 'vocabulary';
        }

        return 'explanation';
    }
}
