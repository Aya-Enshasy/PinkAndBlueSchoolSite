import './bootstrap';
import Alpine from 'alpinejs';
import confetti from 'canvas-confetti';
import { Howl } from 'howler';

window.Alpine = Alpine;
Alpine.start();

const app = document.querySelector('#app');

if (app) {
const STORAGE_KEY = 'pink-blue-school-state-v7';
const STUDENT_AUTH_KEY = 'pink-blue-student-auth-v1';
const APP_MODE = app.dataset.initialView || 'student';
const IS_TEACHER_MODE = APP_MODE === 'teacher';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

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

const studentAuth = loadStudentAuth();
const state = loadState();
applyInitialRoute();
recordVisit();
syncStudentSession();
loadPublishedLearningUnits();

function loadStudentAuth() {
    try {
        const saved = JSON.parse(localStorage.getItem(STUDENT_AUTH_KEY));
        if (saved?.mode === 'registered' && saved?.token) {
            return {
                mode: 'registered',
                token: saved.token,
                student: saved.student || null,
                status: '',
                loading: false,
            };
        }
    } catch {
        // Fall through to a fresh student gate.
    }

    return { mode: 'pending', token: '', student: null, status: '', loading: false };
}

function persistStudentAuth() {
    if (state.studentAuth?.mode === 'registered' && state.studentAuth?.token) {
        localStorage.setItem(STUDENT_AUTH_KEY, JSON.stringify({
            mode: 'registered',
            token: state.studentAuth.token,
            student: state.studentAuth.student,
        }));
        return;
    }

    localStorage.removeItem(STUDENT_AUTH_KEY);
}

function studentAuthHeaders(extra = {}) {
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...extra,
    };

    if (CSRF_TOKEN) headers['X-CSRF-TOKEN'] = CSRF_TOKEN;
    if (state.studentAuth?.token) headers.Authorization = `Bearer ${state.studentAuth.token}`;

    return headers;
}

function isRegisteredStudent() {
    return !IS_TEACHER_MODE && state.studentAuth?.mode === 'registered' && Boolean(state.studentAuth?.token);
}

function isGuestStudent() {
    return !IS_TEACHER_MODE && state.studentAuth?.mode === 'guest';
}

function isStudentReady() {
    return IS_TEACHER_MODE || isRegisteredStudent() || isGuestStudent();
}

function resetLearningStateForStudent() {
    state.xp = 0;
    state.streak = 1;
    state.dailyGoal = 0;
    state.hearts = 3;
    state.dailyCompletions = {};
    state.lessonProgress = {};
    state.activeUnitId = '';
    state.activeLessonId = '';
    state.lessonMode = 'path';
    state.lessonSection = 'theory';
    state.currentTheoryBlock = 0;
    state.blockInteractions = {};
}

function applyStudentPayload(payload = {}) {
    const student = payload.student || state.studentAuth.student;
    state.studentAuth = {
        ...state.studentAuth,
        mode: payload.mode || 'registered',
        student,
        status: '',
        loading: false,
    };

    if (student?.gradeNumber) {
        state.grade = Number(student.gradeNumber);
    }

    const progressRows = Array.isArray(payload.progress) ? payload.progress : [];
    state.lessonProgress = {};
    progressRows.forEach((row) => {
        if (!row.lessonKey) return;
        state.lessonProgress[row.lessonKey] = {
            sections: row.sections || {},
            done: Boolean(row.completed),
        };
    });

    if (payload.summary) {
        state.xp = Number(payload.summary.xp || state.xp || 0);
        if (payload.summary.lessonsCompleted > 0) {
            state.dailyGoal = Math.min(4, Number(payload.summary.lessonsCompleted || 0));
        }
    } else if (progressRows.length) {
        state.xp = progressRows.reduce((total, row) => total + Number(row.xp || 0), 0);
    }
}

async function syncStudentSession() {
    if (IS_TEACHER_MODE || !studentAuth.token) return;

    try {
        const response = await fetch('/api/student/me', {
            headers: studentAuthHeaders({ 'Content-Type': 'application/json' }),
        });

        if (!response.ok) throw new Error('student session failed');

        const payload = await response.json();
        if (payload.mode !== 'registered') {
            state.studentAuth = { mode: 'pending', token: '', student: null, status: '', loading: false };
            persistStudentAuth();
            render();
            return;
        }

        applyStudentPayload(payload);
        persistStudentAuth();
        saveState();
        render();
    } catch {
        state.studentAuth = {
            mode: 'pending',
            token: '',
            student: null,
            status: 'انتهت جلسة الطالب. أدخل الهوية مرة أخرى.',
            loading: false,
        };
        persistStudentAuth();
        render();
    }
}

async function enterGuestMode() {
    try {
        await fetch('/api/student/guest', {
            method: 'POST',
            headers: studentAuthHeaders(),
            body: JSON.stringify({}),
        });
    } catch {
        // Guest mode can still continue locally.
    }

    state.studentAuth = { mode: 'guest', token: '', student: null, status: '', loading: false };
    resetLearningStateForStudent();
    persistStudentAuth();
    saveState();
    render();
}

async function logoutStudent() {
    if (isRegisteredStudent()) {
        try {
            await fetch('/api/student/logout', {
                method: 'POST',
                headers: studentAuthHeaders(),
                body: JSON.stringify({}),
            });
        } catch {
            // Logout locally even if the network request fails.
        }
    }

    state.studentAuth = { mode: 'pending', token: '', student: null, status: 'اختر طريقة الدخول للمتابعة.', loading: false };
    resetLearningStateForStudent();
    persistStudentAuth();
    saveState();
    render();
}

function applyInitialRoute() {
    const initialView = APP_MODE;

    if (initialView === 'teacher') {
        state.view = 'learn';
        state.teacherPanel = true;
        return;
    }

    state.teacherPanel = false;
}

