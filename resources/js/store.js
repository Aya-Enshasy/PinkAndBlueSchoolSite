import { starterUnits } from './content';

const STORAGE_KEY = 'pink-blue-learning-worlds-v7';

export function loadUnits() {
    const saved = window.localStorage.getItem(STORAGE_KEY);

    if (!saved) return structuredClone(starterUnits);

    try {
        const parsed = JSON.parse(saved);
        return Array.isArray(parsed) ? parsed : structuredClone(starterUnits);
    } catch {
        return structuredClone(starterUnits);
    }
}

export function saveUnits(units) {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(units, null, 2));
}

function cleanArray(value) {
    if (!Array.isArray(value)) return [];
    return value.map((item) => String(item).trim()).filter(Boolean);
}

function normalizeQuestionBlock(detail, fallbackQuestion) {
    const options = cleanArray(detail.options);
    const safeOptions = options.length ? options : ['Option 1', 'Option 2'];
    const answer = detail.answer?.trim() || safeOptions[0];

    return {
        question: detail.question?.trim() || detail.title?.trim() || fallbackQuestion,
        options: safeOptions,
        answer,
        hint: detail.body?.trim() || 'Review the lesson and try again.',
    };
}

function buildLessonFromBlocks({ id, title, thumbnail, blocks }) {
    const theoryBlocks = blocks.filter((block) => ['theory', 'news', 'game'].includes(block.type));
    const exampleBlocks = blocks.filter((block) => block.type === 'example');
    const worksheetBlocks = blocks.filter((block) => block.type === 'worksheet');
    const quizBlocks = blocks.filter((block) => block.type === 'quiz');

    const cards = (theoryBlocks.length
        ? theoryBlocks
        : [{ title: 'Explanation Card', body: 'Teacher can add explanation blocks here.', type: 'theory' }]
    ).map((block) => ({
        title: block.title?.trim() || 'Card',
        body: block.body?.trim() || 'Content',
        badge: block.type === 'game' ? 'Game' : block.type === 'news' ? 'News' : 'Theory',
    }));

    const examples = exampleBlocks.map((block, index) => ({
        title: block.title?.trim() || `Example ${index + 1}`,
        level: 'Practice',
        prompt: block.body?.trim() || 'Write solved example text.',
        steps: cleanArray(block.steps).length
            ? cleanArray(block.steps)
            : ['Read the question.', 'Solve step by step.', 'Check the result.'],
        answer: block.answer?.trim() || 'Teacher answer appears here.',
    }));

    const worksheet = worksheetBlocks.map((block, index) => normalizeQuestionBlock(block, `Worksheet Question ${index + 1}`));
    const quiz = quizBlocks.map((block, index) => {
        const normalized = normalizeQuestionBlock(block, `Quiz Question ${index + 1}`);
        return {
            question: normalized.question,
            options: normalized.options,
            answer: normalized.answer,
            visual: block.visual?.trim() || '⭐',
        };
    });

    return {
        id,
        title: title?.trim() || 'New Lesson',
        thumbnail: thumbnail?.trim() || '📘',
        stars: 0,
        sections: {
            theory: {
                title: 'الشرح',
                intro: cards[0]?.body || 'Teacher can add lesson explanation from dashboard.',
                cards,
                visual: {
                    kind: 'custom',
                    title: title?.trim() || 'Lesson Visual',
                    items: cards.slice(0, 5).map((card) => card.title),
                },
            },
            examples,
            worksheet,
            quiz,
        },
    };
}

export function createUnitFromForm(formData) {
    const unitId = `${formData.subjectId}-unit-${Date.now()}`;
    const lessonsInput = Array.isArray(formData.lessons) && formData.lessons.length
        ? formData.lessons
        : [
            {
                title: formData.lessonTitle,
                thumbnail: formData.thumbnail,
                blocks: formData.details,
            },
        ];

    const lessons = lessonsInput.map((lesson, index) => buildLessonFromBlocks({
        id: `${unitId}-lesson-${index + 1}`,
        title: lesson.title,
        thumbnail: lesson.thumbnail,
        blocks: Array.isArray(lesson.blocks) ? lesson.blocks : [],
    }));

    return {
        id: unitId,
        gradeId: formData.gradeId,
        subjectId: formData.subjectId,
        level: Math.max(1, Number(formData.level || 1)),
        title: formData.unitTitle?.trim() || 'New Unit',
        subtitle: formData.unitSubtitle?.trim() || 'Added from teacher panel',
        locked: Boolean(formData.locked),
        x: Number(formData.x || 48),
        y: Number(formData.y || 48),
        theme: formData.theme || 'custom',
        lessons,
    };
}
