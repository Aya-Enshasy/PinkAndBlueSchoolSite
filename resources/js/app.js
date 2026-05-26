import './bootstrap';

const app = document.querySelector('#app');
const STORAGE_KEY = 'pink-blue-school-state-v7';

const subjects = [
    { id: 'arabic', name: 'عربي', color: '#d22d78', bg: '#fff0f7', border: '#f8c6dc', icon: 'book' },
    { id: 'english', name: 'إنجليزي', color: '#215b9f', bg: '#edf6ff', border: '#bfddff', icon: 'abc' },
    { id: 'math', name: 'حساب', color: '#8b5cf6', bg: '#f4f0ff', border: '#ddd0ff', icon: 'ruler' },
    { id: 'science', name: 'علوم', color: '#0ea5e9', bg: '#eefaff', border: '#bae6fd', icon: 'micro' },
];

const grades = [
    'الصف الأول',
    'الصف الثاني',
    'الصف الثالث',
    'الصف الرابع',
    'الصف الخامس',
    'الصف السادس',
    'الصف السابع',
    'الصف الثامن',
    'الصف التاسع',
];

const gradeShortNames = ['الأول', 'الثاني', 'الثالث', 'الرابع', 'الخامس', 'السادس', 'السابع', 'الثامن', 'التاسع'];

const learningUnits = [];

const state = loadState();
recordVisit();

function loadState() {
    try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));
        const savedSubject = subjects.some((subject) => subject.id === saved?.subject) ? saved.subject : 'english';
        return {
            view: saved?.view || 'home',
            subject: savedSubject,
            grade: Number(saved?.grade || 1),
            xp: Number(saved?.xp || 0),
            streak: Number(saved?.streak || 1),
            dailyGoal: Math.min(4, Number(saved?.dailyGoal || 0)),
            dailyCompletions: saved?.dailyCompletions && typeof saved.dailyCompletions === 'object' ? saved.dailyCompletions : {},
            lessonProgress: saved?.lessonProgress && typeof saved.lessonProgress === 'object' ? saved.lessonProgress : {},
            customUnits: Array.isArray(saved?.customUnits) ? saved.customUnits : [],
            teacherDraft: saved?.teacherDraft && typeof saved.teacherDraft === 'object' ? saved.teacherDraft : defaultTeacherDraft(),
            lastVisit: saved?.lastVisit || '',
            lastGoalDate: saved?.lastGoalDate || todayKey(),
            completed: Array.isArray(saved?.completed) ? saved.completed : [0, 1, 2, 3, 4],
            activeUnitId: saved?.activeUnitId || '',
            activeLessonId: saved?.activeLessonId || '',
            lessonMode: saved?.lessonMode || 'path',
            lessonSection: saved?.lessonSection || 'theory',
            selectedAnswer: '',
            lastResult: '',
            currentQuestion: 0,
            currentTheoryBlock: Number(saved?.currentTheoryBlock || 0),
            imageSearch: { blockIndex: null, query: '', type: 'vector', results: [], message: '' },
            teacherPanel: false,
        };
    } catch {
        return {
            view: 'home',
            subject: 'english',
            grade: 1,
            xp: 0,
            streak: 1,
            dailyGoal: 0,
            dailyCompletions: {},
            lessonProgress: {},
            customUnits: [],
            teacherDraft: defaultTeacherDraft(),
            lastVisit: '',
            lastGoalDate: todayKey(),
            completed: [0, 1, 2, 3, 4],
            activeUnitId: '',
            activeLessonId: '',
            lessonMode: 'path',
            lessonSection: 'theory',
            selectedAnswer: '',
            lastResult: '',
            currentQuestion: 0,
            currentTheoryBlock: 0,
            imageSearch: { blockIndex: null, query: '', type: 'vector', results: [], message: '' },
            teacherPanel: false,
        };
    }
}

function todayKey() {
    return new Date().toISOString().slice(0, 10);
}

function daysBetween(oldDate, newDate) {
    const first = new Date(`${oldDate}T00:00:00`);
    const second = new Date(`${newDate}T00:00:00`);
    return Math.round((second - first) / 86400000);
}

function recordVisit() {
    const today = todayKey();

    if (state.lastGoalDate !== today) {
        state.dailyGoal = 0;
        state.lastGoalDate = today;
    }

    if (!state.lastVisit) {
        state.lastVisit = today;
        saveState();
        return;
    }

    const gap = daysBetween(state.lastVisit, today);
    if (gap === 1) {
        state.streak += 1;
    } else if (gap > 1) {
        state.streak = 1;
    }

    if (gap > 0) {
        state.lastVisit = today;
        saveState();
    }
}

function dailyCompletionKey(grade = state.grade, subject = state.subject) {
    return `${todayKey()}:grade-${grade}:${subject}`;
}

function dailyGoalCount(grade = state.grade) {
    return subjects.filter((subject) => state.dailyCompletions?.[dailyCompletionKey(grade, subject.id)]).length;
}

function syncDailyGoal() {
    state.dailyGoal = dailyGoalCount();
}

function saveState() {
    const { selectedAnswer, lastResult, imageSearch, ...persisted } = state;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(persisted));
}

function currentSubject() {
    return subjects.find((subject) => subject.id === state.subject) || subjects[1];
}

function defaultTeacherDraft() {
    return {
        grade: 1,
        subject: 'arabic',
        unitNo: 1,
        unitTitle: '',
        lessonTitle: '',
        theoryTitle: '',
        theoryBody: '',
        theoryBlocks: [
            {
                type: 'hook',
                emoji: '✨',
                title: '',
                body: '',
                term: '',
                symbol: '',
                items: '',
                result: '',
                note: '',
                url: '',
            },
        ],
        examples: [
            {
                title: '',
                body: '',
                answer: '',
            },
        ],
        worksheet: [
            {
                question: '',
                options: '',
                answer: '',
            },
        ],
        quiz: [
            {
                question: '',
                options: '',
                answer: '',
                score: 1,
            },
        ],
    };
}

function allLearningUnits() {
    const merged = new Map();
    [...learningUnits, ...(state.customUnits || [])].forEach((unit) => {
        const key = `${unit.grade}:${unit.subject}:${unit.unitNo}`;
        merged.set(key, unit);
    });
    return [...merged.values()].sort((a, b) => Number(a.unitNo) - Number(b.unitNo));
}

function unitsForSelection() {
    const units = allLearningUnits();
    return units.filter((unit) => unit.subject === state.subject && Number(unit.grade) === Number(state.grade));
}

function currentUnit() {
    const units = unitsForSelection();
    return units.find((unit) => unit.id === state.activeUnitId) || units[0] || null;
}

function currentLesson() {
    const unit = currentUnit();
    if (!unit) return null;
    return unit.lessons.find((lesson) => lesson.id === state.activeLessonId) || unit.lessons[0] || null;
}

function lessonDone(lessonId) {
    return Boolean(state.lessonProgress[lessonId]?.done);
}

function sectionDone(lessonId, section) {
    return Boolean(state.lessonProgress[lessonId]?.sections?.[section]);
}

function lessonUnlocked(unit, index) {
    if (index === 0) return true;
    return unit.lessons.slice(0, index).every((lesson) => lessonDone(lesson.id));
}

function markSectionDone(section = state.lessonSection) {
    const lesson = currentLesson();
    if (!state.lessonProgress[lesson.id]) {
        state.lessonProgress[lesson.id] = { sections: {}, done: false };
    }

    state.lessonProgress[lesson.id].sections[section] = true;
}

function lessonProgressPercent(lesson = currentLesson()) {
    if (!lesson) return 0;
    const sections = ['theory', 'examples', 'worksheet', 'quiz'];
    const done = sections.filter((section) => sectionDone(lesson.id, section)).length;
    return Math.round((done / sections.length) * 100);
}

function unitProgressPercent(unit = currentUnit()) {
    if (!unit) return 0;
    const done = unit.lessons.filter((lesson) => lessonDone(lesson.id)).length;
    return Math.round((done / unit.lessons.length) * 100);
}

function nextLesson(unit, lessonId) {
    const index = unit.lessons.findIndex((lesson) => lesson.id === lessonId);
    return unit.lessons[index + 1] || null;
}

function speakText(text) {
    if (!('speechSynthesis' in window)) return;
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = /[A-Za-z]/.test(text) ? 'en-US' : 'ar';
    utterance.rate = 0.9;
    window.speechSynthesis.speak(utterance);
}

