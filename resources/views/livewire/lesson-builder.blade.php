@php
    $subjectInfo = $subjects[$subject] ?? ['label' => $subject, 'color' => '#215b9f'];
    $previewBlock = $blocks[0] ?? null;
    $previewType = $previewBlock['type'] ?? ($presets[0]['type'] ?? 'reading_text');
    $previewMeta = $previewBlock ? $this->typeMeta($previewType) : $this->typeMeta($previewType);
    $previewForm = $previewBlock ? $this->formTemplate($previewType) : $this->formTemplate($previewType);
    $previewContent = $previewBlock['content'] ?? [];
    $previewMedia = $previewBlock['media'] ?? [];
    $splitLines = fn ($value) => collect(preg_split('/\r\n|\r|\n|,/', (string) $value) ?: [])->map(fn ($line) => trim($line))->filter()->values();
    $previewLeft = $splitLines($previewContent['left_items_text'] ?? '');
    $previewRight = $splitLines($previewContent['right_items_text'] ?? '');
    $previewOptions = $splitLines($previewContent['options_text'] ?? '');
    $previewItems = $splitLines($previewContent['items_text'] ?? '');
    $fallbackLeft = collect(['تفاحة', 'موزة', 'عنب', 'برتقالة']);
    $fallbackRight = collect(['عنب', 'برتقالة', 'تفاحة', 'موزة']);
    $previewProgress = min(92, max(18, count($blocks) * 16));
@endphp