function loadState() {
    try {
        const saved = studentAuth.mode === 'registered' ? JSON.parse(localStorage.getItem(STORAGE_KEY)) : null;
        const savedSubject = subjects.some((subject) => subject.id === saved?.subject) ? saved.subject : 'english';
        return {
            view: saved?.view || 'home',
            subject: savedSubject,
            grade: Number(saved?.grade || 1),
            xp: Number(saved?.xp || 0),
            streak: Number(saved?.streak || 1),
            dailyGoal: Math.min(4, Number(saved?.dailyGoal || 0)),
            hearts: Math.max(0, Math.min(3, Number(saved?.hearts ?? 3))),
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
            blockInteractions: saved?.blockInteractions && typeof saved.blockInteractions === 'object' ? saved.blockInteractions : {},
            imageSearch: { blockIndex: null, query: '', type: 'vector', results: [], message: '' },
            studentAuth,
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
            hearts: 3,
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
            blockInteractions: {},
            imageSearch: { blockIndex: null, query: '', type: 'vector', results: [], message: '' },
            studentAuth,
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

async function loadPublishedLearningUnits() {
    try {
        const response = await fetch('/api/learning-units', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) return;

        const units = await response.json();
        if (!Array.isArray(units)) return;

        learningUnits.splice(0, learningUnits.length, ...units);
        render();
    } catch {
        // Student mode still works with local lessons if the API is unavailable.
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
    persistStudentAuth();

    if (IS_TEACHER_MODE || isRegisteredStudent()) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(persisted));
        scheduleStudentProgressSync();
        return;
    }

    localStorage.removeItem(STORAGE_KEY);
}

let progressSyncTimer = null;

function studentProgressPayload() {
    if (!isRegisteredStudent()) return null;

    const lesson = currentLesson();
    const unit = currentUnit();
    if (!lesson) return null;

    const lessonProgress = state.lessonProgress?.[lesson.id] || {};

    return {
        lesson_key: lesson.id,
        lesson_title: lesson.title || '',
        grade: Number(state.grade || 1),
        subject: state.subject,
        unit_no: Number(unit?.unitNo || 1),
        xp: lessonProgress.done ? Number(lesson.xp || 0) : 0,
        streak: Number(state.streak || 1),
        hearts: Number(state.hearts ?? 3),
        progress_percent: lessonProgressPercent(lesson),
        current_block: Number(state.currentTheoryBlock || 0),
        completed: Boolean(lessonProgress.done),
        sections: lessonProgress.sections || {},
        activity: {
            lessonMode: state.lessonMode,
            lessonSection: state.lessonSection,
            blockInteractions: state.blockInteractions || {},
            dailyGoal: state.dailyGoal,
            savedAt: new Date().toISOString(),
        },
    };
}

function scheduleStudentProgressSync() {
    if (!isRegisteredStudent()) return;

    clearTimeout(progressSyncTimer);
    progressSyncTimer = setTimeout(syncStudentProgress, 550);
}

async function syncStudentProgress() {
    const payload = studentProgressPayload();
    if (!payload) return;

    try {
        await fetch('/api/student/progress', {
            method: 'POST',
            headers: studentAuthHeaders(),
            body: JSON.stringify(payload),
        });
    } catch {
        // Keep the local state; the next progress change will retry.
    }
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
                emoji: '',
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
    const localDraftUnits = IS_TEACHER_MODE ? (state.customUnits || []) : [];
    [...learningUnits, ...localDraftUnits].forEach((unit) => {
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

function markLessonDone(lesson = currentLesson()) {
    if (!lesson) return;
    if (!state.lessonProgress[lesson.id]) {
        state.lessonProgress[lesson.id] = { sections: {}, done: false };
    }

    const progress = state.lessonProgress[lesson.id];
    progress.sections = { ...(progress.sections || {}), theory: true };

    if (!progress.done) {
        progress.done = true;
        state.xp += Number(lesson.xp || 0);
        state.dailyCompletions[dailyCompletionKey()] = true;
        syncDailyGoal();
    }
}

function lessonBlocks(lesson = currentLesson()) {
    if (!lesson) return [];
    return lesson.theory?.blocks?.length ? lesson.theory.blocks : [{
        emoji: '',
        title: lesson.theory?.title || lesson.title,
        body: lesson.theory?.body || '',
    }];
}

function lessonProgressPercent(lesson = currentLesson()) {
    if (!lesson) return 0;
    if (lessonDone(lesson.id)) return 100;
    if (sectionDone(lesson.id, 'theory')) return 100;

    if (currentLesson()?.id === lesson.id && state.lessonMode === 'lesson') {
        const total = Math.max(1, lessonBlocks(lesson).length);
        return Math.round((Math.min(Number(state.currentTheoryBlock || 0), total - 1) / total) * 100);
    }

    return 0;
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

function theoryInteractionKey(index) {
    return `${currentLesson()?.id || 'lesson'}:${index}`;
}

function interactiveTheoryTypes() {
    return ['matching', 'ordering', 'choice', 'writing'];
}

function isInteractiveTheoryBlock(block) {
    if (block?.type === 'audio') {
        return audioChoices(block).length > 0;
    }

    return interactiveTheoryTypes().includes(block?.type);
}

function getTheoryInteraction(index) {
    const key = theoryInteractionKey(index);
    if (!state.blockInteractions[key]) {
        state.blockInteractions[key] = {
            choice: '',
            text: '',
            selectedLeft: '',
            pairs: {},
            order: [],
            result: '',
            rewarded: false,
        };
    }

    return state.blockInteractions[key];
}

function resetTheoryInteraction(index) {
    state.blockInteractions[theoryInteractionKey(index)] = {
        choice: '',
        text: '',
        selectedLeft: '',
        pairs: {},
        order: [],
        result: '',
        rewarded: false,
    };
}

function blockLines(value) {
    return optionList(value);
}

function audioChoices(block) {
    const options = Array.isArray(block.options) && block.options.length
        ? block.options
        : optionList(block.options || block.items);
    return options.filter(Boolean);
}

function resetLessonRun(lessonId) {
    if (!lessonId) return;
    state.hearts = 3;

    Object.keys(state.blockInteractions || {}).forEach((key) => {
        if (key.startsWith(`${lessonId}:`)) {
            delete state.blockInteractions[key];
        }
    });
}

function normalizeAnswer(value = '') {
    return String(value)
        .trim()
        .replace(/\s+/g, ' ')
        .toLowerCase();
}

function matchingPairs(block) {
    if (Array.isArray(block.pairs) && block.pairs.length) {
        return block.pairs
            .map((pair) => ({
                left: String(pair.left || '').trim(),
                right: String(pair.right || '').trim(),
            }))
            .filter((pair) => pair.left || pair.right);
    }

    const left = blockLines(block.leftItems);
    const right = blockLines(block.rightItems);
    return left.map((item, index) => ({ left: item, right: right[index] || '' })).filter((pair) => pair.left);
}

function hashString(value = '') {
    return String(value).split('').reduce((hash, char) => ((hash << 5) - hash + char.charCodeAt(0)) | 0, 0);
}

function seededShuffle(items, seedValue = '') {
    const result = [...items];
    let seed = Math.abs(hashString(seedValue)) || 1;

    for (let index = result.length - 1; index > 0; index -= 1) {
        seed = (seed * 9301 + 49297) % 233280;
        const swapIndex = Math.floor((seed / 233280) * (index + 1));
        [result[index], result[swapIndex]] = [result[swapIndex], result[index]];
    }

    return result;
}

function matchCardMark(value = '', index = 0) {
    const clean = String(value).trim();
    const first = Array.from(clean)[0] || String(index + 1);
    return /[a-z]/i.test(first) ? first.toUpperCase() : first;
}

function orderedItemsForBlock(block, index) {
    const items = blockLines(block.items);
    const interaction = getTheoryInteraction(index);
    if (!interaction.order?.length || interaction.order.length !== items.length) {
        interaction.order = items.length > 1 ? [...items].reverse() : [...items];
    }

    return interaction.order;
}

function isTheoryBlockSolved(block, index) {
    if (!isInteractiveTheoryBlock(block)) return true;
    return getTheoryInteraction(index).result === 'correct';
}

function rewardInteractiveBlock(block, index) {
    const interaction = getTheoryInteraction(index);
    if (interaction.rewarded) return;
    state.xp += Math.max(1, Number(block.score || 1));
    interaction.rewarded = true;
}

function triggerLearningEffect(type = 'success') {
    playLearningTone(type);

    if (type !== 'success' && type !== 'finish') return;

    const colors = ['#d22d78', '#215b9f', '#58cc02', '#ffc800', '#ff6b00'];

    confetti({
        particleCount: type === 'finish' ? 150 : 64,
        spread: type === 'finish' ? 84 : 58,
        startVelocity: type === 'finish' ? 46 : 32,
        scalar: type === 'finish' ? 1.05 : 0.82,
        origin: { x: 0.5, y: type === 'finish' ? 0.5 : 0.72 },
        colors,
        disableForReducedMotion: true,
    });

    if (type === 'finish') {
        window.setTimeout(() => confetti({
            particleCount: 90,
            angle: 60,
            spread: 65,
            origin: { x: 0, y: 0.72 },
            colors,
            disableForReducedMotion: true,
        }), 180);
        window.setTimeout(() => confetti({
            particleCount: 90,
            angle: 120,
            spread: 65,
            origin: { x: 1, y: 0.72 },
            colors,
            disableForReducedMotion: true,
        }), 180);
    }
}

const learningSoundCache = {};

function toneDataUrl(frequency = 520, duration = 0.18) {
    const sampleRate = 22050;
    const samples = Math.floor(sampleRate * duration);
    const buffer = new ArrayBuffer(44 + samples * 2);
    const view = new DataView(buffer);
    const write = (offset, value) => {
        for (let index = 0; index < value.length; index += 1) {
            view.setUint8(offset + index, value.charCodeAt(index));
        }
    };

    write(0, 'RIFF');
    view.setUint32(4, 36 + samples * 2, true);
    write(8, 'WAVE');
    write(12, 'fmt ');
    view.setUint32(16, 16, true);
    view.setUint16(20, 1, true);
    view.setUint16(22, 1, true);
    view.setUint32(24, sampleRate, true);
    view.setUint32(28, sampleRate * 2, true);
    view.setUint16(32, 2, true);
    view.setUint16(34, 16, true);
    write(36, 'data');
    view.setUint32(40, samples * 2, true);

    for (let sample = 0; sample < samples; sample += 1) {
        const fade = Math.min(1, sample / 900, (samples - sample) / 1400);
        const value = Math.sin((sample / sampleRate) * Math.PI * 2 * frequency) * 0.28 * fade;
        view.setInt16(44 + sample * 2, value * 32767, true);
    }

    const bytes = new Uint8Array(buffer);
    let binary = '';
    bytes.forEach((byte) => { binary += String.fromCharCode(byte); });
    return `data:audio/wav;base64,${window.btoa(binary)}`;
}

function playLearningTone(type = 'success') {
    try {
        const frequency = type === 'error' ? 180 : type === 'finish' ? 720 : 540;
        const key = `${type}-${frequency}`;
        if (!learningSoundCache[key]) {
            learningSoundCache[key] = new Howl({
                src: [toneDataUrl(frequency, type === 'finish' ? 0.28 : 0.18)],
                volume: type === 'error' ? 0.28 : 0.36,
                html5: false,
            });
        }
        learningSoundCache[key].play();
    } catch {
        // Audio feedback is optional; browsers can block it in some contexts.
    }
}

function defaultTheoryBlock(type = 'hook') {
    const base = {
        type,
        emoji: '',
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
        leftItems: '',
        rightItems: '',
    };

    const presets = {
        hook: { emoji: '', title: 'فكرة تشويقية' },
        definition: { emoji: '', title: 'تعريف', term: '', symbol: '' },
        idea: { emoji: '', title: 'الفكرة المهمة' },
        example: { emoji: '', title: 'مثال سريع' },
        tip: { emoji: '', title: 'تذكّر' },
        youtube: { emoji: '', title: 'فيديو مساعد' },
        pdf: { emoji: '', title: 'ملف PDF مساعد' },
        matching: { title: 'وصل بين العناصر' },
        ordering: { title: 'رتب العناصر' },
        choice: { title: 'اختر الإجابة الصحيحة' },
        writing: { title: 'اكتب الإجابة' },
        audio: { title: 'استمع وكرر' },
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
    return [block.title, block.term, block.question, block.body, block.items, block.result, block.note].filter(Boolean).join('. ');
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
            chips: ['سؤال', 'كلمة وصورة', 'محادثة', 'استماع'],
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
    const interactive = [
        ['choice', 'اختيار متعدد'],
        ['matching', 'وصل'],
        ['ordering', 'ترتيب'],
        ['writing', 'كتابة'],
        ['audio', 'استماع'],
    ];

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

    return [...(bySubject[subjectId] || bySubject.arabic), ...interactive];
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
            hook: { title: 'السؤال الذي يظهر للطالب', body: 'توجيه قصير', items: 'الخيارات، كل خيار بسطر', imageUrl: 'صورة أو أيقونة للسؤال' },
            definition: { term: 'الكلمة الإنجليزية', symbol: 'المعنى بالعربي أو وسم قصير', body: 'جملة مثال', imageUrl: 'صورة للكلمة' },
            example: { title: 'عنوان المحادثة', body: 'السؤال أو التعليمات', items: 'خيارات المحادثة، كل خيار بسطر', result: 'العبارة الصحيحة / الإجابة النموذجية', note: 'تلميح نطق' },
            idea: { title: 'عنوان القاعدة أو الاستخدام', body: 'قاعدة بسيطة', items: 'أمثلة الاستخدام، كل مثال بسطر' },
            tip: { title: 'ملاحظة نطق', body: 'ماذا يجب أن يلاحظ الطالب؟', items: 'عبارات تدريب' },
            youtube: { title: 'عنوان الاستماع', body: 'ماذا يسمع الطالب؟', url: 'رابط YouTube للاستماع' },
            pdf: { title: 'عنوان الملف', body: 'ماذا يراجع الطالب؟', url: 'رابط PDF أو الملف المرفق' },
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
            points: ['اقرأ الخطوة الحالية.', 'تفاعل مع النشاط.', 'اجمع النقاط وافتح الخطوة التالية.'],
        },
        examples: [],
        worksheet: [],
        quiz: [],
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
        title: draft.unitTitle || sourceUnit?.title || `الوحدة ${unitNo}`,
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

function studentGateView() {
    const status = state.studentAuth?.status || 'أدخل رقم هوية الطالب المسجل في المدرسة حتى يتم حفظ تقدمك.';

    return `
        <section class="student-login-page">
            <div class="student-login-card">
                <div class="student-login-brand">
                    <img src="/assets/pink-blue-logo.png" alt="مدرسة بينك أند بلو">
                    <span>منصة الطلاب</span>
                </div>
                <div class="student-login-copy">
                    <span>دخول آمن للطلاب</span>
                    <h1>ادخل بهويتك أو جرّب كزائر</h1>
                    <p>الطالب المسجل يحفظ نقاطه ودروسه تلقائيا، والزائر يستطيع التجربة بدون حفظ أي تقدم.</p>
                </div>

                <form class="student-login-form" data-student-login-form>
                    <label>
                        <span>رقم هوية الطالب</span>
                        <input name="student_id_number" inputmode="numeric" autocomplete="username" placeholder="مثال: 123456789" required>
                    </label>
                    <label>
                        <span>السنة الدراسية <small>اختياري</small></span>
                        <input name="academic_year" placeholder="مثال: 2026/2027">
                    </label>
                    <button type="submit" ${state.studentAuth?.loading ? 'disabled' : ''}>
                        ${state.studentAuth?.loading ? 'جار التحقق...' : 'دخول الطالب'}
                    </button>
                </form>

                <button class="guest-entry-btn" data-student-guest="true">الدخول كزائر بدون حفظ التقدم</button>
                <p class="student-login-status">${escapeHtml(status)}</p>
            </div>
        </section>
    `;
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
    const focusClass = ['lesson', 'complete'].includes(state.lessonMode) ? ' lesson-focus-shell' : '';
    return `
        <div class="platform${focusClass}">
            <aside class="sidebar">
                <button class="brand" data-view="home" aria-label="الرئيسية">
                    <span class="brand-mark"><img src="/assets/pink-blue-logo.png" alt=""></span>
                    <span>مدرسة بينك أند بلو</span>
                </button>
                <nav class="side-nav" aria-label="التنقل الرئيسي">
                    ${navButton('home', 'الرئيسية', 'home')}
                    ${navButton('learn', 'التعلم', 'compass')}
                    ${navButton('awards', 'الجوائز', 'trophy')}
                    ${navButton('leaders', 'المتصدرون', 'medal')}
                </nav>
            </aside>
            <main class="main-panel">${content}</main>
            <nav class="mobile-nav" aria-label="التنقل للجوال">
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

function teacherPanelButton(label = 'لوحة المعلم', className = 'teacher-open-btn') {
    if (!IS_TEACHER_MODE) return '';

    return `<button class="${className}" data-teacher-panel="open">${label}</button>`;
}

function studentName() {
    return isRegisteredStudent()
        ? (state.studentAuth.student?.name || 'طالب')
        : 'زائر';
}

function studentAccountChip() {
    if (IS_TEACHER_MODE) return '';

    return `
        <div class="student-account-chip ${isGuestStudent() ? 'is-guest' : 'is-registered'}">
            <span>${isGuestStudent() ? 'تجربة زائر' : 'طالب مسجل'}</span>
            <strong>${escapeHtml(studentName())}</strong>
            <button type="button" data-student-logout="true">${isGuestStudent() ? 'تغيير الدخول' : 'خروج'}</button>
        </div>
    `;
}

function statsBar() {
    return `
        <div class="stats-row">
            ${studentAccountChip()}
            <div class="stat-pill stat-fire" title="أيام التعلم المتتالية">${icon('fire')}<strong>${state.streak}</strong></div>
            <div class="stat-pill stat-xp" title="نقاط من حل الدروس">${icon('star')}<strong>${state.xp} نقطة</strong></div>
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
                    <strong>${xpIntoLevel} / 200 نقطة</strong>
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
                    <h1>أهلا ${escapeHtml(studentName())}! <span class="wave"></span></h1>
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
                        <button class="grade-chip ${state.grade === index + 1 ? 'is-active' : ''}" data-grade="${index + 1}" ${isRegisteredStudent() && state.grade !== index + 1 ? 'disabled' : ''}>
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
    if (IS_TEACHER_MODE && state.teacherPanel) return teacherDashboardView();
    if (['lesson', 'complete'].includes(state.lessonMode)) return lessonPlayerView();

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
                    <p>الوحدات والدروس تفتح بالترتيب. كل درس يظهر كرحلة خطوات وتحديات قصيرة.</p>
                </div>
                ${teacherPanelButton()}
            </header>

            <div class="unit-switcher">
                ${units.map((item) => `
                    <button class="unit-pill ${item.id === unit.id ? 'is-active' : ''}" data-unit-id="${item.id}">
                        الوحدة ${item.unitNo}: ${item.title}
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
                            <span>الوحدة ${unit.unitNo}</span>
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
    const emptyIntro = IS_TEACHER_MODE
        ? 'لا توجد وحدات بعد لهذا الصف والمادة. ابدأ من لوحة المعلم وأدخل محتواك.'
        : 'لا يوجد محتوى منشور بعد لهذا الصف والمادة. سيظهر هنا عندما يضيف المعلم الدروس.';
    const emptyBody = IS_TEACHER_MODE
        ? 'أضف وحدة، ثم أضف درساً تحتها، وبعد الحفظ سيظهر المسار هنا للطالب.'
        : 'تابع لاحقا بعد نشر وحدات ودروس جديدة من المعلم.';

    return shell(`
        <section class="learn-page">
            <header class="learn-hero">
                <div class="learn-subject-mark" style="--subject:${subject.color};--subject-bg:${subject.bg}">
                    ${subjectIcon(subject)}
                </div>
                <div>
                    <span>${grades[state.grade - 1]} - ${subject.name}</span>
                    <h1>مسار التعلم</h1>
                    <p>${emptyIntro}</p>
                </div>
                ${teacherPanelButton()}
            </header>
            <section class="empty-learning-card">
                <h2>لا يوجد محتوى بعد</h2>
                <p>${emptyBody}</p>
                ${teacherPanelButton('إضافة أول وحدة', 'primary-action')}
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

function legacyLessonPlayerView() {
    const lesson = currentLesson();
    const unit = currentUnit();
    if (!lesson || !unit) return emptyLearningView(currentSubject());

    const blocks = lessonBlocks(lesson);
    const index = Math.min(Number(state.currentTheoryBlock || 0), Math.max(0, blocks.length - 1));
    const flowProgress = lessonDone(lesson.id) ? 100 : Math.round(((index + 1) / Math.max(1, blocks.length)) * 100);
    const hearts = Math.max(0, Math.min(3, Number(state.hearts ?? 3)));

    return shell(`
        <section class="lesson-player-page lesson-flow-page">
            <header class="lesson-focus-top">
                <button class="circle-back in-flow" data-back-learning="true">${icon('back')}</button>
                <div class="lesson-player-title focus-title">
                    <small>${unit.title}</small>
                    <strong>${lesson.title}</strong>
                    <div class="player-progress"><span style="width:${flowProgress}%"></span></div>
                </div>
                <div class="lesson-focus-status">
                    <span class="heart-meter" title="المحاولات" aria-label="المحاولات">${Array.from({ length: 3 }).map((_, heartIndex) => heartIndex < hearts ? '♥' : '♡').join('')}</span>
                    <span class="focus-xp-pill">${state.xp} نقطة</span>
                    <button class="sound-btn" data-speak-current="true" aria-label="استمع للنص">🔊</button>
                </div>
            </header>

            <article class="duo-lesson-card lesson-flow-card">
                ${renderTheorySection(lesson)}
            </article>
        </section>
    `);
}

function renderLessonSection(lesson) {
    return renderTheorySection(lesson);
}

function legacyRenderTheorySection(lesson) {
    const blocks = lessonBlocks(lesson);
    const index = Math.min(state.currentTheoryBlock || 0, blocks.length - 1);
    const block = blocks[index];
    const isLast = index >= blocks.length - 1;
    const needsInteraction = isInteractiveTheoryBlock(block);
    const solved = isTheoryBlockSolved(block, index);
    const helper = needsInteraction && !solved
        ? 'حل النشاط حتى يفتح زر التالي'
        : (isLast ? 'آخر خطوة في هذا الدرس' : 'خطوة واحدة كل مرة');

    return `
        <div class="lesson-stage theory-stage">
            ${renderTheoryBlock(block, index)}
            <footer class="theory-actions">
                <button class="soft-action" data-theory-prev="true" ${index === 0 ? 'disabled' : ''}>السابق</button>
                <span class="lesson-step-helper">خطوة ${index + 1} من ${blocks.length} · ${helper}</span>
                <button class="primary-action" data-theory-next="true" ${needsInteraction && !solved ? 'disabled' : ''}>
                    ${needsInteraction && !solved ? 'حل النشاط أولاً' : (isLast ? 'إنهاء الدرس' : 'التالي')}
                </button>
            </footer>
        </div>
    `;
}

function legacyRenderTheoryBlock(block, index) {
    const subject = currentSubject();
    const typeLabel = {
        hook: 'مقدمة',
        definition: 'تعريف',
        idea: 'فكرة',
        example: 'مثال',
        tip: 'تلميح',
        youtube: 'فيديو',
        pdf: 'PDF',
        matching: 'وصل',
        ordering: 'ترتيب',
        choice: 'سؤال',
        writing: 'كتابة',
        audio: 'استماع',
    }[block.type] || 'شرح';
    const items = optionList(block.items);
    const lines = splitLessonLines(block.items || block.body);
    const visual = renderTheoryVisual(block, subject);
    const bodyClass = visual ? 'duo-choice-body has-visual' : 'duo-choice-body no-visual';
    const speech = block.body || block.note || 'اقرأ هذه الخطوة بهدوء، ثم اضغط التالي.';

    if (block.type === 'matching') {
        return renderMatchingTheoryBlock(block, index, subject);
    }

    if (block.type === 'ordering') {
        return renderOrderingTheoryBlock(block, index, subject);
    }

    if (block.type === 'choice') {
        return renderChoiceTheoryBlock(block, index, subject);
    }

    if (block.type === 'writing') {
        return renderWritingTheoryBlock(block, index, subject);
    }

    if (block.type === 'audio') {
        return renderAudioTheoryBlock(block, index, subject);
    }

    if (block.type === 'youtube') {
        const embed = youtubeEmbedUrl(block.url);
        return `
            <section class="duo-activity-card media-card pop-card" style="--delay:${index * 70}ms;--subject:${subject.color}">
                <div class="activity-progress"><span style="width:${Math.max(18, (index + 1) * 22)}%"></span></div>
                <div class="lesson-card-intro">
                    <span class="lesson-card-badge">فيديو</span>
                    <strong>${escapeHtml(block.title || 'فيديو مساعد')}</strong>
                    ${block.body ? `<p>${escapeHtml(block.body)}</p>` : '<p>شاهد الفيديو ثم كمل.</p>'}
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
                <div class="lesson-card-intro">
                    <span class="lesson-card-badge">ملف مساعد</span>
                    <strong>${escapeHtml(block.title || 'ملف PDF مساعد')}</strong>
                    ${block.body ? `<p>${escapeHtml(block.body)}</p>` : '<p>افتح الملف المساعد وراجع الفكرة.</p>'}
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
            <div class="lesson-card-intro">
                <span class="lesson-card-badge">${escapeHtml(typeLabel)}</span>
                <strong>${escapeHtml(block.title || block.term || 'شرح الدرس')}</strong>
                <p>${escapeHtml(speech)}</p>
            </div>
            <div class="${bodyClass}">
                ${visual}
                ${renderSubjectLessonContent(block, subject, lines, items)}
            </div>
        </section>
    `;
}

function renderInteractiveFeedback(interaction, block = {}) {
    if (!interaction.result) return '<div class="interactive-feedback is-empty"></div>';
    const success = interaction.result === 'correct';
    const points = Math.max(1, Number(block.score || 1));
    return `
        <div class="interactive-feedback ${success ? 'success' : 'error'}">
            <strong>${success ? 'رائع!' : 'حاول مرة أخرى'}</strong>
            <span>${success ? `+${points} نقطة` : (block.note || 'راجع التلميح، ثم جرّب من جديد.')}</span>
        </div>
    `;
}

function renderInteractivePrompt(block, typeLabel, helper) {
    return `
        <header class="interactive-question-header">
            <span class="interactive-kicker">${escapeHtml(typeLabel)}</span>
            <h2>${escapeHtml(block.question || block.title || typeLabel)}</h2>
            <p>${escapeHtml(block.body || block.note || helper)}</p>
        </header>
    `;
}

function renderChoiceTheoryBlock(block, index, subject) {
    const interaction = getTheoryInteraction(index);
    const resultClass = interaction.result ? `is-${interaction.result}` : '';
    const options = Array.isArray(block.options) && block.options.length
        ? block.options
        : optionList(block.options || block.items);
    const choices = options.length ? options : ['نعم', 'لا'];

    return `
        <section class="duo-activity-card theory-choice interactive-theory-card ${resultClass} pop-card" style="--delay:${index * 70}ms;--subject:${subject.color}">
            <div class="activity-progress"><span style="width:${Math.max(18, (index + 1) * 22)}%"></span></div>
            ${renderInteractivePrompt(block, 'اختر الإجابة الصحيحة', 'اقرأ السؤال بهدوء، ثم اختر جواباً واحداً.')}
            <div class="interactive-question-body">
                ${renderTheoryVisual(block, subject)}
                <div class="interactive-choice-stack">
                ${choices.map((choice, choiceIndex) => `
                    <button class="${interaction.choice === choice ? 'is-picked' : ''}" data-theory-choice="${index}" data-choice-value="${escapeHtml(choice)}">
                        <b>${choiceIndex + 1}</b>
                        <span>${escapeHtml(choice)}</span>
                    </button>
                `).join('')}
                </div>
            </div>
            ${renderInteractiveFeedback(interaction, block)}
            <div class="interactive-actions">
                <button class="soft-action" data-reset-theory-block="${index}">إعادة</button>
                <button class="check-btn" data-check-theory-block="${index}" data-interaction-type="choice" ${interaction.choice ? '' : 'disabled'}>تحقق</button>
            </div>
        </section>
    `;
}

function renderMatchingTheoryBlock(block, index, subject) {
    const interaction = getTheoryInteraction(index);
    const resultClass = interaction.result ? `is-${interaction.result}` : '';
    const pairs = matchingPairs(block);
    const leftItems = pairs.map((pair) => pair.left).filter(Boolean);
    const rightItems = seededShuffle(pairs.map((pair) => pair.right).filter(Boolean), `${currentLesson()?.id || 'lesson'}:${index}:matching`);

    return `
        <section class="duo-activity-card theory-matching interactive-theory-card ${resultClass} pop-card" style="--delay:${index * 70}ms;--subject:${subject.color}">
            <div class="activity-progress"><span style="width:${Math.max(18, (index + 1) * 22)}%"></span></div>
            ${renderInteractivePrompt(block, 'وصل بين العناصر', 'اختَر من العمود الأول ثم وصّله بما يناسبه من العمود الثاني.')}
            <div class="matching-board" data-match-board="${index}">
                <svg class="matching-lines" aria-hidden="true"></svg>
                <div class="matching-column">
                    <small>الطرف الأول</small>
                    ${leftItems.map((item, itemIndex) => `
                        <button class="matching-card ${interaction.selectedLeft === item ? 'is-picked' : ''} ${interaction.pairs?.[item] ? 'is-linked' : ''}" data-match-left="${escapeHtml(item)}" data-linked-right="${escapeHtml(interaction.pairs?.[item] || '')}" data-block-index="${index}">
                            <i>${escapeHtml(matchCardMark(item, itemIndex))}</i>
                            <span>${escapeHtml(item)}</span>
                        </button>
                    `).join('')}
                </div>
                <div class="matching-column">
                    <small>الطرف الثاني</small>
                    ${rightItems.map((item, itemIndex) => {
                        const used = Object.values(interaction.pairs || {}).includes(item);
                        return `
                            <button class="matching-card ${used ? 'is-linked' : ''}" data-match-right="${escapeHtml(item)}" data-block-index="${index}">
                                <i>${escapeHtml(matchCardMark(item, itemIndex))}</i>
                                <span>${escapeHtml(item)}</span>
                            </button>
                        `;
                    }).join('')}
                </div>
            </div>
            ${renderInteractiveFeedback(interaction, block)}
            <div class="interactive-actions">
                <button class="soft-action" data-reset-theory-block="${index}">إعادة</button>
                <button class="check-btn" data-check-theory-block="${index}" data-interaction-type="matching" ${Object.keys(interaction.pairs || {}).length >= leftItems.length && leftItems.length ? '' : 'disabled'}>تحقق</button>
            </div>
        </section>
    `;
}

function renderOrderingTheoryBlock(block, index, subject) {
    const interaction = getTheoryInteraction(index);
    const resultClass = interaction.result ? `is-${interaction.result}` : '';
    const correctItems = blockLines(block.items);
    const currentOrder = orderedItemsForBlock(block, index);

    return `
        <section class="duo-activity-card theory-ordering interactive-theory-card ${resultClass} pop-card" style="--delay:${index * 70}ms;--subject:${subject.color}">
            <div class="activity-progress"><span style="width:${Math.max(18, (index + 1) * 22)}%"></span></div>
            ${renderInteractivePrompt(block, 'رتب العناصر', 'اسحب البطاقات أو استخدم الأسهم حتى تصبح بالترتيب الصحيح.')}
            <div class="ordering-board">
                ${currentOrder.map((item, itemIndex) => `
                    <div draggable="true" data-order-drag="${index}" data-order-index="${itemIndex}">
                        <b>${itemIndex + 1}</b>
                        <span>${escapeHtml(item)}</span>
                        <button data-order-move="${index}" data-order-index="${itemIndex}" data-order-dir="-1" ${itemIndex === 0 ? 'disabled' : ''}>↑</button>
                        <button data-order-move="${index}" data-order-index="${itemIndex}" data-order-dir="1" ${itemIndex === currentOrder.length - 1 ? 'disabled' : ''}>↓</button>
                    </div>
                `).join('')}
            </div>
            ${correctItems.length ? '' : '<div class="media-placeholder">أضف عناصر الترتيب من لوحة المعلم</div>'}
            ${renderInteractiveFeedback(interaction, block)}
            <div class="interactive-actions">
                <button class="soft-action" data-reset-theory-block="${index}">إعادة</button>
                <button class="check-btn" data-check-theory-block="${index}" data-interaction-type="ordering" ${correctItems.length ? '' : 'disabled'}>تحقق</button>
            </div>
        </section>
    `;
}

function renderWritingTheoryBlock(block, index, subject) {
    const interaction = getTheoryInteraction(index);
    const resultClass = interaction.result ? `is-${interaction.result}` : '';

    return `
        <section class="duo-activity-card theory-writing interactive-theory-card ${resultClass} pop-card" style="--delay:${index * 70}ms;--subject:${subject.color}">
            <div class="activity-progress"><span style="width:${Math.max(18, (index + 1) * 22)}%"></span></div>
            ${renderInteractivePrompt(block, 'اكتب الإجابة', 'اكتب إجابتك في الصندوق الكبير، ثم اضغط تحقق.')}
            <div class="writing-answer-box">
                ${renderTheoryVisual(block, subject)}
                <label>
                    <span>${escapeHtml(block.term || 'إجابتك')}</span>
                    <textarea data-theory-write="${index}" rows="4" placeholder="اكتب هنا...">${escapeHtml(interaction.text || '')}</textarea>
                </label>
            </div>
            ${renderInteractiveFeedback(interaction, block)}
            <div class="interactive-actions">
                <button class="soft-action" data-reset-theory-block="${index}">إعادة</button>
                <button class="check-btn" data-check-theory-block="${index}" data-interaction-type="writing" ${(interaction.text || '').trim() ? '' : 'disabled'}>تحقق</button>
            </div>
        </section>
    `;
}

function renderAudioTheoryBlock(block, index, subject) {
    const audioUrl = block.url || '';
    const interaction = getTheoryInteraction(index);
    const choices = audioChoices(block);
    const resultClass = interaction.result ? `is-${interaction.result}` : '';

    return `
        <section class="duo-activity-card media-card audio-activity-card interactive-theory-card ${resultClass} pop-card" style="--delay:${index * 70}ms;--subject:${subject.color}">
            <div class="activity-progress"><span style="width:${Math.max(18, (index + 1) * 22)}%"></span></div>
            ${renderInteractivePrompt(block, 'استمع وكرر', 'اضغط زر الاستماع، اسمع بهدوء، ثم كرر بصوتك.')}
            <div class="audio-play-card">
                <button class="audio-play-main" data-speak-current="true" aria-label="تشغيل الصوت">▶</button>
                <div class="audio-wave" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
                <strong>${escapeHtml(block.term || block.title || 'استماع')}</strong>
                <span>${escapeHtml(block.body || 'استمع للنص ثم كرره.')}</span>
                ${audioUrl ? `<audio controls src="${escapeHtml(audioUrl)}"></audio>` : ''}
            </div>
            ${choices.length ? `
                <div class="audio-listening-question">
                    <strong>ما الكلمة التي سمعتها؟</strong>
                    <div class="interactive-choice-stack">
                        ${choices.map((choice, choiceIndex) => `
                            <button class="${interaction.choice === choice ? 'is-picked' : ''}" data-theory-choice="${index}" data-choice-value="${escapeHtml(choice)}">
                                <b>${choiceIndex + 1}</b>
                                <span>${escapeHtml(choice)}</span>
                            </button>
                        `).join('')}
                    </div>
                </div>
                ${renderInteractiveFeedback(interaction, block)}
                <div class="interactive-actions">
                    <button class="soft-action" data-reset-theory-block="${index}">إعادة</button>
                    <button class="check-btn" data-check-theory-block="${index}" data-interaction-type="audio" ${interaction.choice ? '' : 'disabled'}>تحقق</button>
                </div>
            ` : ''}
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
                <div class="english-conversation-label">محادثة</div>
                <div class="english-option-stack">
                    ${(options.length ? options : ['Listen and repeat', 'Choose the picture', 'Use it in a sentence']).map((item, index) => `
                        <div>
                            <i>${['A', 'B', 'C', 'D', 'E', 'F'][index] || '✓'}</i>
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
                        <div><i>${index + 1}</i><span>${escapeHtml(line)}</span></div>
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
                    <div><i>${index + 1}</i><span>${escapeHtml(line)}</span></div>
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

function duoActivityMeta(block = {}) {
    const map = {
        matching: { label: 'وصل الكلمات', icon: '🔗', className: 'match', helper: 'اضغط على عنصر من كل عمود حتى تربط الإجابة الصحيحة.' },
        ordering: { label: 'رتّب البطاقات', icon: '↕', className: 'drag', helper: 'اسحب البطاقات أو استخدم الأسهم حتى يصبح الترتيب صحيحًا.' },
        choice: { label: 'اختر الإجابة', icon: 'A', className: 'quiz', helper: 'اختر بطاقة واحدة ثم اضغط تحقق.' },
        writing: { label: 'اكتب الإجابة', icon: '✎', className: 'write', helper: 'اكتب إجابتك في الصندوق الكبير.' },
        audio: { label: 'استمع جيدًا', icon: '▶', className: 'listen', helper: 'شغّل الصوت ثم أجب إذا ظهر سؤال.' },
        youtube: { label: 'فيديو قصير', icon: '▶', className: 'media', helper: 'شاهد الفيديو ثم أكمل الخطوة.' },
        pdf: { label: 'ملف مساعد', icon: 'PDF', className: 'media', helper: 'افتح الملف المرفق عند الحاجة.' },
        definition: { label: 'كلمة جديدة', icon: 'Aa', className: 'vocab', helper: 'اقرأ الكلمة والمعنى بصوت واضح.' },
        idea: { label: 'فكرة الدرس', icon: '💡', className: 'read', helper: 'اقرأ الفكرة بهدوء ثم انتقل للخطوة التالية.' },
        example: { label: 'مثال محلول', icon: '✓', className: 'read', helper: 'تابع المثال خطوة بخطوة.' },
        tip: { label: 'تذكّر', icon: '★', className: 'read', helper: 'احفظ التلميح لأنه سيساعدك في السؤال.' },
        hook: { label: 'قراءة النص', icon: '📖', className: 'read', helper: 'اقرأ النص ثم اضغط التالي.' },
    };

    if (block.type === 'definition' || block.term || block.symbol) {
        return map[block.type] || map.definition;
    }

    return map[block.type] || map.hook;
}

function lessonCompletionView(lesson, unit) {
    const earnedXp = Number(lesson?.xp || 0);
    const badge = currentSubject().id === 'math'
        ? 'عبقري الرياضيات'
        : currentSubject().id === 'science'
            ? 'مستكشف العلوم'
            : currentSubject().id === 'english'
                ? 'بطل الكلمات'
                : 'قارئ ممتاز';

    return `
        <section class="duo-complete-page" style="--subject:${currentSubject().color}">
            <div class="duo-complete-card pop-card">
                <div class="complete-burst">★</div>
                <span>أكملت الدرس</span>
                <h1>${escapeHtml(lesson?.title || 'درس جديد')}</h1>
                <p>${escapeHtml(unit?.title || 'رحلة التعلم')} انتهت بنجاح. ممتاز، هذه خطوة جديدة في مسارك.</p>
                <div class="complete-rewards">
                    <div><b>+${earnedXp}</b><small>XP</small></div>
                    <div><b>3</b><small>نجوم</small></div>
                    <div><b>${state.streak}</b><small>Streak</small></div>
                </div>
                <div class="complete-badge">
                    <i>🏆</i>
                    <strong>${badge}</strong>
                    <small>شارة جديدة أضيفت لإنجازاتك</small>
                </div>
                <button class="duo-primary-pill" data-continue-after-complete="true">متابعة</button>
            </div>
        </section>
    `;
}

function lessonPlayerView() {
    const lesson = currentLesson();
    const unit = currentUnit();
    if (!lesson || !unit) return emptyLearningView(currentSubject());

    if (state.lessonMode === 'complete') {
        return shell(lessonCompletionView(lesson, unit));
    }

    const subject = currentSubject();
    const blocks = lessonBlocks(lesson);
    const index = Math.min(Number(state.currentTheoryBlock || 0), Math.max(0, blocks.length - 1));
    const block = blocks[index] || {};
    const meta = duoActivityMeta(block);
    const flowProgress = lessonDone(lesson.id) ? 100 : Math.round(((index + 1) / Math.max(1, blocks.length)) * 100);
    const hearts = Math.max(0, Math.min(3, Number(state.hearts ?? 3)));

    return shell(`
        <section class="duo-game-page duo-activity-${meta.className}" style="--subject:${subject.color};--subject-soft:${subject.bg};--subject-line:${subject.border}">
            <header class="duo-game-header">
                <button class="duo-exit-btn" data-back-learning="true" aria-label="خروج">×</button>
                <div class="duo-progress-wrap" aria-label="تقدم الدرس">
                    <span style="width:${flowProgress}%"></span>
                </div>
                <div class="duo-game-stats">
                    <span class="duo-hearts" title="المحاولات">${Array.from({ length: 3 }).map((_, heartIndex) => heartIndex < hearts ? '<b>♥</b>' : '<i>♥</i>').join('')}</span>
                    <span class="duo-streak">🔥 ${state.streak}</span>
                    <span class="duo-xp">⭐ ${state.xp} XP</span>
                </div>
            </header>

            <main class="duo-game-main">
                <div class="duo-mascot-coach">
                    <div class="duo-mascot ${getTheoryInteraction(index).result === 'wrong' ? 'is-sad' : 'is-happy'}" aria-hidden="true"><span></span></div>
                    <div class="duo-speech">
                        <strong>${escapeHtml(meta.label)}</strong>
                        <p>${escapeHtml(block.note || meta.helper)}</p>
                    </div>
                </div>
                ${renderTheorySection(lesson)}
            </main>
        </section>
    `);
}

function renderTheorySection(lesson) {
    const blocks = lessonBlocks(lesson);
    const index = Math.min(Number(state.currentTheoryBlock || 0), Math.max(0, blocks.length - 1));
    const block = blocks[index] || {};
    const isLast = index >= blocks.length - 1;
    const needsInteraction = isInteractiveTheoryBlock(block);
    const solved = isTheoryBlockSolved(block, index);
    const interactionType = interactionTypeForBlock(block);
    const ready = isInteractionReady(block, index, interactionType);
    const primaryLabel = needsInteraction && !solved ? 'تحقق' : (isLast ? 'إنهاء الدرس' : 'التالي');

    return `
        <div class="duo-lesson-stage">
            <div class="duo-step-counter">
                ${blocks.map((_, dotIndex) => `<span class="${dotIndex <= index ? 'is-filled' : ''}"></span>`).join('')}
            </div>
            ${renderTheoryBlock(block, index)}
            <footer class="duo-bottom-bar">
                <button class="duo-secondary-pill" data-theory-prev="true" ${index === 0 ? 'disabled' : ''}>السابق</button>
                <div class="duo-step-helper">نشاط ${index + 1} من ${blocks.length}</div>
                ${needsInteraction && !solved
                    ? `<button class="duo-primary-pill" data-check-theory-block="${index}" data-interaction-type="${interactionType}" ${ready ? '' : 'disabled'}>${primaryLabel}</button>`
                    : `<button class="duo-primary-pill" data-theory-next="true">${primaryLabel}</button>`
                }
            </footer>
        </div>
    `;
}

function interactionTypeForBlock(block = {}) {
    if (block.type === 'audio') return 'audio';
    if (block.type === 'matching') return 'matching';
    if (block.type === 'ordering') return 'ordering';
    if (block.type === 'writing') return 'writing';
    return 'choice';
}

function isInteractionReady(block, index, type) {
    const interaction = getTheoryInteraction(index);

    if (type === 'matching') {
        const pairs = matchingPairs(block);
        return pairs.length > 0 && Object.keys(interaction.pairs || {}).length >= pairs.length;
    }

    if (type === 'ordering') {
        return blockLines(block.items).length > 0;
    }

    if (type === 'writing') {
        return Boolean((interaction.text || '').trim());
    }

    return Boolean(interaction.choice);
}

function renderTheoryBlock(block, index) {
    const subject = currentSubject();

    if (block.type === 'matching') return renderDuoMatchingCard(block, index, subject);
    if (block.type === 'ordering') return renderDuoOrderingCard(block, index, subject);
    if (block.type === 'choice') return renderDuoChoiceCard(block, index, subject);
    if (block.type === 'writing') return renderDuoWritingCard(block, index, subject);
    if (block.type === 'audio') return renderDuoAudioCard(block, index, subject);
    if (block.type === 'youtube') return renderDuoVideoCard(block, index, subject);
    if (block.type === 'pdf') return renderDuoPdfCard(block, index, subject);
    if (block.type === 'definition' || block.term || block.symbol) return renderDuoVocabularyCard(block, index, subject);
    return renderDuoReadingCard(block, index, subject);
}

function renderDuoCardShell(block, index, body, extraClass = '') {
    const meta = duoActivityMeta(block);
    const interaction = getTheoryInteraction(index);
    const resultClass = interaction.result ? `is-${interaction.result}` : '';
    return `
        <section class="duo-play-card ${extraClass} ${resultClass}" style="--delay:${index * 70}ms">
            <div class="duo-card-title">
                <span>${meta.icon}</span>
                <div>
                    <small>${escapeHtml(meta.label)}</small>
                    <h2>${escapeHtml(block.question || block.title || block.term || meta.label)}</h2>
                </div>
            </div>
            ${body}
            ${renderDuoFeedback(interaction, block)}
        </section>
    `;
}

function renderDuoFeedback(interaction, block = {}) {
    if (!interaction.result) return '<div class="duo-feedback is-empty"></div>';
    const success = interaction.result === 'correct';
    const points = Math.max(1, Number(block.score || 1));
    return `
        <div class="duo-feedback ${success ? 'success' : 'error'}">
            <strong>${success ? '🎉 أحسنت' : 'حاول مرة أخرى'}</strong>
            <span>${success ? `+${points} XP` : (block.note || 'راجع المطلوب وجرب من جديد.')}</span>
        </div>
    `;
}

function renderDuoReadingCard(block, index, subject) {
    const lines = splitLessonLines(block.body || block.items || block.note);
    const body = `
        <div class="duo-reading-layout">
            ${renderTheoryVisual(block, subject) || '<div class="duo-illustration-card reading-illustration"><span>اقرأ</span></div>'}
            <article class="duo-reading-paper">
                <p>${escapeHtml(block.body || block.note || 'اقرأ هذه الخطوة بهدوء ثم اضغط التالي.')}</p>
                ${lines.length ? `<div class="duo-reading-lines">${lines.slice(0, 4).map((line) => `<span>${escapeHtml(line)}</span>`).join('')}</div>` : ''}
                <button class="duo-listen-chip" data-speak-current="true">🔊 استمع للنص</button>
            </article>
        </div>
    `;
    return renderDuoCardShell(block, index, body, 'duo-reading-card');
}

function renderDuoVocabularyCard(block, index, subject) {
    const body = `
        <div class="duo-vocab-layout">
            ${renderTheoryVisual(block, subject) || '<div class="duo-illustration-card vocab-illustration"><span>Aa</span></div>'}
            <div class="duo-vocab-word">
                <small>${escapeHtml(block.symbol || block.term || 'كلمة جديدة')}</small>
                <strong>${escapeHtml(block.term || block.title || 'كلمة')}</strong>
                <p>${escapeHtml(block.body || block.result || block.note || 'اقرأ الكلمة ومعناها ثم استمع للنطق.')}</p>
                <button class="duo-listen-chip" data-speak-current="true">🔊 استمع</button>
            </div>
        </div>
    `;
    return renderDuoCardShell(block, index, body, 'duo-vocab-card');
}

function renderDuoChoiceCard(block, index) {
    const interaction = getTheoryInteraction(index);
    const choices = (Array.isArray(block.options) && block.options.length ? block.options : optionList(block.options || block.items));
    const safeChoices = choices.length ? choices : ['نعم', 'لا'];
    const body = `
        <div class="duo-quiz-grid">
            ${safeChoices.map((choice, choiceIndex) => `
                <button class="duo-answer-card ${interaction.choice === choice ? 'is-picked' : ''}" data-theory-choice="${index}" data-choice-value="${escapeHtml(choice)}">
                    <b>${choiceIndex + 1}</b>
                    <span>${escapeHtml(choice)}</span>
                </button>
            `).join('')}
        </div>
    `;
    return renderDuoCardShell(block, index, body, 'duo-quiz-card');
}

function renderDuoMatchingCard(block, index) {
    const interaction = getTheoryInteraction(index);
    const pairs = matchingPairs(block);
    const leftItems = pairs.map((pair) => pair.left).filter(Boolean);
    const rightItems = seededShuffle(pairs.map((pair) => pair.right).filter(Boolean), `${currentLesson()?.id || 'lesson'}:${index}:duo`);
    const body = `
        <div class="duo-matching-board matching-board" data-match-board="${index}">
            <svg class="matching-lines duo-lines" aria-hidden="true"></svg>
            <div class="duo-match-column">
                <small>الطرف الأول</small>
                ${leftItems.map((item, itemIndex) => `
                    <button class="matching-card duo-match-card ${interaction.selectedLeft === item ? 'is-picked' : ''} ${interaction.pairs?.[item] ? 'is-linked' : ''}" data-match-left="${escapeHtml(item)}" data-linked-right="${escapeHtml(interaction.pairs?.[item] || '')}" data-block-index="${index}">
                        <i>${escapeHtml(matchCardMark(item, itemIndex))}</i>
                        <span>${escapeHtml(item)}</span>
                    </button>
                `).join('')}
            </div>
            <div class="duo-match-column">
                <small>الطرف الثاني</small>
                ${rightItems.map((item, itemIndex) => {
                    const used = Object.values(interaction.pairs || {}).includes(item);
                    return `
                        <button class="matching-card duo-match-card ${used ? 'is-linked' : ''}" data-match-right="${escapeHtml(item)}" data-block-index="${index}">
                            <i>${escapeHtml(matchCardMark(item, itemIndex))}</i>
                            <span>${escapeHtml(item)}</span>
                        </button>
                    `;
                }).join('')}
            </div>
        </div>
    `;
    return renderDuoCardShell(block, index, body, 'duo-match-card-wrap');
}

function renderDuoOrderingCard(block, index) {
    const currentOrder = orderedItemsForBlock(block, index);
    const body = `
        <div class="duo-order-board ordering-board">
            ${currentOrder.map((item, itemIndex) => `
                <div class="duo-order-card" draggable="true" data-order-drag="${index}" data-order-index="${itemIndex}">
                    <b>${itemIndex + 1}</b>
                    <span>${escapeHtml(item)}</span>
                    <button data-order-move="${index}" data-order-index="${itemIndex}" data-order-dir="-1" ${itemIndex === 0 ? 'disabled' : ''}>↑</button>
                    <button data-order-move="${index}" data-order-index="${itemIndex}" data-order-dir="1" ${itemIndex === currentOrder.length - 1 ? 'disabled' : ''}>↓</button>
                </div>
            `).join('')}
        </div>
    `;
    return renderDuoCardShell(block, index, body, 'duo-drag-card');
}

function renderDuoWritingCard(block, index, subject) {
    const interaction = getTheoryInteraction(index);
    const body = `
        <div class="duo-writing-layout">
            ${renderTheoryVisual(block, subject)}
            <label>
                <span>${escapeHtml(block.term || 'اكتب إجابتك')}</span>
                <textarea data-theory-write="${index}" rows="5" placeholder="اكتب هنا...">${escapeHtml(interaction.text || '')}</textarea>
            </label>
        </div>
    `;
    return renderDuoCardShell(block, index, body, 'duo-writing-card');
}

function renderDuoAudioCard(block, index) {
    const interaction = getTheoryInteraction(index);
    const choices = audioChoices(block);
    const body = `
        <div class="duo-listening-layout">
            <button class="duo-big-audio" data-speak-current="true" aria-label="استمع">▶</button>
            <div class="duo-wave" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
            ${block.url ? `<audio controls src="${escapeHtml(block.url)}"></audio>` : ''}
            ${choices.length ? `
                <div class="duo-quiz-grid compact">
                    ${choices.map((choice, choiceIndex) => `
                        <button class="duo-answer-card ${interaction.choice === choice ? 'is-picked' : ''}" data-theory-choice="${index}" data-choice-value="${escapeHtml(choice)}">
                            <b>${choiceIndex + 1}</b>
                            <span>${escapeHtml(choice)}</span>
                        </button>
                    `).join('')}
                </div>
            ` : '<p>استمع جيدًا ثم كرر بصوتك.</p>'}
        </div>
    `;
    return renderDuoCardShell(block, index, body, 'duo-listening-card');
}

function renderDuoVideoCard(block, index) {
    const embed = youtubeEmbedUrl(block.url);
    const body = embed
        ? `<iframe class="duo-media-frame" src="${escapeHtml(embed)}" title="${escapeHtml(block.title || 'فيديو الدرس')}" allowfullscreen></iframe>`
        : '<div class="duo-media-empty">أضف رابط YouTube صحيح من لوحة المعلم.</div>';
    return renderDuoCardShell(block, index, body, 'duo-media-card');
}

function renderDuoPdfCard(block, index) {
    const pdfUrl = block.url || '';
    const body = pdfUrl && isPdfSource(pdfUrl)
        ? `<object class="duo-pdf-frame" data="${escapeHtml(pdfUrl)}" type="application/pdf"><a href="${escapeHtml(pdfUrl)}" target="_blank" rel="noreferrer">فتح PDF</a></object>`
        : '<div class="duo-media-empty">ارفع ملف PDF ليظهر للطالب هنا.</div>';
    return renderDuoCardShell(block, index, body, 'duo-media-card');
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
    const draftSubjectName = subjects.find((subject) => subject.id === draft.subject)?.name || 'المادة';
    const publishedLessonCount = units.reduce((total, unit) => total + unit.lessons.length, 0);
    const selectedUnitLessonCount = selectedDraftUnit?.lessons?.length || 0;

    return shell(`
        <section class="teacher-dashboard-page">
            <header class="teacher-dash-head">
                <div>
                    <span>لوحة المعلم</span>
                    <h1>لوحة تحكم المحتوى التعليمي</h1>
                    <p>اختار الصف والمادة، ثم ابنِ الوحدة والدرس. كل ما تحفظه هنا هو المحتوى المنشور الذي سيظهر للطالب فقط.</p>
                </div>
                <button class="teacher-open-btn" data-teacher-panel="close">رجوع للتعلم</button>
            </header>

            <section class="teacher-overview-grid" aria-label="ملخص المحتوى المنشور">
                <article class="teacher-overview-card">
                    <span>النطاق الحالي</span>
                    <strong>${grades[draftGrade - 1]} - ${draftSubjectName}</strong>
                    <small>أي حفظ سيتم لهذا الصف وهذه المادة فقط.</small>
                </article>
                <article class="teacher-overview-card">
                    <span>منشور للطالب</span>
                    <strong>${units.length} وحدات</strong>
                    <small>${publishedLessonCount} دروس متاحة في مسار الطالب.</small>
                </article>
                <article class="teacher-overview-card">
                    <span>الوحدة المختارة</span>
                    <strong>الوحدة ${draftUnitNo}</strong>
                    <small>${selectedUnitLessonCount ? `${selectedUnitLessonCount} دروس منشورة تحتها` : 'لا توجد دروس تحتها بعد'}</small>
                </article>
            </section>

            <section class="teacher-scope-card">
                <h2>1. اختار الصف والمادة</h2>
                <p class="teacher-hint">هذه الخطوة تحدد أين سيظهر المحتوى للطالب. المادة لا تنتقل تلقائيا إلى صفوف أخرى.</p>
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
                    <h2>ما يظهر للطالب الآن</h2>
                    <div class="student-meter">
                        <strong>${units.length}</strong>
                        <span>وحدات منشورة لهذا الصف والمادة</span>
                    </div>
                    <p>عدد الدروس المنشورة: ${publishedLessonCount}</p>
                    <p>الطالب لا يرى نموذج الإدخال، يرى فقط الوحدات والدروس بعد الحفظ.</p>
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
                        <span>تفاعل</span>
                        <span>وسائط</span>
                        <span>تقييم</span>
                    </div>
                    <label>عنوان الدرس <input data-teacher-field="lessonTitle" value="${escapeHtml(draft.lessonTitle)}"></label>
                </section>
            </div>

            <section class="teacher-builder-card theory-builder-card">
                <div class="builder-head">
                    <div>
                        <h2>4. بناء الدرس التفاعلي</h2>
                        <p class="teacher-hint">كل قسم هنا يظهر للطالب كبطاقة مستقلة: شرح، وصل، ترتيب، اختيار، كتابة، صوت، فيديو أو PDF. لا يوجد تقسيم منفصل للأمثلة أو ورقة العمل أو الاختبار.</p>
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

            <div class="teacher-save-row">
                <button class="primary-action" type="button" data-save-teacher-unit="true">حفظ الوحدة وإظهارها للطالب</button>
                <button class="teacher-open-btn" type="button" data-reset-teacher-draft="true">تفريغ النموذج</button>
            </div>

            <section class="teacher-unit-preview">
                <h2>المحتوى المنشور للطالب</h2>
                ${units.length ? units.map((unit) => `
                    <article>
                        <strong>الوحدة ${unit.unitNo}: ${unit.title}</strong>
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
    const needsItems = ['hook', 'definition', 'example', 'idea', 'tip', 'ordering', 'choice'].includes(current.type);
    const needsResult = ['example', 'idea', 'tip', 'choice', 'writing'].includes(current.type);
    const needsUrl = ['youtube', 'pdf', 'audio'].includes(current.type);
    const needsImage = !['youtube', 'pdf', 'audio'].includes(current.type);
    const needsMatching = current.type === 'matching';
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
                    <label>${labels.symbol} <input data-theory-block="${index}" data-field="symbol" value="${escapeHtml(current.symbol)}" placeholder="ج / ح / ="></label>
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
                            <option value="vector" ${state.imageSearch?.type !== 'illustration' && state.imageSearch?.type !== 'photo' ? 'selected' : ''}>رسومات متجهة</option>
                            <option value="illustration" ${state.imageSearch?.type === 'illustration' ? 'selected' : ''}>رسوم توضيحية</option>
                            <option value="photo" ${state.imageSearch?.type === 'photo' ? 'selected' : ''}>صور فوتوغرافية</option>
                        </select>
                    </label>
                    <button type="button" data-search-images="${index}">بحث</button>
                    ${renderImageSearchResults(index)}
                </div>
                ` : ''}
                ${needsItems ? `
                    <label>
                        ${current.type === 'choice' ? 'الخيارات: كل خيار بسطر' : labels.items}
                        <textarea data-theory-block="${index}" data-field="items" rows="3" placeholder="كل عنصر بسطر منفصل">${escapeHtml(current.items)}</textarea>
                    </label>
                ` : ''}
                ${needsMatching ? `
                    <label>
                        الطرف الأول
                        <textarea data-theory-block="${index}" data-field="leftItems" rows="3" placeholder="تفاحة&#10;قلم">${escapeHtml(current.leftItems || '')}</textarea>
                    </label>
                    <label>
                        الطرف الثاني
                        <textarea data-theory-block="${index}" data-field="rightItems" rows="3" placeholder="Apple&#10;Pencil">${escapeHtml(current.rightItems || '')}</textarea>
                    </label>
                ` : ''}
                ${needsResult ? `
                    <label>${current.type === 'choice' || current.type === 'writing' ? 'الإجابة الصحيحة' : labels.result} <input data-theory-block="${index}" data-field="result" value="${escapeHtml(current.result)}"></label>
                    <label>${labels.note} <input data-theory-block="${index}" data-field="note" value="${escapeHtml(current.note)}"></label>
                ` : ''}
                ${needsUrl ? `
                    <label>
                        ${labels.url}
                        <input data-theory-block="${index}" data-field="url" value="${escapeHtml(current.url)}" placeholder="${current.type === 'youtube' ? 'https://www.youtube.com/watch?v=...' : current.type === 'audio' ? 'https://example.com/audio.mp3' : 'https://example.com/file.pdf'}">
                    </label>
                    ${current.type === 'pdf' ? `
                        <label>
                            إرفاق PDF من الجهاز
                            <input type="file" accept="application/pdf" data-theory-file="${index}" data-file-field="url">
                        </label>
                        ${current.fileName ? `<p class="attached-file-name">تم الإرفاق: ${escapeHtml(current.fileName)}</p>` : ''}
                    ` : ''}
                    ${current.type === 'audio' ? `
                        <label>
                            إرفاق صوت من الجهاز
                            <input type="file" accept="audio/*" data-theory-file="${index}" data-file-field="url">
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
        ['جامع النقاط', `${state.xp} نقطة`, 'star'],
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
                        <b>${xp} نقطة</b>
                    </article>
                `).join('')}
            </div>
        </section>
    `);
}

function drawMatchingLines() {
    document.querySelectorAll('.matching-board[data-match-board]').forEach((board) => {
        const svg = board.querySelector('.matching-lines');
        if (!svg) return;
        svg.replaceChildren();
        const boardRect = board.getBoundingClientRect();
        const leftButtons = Array.from(board.querySelectorAll('[data-match-left]'));
        const rightButtons = Array.from(board.querySelectorAll('[data-match-right]'));

        leftButtons.forEach((leftButton) => {
            const linkedRight = leftButton.dataset.linkedRight;
            if (!linkedRight) return;
            const rightButton = rightButtons.find((item) => item.dataset.matchRight === linkedRight);
            if (!rightButton) return;

            const leftRect = leftButton.getBoundingClientRect();
            const rightRect = rightButton.getBoundingClientRect();
            const startX = leftRect.left + leftRect.width / 2 - boardRect.left;
            const startY = leftRect.top + leftRect.height / 2 - boardRect.top;
            const endX = rightRect.left + rightRect.width / 2 - boardRect.left;
            const endY = rightRect.top + rightRect.height / 2 - boardRect.top;
            const curve = Math.max(60, Math.abs(endX - startX) * 0.34);
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', `M ${startX} ${startY} C ${startX - curve} ${startY}, ${endX + curve} ${endY}, ${endX} ${endY}`);
            path.setAttribute('class', 'matching-line-path');
            svg.appendChild(path);
        });
    });
}

function render() {
    syncDailyGoal();
    if (!isStudentReady()) {
        app.innerHTML = studentGateView();
        return;
    }

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
    requestAnimationFrame(drawMatchingLines);
}

function scrollMainTop() {
    requestAnimationFrame(() => {
        document.querySelector('.main-panel')?.scrollTo({ top: 0, behavior: 'instant' });
        window.scrollTo({ top: 0, behavior: 'instant' });
    });
}

app.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-student-login-form]');
    if (!form) return;

    event.preventDefault();
    const formData = new FormData(form);
    const studentIdNumber = String(formData.get('student_id_number') || '').trim();
    const academicYear = String(formData.get('academic_year') || '').trim();

    state.studentAuth = {
        ...state.studentAuth,
        mode: 'pending',
        loading: true,
        status: 'جار التحقق من هوية الطالب...',
    };
    render();

    try {
        const response = await fetch('/api/student/login', {
            method: 'POST',
            headers: studentAuthHeaders(),
            body: JSON.stringify({
                student_id_number: studentIdNumber,
                academic_year: academicYear,
            }),
        });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || 'تعذر تسجيل الدخول.');
        }

        state.studentAuth = {
            mode: 'registered',
            token: payload.token,
            student: payload.student,
            status: '',
            loading: false,
        };
        resetLearningStateForStudent();
        applyStudentPayload(payload);
        persistStudentAuth();
        saveState();
        render();
    } catch (error) {
        state.studentAuth = {
            mode: 'pending',
            token: '',
            student: null,
            loading: false,
            status: error.message || 'تعذر تسجيل الدخول. حاول مرة أخرى.',
        };
        persistStudentAuth();
        render();
    }
});

app.addEventListener('click', (event) => {
    const button = event.target.closest('button');
    if (!button) return;

    if (button.dataset.studentGuest) {
        enterGuestMode();
        return;
    }

    if (button.dataset.studentLogout) {
        logoutStudent();
        return;
    }

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
        if (!IS_TEACHER_MODE) return;
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
        resetLessonRun(button.dataset.learningLesson);
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

    if (button.dataset.continueAfterComplete) {
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

    if (button.dataset.theoryChoice) {
        const interaction = getTheoryInteraction(Number(button.dataset.theoryChoice));
        interaction.choice = button.dataset.choiceValue || '';
        interaction.result = '';
        saveState();
        render();
        return;
    }

    if (button.dataset.matchLeft) {
        const interaction = getTheoryInteraction(Number(button.dataset.blockIndex || 0));
        const leftValue = button.dataset.matchLeft;
        if (interaction.pairs?.[leftValue]) {
            delete interaction.pairs[leftValue];
            interaction.selectedLeft = '';
        } else {
            interaction.selectedLeft = leftValue;
        }
        interaction.result = '';
        saveState();
        render();
        return;
    }

    if (button.dataset.matchRight) {
        const index = Number(button.dataset.blockIndex || 0);
        const interaction = getTheoryInteraction(index);
        if (interaction.selectedLeft) {
            const lesson = currentLesson();
            const block = lessonBlocks(lesson)[index];
            const pairs = matchingPairs(block);
            const correctRight = pairs.find((pair) => pair.left === interaction.selectedLeft)?.right || '';
            const chosenRight = button.dataset.matchRight;
            const isCorrectPair = correctRight && chosenRight === correctRight;

            if (isCorrectPair) {
                interaction.pairs = {
                    ...(interaction.pairs || {}),
                    [interaction.selectedLeft]: chosenRight,
                };
                const complete = pairs.length > 0 && pairs.every((pair) => interaction.pairs?.[pair.left] === pair.right);
                interaction.result = complete ? 'correct' : '';
                if (complete) {
                    rewardInteractiveBlock(block, index);
                    triggerLearningEffect('success');
                } else {
                    playLearningTone('success');
                }
            } else {
                interaction.result = 'wrong';
                state.hearts = Math.max(0, Number(state.hearts ?? 3) - 1);
                triggerLearningEffect('error');
            }
            interaction.selectedLeft = '';
            saveState();
            render();
        }
        return;
    }

    if (button.dataset.orderMove) {
        const blockIndex = Number(button.dataset.orderMove);
        const itemIndex = Number(button.dataset.orderIndex);
        const direction = Number(button.dataset.orderDir);
        const interaction = getTheoryInteraction(blockIndex);
        const nextIndex = itemIndex + direction;
        if (nextIndex >= 0 && nextIndex < interaction.order.length) {
            [interaction.order[itemIndex], interaction.order[nextIndex]] = [interaction.order[nextIndex], interaction.order[itemIndex]];
            interaction.result = '';
            saveState();
            render();
        }
        return;
    }

    if (button.dataset.resetTheoryBlock) {
        resetTheoryInteraction(Number(button.dataset.resetTheoryBlock));
        saveState();
        render();
        return;
    }

    if (button.dataset.checkTheoryBlock) {
        const blockIndex = Number(button.dataset.checkTheoryBlock);
        const lesson = currentLesson();
        const blocks = lessonBlocks(lesson);
        const block = blocks[blockIndex];
        const interaction = getTheoryInteraction(blockIndex);
        let correct = false;

        if (button.dataset.interactionType === 'choice' || button.dataset.interactionType === 'audio') {
            const answer = block.answer || block.result;
            correct = answer ? normalizeAnswer(interaction.choice) === normalizeAnswer(answer) : Boolean(interaction.choice);
        }

        if (button.dataset.interactionType === 'matching') {
            const pairs = matchingPairs(block);
            correct = pairs.length > 0 && pairs.every((pair) => interaction.pairs?.[pair.left] === pair.right);
        }

        if (button.dataset.interactionType === 'ordering') {
            const correctOrder = blockLines(block.items);
            correct = correctOrder.length > 0 && correctOrder.every((item, index) => item === interaction.order[index]);
        }

        if (button.dataset.interactionType === 'writing') {
            const answer = block.answer || block.result;
            correct = answer ? normalizeAnswer(interaction.text) === normalizeAnswer(answer) : normalizeAnswer(interaction.text).length > 0;
        }

        interaction.result = correct ? 'correct' : 'wrong';
        if (correct) {
            rewardInteractiveBlock(block, blockIndex);
        } else {
            state.hearts = Math.max(0, Number(state.hearts ?? 3) - 1);
        }
        saveState();
        render();
        triggerLearningEffect(correct ? 'success' : 'error');
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
        const blocks = lessonBlocks(lesson);
        const current = Number(state.currentTheoryBlock || 0);
        let finishedLesson = false;
        if (current < blocks.length - 1) {
            state.currentTheoryBlock = current + 1;
        } else {
            markLessonDone(lesson);
            state.lessonMode = 'complete';
            state.currentTheoryBlock = 0;
            finishedLesson = true;
        }
        saveState();
        render();
        scrollMainTop();
        if (finishedLesson) triggerLearningEffect('finish');
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
        const blocks = lessonBlocks(lesson);
        const activeTheoryBlock = blocks[Math.min(Number(state.currentTheoryBlock || 0), Math.max(0, blocks.length - 1))];
        const text = theoryBlockText(activeTheoryBlock || lesson?.theory || {});
        speakText(text);
        return;
    }

    if (button.dataset.grade) {
        if (isRegisteredStudent() && Number(button.dataset.grade) !== Number(state.studentAuth.student?.gradeNumber || state.grade)) return;
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
    if (event.target instanceof HTMLElement && event.target.dataset.theoryWrite) {
        const index = Number(event.target.dataset.theoryWrite);
        const interaction = getTheoryInteraction(index);
        interaction.text = event.target.value || '';
        interaction.result = '';
        const check = document.querySelector(`button[data-check-theory-block="${index}"][data-interaction-type="writing"]`);
        if (check) check.disabled = !interaction.text.trim();
        saveState();
        return;
    }

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

let draggedOrderItem = null;

app.addEventListener('dragstart', (event) => {
    const item = event.target.closest('[data-order-drag]');
    if (!item) return;
    draggedOrderItem = {
        blockIndex: Number(item.dataset.orderDrag),
        itemIndex: Number(item.dataset.orderIndex),
    };
    item.classList.add('is-dragging');
    event.dataTransfer?.setData('text/plain', JSON.stringify(draggedOrderItem));
});

app.addEventListener('dragover', (event) => {
    const item = event.target.closest('[data-order-drag]');
    if (!item || !draggedOrderItem) return;
    event.preventDefault();
});

app.addEventListener('drop', (event) => {
    const item = event.target.closest('[data-order-drag]');
    if (!item || !draggedOrderItem) return;
    event.preventDefault();
    const toIndex = Number(item.dataset.orderIndex);
    const interaction = getTheoryInteraction(draggedOrderItem.blockIndex);
    const [moved] = interaction.order.splice(draggedOrderItem.itemIndex, 1);
    interaction.order.splice(toIndex, 0, moved);
    interaction.result = '';
    draggedOrderItem = null;
    saveState();
    render();
});

app.addEventListener('dragend', () => {
    draggedOrderItem = null;
    document.querySelectorAll('.ordering-board .is-dragging').forEach((item) => item.classList.remove('is-dragging'));
});

window.addEventListener('resize', () => {
    requestAnimationFrame(drawMatchingLines);
});

render();
}