function escapeHtml(value = '') {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function optionList(value) {
    if (Array.isArray(value)) return value.filter(Boolean);
    return String(value || '')
        .split(/[,،\n]+/)
        .map((item) => item.trim())
        .filter(Boolean);
}

function defaultTheoryBlock(type = 'hook') {
    const base = {
        type,
        emoji: '✨',
        title: '',
        body: '',
        term: '',
        symbol: '',
        items: '',
        result: '',
        note: '',
        url: '',
        imageUrl: '',
        fileName: '',
    };

    const presets = {
        hook: { emoji: '🎯', title: 'فكرة تشويقية' },
        definition: { emoji: '💡', title: 'تعريف', term: '', symbol: '' },
        idea: { emoji: '🧠', title: 'الفكرة المهمة' },
        example: { emoji: '🎲', title: 'مثال سريع' },
        tip: { emoji: '⭐', title: 'تذكّر' },
        youtube: { emoji: '▶', title: 'فيديو مساعد' },
        pdf: { emoji: '📄', title: 'ملف PDF مساعد' },
    };

    return { ...base, ...(presets[type] || presets.hook) };
}

function normalizeTheoryBlocks(draft) {
    const blocks = Array.isArray(draft.theoryBlocks) ? draft.theoryBlocks : [];
    const normalized = blocks
        .map((block) => ({
            ...defaultTheoryBlock(block.type || 'hook'),
            ...block,
            type: block.type || 'hook',
        }))
        .filter((block) => block.title || block.body || block.term || block.url || block.items || block.result);

    if (normalized.length) return normalized;

    if (draft.theoryTitle || draft.theoryBody) {
        return [{
            ...defaultTheoryBlock('hook'),
            title: draft.theoryTitle || 'شرح الدرس',
            body: draft.theoryBody || '',
        }];
    }

    return [{
        ...defaultTheoryBlock('hook'),
        title: 'شرح الدرس',
        body: 'أدخل أقسام الشرح من لوحة المعلم.',
    }];
}

function theoryBlockText(block) {
    return [block.title, block.term, block.body, block.result, block.note].filter(Boolean).join('. ');
}

function youtubeEmbedUrl(url = '') {
    const value = String(url).trim();
    if (!value) return '';
    const match = value.match(/(?:youtube\.com\/watch\?.*v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/shorts\/)([A-Za-z0-9_-]{6,})/);
    return match ? `https://www.youtube.com/embed/${match[1]}` : value;
}

function isPdfSource(value = '') {
    const source = String(value || '');
    return source.startsWith('data:application/pdf') || /\.pdf($|\?)/i.test(source);
}

function subjectTheoryGuide(subjectId) {
    const guides = {
        english: {
            title: 'طريقة مناسبة للإنجليزي',
            text: 'ابدأ بسؤال قصير، ثم كلمة/صورة، ثم محادثة بخيارات تحت بعض. لا تشرح كثير؛ خلي الطالب يختار ويسمع ويكرر.',
            chips: ['Question', 'Picture word', 'Conversation', 'Listen'],
        },
        arabic: {
            title: 'طريقة مناسبة للعربي',
            text: 'ابدأ بتمهيد قراءة، ثم معنى/تعريف، ثم جملة مثال، ثم تلميح قصير. ركز على الفهم والمعنى والسياق.',
            chips: ['قراءة', 'معنى', 'جملة', 'تذكّر'],
        },
        math: {
            title: 'طريقة مناسبة للحساب',
            text: 'ابدأ بنموذج بصري، ثم القاعدة، ثم مثال محلول مرقم. الطالب يحتاج يرى: ماذا نعرف؟ ماذا نطبق؟ ما الناتج؟',
            chips: ['نموذج', 'قاعدة', 'خطوات', 'ناتج'],
        },
        science: {
            title: 'طريقة مناسبة للعلوم',
            text: 'ابدأ بملاحظة ظاهرة، ثم سؤال لماذا، ثم تفسير أو تجربة، ثم استنتاج. العلم لازم يكون صورة/فيديو/ملاحظة.',
            chips: ['لاحظ', 'لماذا؟', 'تجربة', 'استنتاج'],
        },
    };

    return guides[subjectId] || guides.arabic;
}

function theoryTypeOptions(subjectId) {
    const bySubject = {
        english: [
            ['hook', 'سؤال تمهيدي'],
            ['definition', 'كلمة ومعناها'],
            ['example', 'محادثة / اختيارات'],
            ['idea', 'قاعدة استعمال'],
            ['tip', 'ملاحظة نطق'],
            ['youtube', 'استماع / فيديو'],
            ['pdf', 'ملف مساعد'],
        ],
        arabic: [
            ['hook', 'تمهيد قراءة'],
            ['definition', 'تعريف / مصطلح'],
            ['idea', 'فكرة لغوية'],
            ['example', 'مثال من جملة'],
            ['tip', 'تذكّر'],
            ['youtube', 'فيديو شرح'],
            ['pdf', 'ورقة قراءة PDF'],
        ],
        math: [
            ['hook', 'نموذج بصري'],
            ['definition', 'قاعدة / رمز'],
            ['example', 'مثال محلول خطوة بخطوة'],
            ['idea', 'فكرة الحل'],
            ['tip', 'خطأ شائع'],
            ['youtube', 'فيديو توضيحي'],
            ['pdf', 'ورقة قوانين PDF'],
        ],
        science: [
            ['hook', 'ملاحظة ظاهرة'],
            ['definition', 'مفهوم علمي'],
            ['idea', 'تفسير السبب'],
            ['example', 'تجربة / تطبيق'],
            ['tip', 'استنتاج'],
            ['youtube', 'فيديو تجربة'],
            ['pdf', 'ملف مختبر PDF'],
        ],
    };

    return bySubject[subjectId] || bySubject.arabic;
}

function theoryFieldLabels(subjectId, type) {
    const defaults = {
        emoji: 'الأيقونة',
        title: 'عنوان البطاقة',
        body: 'النص الذي تقوله الشخصية',
        imageUrl: 'صورة أو رسم مساعد',
        term: 'المصطلح',
        symbol: 'الرمز',
        items: 'عناصر تظهر تحت بعض، كل عنصر بسطر أو بفاصلة',
        result: 'النتيجة / الخلاصة',
        note: 'ملاحظة قصيرة',
        url: 'الرابط',
    };

    const subjectLabels = {
        english: {
            hook: { title: 'Question shown to student', body: 'Short prompt', items: 'Answer choices, each on a new line', imageUrl: 'Picture/icon for the prompt' },
            definition: { term: 'English word', symbol: 'Arabic meaning or tag', body: 'Example sentence', imageUrl: 'Picture for the word' },
            example: { title: 'Conversation title', body: 'Question or instruction', items: 'Conversation choices, each on a new line', result: 'Correct phrase / model answer', note: 'Pronunciation tip' },
            idea: { title: 'Grammar/use title', body: 'Simple rule', items: 'Usage examples, each on a new line' },
            tip: { title: 'Pronunciation note', body: 'What should the student notice?', items: 'Practice phrases' },
            youtube: { title: 'Listening title', body: 'What should the student listen for?', url: 'YouTube listening link' },
            pdf: { title: 'Worksheet title', body: 'What should the student review?', url: 'PDF link or uploaded file' },
        },
        arabic: {
            hook: { title: 'تمهيد القراءة', body: 'سؤال أو مدخل قصير', items: 'نقاط قراءة، كل نقطة بسطر' },
            definition: { term: 'الكلمة أو المصطلح', symbol: 'النوع/الرمز إن وجد', body: 'المعنى أو التعريف', items: 'جمل قصيرة للتوضيح' },
            example: { title: 'مثال من جملة', body: 'الجملة أو السؤال', items: 'ملاحظات على الجملة، كل ملاحظة بسطر', result: 'الخلاصة' },
            idea: { title: 'الفكرة اللغوية', body: 'اشرح الفكرة بجملة قصيرة', items: 'أمثلة أو حالات' },
            tip: { title: 'تذكّر', body: 'قاعدة تذكّر قصيرة', items: 'كلمات مفتاحية' },
            youtube: { title: 'عنوان الفيديو', body: 'ما الذي يركز عليه الطالب؟', url: 'رابط YouTube' },
            pdf: { title: 'ورقة قراءة', body: 'تعليمات الملف', url: 'رابط PDF أو الملف المرفق' },
        },
        math: {
            hook: { title: 'النموذج البصري', body: 'ماذا يرى الطالب؟', items: 'المعطيات أو الأجزاء، كل جزء بسطر', imageUrl: 'رسم/صورة للمسألة' },
            definition: { term: 'اسم القاعدة', symbol: 'الرمز أو القانون', body: 'شرح القاعدة', items: 'متى نستخدمها؟' },
            example: { title: 'عنوان المثال المحلول', body: 'نص المسألة', items: 'خطوات الحل، كل خطوة بسطر', result: 'الناتج النهائي', note: 'تنبيه أو تحقق' },
            idea: { title: 'فكرة الحل', body: 'كيف نبدأ؟', items: 'استراتيجية الحل' },
            tip: { title: 'خطأ شائع', body: 'ما الخطأ؟', items: 'كيف نتجنبه؟' },
            youtube: { title: 'فيديو توضيحي', body: 'ما الفكرة في الفيديو؟', url: 'رابط YouTube' },
            pdf: { title: 'ورقة قوانين', body: 'تعليمات للطالب', url: 'رابط PDF أو الملف المرفق' },
        },
        science: {
            hook: { title: 'ملاحظة الظاهرة', body: 'ماذا يلاحظ الطالب؟', items: 'أسئلة ملاحظة، كل سؤال بسطر', imageUrl: 'صورة الظاهرة أو التجربة' },
            definition: { term: 'المفهوم العلمي', symbol: 'رمز/مصطلح إن وجد', body: 'التعريف العلمي', items: 'أمثلة من الحياة' },
            idea: { title: 'تفسير السبب', body: 'لماذا يحدث ذلك؟', items: 'أسباب أو مراحل' },
            example: { title: 'تجربة أو تطبيق', body: 'وصف التجربة', items: 'خطوات التجربة، كل خطوة بسطر', result: 'الاستنتاج', note: 'تنبيه أمان أو ملاحظة' },
            tip: { title: 'استنتاج', body: 'ماذا يجب أن يتذكر الطالب؟', items: 'كلمات علمية مهمة' },
            youtube: { title: 'فيديو تجربة', body: 'ما الذي يشاهده الطالب؟', url: 'رابط YouTube' },
            pdf: { title: 'ملف مختبر', body: 'تعليمات الملف', url: 'رابط PDF أو الملف المرفق' },
        },
    };

    return { ...defaults, ...(subjectLabels[subjectId]?.[type] || {}) };
}

function splitLessonLines(value = '') {
    const lines = optionList(value);
    if (lines.length) return lines;
    return String(value || '')
        .split(/[.؟!\n]/)
        .map((item) => item.trim())
        .filter(Boolean);
}

function createLessonFromTeacherDraft(unitId) {
    const draft = state.teacherDraft;
    const quizScore = draft.quiz.reduce((sum, item) => sum + Number(item.score || 1), 0);

    return {
        id: `${unitId}-lesson-${Date.now()}`,
        title: draft.lessonTitle || 'درس جديد',
        xp: Math.max(10, quizScore || 10),
        theory: {
            title: draft.theoryTitle || draft.lessonTitle || 'شرح الدرس',
            body: draft.theoryBody || '',
            blocks: normalizeTheoryBlocks(draft),
            points: ['اقرأ الشرح جيداً.', 'انتقل للأمثلة.', 'حل ورقة العمل ثم الاختبار.'],
        },
        examples: draft.examples.map((example, index) => ({
            title: example.title || `مثال ${index + 1}`,
            prompt: example.body || 'نص المثال اختياري.',
            steps: optionList(example.answer).length ? optionList(example.answer) : ['يمكن للمعلم إضافة الحل لاحقاً.'],
        })),
        worksheet: draft.worksheet.map((item) => ({
            question: item.question || 'سؤال ورقة عمل',
            options: optionList(item.options),
            answer: item.answer || optionList(item.options)[0] || '',
        })),
        quiz: draft.quiz.map((item) => ({
            question: item.question || 'سؤال اختبار قصير',
            options: optionList(item.options),
            answer: item.answer || optionList(item.options)[0] || '',
            score: Number(item.score || 1),
        })),
    };
}

function saveTeacherDraftToUnits() {
    const draft = state.teacherDraft;
    const unitNo = Math.max(1, Math.min(9, Number(draft.unitNo || 1)));
    const grade = Number(draft.grade || 1);
    const unitKey = (unit) => unit.grade === grade && unit.subject === draft.subject && Number(unit.unitNo) === unitNo;
    const customIndex = state.customUnits.findIndex(unitKey);
    const baseUnit = learningUnits.find(unitKey);
    const sourceUnit = customIndex >= 0 ? state.customUnits[customIndex] : baseUnit;
    const unitId = sourceUnit?.id || `custom-${Date.now()}`;
    const newLesson = createLessonFromTeacherDraft(unitId);
    const draftSubject = subjects.find((subject) => subject.id === draft.subject) || subjects[0];
    const savedUnit = {
        id: unitId,
        title: draft.unitTitle || sourceUnit?.title || `وحدة ${unitNo}`,
        subject: draft.subject,
        grade,
        unitNo,
        accent: draftSubject.color,
        lessons: [...(sourceUnit?.lessons || []), newLesson],
    };

    if (customIndex >= 0) {
        state.customUnits[customIndex] = savedUnit;
    } else {
        state.customUnits.push(savedUnit);
    }

    return { unit: savedUnit, lesson: newLesson };
}

function createUnitFromTeacherDraft() {
    const draft = state.teacherDraft;
    const unitId = `custom-${Date.now()}`;
    const draftSubject = subjects.find((subject) => subject.id === draft.subject) || subjects[0];
    return {
        id: unitId,
        title: draft.unitTitle || 'وحدة جديدة',
        subject: draft.subject,
        grade: Number(draft.grade || 1),
        unitNo: Number(draft.unitNo || 1),
        accent: draftSubject.color,
        lessons: [createLessonFromTeacherDraft(unitId)],
    };
}

function icon(name) {
    const icons = {
        home: '<svg viewBox="0 0 24 24"><path d="M3 10.8 12 3l9 7.8V21h-6v-6H9v6H3z"/></svg>',
        compass: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2.2 5.1-4.8 1.9 2.2-5.1z"/></svg>',
        trophy: '<svg viewBox="0 0 24 24"><path d="M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M7 7H4.8a2 2 0 0 0 0 4H7M17 7h2.2a2 2 0 0 1 0 4H17M10 15v3M14 15v3M8 21h8"/></svg>',
        medal: '<svg viewBox="0 0 24 24"><path d="M6 3h12l-4 7H10z"/><circle cx="12" cy="16" r="5"/><path d="M12 14v4"/></svg>',
        star: '<svg viewBox="0 0 24 24"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.1L12 17.2 6.4 20.1 7.5 14 3 9.6l6.2-.9z"/></svg>',
        fire: '<svg viewBox="0 0 24 24"><path d="M13 2C8 7 17 8 10 14c-2.3-2-1.8-4.5-.8-6.8C5.5 10 4 13 4 16a8 8 0 0 0 16 0c0-5-3.5-7-7-14z"/></svg>',
        target: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="2"/></svg>',
        check: '<svg viewBox="0 0 24 24"><path d="m5 12 4.2 4.2L19 6.5"/></svg>',
        lock: '<svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>',
        arrow: '<svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>',
        back: '<svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>',
    };

    return icons[name] || '';
}

function subjectIcon(subject) {
    const className = `subject-art subject-art-${subject.icon}`;
    if (subject.icon === 'abc') return `<span class="${className}">abc</span>`;
    if (subject.icon === 'book') return `<span class="${className}"><i></i><b></b></span>`;
    if (subject.icon === 'moon') return `<span class="${className}"><i></i></span>`;
    if (subject.icon === 'ruler') return `<span class="${className}"><i></i></span>`;
    if (subject.icon === 'micro') return `<span class="${className}"><i></i><b></b></span>`;
    return `<span class="${className}"><i></i><b></b></span>`;
}

function shell(content) {
    return `
        <div class="platform">
            <aside class="sidebar">
                <button class="brand" data-view="home" aria-label="الرئيسية">
                    <span class="brand-mark"><img src="/assets/pink-blue-logo.png" alt=""></span>
                    <span>Pink & Blue</span>
                </button>
                <nav class="side-nav" aria-label="Main navigation">
                    ${navButton('home', 'الرئيسية', 'home')}
                    ${navButton('learn', 'التعلم', 'compass')}
                    ${navButton('awards', 'الجوائز', 'trophy')}
                    ${navButton('leaders', 'المتصدرون', 'medal')}
                </nav>
            </aside>
            <main class="main-panel">${content}</main>
            <nav class="mobile-nav" aria-label="Mobile navigation">
                ${mobileNavButton('home', 'الرئيسية', 'home')}
                ${mobileNavButton('learn', 'التعلم', 'compass')}
                ${mobileNavButton('awards', 'الجوائز', 'trophy')}
                ${mobileNavButton('leaders', 'المتصدرون', 'medal')}
            </nav>
        </div>
    `;
}

function navButton(view, label, iconName) {
    const active = state.view === view || (view === 'learn' && ['subjects', 'path', 'lesson'].includes(state.view));
    return `
        <button class="nav-item ${active ? 'is-active' : ''}" data-view="${view}">
            ${icon(iconName)}
            <span>${label}</span>
        </button>
    `;
}

function mobileNavButton(view, label, iconName) {
    const active = state.view === view || (view === 'learn' && ['subjects', 'path', 'lesson'].includes(state.view));
    return `
        <button class="mobile-item ${active ? 'is-active' : ''}" data-view="${view}">
            ${icon(iconName)}
            <span>${label}</span>
        </button>
    `;
}

function statsBar() {
    return `
        <div class="stats-row">
            <div class="stat-pill stat-fire" title="أيام التعلم المتتالية">${icon('fire')}<strong>${state.streak}</strong></div>
            <div class="stat-pill stat-xp" title="نقاط من حل الدروس">${icon('star')}<strong dir="ltr">${state.xp} XP</strong></div>
        </div>
    `;
}

function progressCard() {
    const xpIntoLevel = state.xp % 200;
    const progress = Math.min(100, (xpIntoLevel / 200) * 100);
    const gradeName = grades[state.grade - 1] || grades[0];
    return `
        <section class="level-card">
            <div class="level-copy">
                <div class="level-line">
                    <h2>${gradeName}</h2>
                    <strong dir="ltr">${xpIntoLevel} / 200 XP</strong>
                </div>
                <div class="progress-track"><span style="width:${progress}%"></span></div>
            </div>
            <div class="daily-card">
                <span class="target-icon">${icon('target')}</span>
                <div>
                    <small>هدف اليوم</small>
                    <strong dir="ltr">${state.dailyGoal} / 4</strong>
                </div>
            </div>
        </section>
    `;
}

function homeView() {
    return shell(`
        <section class="dashboard">
            <header class="top-heading">
                <div>
                    <h1>أهلا بك مجددا! <span class="wave">👏</span></h1>
                    <p>اختار صفك ومادتك وابدأ رحلة اليوم.</p>
                </div>
                ${statsBar()}
            </header>
            ${progressCard()}
            <section class="home-picker">
                <div class="section-title">
                    <h2>اختار الصف</h2>
                    <p>من الأول للتاسع</p>
                </div>
                <div class="grade-grid">
                    ${grades.map((grade, index) => `
                        <button class="grade-chip ${state.grade === index + 1 ? 'is-active' : ''}" data-grade="${index + 1}">
                            ${gradeShortNames[index]}
                        </button>
                    `).join('')}
                </div>

                <div class="section-title subjects-title">
                    <h2>اختار المادة</h2>
                    <p>المواد الأساسية المتاحة حالياً</p>
                </div>
                <div class="home-subjects">
                    ${subjects.map((subject) => `
                        <button
                            class="home-subject ${state.subject === subject.id ? 'is-selected' : ''}"
                            data-subject="${subject.id}"
                            style="--subject:${subject.color};--subject-bg:${subject.bg};--subject-border:${subject.border};"
                        >
                            ${subjectIcon(subject)}
                            <span>${subject.name}</span>
                        </button>
                    `).join('')}
                </div>
            </section>
            <section class="continue-block">
                <h2>كمّل التعلم</h2>
                <div class="continue-grid single-action">
                    <button class="resume-card" data-view="path">
                        <span>${grades[state.grade - 1]}</span>
                        <strong>${currentSubject().name}</strong>
                        <i>${icon('arrow')}</i>
                    </button>
                </div>
            </section>
        </section>
    `);
}

function subjectsView() {
    return learningView();
}

function pathView() {
    return learningView();
}

function learningView() {
    if (state.teacherPanel) return teacherDashboardView();
    if (state.lessonMode === 'lesson') return lessonPlayerView();

    const subject = currentSubject();
    const units = unitsForSelection();
    const unit = currentUnit();
    if (!unit) return emptyLearningView(subject);
    const progress = unitProgressPercent(unit);

    return shell(`
        <section class="learn-page">
            <header class="learn-hero">
                <div class="learn-subject-mark" style="--subject:${subject.color};--subject-bg:${subject.bg}">
                    ${subjectIcon(subject)}
                </div>
                <div>
                    <span>${grades[state.grade - 1]} - ${subject.name}</span>
                    <h1>مسار التعلم</h1>
                    <p>الوحدات والدروس تفتح بالترتيب بعد إنهاء الشرح والأمثلة وورقة العمل والاختبار.</p>
                </div>
                <button class="teacher-open-btn" data-teacher-panel="open">لوحة المعلم</button>
            </header>

            <div class="unit-switcher">
                ${units.map((item) => `
                    <button class="unit-pill ${item.id === unit.id ? 'is-active' : ''}" data-unit-id="${item.id}">
                        وحدة ${item.unitNo}: ${item.title}
                    </button>
                `).join('')}
            </div>

            <section class="learning-board">
                <div class="unit-card">
                    <small>الوحدة ${unit.unitNo}</small>
                    <h2>${unit.title}</h2>
                    <div class="unit-progress"><span style="width:${progress}%"></span></div>
                    <p>${unit.lessons.filter((lesson) => lessonDone(lesson.id)).length} / ${unit.lessons.length} دروس مكتملة</p>
                </div>

                <div class="duo-road">
                    <article class="unit-road-head">
                        <button class="unit-road-node" type="button">
                            <span>وحدة ${unit.unitNo}</span>
                        </button>
                        <div>
                            <strong>${unit.title}</strong>
                            <small>تحتها ${unit.lessons.length} دروس</small>
                        </div>
                    </article>
                    ${unit.lessons.map((lesson, index) => lessonPathNode(unit, lesson, index)).join('')}
                </div>
            </section>
        </section>
    `);
}

function emptyLearningView(subject) {
    return shell(`
        <section class="learn-page">
            <header class="learn-hero">
                <div class="learn-subject-mark" style="--subject:${subject.color};--subject-bg:${subject.bg}">
                    ${subjectIcon(subject)}
                </div>
                <div>
                    <span>${grades[state.grade - 1]} - ${subject.name}</span>
                    <h1>مسار التعلم</h1>
                    <p>لا توجد وحدات بعد لهذا الصف والمادة. ابدأ من لوحة المعلم وأدخل محتواك.</p>
                </div>
                <button class="teacher-open-btn" data-teacher-panel="open">لوحة المعلم</button>
            </header>
            <section class="empty-learning-card">
                <h2>لا يوجد محتوى بعد</h2>
                <p>أضف وحدة، ثم أضف درساً تحتها، وبعد الحفظ سيظهر المسار هنا للطالب.</p>
                <button class="primary-action" data-teacher-panel="open">إضافة أول وحدة</button>
            </section>
        </section>
    `);
}

function lessonPathNode(unit, lesson, index) {
    const unlocked = lessonUnlocked(unit, index);
    const done = lessonDone(lesson.id);
    const active = unlocked && !done;
    const shift = [0, -42, 28, -18, 46, -34][index % 6];
    const status = done ? 'done' : active ? 'active' : 'locked';

    return `
        <article class="learn-step" style="--shift:${shift}px">
            ${index < unit.lessons.length - 1 ? `<span class="learn-link ${done ? 'done' : ''}"></span>` : ''}
            <button
                class="learn-node ${status}"
                data-learning-lesson="${lesson.id}"
                ${unlocked ? '' : 'disabled'}
                aria-label="${lesson.title}"
            >
                ${done ? icon('check') : active ? icon('star') : icon('lock')}
            </button>
            <div class="learn-bubble ${active ? 'is-active' : ''}">
                <strong>${lesson.title}</strong>
                <span>${lessonProgressPercent(lesson)}% مكتمل</span>
            </div>
        </article>
    `;
}

function lessonPlayerView() {
    const lesson = currentLesson();
    const unit = currentUnit();
    if (!lesson || !unit) return emptyLearningView(currentSubject());
    const sections = [
        ['theory', 'شرح'],
        ['examples', 'أمثلة'],
        ['worksheet', 'ورقة عمل'],
        ['quiz', 'اختبار قصير'],
    ];

    return shell(`
        <section class="lesson-player-page">
            <header class="lesson-player-top">
                <button class="circle-back in-flow" data-back-learning="true">${icon('back')}</button>
                <div class="lesson-player-title">
                    <small>${unit.title}</small>
                    <h1>${lesson.title}</h1>
                    <div class="player-progress"><span style="width:${lessonProgressPercent(lesson)}%"></span></div>
                </div>
                <button class="sound-btn" data-speak-current="true">🔊</button>
            </header>

            <nav class="lesson-section-tabs">
                ${sections.map(([id, label]) => `
                    <button class="${state.lessonSection === id ? 'is-active' : ''}" data-section="${id}">
                        ${label}
                    </button>
                `).join('')}
            </nav>

            <article class="duo-lesson-card">
                ${renderLessonSection(lesson)}
            </article>
        </section>
    `);
}

function renderLessonSection(lesson) {
    if (state.lessonSection === 'examples') return renderExamplesSection(lesson);
    if (state.lessonSection === 'worksheet') return renderQuestionSection(lesson, 'worksheet');
    if (state.lessonSection === 'quiz') return renderQuestionSection(lesson, 'quiz');
    return renderTheorySection(lesson);
}

function renderTheorySection(lesson) {
    const blocks = lesson.theory.blocks?.length ? lesson.theory.blocks : [{
        type: 'hook',
        emoji: '✨',
        title: lesson.theory.title,
        body: lesson.theory.body,
    }];
    const index = Math.min(state.currentTheoryBlock || 0, blocks.length - 1);
    const block = blocks[index];
    const isLast = index >= blocks.length - 1;

    return `
        <div class="lesson-stage theory-stage">
            <div class="theory-game-head">
                <span class="stage-kicker">الشرح</span>
                <div class="theory-progress">
                    ${blocks.map((_, dotIndex) => `<span class="${dotIndex <= index ? 'is-filled' : ''}"></span>`).join('')}
                </div>
            </div>
            ${renderTheoryBlock(block, index)}
            <footer class="theory-actions">
                <button class="soft-action" data-theory-prev="true" ${index === 0 ? 'disabled' : ''}>السابق</button>
                <button class="primary-action" data-theory-next="true">
                    ${isLast ? 'فهمت الشرح' : 'التالي'}
                </button>
            </footer>
        </div>
    `;
}

function renderTheoryBlock(block, index) {
    const subject = currentSubject();
    const typeLabel = {
        hook: 'مقدمة',
        definition: 'تعريف',
        idea: 'فكرة',
        example: 'مثال',
        tip: 'تلميح',
        youtube: 'فيديو',
        pdf: 'PDF',
    }[block.type] || 'شرح';
    const items = optionList(block.items);
    const lines = splitLessonLines(block.items || block.body);
    const visual = renderTheoryVisual(block, subject);
    const bodyClass = visual ? 'duo-choice-body has-visual' : 'duo-choice-body no-visual';
    const speech = block.body || block.note || 'اقرأ هذه الخطوة بهدوء، ثم اضغط التالي.';

    if (block.type === 'youtube') {
        const embed = youtubeEmbedUrl(block.url);
        return `
            <section class="duo-activity-card media-card pop-card" style="--delay:${index * 70}ms;--subject:${subject.color}">
                <div class="activity-progress"><span style="width:${Math.max(18, (index + 1) * 22)}%"></span></div>
                <div class="duo-mascot-row compact">
                    ${mascot('happy')}
                    <div class="speech-bubble">
                        <strong>${escapeHtml(block.title || 'فيديو مساعد')}</strong>
                        ${block.body ? `<span>${escapeHtml(block.body)}</span>` : '<span>شاهد الفيديو ثم كمل.</span>'}
                    </div>
                </div>
                ${embed ? `<iframe class="lesson-media-frame" src="${escapeHtml(embed)}" title="${escapeHtml(block.title || 'فيديو الدرس')}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>` : '<div class="media-placeholder">أضف رابط YouTube صحيح من لوحة المعلم</div>'}
            </section>
        `;
    }

    if (block.type === 'pdf') {
        const pdfUrl = block.url || '';
        return `
            <section class="duo-activity-card media-card pop-card" style="--delay:${index * 70}ms;--subject:${subject.color}">
                <div class="activity-progress"><span style="width:${Math.max(18, (index + 1) * 22)}%"></span></div>
                <div class="duo-mascot-row compact">
                    ${mascot('read')}
                    <div class="speech-bubble">
                        <strong>${escapeHtml(block.title || 'ملف PDF مساعد')}</strong>
                        ${block.body ? `<span>${escapeHtml(block.body)}</span>` : '<span>افتح الملف المساعد وراجع الفكرة.</span>'}
                    </div>
                </div>
                ${pdfUrl && isPdfSource(pdfUrl) ? `
                    <object class="lesson-pdf-frame" data="${escapeHtml(pdfUrl)}" type="application/pdf">
                        <a href="${escapeHtml(pdfUrl)}" target="_blank" rel="noreferrer">فتح ملف PDF</a>
                    </object>
                    <a class="open-media-link" href="${escapeHtml(pdfUrl)}" target="_blank" rel="noreferrer">فتح الملف بنافذة جديدة</a>
                ` : '<div class="media-placeholder">أرفق PDF من الجهاز أو أضف رابط PDF مباشر</div>'}
            </section>
        `;
    }

    return `
        <section class="duo-activity-card theory-${block.type || 'hook'} pop-card" style="--delay:${index * 70}ms;--subject:${subject.color}">
            <div class="activity-progress"><span style="width:${Math.max(18, (index + 1) * 22)}%"></span></div>
            <div class="duo-mascot-row compact">
                ${mascot(block.type === 'tip' ? 'happy' : 'think')}
                <div class="speech-bubble">
                    <strong>${escapeHtml(block.title || block.term || 'شرح الدرس')}</strong>
                    <span>${escapeHtml(speech)}</span>
                </div>
            </div>
            <div class="${bodyClass}">
                ${visual}
                ${renderSubjectLessonContent(block, subject, lines, items)}
            </div>
        </section>
    `;
}

function renderSubjectLessonContent(block, subject, lines, items) {
    const result = block.result || block.note ? `
        <div class="result-note">
            ${block.result ? `<strong>${escapeHtml(block.result)}</strong>` : ''}
            ${block.note ? `<small>${escapeHtml(block.note)}</small>` : ''}
        </div>
    ` : '';

    if (subject.id === 'english') {
        const options = (items.length ? items : lines).slice(0, 6);
        return `
            <div class="duo-content-panel english-panel">
                <div class="english-conversation-label">Conversation</div>
                <div class="english-option-stack">
                    ${(options.length ? options : ['Listen and repeat', 'Choose the picture', 'Use it in a sentence']).map((item, index) => `
                        <div>
                            <i>${['🗣️', '🎧', '🧩', '📝', '⭐', '✅'][index] || '✅'}</i>
                            <span>${escapeHtml(item)}</span>
                        </div>
                    `).join('')}
                </div>
                ${result}
            </div>
        `;
    }

    if (subject.id === 'math') {
        return `
            <div class="duo-content-panel math-panel">
                ${block.type === 'definition' && (block.term || block.symbol) ? `
                    <div class="definition-tile math-rule">
                        <small>قاعدة / رمز</small>
                        <strong>${escapeHtml(block.term || 'قاعدة')}</strong>
                        ${block.symbol ? `<b>${escapeHtml(block.symbol)}</b>` : ''}
                    </div>
                ` : ''}
                <div class="math-step-stack">
                    ${(lines.length ? lines : ['اكتب المعطيات', 'طبّق القاعدة', 'تحقق من الناتج']).slice(0, 5).map((line, index) => `
                        <div><b>${index + 1}</b><span>${escapeHtml(line)}</span></div>
                    `).join('')}
                </div>
                ${result}
            </div>
        `;
    }

    if (subject.id === 'science') {
        return `
            <div class="duo-content-panel science-panel">
                <div class="science-observation">
                    <strong>لاحظ ثم استنتج</strong>
                    <span>${escapeHtml(block.term || block.title || 'تجربة قصيرة')}</span>
                </div>
                <div class="science-observation-stack">
                    ${(lines.length ? lines : ['ماذا ترى؟', 'ما السبب؟', 'ما الاستنتاج؟']).slice(0, 4).map((line, index) => `
                        <div><i>${['🔎', '🧪', '🌱', '✅'][index] || '✅'}</i><span>${escapeHtml(line)}</span></div>
                    `).join('')}
                </div>
                ${result}
            </div>
        `;
    }

    return `
        <div class="duo-content-panel arabic-panel">
            ${block.type === 'definition' && (block.term || block.symbol) ? `
                <div class="definition-tile">
                    <small>مصطلح اليوم</small>
                    <strong>${escapeHtml(block.term || 'مصطلح')}</strong>
                    ${block.symbol ? `<b>${escapeHtml(block.symbol)}</b>` : ''}
                </div>
            ` : ''}
            <div class="arabic-reading-card">
                ${(lines.length ? lines : ['اقرأ الفكرة', 'لاحظ المثال', 'احفظ التلميح']).slice(0, 4).map((line, index) => `
                    <div><i>${['📖', '✏️', '💡', '⭐'][index] || '✅'}</i><span>${escapeHtml(line)}</span></div>
                `).join('')}
            </div>
            ${result}
        </div>
    `;
}

function renderTheoryVisual(block, subject) {
    if (block.imageUrl) {
        return `
            <figure class="duo-visual image-visual">
                <img src="${escapeHtml(block.imageUrl)}" alt="">
            </figure>
        `;
    }

    return '';
}

function mascot(mood = 'happy') {
    return `
        <div class="mascot mascot-${mood}" aria-hidden="true">
            <span class="mascot-head"><i></i><b></b></span>
            <span class="mascot-body"></span>
        </div>
    `;
}

function renderExamplesSection(lesson) {
    return `
        <div class="lesson-stage">
            <span class="stage-kicker">الأمثلة</span>
            <h2>حل خطوة بخطوة</h2>
            <div class="example-stack">
                ${lesson.examples.map((example, index) => `
                    <section class="example-card">
                        <small>مثال ${index + 1}</small>
                        <h3>${example.title}</h3>
                        <p>${example.prompt}</p>
                        <ol>
                            ${example.steps.map((step) => `<li>${step}</li>`).join('')}
                        </ol>
                    </section>
                `).join('')}
            </div>
            <button class="primary-action" data-complete-section="examples">خلصت الأمثلة</button>
        </div>
    `;
}

function renderQuestionSection(lesson, section) {
    const questionsList = lesson[section];
    const question = questionsList[state.currentQuestion] || questionsList[0];
    const done = sectionDone(lesson.id, section);
    const label = section === 'quiz' ? 'الاختبار القصير' : 'ورقة العمل';

    return `
        <div class="lesson-stage question-stage">
            <span class="stage-kicker">${label}</span>
            <h2>${question.question}</h2>
            <div class="answer-grid">
                ${question.options.map((option) => `
                    <button class="answer-choice ${state.selectedAnswer === option ? 'is-picked' : ''}" data-learning-answer="${option}">
                        ${option}
                    </button>
                `).join('')}
            </div>
            <footer class="practice-footer">
                <p class="${state.lastResult === 'إجابة صحيحة!' ? 'success' : state.lastResult ? 'error' : ''}">
                    ${done ? 'تم إنجاز هذه المرحلة' : state.lastResult || ' '}
                </p>
                <button class="check-btn" data-check-learning="${section}" ${state.selectedAnswer || done ? '' : 'disabled'}>
                    ${done ? 'التالي' : 'تحقق'}
                </button>
            </footer>
        </div>
    `;
}

function teacherDashboardView() {
    const units = unitsForSelection();
    const draft = state.teacherDraft;
    const theoryGuide = subjectTheoryGuide(draft.subject);
    const typeOptions = theoryTypeOptions(draft.subject);
    const draftGrade = Number(draft.grade || 1);
    const draftUnitNo = Math.max(1, Math.min(9, Number(draft.unitNo || 1)));
    const draftUnits = allLearningUnits().filter((unit) => unit.grade === draftGrade && unit.subject === draft.subject);
    const selectedDraftUnit = draftUnits.find((unit) => Number(unit.unitNo) === draftUnitNo);

    return shell(`
        <section class="teacher-dashboard-page">
            <header class="teacher-dash-head">
                <div>
                    <span>لوحة المعلم</span>
                    <h1>ترتيب الوحدات والدروس</h1>
                    <p>تصور مبدئي لطريقة إدخال وحدة، ثم دروس، ثم شرح وأمثلة وورقة عمل واختبار قصير لكل درس.</p>
                </div>
                <button class="teacher-open-btn" data-teacher-panel="close">رجوع للتعلم</button>
            </header>

            <section class="teacher-scope-card">
                <h2>1. اختار الصف والمادة</h2>
                <div class="teacher-scope-grid">
                    <label>
                        الصف
                        <select data-teacher-field="grade">
                            ${grades.map((grade, index) => `
                                <option value="${index + 1}" ${Number(draft.grade) === index + 1 ? 'selected' : ''}>${grade}</option>
                            `).join('')}
                        </select>
                    </label>
                    <label>
                        المادة
                        <select data-teacher-field="subject">
                            ${subjects.map((subject) => `
                                <option value="${subject.id}" ${draft.subject === subject.id ? 'selected' : ''}>${subject.name}</option>
                            `).join('')}
                        </select>
                    </label>
                </div>
            </section>

            <div class="teacher-dash-grid">
                <section class="teacher-form-card student-snapshot">
                    <h2>متابعة الطالب</h2>
                    <div class="student-meter">
                        <strong>${state.xp} XP</strong>
                        <span>${state.dailyGoal} / 4 هدف اليوم</span>
                    </div>
                    <p>الدرس الحالي: ${currentLesson()?.title || 'لا يوجد درس بعد'}</p>
                    <p>تقدم الدرس: ${lessonProgressPercent(currentLesson())}%</p>
                </section>

                <section class="teacher-form-card">
                    <h2>2. إضافة وحدة</h2>
                    <label>
                        اختر الوحدة
                        <select data-teacher-field="unitNo">
                            ${Array.from({ length: 9 }, (_, index) => {
                                const unitNo = index + 1;
                                const existing = draftUnits.find((unit) => Number(unit.unitNo) === unitNo);
                                return `
                                    <option value="${unitNo}" ${draftUnitNo === unitNo ? 'selected' : ''}>
                                        الوحدة ${unitNo}${existing ? ` - ${existing.title}` : ''}
                                    </option>
                                `;
                            }).join('')}
                        </select>
                    </label>
                    <label>اسم الوحدة <input data-teacher-field="unitTitle" value="${escapeHtml(draft.unitTitle)}"></label>
                    <p class="teacher-hint">اختر وحدة من القائمة حتى 9 وحدات. إذا كانت موجودة سيضاف الدرس الجديد تحتها، وإذا كانت فاضية سيتم إنشاؤها.</p>
                    <div class="unit-lessons-preview">
                        <strong>الدروس تحت هذه الوحدة</strong>
                        ${(selectedDraftUnit?.lessons || []).length ? selectedDraftUnit.lessons.map((lesson, index) => `
                            <span>${index + 1}. ${lesson.title}</span>
                        `).join('') : '<span>لا توجد دروس بعد. الدرس الذي تدخله الآن سيكون أول درس.</span>'}
                    </div>
                </section>

                <section class="teacher-form-card">
                    <h2>3. إضافة درس داخل الوحدة</h2>
                    <div class="teacher-flow">
                        <span>شرح</span>
                        <span>أمثلة</span>
                        <span>ورقة عمل</span>
                        <span>اختبار قصير</span>
                    </div>
                    <label>عنوان الدرس <input data-teacher-field="lessonTitle" value="${escapeHtml(draft.lessonTitle)}"></label>
                </section>
            </div>

            <section class="teacher-builder-card theory-builder-card">
                <div class="builder-head">
                    <div>
                        <h2>4. بناء الشرح</h2>
                        <p class="teacher-hint">اختار نوع كل جزء من الشرح: فكرة، تعريف، مثال، تلميح، فيديو YouTube أو ملف PDF. الطالب سيشاهده خطوة بخطوة مثل لعبة.</p>
                    </div>
                    <div class="add-theory-actions">
                        <select data-theory-add-select="true" aria-label="نوع قسم الشرح">
                            ${typeOptions.map(([value, label]) => `<option value="${value}">${label}</option>`).join('')}
                        </select>
                        <button type="button" data-add-theory-block="true">+ قسم شرح</button>
                    </div>
                </div>
                <div class="subject-guide-card">
                    <strong>${theoryGuide.title}</strong>
                    <p>${theoryGuide.text}</p>
                    <div>${theoryGuide.chips.map((chip) => `<span>${chip}</span>`).join('')}</div>
                </div>
                <div class="theory-editor-list">
                    ${(draft.theoryBlocks?.length ? draft.theoryBlocks : [defaultTheoryBlock('hook')]).map((block, index) => theoryBlockEditor(block, index)).join('')}
                </div>
            </section>

            <section class="teacher-builder-grid">
                <article class="teacher-builder-card">
                    <div class="builder-head">
                        <h2>5. الأمثلة</h2>
                        <button type="button" data-add-teacher-row="examples">+ مثال</button>
                    </div>
                    <p class="teacher-hint">العنوان والشرح والحل اختياريين، ويمكن إضافة أمثلة بعدد مفتوح.</p>
                    ${draft.examples.map((example, index) => `
                        <div class="teacher-repeat-row">
                            <label>عنوان المثال <input data-teacher-list="examples" data-index="${index}" data-field="title" value="${escapeHtml(example.title)}"></label>
                            <label>شرح المثال <textarea data-teacher-list="examples" data-index="${index}" data-field="body" rows="2">${escapeHtml(example.body)}</textarea></label>
                            <label>حل أو إجابة المثال <textarea data-teacher-list="examples" data-index="${index}" data-field="answer" rows="2">${escapeHtml(example.answer)}</textarea></label>
                        </div>
                    `).join('')}
                </article>

                <article class="teacher-builder-card">
                    <div class="builder-head">
                        <h2>6. ورقة العمل</h2>
                        <button type="button" data-add-teacher-row="worksheet">+ سؤال</button>
                    </div>
                    ${draft.worksheet.map((item, index) => teacherQuestionEditor('worksheet', item, index, false)).join('')}
                </article>

                <article class="teacher-builder-card">
                    <div class="builder-head">
                        <h2>7. الاختبار القصير</h2>
                        <button type="button" data-add-teacher-row="quiz">+ سؤال</button>
                    </div>
                    <p class="teacher-hint">كل سؤال في الاختبار له سكور، ومجموع السكور يتحول إلى XP للدرس.</p>
                    ${draft.quiz.map((item, index) => teacherQuestionEditor('quiz', item, index, true)).join('')}
                </article>
            </section>

            <div class="teacher-save-row">
                <button class="primary-action" type="button" data-save-teacher-unit="true">حفظ الوحدة وإظهارها للطالب</button>
                <button class="teacher-open-btn" type="button" data-reset-teacher-draft="true">تفريغ النموذج</button>
            </div>

            <section class="teacher-unit-preview">
                <h2>المحتوى الحالي</h2>
                ${units.length ? units.map((unit) => `
                    <article>
                        <strong>وحدة ${unit.unitNo}: ${unit.title}</strong>
                        <p>${unit.lessons.length} دروس، تفتح بالتتابع للطالب.</p>
                    </article>
                `).join('') : '<p class="teacher-hint">لا توجد وحدات بعد لهذا الصف والمادة.</p>'}
            </section>
        </section>
    `);
}

function teacherQuestionEditor(listName, item, index, withScore) {
    return `
        <div class="teacher-repeat-row">
            <label>السؤال <textarea data-teacher-list="${listName}" data-index="${index}" data-field="question" rows="2">${escapeHtml(item.question)}</textarea></label>
            <label>الخيارات <input data-teacher-list="${listName}" data-index="${index}" data-field="options" value="${escapeHtml(item.options)}"></label>
            <label>الإجابة الصحيحة <input data-teacher-list="${listName}" data-index="${index}" data-field="answer" value="${escapeHtml(item.answer)}"></label>
            ${withScore ? `<label>السكور <input type="number" min="1" data-teacher-list="${listName}" data-index="${index}" data-field="score" value="${escapeHtml(item.score || 1)}"></label>` : ''}
        </div>
    `;
}

function theoryBlockEditor(block, index) {
    const current = { ...defaultTheoryBlock(block.type || 'hook'), ...block };
    const typeNames = Object.fromEntries(theoryTypeOptions(state.teacherDraft.subject));
    const labels = theoryFieldLabels(state.teacherDraft.subject, current.type);
    const needsTerm = current.type === 'definition';
    const needsItems = ['hook', 'definition', 'example', 'idea', 'tip'].includes(current.type);
    const needsResult = ['example', 'idea', 'tip'].includes(current.type);
    const needsUrl = ['youtube', 'pdf'].includes(current.type);
    const needsImage = !['youtube', 'pdf'].includes(current.type);
    const imageLabel = labels.imageUrl;

    return `
        <article class="theory-edit-card">
            <header>
                <strong>${index + 1}. ${typeNames[current.type] || 'شرح'}</strong>
                <button type="button" data-remove-theory-block="${index}" ${index === 0 ? 'disabled' : ''}>حذف</button>
            </header>
            <div class="teacher-repeat-row compact">
                <label>
                    النوع
                    <select data-theory-block="${index}" data-field="type">
                        ${Object.entries(typeNames).map(([value, label]) => `
                            <option value="${value}" ${current.type === value ? 'selected' : ''}>${label}</option>
                        `).join('')}
                    </select>
                </label>
                <label>${labels.emoji} <input data-theory-block="${index}" data-field="emoji" value="${escapeHtml(current.emoji)}"></label>
                <label>${labels.title} <input data-theory-block="${index}" data-field="title" value="${escapeHtml(current.title)}"></label>
                ${needsTerm ? `
                    <label>${labels.term} <input data-theory-block="${index}" data-field="term" value="${escapeHtml(current.term)}"></label>
                    <label>${labels.symbol} <input data-theory-block="${index}" data-field="symbol" value="${escapeHtml(current.symbol)}" placeholder="Ω / ح / ="></label>
                ` : ''}
                <label>
                    ${labels.body}
                    <textarea data-theory-block="${index}" data-field="body" rows="3">${escapeHtml(current.body)}</textarea>
                </label>
                ${needsImage ? `
                    <label>
                        ${imageLabel}
                        <input data-theory-block="${index}" data-field="imageUrl" value="${escapeHtml(current.imageUrl)}" placeholder="رابط صورة اختياري">
                    </label>
                    <label>
                        إرفاق صورة من الجهاز
                        <input type="file" accept="image/*" data-theory-file="${index}" data-file-field="imageUrl">
                    </label>
                <div class="image-search-box">
                    <label>
                        بحث صور Pixabay
                        <input data-image-query="${index}" value="${state.imageSearch?.blockIndex === index ? escapeHtml(state.imageSearch.query) : ''}" placeholder="مثال: solar system, apple, fractions">
                    </label>
                    <label>
                        نوع الصور
                        <select data-image-type="${index}">
                            <option value="vector" ${state.imageSearch?.type !== 'illustration' && state.imageSearch?.type !== 'photo' ? 'selected' : ''}>Vector / رسومات</option>
                            <option value="illustration" ${state.imageSearch?.type === 'illustration' ? 'selected' : ''}>Illustration</option>
                            <option value="photo" ${state.imageSearch?.type === 'photo' ? 'selected' : ''}>Photo</option>
                        </select>
                    </label>
                    <button type="button" data-search-images="${index}">بحث</button>
                    ${renderImageSearchResults(index)}
                </div>
                ` : ''}
                ${needsItems ? `
                    <label>
                        ${labels.items}
                        <textarea data-theory-block="${index}" data-field="items" rows="3" placeholder="كل عنصر بسطر منفصل">${escapeHtml(current.items)}</textarea>
                    </label>
                ` : ''}
                ${needsResult ? `
                    <label>${labels.result} <input data-theory-block="${index}" data-field="result" value="${escapeHtml(current.result)}"></label>
                    <label>${labels.note} <input data-theory-block="${index}" data-field="note" value="${escapeHtml(current.note)}"></label>
                ` : ''}
                ${needsUrl ? `
                    <label>
                        ${labels.url}
                        <input data-theory-block="${index}" data-field="url" value="${escapeHtml(current.url)}" placeholder="${current.type === 'youtube' ? 'https://www.youtube.com/watch?v=...' : 'https://example.com/file.pdf'}">
                    </label>
                    ${current.type === 'pdf' ? `
                        <label>
                            إرفاق PDF من الجهاز
                            <input type="file" accept="application/pdf" data-theory-file="${index}" data-file-field="url">
                        </label>
                        ${current.fileName ? `<p class="attached-file-name">تم الإرفاق: ${escapeHtml(current.fileName)}</p>` : ''}
                    ` : ''}
                ` : ''}
            </div>
        </article>
    `;
}

function renderImageSearchResults(index) {
    const search = state.imageSearch || {};
    if (search.blockIndex !== index) return '';

    if (search.message) {
        return `<p class="image-search-message">${escapeHtml(search.message)}</p>`;
    }

    if (!search.results?.length) return '';

    return `
        <div class="image-result-grid">
            ${search.results.map((image) => `
                <button type="button" data-pick-image="${index}" data-image-url="${escapeHtml(image.webformatURL || image.previewURL)}">
                    <img src="${escapeHtml(image.previewURL || image.webformatURL)}" alt="">
                    <span>إدراج</span>
                </button>
            `).join('')}
        </div>
    `;
}

function awardsView() {
    const badges = [
        ['هدف اليوم', `${state.dailyGoal}/4 مواد اليوم`, 'target'],
        ['جامع النقاط', `${state.xp} نقطة XP`, 'star'],
        ['سلسلة التعلم', `${state.streak} أيام متتالية`, 'fire'],
    ];

    return shell(`
        <section class="simple-page">
            <h1>الجوائز</h1>
            <div class="award-grid">
                ${badges.map(([title, text, iconName]) => `
                    <article class="award-card">
                        <span>${icon(iconName)}</span>
                        <h2>${title}</h2>
                        <p>${text}</p>
                    </article>
                `).join('')}
            </div>
        </section>
    `);
}

function leadersView() {
    const rows = [
        ['ميرا', 280],
        ['أنت', state.xp],
        ['عمر', 160],
        ['لينا', 120],
    ].sort((a, b) => b[1] - a[1]);

    return shell(`
        <section class="simple-page">
            <h1>المتصدرون</h1>
            <div class="leader-list">
                ${rows.map(([name, xp], index) => `
                    <article class="leader-row ${name === 'أنت' ? 'is-you' : ''}">
                        <span>${index + 1}</span>
                        <strong>${name}</strong>
                        <b>${xp} XP</b>
                    </article>
                `).join('')}
            </div>
        </section>
    `);
}

function render() {
    syncDailyGoal();
    const views = {
        home: homeView,
        learn: subjectsView,
        subjects: subjectsView,
        path: pathView,
        lesson: lessonPlayerView,
        awards: awardsView,
        leaders: leadersView,
    };

    app.innerHTML = (views[state.view] || homeView)();
}

function scrollMainTop() {
    requestAnimationFrame(() => {
        document.querySelector('.main-panel')?.scrollTo({ top: 0, behavior: 'instant' });
        window.scrollTo({ top: 0, behavior: 'instant' });
    });
}

app.addEventListener('click', (event) => {
    const button = event.target.closest('button');
    if (!button) return;

    if (button.dataset.view) {
        state.view = button.dataset.view;
        if (button.dataset.view === 'learn') {
            state.lessonMode = 'path';
            state.teacherPanel = false;
        }
        state.selectedAnswer = '';
        state.lastResult = '';
        saveState();
        render();
        scrollMainTop();
        return;
    }

    if (button.dataset.teacherPanel) {
        state.view = 'learn';
        state.teacherPanel = button.dataset.teacherPanel === 'open';
        state.lessonMode = 'path';
        saveState();
        render();
        scrollMainTop();
        return;
    }

    if (button.dataset.addTeacherRow) {
        const list = button.dataset.addTeacherRow;
        if (list === 'examples') {
            state.teacherDraft.examples.push({ title: '', body: '', answer: '' });
        }
        if (list === 'worksheet') {
            state.teacherDraft.worksheet.push({ question: '', options: '', answer: '' });
        }
        if (list === 'quiz') {
            state.teacherDraft.quiz.push({ question: '', options: '', answer: '', score: 1 });
        }
        saveState();
        render();
        return;
    }

    if (button.dataset.addTheoryBlock) {
        const selectedType = document.querySelector('[data-theory-add-select]')?.value || 'hook';
        if (!Array.isArray(state.teacherDraft.theoryBlocks)) state.teacherDraft.theoryBlocks = [];
        state.teacherDraft.theoryBlocks.push(defaultTheoryBlock(selectedType));
        saveState();
        render();
        return;
    }

    if (button.dataset.removeTheoryBlock) {
        const index = Number(button.dataset.removeTheoryBlock);
        if (Array.isArray(state.teacherDraft.theoryBlocks) && state.teacherDraft.theoryBlocks.length > 1) {
            state.teacherDraft.theoryBlocks.splice(index, 1);
            saveState();
            render();
        }
        return;
    }

    if (button.dataset.searchImages) {
        const index = Number(button.dataset.searchImages);
        const search = state.imageSearch?.blockIndex === index ? state.imageSearch : {};
        const query = String(search.query || '').trim();
        const type = search.type || 'vector';
        state.imageSearch = { blockIndex: index, query, type, results: [], message: 'جاري البحث...' };
        render();
        searchPixabayImages(index, query, type);
        return;
    }

    if (button.dataset.pickImage) {
        const index = Number(button.dataset.pickImage);
        const url = button.dataset.imageUrl || '';
        if (!Array.isArray(state.teacherDraft.theoryBlocks)) state.teacherDraft.theoryBlocks = [defaultTheoryBlock('hook')];
        state.teacherDraft.theoryBlocks[index] = {
            ...(state.teacherDraft.theoryBlocks[index] || defaultTheoryBlock('hook')),
            imageUrl: url,
        };
        state.imageSearch = { blockIndex: null, query: '', type: 'vector', results: [], message: '' };
        saveState();
        render();
        return;
    }

    if (button.dataset.resetTeacherDraft) {
        state.teacherDraft = defaultTeacherDraft();
        saveState();
        render();
        return;
    }

    if (button.dataset.saveTeacherUnit) {
        const saved = saveTeacherDraftToUnits();
        state.grade = saved.unit.grade;
        state.subject = saved.unit.subject;
        state.activeUnitId = saved.unit.id;
        state.activeLessonId = saved.lesson.id;
                state.teacherDraft = {
                    ...defaultTeacherDraft(),
                    grade: saved.unit.grade,
                    subject: saved.unit.subject,
                    unitNo: saved.unit.unitNo,
                    unitTitle: saved.unit.title,
                    lessonTitle: '',
                };
        saveState();
        render();
        scrollMainTop();
        return;
    }

    if (button.dataset.unitId) {
        const unit = unitsForSelection().find((item) => item.id === button.dataset.unitId);
        if (!unit) return;
        state.activeUnitId = unit.id;
        state.activeLessonId = unit.lessons[0]?.id || '';
        state.lessonMode = 'path';
        saveState();
        render();
        return;
    }

    if (button.dataset.learningLesson) {
        const unit = currentUnit();
        const index = unit.lessons.findIndex((lesson) => lesson.id === button.dataset.learningLesson);
        if (!lessonUnlocked(unit, index)) return;
        state.activeLessonId = button.dataset.learningLesson;
        state.lessonMode = 'lesson';
        state.lessonSection = 'theory';
        state.selectedAnswer = '';
        state.lastResult = '';
        state.currentQuestion = 0;
        state.currentTheoryBlock = 0;
        state.view = 'learn';
        saveState();
        render();
        scrollMainTop();
        return;
    }

    if (button.dataset.backLearning) {
        state.lessonMode = 'path';
        state.selectedAnswer = '';
        state.lastResult = '';
        saveState();
        render();
        scrollMainTop();
        return;
    }

    if (button.dataset.section) {
        state.lessonSection = button.dataset.section;
        state.currentQuestion = 0;
        state.currentTheoryBlock = 0;
        state.selectedAnswer = '';
        state.lastResult = '';
        saveState();
        render();
        return;
    }

    if (button.dataset.theoryPrev) {
        state.currentTheoryBlock = Math.max(0, Number(state.currentTheoryBlock || 0) - 1);
        saveState();
        render();
        return;
    }

    if (button.dataset.theoryNext) {
        const lesson = currentLesson();
        const blocks = lesson?.theory?.blocks?.length ? lesson.theory.blocks : [lesson?.theory || {}];
        const current = Number(state.currentTheoryBlock || 0);
        if (current < blocks.length - 1) {
            state.currentTheoryBlock = current + 1;
        } else {
            markSectionDone('theory');
            state.lessonSection = 'examples';
            state.currentTheoryBlock = 0;
        }
        saveState();
        render();
        scrollMainTop();
        return;
    }

    if (button.dataset.completeSection) {
        markSectionDone(button.dataset.completeSection);
        const order = ['theory', 'examples', 'worksheet', 'quiz'];
        const next = order[order.indexOf(button.dataset.completeSection) + 1];
        if (next) state.lessonSection = next;
        saveState();
        render();
        scrollMainTop();
        return;
    }

    if (button.dataset.learningAnswer) {
        state.selectedAnswer = button.dataset.learningAnswer;
        state.lastResult = '';
        render();
        return;
    }

    if (button.dataset.checkLearning) {
        const section = button.dataset.checkLearning;
        const lesson = currentLesson();
        const questionsList = lesson[section];
        const question = questionsList[state.currentQuestion] || questionsList[0];

        if (sectionDone(lesson.id, section)) {
            if (section === 'quiz') {
                state.lessonMode = 'path';
            } else {
                const order = ['theory', 'examples', 'worksheet', 'quiz'];
                state.lessonSection = order[order.indexOf(section) + 1] || 'quiz';
            }
            state.selectedAnswer = '';
            state.lastResult = '';
            saveState();
            render();
            scrollMainTop();
            return;
        }

        const correct = state.selectedAnswer === question.answer;
        state.lastResult = correct ? 'إجابة صحيحة!' : 'حاول مرة ثانية';

        if (correct) {
            const isLast = state.currentQuestion >= questionsList.length - 1;
            if (isLast) {
                markSectionDone(section);
                if (section === 'quiz') {
                    const progress = state.lessonProgress[lesson.id];
                    if (!progress.done) {
                        progress.done = true;
                        state.xp += lesson.xp;
                        state.dailyCompletions[dailyCompletionKey()] = true;
                        syncDailyGoal();
                    }
                }
            } else {
                state.currentQuestion += 1;
            }
            state.selectedAnswer = '';
        }

        saveState();
        render();
        return;
    }

    if (button.dataset.speakCurrent) {
        const lesson = currentLesson();
        const blocks = lesson?.theory?.blocks || [];
        const activeTheoryBlock = blocks[Math.min(Number(state.currentTheoryBlock || 0), Math.max(0, blocks.length - 1))];
        const text = state.lessonSection === 'theory'
            ? theoryBlockText(activeTheoryBlock || lesson.theory)
            : state.lessonSection === 'examples'
                ? lesson.examples.map((example) => `${example.title}. ${example.prompt}. ${example.steps.join('. ')}`).join('. ')
                : (lesson[state.lessonSection][state.currentQuestion]?.question || lesson.title);
        speakText(text);
        return;
    }

    if (button.dataset.grade) {
        state.grade = Number(button.dataset.grade);
        syncDailyGoal();
        saveState();
        render();
        return;
    }

    if (button.dataset.subject) {
        state.subject = button.dataset.subject;
        if (state.view !== 'home') state.view = 'path';
        saveState();
        render();
        if (state.view !== 'home') scrollMainTop();
        return;
    }

});

function updateTeacherDraft(target) {
    if (!(target instanceof HTMLElement)) return;

    if (target.dataset.teacherField) {
        const field = target.dataset.teacherField;
        state.teacherDraft[field] = field === 'grade' ? Number(target.value) : target.value;
        if (field === 'unitNo') state.teacherDraft.unitNo = Number(target.value);
        if (['grade', 'subject', 'unitNo'].includes(field)) {
            const unit = allLearningUnits().find((item) =>
                item.grade === Number(state.teacherDraft.grade || 1)
                && item.subject === state.teacherDraft.subject
                && Number(item.unitNo) === Number(state.teacherDraft.unitNo || 1)
            );
            if (unit) state.teacherDraft.unitTitle = unit.title;
        }
        saveState();
        return;
    }

    if (target.dataset.teacherList) {
        const list = target.dataset.teacherList;
        const index = Number(target.dataset.index);
        const field = target.dataset.field;
        const row = state.teacherDraft[list]?.[index];
        if (!row) return;
        row[field] = field === 'score' ? Number(target.value || 1) : target.value;
        saveState();
    }

    if (target.dataset.theoryBlock) {
        const index = Number(target.dataset.theoryBlock);
        const field = target.dataset.field;
        if (!Array.isArray(state.teacherDraft.theoryBlocks)) state.teacherDraft.theoryBlocks = [defaultTheoryBlock('hook')];
        const current = state.teacherDraft.theoryBlocks[index] || defaultTheoryBlock('hook');
        if (field === 'type') {
            state.teacherDraft.theoryBlocks[index] = {
                ...defaultTheoryBlock(target.value),
                ...current,
                type: target.value,
                emoji: current.emoji || defaultTheoryBlock(target.value).emoji,
                title: current.title || defaultTheoryBlock(target.value).title,
            };
        } else {
            state.teacherDraft.theoryBlocks[index] = { ...current, [field]: target.value };
        }
        saveState();
        if (field === 'type') render();
    }
}

function handleTeacherFile(target) {
    if (!(target instanceof HTMLInputElement) || !target.dataset.theoryFile || !target.files?.[0]) return false;
    const index = Number(target.dataset.theoryFile);
    const field = target.dataset.fileField || 'url';
    const file = target.files[0];
    if (!Array.isArray(state.teacherDraft.theoryBlocks)) state.teacherDraft.theoryBlocks = [defaultTheoryBlock('hook')];
    const current = state.teacherDraft.theoryBlocks[index] || defaultTheoryBlock('hook');
    const reader = new FileReader();

    reader.onload = () => {
        state.teacherDraft.theoryBlocks[index] = {
            ...current,
            [field]: reader.result,
            fileName: file.name,
        };
        saveState();
        render();
    };

    reader.readAsDataURL(file);
    return true;
}

async function searchPixabayImages(index, query, type = 'vector') {
    try {
        const response = await fetch(`/api/images?q=${encodeURIComponent(query || 'education illustration')}&type=${encodeURIComponent(type)}`);
        const data = await response.json();
        const hits = Array.isArray(data.hits) ? data.hits : [];
        state.imageSearch = {
            blockIndex: index,
            query,
            type,
            results: hits.slice(0, 8),
            message: data.message || (hits.length ? '' : 'لا توجد نتائج مناسبة. جرّب كلمة إنجليزية مثل: alphabet, fractions, solar system.'),
        };
    } catch {
        state.imageSearch = {
            blockIndex: index,
            query,
            type,
            results: [],
            message: 'تعذر البحث الآن. تأكد من إعداد Pixabay API.',
        };
    }

    render();
}

app.addEventListener('input', (event) => {
    if (event.target instanceof HTMLElement && event.target.dataset.imageQuery) {
        const index = Number(event.target.dataset.imageQuery);
        state.imageSearch = {
            ...(state.imageSearch || {}),
            blockIndex: index,
            query: event.target.value,
        };
        return;
    }

    if (event.target instanceof HTMLElement && event.target.dataset.imageType) {
        const index = Number(event.target.dataset.imageType);
        state.imageSearch = {
            ...(state.imageSearch || {}),
            blockIndex: index,
            type: event.target.value,
        };
        return;
    }

    updateTeacherDraft(event.target);
});

app.addEventListener('change', (event) => {
    if (handleTeacherFile(event.target)) return;
    updateTeacherDraft(event.target);
});

render();
