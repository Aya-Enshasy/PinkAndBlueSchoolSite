const STORAGE_KEY = 'pb-game-progress-v1';

const defaultProgress = {
    currentNode: 1,
    unlocked: [1],
    xp: 0,
    stars: {},
};

export function loadProgress() {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) return structuredClone(defaultProgress);
        const parsed = JSON.parse(raw);
        return {
            ...structuredClone(defaultProgress),
            ...parsed,
            unlocked: Array.isArray(parsed?.unlocked) ? parsed.unlocked : [1],
            stars: parsed?.stars && typeof parsed.stars === 'object' ? parsed.stars : {},
        };
    } catch {
        return structuredClone(defaultProgress);
    }
}

export function saveProgress(progress) {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(progress));
}

