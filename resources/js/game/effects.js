export function showConfetti() {
    const layer = document.createElement('div');
    layer.className = 'confetti-layer';

    const colors = ['#ffd166', '#ff5b92', '#6b5bff', '#2fb86f', '#2ea9ff'];
    for (let i = 0; i < 80; i += 1) {
        const p = document.createElement('span');
        p.className = 'confetti-piece';
        p.style.left = `${Math.random() * 100}%`;
        p.style.background = colors[Math.floor(Math.random() * colors.length)];
        p.style.animationDelay = `${Math.random() * 220}ms`;
        p.style.animationDuration = `${1400 + Math.random() * 900}ms`;
        layer.appendChild(p);
    }

    document.body.appendChild(layer);
    window.setTimeout(() => layer.remove(), 2400);
}