<main class="lesson-builder-shell teacher-studio-shell" data-subject="{{ $subject }}">
    <header class="builder-commandbar">
        <div class="builder-command-actions">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="builder-outline-action">تسجيل خروج</button>
            </form>
            <a class="builder-student-link" href="{{ route('student.platform') }}" target="_blank" rel="noreferrer">
                معاينة الطالب
            </a>
        </div>

        <div class="builder-command-center">
            <span class="builder-saved-pill">✓ درس محفوظ</span>
            <button type="button" class="builder-new-lesson" wire:click="newLesson">+ درس جديد</button>
        </div>

        <a class="builder-studio-brand" href="{{ route('role.portal') }}" aria-label="مدرسة بينك أند بلو">
            <span>
                <b>Dynamic Lesson Builder</b>
                <small>لوحة بناء الدروس للمعلم</small>
            </span>
            <img src="{{ asset('brand-logo.png') }}" alt="">
        </a>
    </header>

    @if ($notice)
        <div class="builder-toast studio-toast" role="status">{{ $notice }}</div>
    @endif

    <form wire:submit.prevent="save" class="builder-studio-form">
        <section class="builder-studio-grid">
            <aside class="student-live-preview" aria-label="معاينة الطالب">
                <div class="preview-score-row">
                    <div class="heart-badge">3</div>
                    <div class="preview-mini-progress" aria-hidden="true">
                        <span style="width: {{ $previewProgress }}%"></span>
                    </div>
                    <div class="preview-xp-chip">{{ $xpReward }} XP</div>
                </div>

                <div class="lesson-phone-surface">
                    <header class="studio-phone-head">
                        <button type="button" aria-label="رجوع">‹</button>
                        <div>
                            <small>{{ $unitTitle ?: 'الوحدة '.$unitNo }}</small>
                            <strong>{{ $lessonTitle ?: 'عنوان الدرس' }}</strong>
                        </div>
                        <span style="--preview-color: {{ $previewMeta['color'] }}">●</span>
                    </header>

                    <div class="phone-progress studio-phone-progress">
                        <span style="width: {{ $previewProgress }}%"></span>
                    </div>

                    <section class="studio-activity-card" style="--preview-color: {{ $previewMeta['color'] }}">
                        <div class="studio-activity-kicker">{{ $previewMeta['group'] }}</div>
                        <div class="preview-mascot-line">
                            <div class="teacher-preview-mascot">PB</div>
                            <div class="preview-bubble">
                                <small>{{ $previewMeta['label'] }}</small>
                                <h3>{{ $previewContent['question'] ?? '' ?: ($previewBlock['title'] ?? '' ?: 'جهّز هذا القسم للطالب') }}</h3>
                                <p>{{ $previewContent['hint'] ?? '' ?: 'المعاينة تتغير مباشرة حسب البيانات التي يدخلها المعلم.' }}</p>
                            </div>
                        </div>

                        @if ($previewForm === 'matching')
                            @php
                                $leftPreview = $previewLeft->isNotEmpty() ? $previewLeft : $fallbackLeft;
                                $rightPreview = $previewRight->isNotEmpty() ? $previewRight : $fallbackRight;
                            @endphp
                            <div class="studio-matching-stage">
                                <div class="studio-match-column word-side">
                                    <span>الكلمة</span>
                                    @foreach ($leftPreview->take(4) as $item)
                                        <button type="button">
                                            <b>{{ $item }}</b>
                                            <i>صوت</i>
                                        </button>
                                    @endforeach
                                </div>
                                <div class="studio-match-lines" aria-hidden="true">
                                    <i></i><i></i><i></i>
                                </div>
                                <div class="studio-match-column image-side">
                                    <span>المعنى / الصورة</span>
                                    @foreach ($rightPreview->take(4) as $item)
                                        <button type="button">
                                            <b>{{ $item }}</b>
                                            <i>اختيار</i>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @elseif ($previewForm === 'ordering')
                            @php
                                $itemsPreview = $previewItems->isNotEmpty()
                                    ? $previewItems
                                    : collect(['اقرأ المطلوب', 'اسحب البطاقات', 'رتب الإجابة', 'تحقق من الحل']);
                            @endphp
                            <div class="studio-order-preview">
                                @foreach ($itemsPreview->take(5) as $itemIndex => $item)
                                    <button type="button">
                                        <span>{{ $itemIndex + 1 }}</span>
                                        <b>{{ $item }}</b>
                                        <i>☰</i>
                                    </button>
                                @endforeach
                            </div>
                        @elseif ($previewForm === 'choice')
                            @php
                                $optionsPreview = $previewOptions->isNotEmpty()
                                    ? $previewOptions
                                    : collect(['الخيار الأول', 'الخيار الثاني', 'الخيار الثالث']);
                            @endphp
                            <div class="studio-choice-preview">
                                @foreach ($optionsPreview->take(4) as $option)
                                    <button type="button">{{ $option }}</button>
                                @endforeach
                            </div>
                        @elseif ($previewForm === 'media')
                            <div class="studio-media-preview">
                                @if (! empty($previewMedia['image_url']))
                                    <img src="{{ $previewMedia['image_url'] }}" alt="">
                                @elseif (! empty($previewMedia['video_url']))
                                    <span>فيديو</span>
                                    <strong>{{ $previewBlock['title'] ?? 'وسيط تعليمي' }}</strong>
                                @elseif (! empty($previewMedia['pdf_url']))
                                    <span>PDF</span>
                                    <strong>ملف داعم للشرح</strong>
                                @else
                                    <span>وسيط</span>
                                    <strong>ارفع صورة أو صوت أو PDF ليظهر هنا</strong>
                                @endif
                            </div>
                        @else
                            <div class="studio-reading-preview">
                                @if (! empty($previewMedia['image_url']))
                                    <img src="{{ $previewMedia['image_url'] }}" alt="">
                                @endif
                                <strong>{{ $previewContent['term'] ?? '' ?: ($previewBlock['title'] ?? 'شرح') }}</strong>
                                <p>{!! $previewContent['body'] ?? 'اكتب شرحًا قصيرًا واضحًا، ثم أضف نقاطًا داعمة أو صورة عند الحاجة.' !!}</p>
                                @if ($previewItems->isNotEmpty())
                                    <ul>
                                        @foreach ($previewItems->take(4) as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif

                        <div class="preview-student-actions">
                            <button type="button">تخطي</button>
                            <button type="button" class="primary">تحقق</button>
                        </div>
                    </section>
                </div>
            </aside>

            <section class="builder-control-zone">
                <div class="lesson-picker-strip">
                    <select wire:model="lessonPicker" wire:change="loadSelectedLesson" aria-label="اختر درسًا للتعديل">
                        <option value="">اختر درسًا للتعديل...</option>
                        @foreach ($savedLessons as $savedLesson)
                            <option value="{{ $savedLesson->id }}">
                                {{ $grades[$savedLesson->grade] ?? 'صف' }} - {{ $subjects[$savedLesson->subject]['label'] ?? $savedLesson->subject }} - وحدة {{ $savedLesson->unit_no }} - {{ $savedLesson->title }}
                            </option>
                        @endforeach
                    </select>
                    <span>✎</span>
                </div>

                <div class="builder-settings-row">
                    <section class="builder-card lesson-settings-card">
                        <div class="builder-card-head">
                            <span>إعدادات الدرس</span>
                        </div>
                        <div class="builder-form-grid compact">
                            <label>
                                الصف
                                <select wire:model.live="grade">
                                    @foreach ($grades as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                المادة
                                <select wire:model.live="subject">
                                    @foreach ($subjects as $value => $item)
                                        <option value="{{ $value }}">{{ $item['label'] }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                الوحدة
                                <select wire:model.live="unitNo">
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit }}">الوحدة {{ $unit }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                حالة النشر
                                <select wire:model.live="status">
                                    <option value="published">منشور للطالب</option>
                                    <option value="draft">مسودة للمعلم فقط</option>
                                </select>
                            </label>
                            <label class="wide">
                                عنوان الوحدة
                                <input type="text" wire:model.live="unitTitle" placeholder="مثال: الأعداد والعمليات">
                            </label>
                            <label class="wide">
                                عنوان الدرس
                                <input type="text" wire:model.live="lessonTitle" placeholder="مثال: جمع عددين">
                                @error('lessonTitle') <small class="builder-error">{{ $message }}</small> @enderror
                            </label>
                            <label>
                                XP عند الإنهاء
                                <input type="number" min="0" max="500" wire:model.live="xpReward">
                            </label>
                        </div>
                        <div class="stage-note">
                            <strong>{{ $stage['label'] }}</strong>
                            <span>{{ $stage['text'] }}</span>
                        </div>
                    </section>

                    <section class="builder-card subject-guide studio-guide-card">
                        <div class="builder-card-head">
                            <span>طريقة تربوية للمادة</span>
                        </div>
                        <h2>{{ $guide['title'] }}</h2>
                        <p>{{ $guide['text'] }}</p>
                        <div class="guide-chip-row">
                            @foreach ($guide['chips'] as $chip)
                                <span>{{ $chip }}</span>
                            @endforeach
                        </div>
                    </section>
                </div>

                <section class="builder-editor-card">
                    <div class="builder-section-title">
                        <div>
                            <span>أقسام الدرس</span>
                            <h2>رتّب رحلة الطالب خطوة بخطوة</h2>
                        </div>
                        <button type="button" wire:click="addBlock">+ إضافة قسم</button>
                    </div>

                    <div class="builder-block-list"
                        x-data
                        x-init="window.attachLessonSortable && window.attachLessonSortable($el, $wire)"
                    >
                        @foreach ($blocks as $index => $block)
                            @php
                                $meta = $this->typeMeta($block['type'] ?? '');
                                $blockUid = $block['uid'] ?? 'block-'.$index;
                                $form = $this->formTemplate($block['type'] ?? '');
                            @endphp
                            <article class="builder-block-card studio-block-card" data-uid="{{ $blockUid }}" wire:key="lesson-block-{{ $blockUid }}" style="--block-color: {{ $meta['color'] }}">
                                <header class="block-edit-head studio-block-head">
                                    <button type="button" class="drag-handle" aria-label="رتب القسم">☰</button>
                                    <div class="block-type-badge">
                                        <i>{{ $meta['icon'] ?: '•' }}</i>
                                        <span>{{ $meta['label'] }}</span>
                                    </div>
                                    <div class="block-structure-badge">
                                        <span>{{ $meta['group'] }}</span>
                                        <small>{{ $meta['layout'] }}</small>
                                        @if ($meta['is_graded'])
                                            <b>مقيّم</b>
                                        @endif
                                    </div>
                                    <select wire:model.live="blocks.{{ $index }}.type" wire:change="normalizeBlockType({{ $index }})">
                                        @foreach ($presets as $preset)
                                            <option value="{{ $preset['type'] }}">{{ $preset['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" wire:click="duplicateBlock({{ $index }})">نسخ</button>
                                    <button type="button" class="danger" wire:click="removeBlock({{ $index }})">حذف</button>
                                </header>

                                @includeFirst([
                                    "livewire.lesson-blocks.forms.{$form}",
                                    'livewire.lesson-blocks.forms.explanation',
                                ], [
                                    'block' => $block,
                                    'meta' => $meta,
                                    'index' => $index,
                                ])
                            </article>
                        @endforeach
                    </div>
                </section>

                <footer class="builder-savebar studio-savebar">
                    <div>
                        <strong>الحفظ مرتبط بالصف والمادة المحددين.</strong>
                        <span>مثلاً: علوم الصف الأول تظهر فقط لطلاب الصف الأول داخل مادة العلوم.</span>
                    </div>
                    <div class="studio-save-actions">
                        <button type="button" class="draft-btn" wire:click="saveDraft" wire:loading.attr="disabled">حفظ كمسودة</button>
                        <button type="submit" class="save-lesson-btn" wire:loading.attr="disabled">
                            <span wire:loading.remove>حفظ ونشر الدرس</span>
                            <span wire:loading>جار الحفظ...</span>
                        </button>
                    </div>
                </footer>
            </section>
        </section>

        <section class="builder-palette-dock" aria-label="إضافة قسم جديد">
            @foreach ($presets as $preset)
                @php
                    $paletteForm = $this->formTemplate($preset['type']);
                    $paletteIcons = [
                        'matching' => 'رابط',
                        'ordering' => 'ترتيب',
                        'choice' => 'اختيار',
                        'media' => 'وسيط',
                        'vocabulary' => 'كلمة',
                        'steps' => 'خطوات',
                    ];
                    $paletteIcon = $preset['icon'] ?: ($paletteIcons[$paletteForm] ?? 'شرح');
                @endphp
                <button type="button" wire:click="addBlock('{{ $preset['type'] }}')" style="--preset-color: {{ $preset['color'] }}">
                    <i>{{ $paletteIcon }}</i>
                    <span>{{ $preset['label'] }}</span>
                    <small>{{ $preset['group'] }}</small>
                </button>
            @endforeach
        </section>
    </form>
</main>
