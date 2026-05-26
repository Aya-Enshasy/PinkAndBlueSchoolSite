import { playSound } from './audio';
import { showConfetti } from './effects';
import { buildBezierPath } from './map';

export class GameEngine {
    constructor({
        container,
        nodes,
        progress,
        onOpenUnit,
        onProgress,
    }) {
        this.container = container;
        this.nodes = nodes;
        this.progress = progress;
        this.path = buildBezierPath(nodes);
        this.onOpenUnit = onOpenUnit;
        this.onProgress = onProgress;

        this.world = { width: 2000, height: 1200 };
        this.camera = { x: 0, y: 0, zoom: 1 };
        this.player = { x: 150, y: 600 };
        this.drag = { active: false, x: 0, y: 0 };
        this.rafId = null;
        this.targetNode = null;
        this.destroyed = false;
    }

    init() {
        if (!this.container || !this.nodes.length) return;
        this.container.innerHTML = '';
        this.createCanvas();
        this.bindEvents();
        this.setPlayerToCurrent();
        this.loop();
        playSound('start');
    }

    destroy() {
        this.destroyed = true;
        if (this.rafId) window.cancelAnimationFrame(this.rafId);
        window.removeEventListener('mousemove', this.onMouseMove);
        window.removeEventListener('mouseup', this.onMouseUp);
        window.removeEventListener('resize', this.onResize);
        this.canvas?.removeEventListener('mousedown', this.onMouseDown);
        this.canvas?.removeEventListener('click', this.onClick);
        this.canvas?.removeEventListener('wheel', this.onWheel);
    }

    createCanvas() {
        this.canvas = document.createElement('canvas');
        this.canvas.className = 'map-engine-canvas';
        this.ctx = this.canvas.getContext('2d');
        this.container.appendChild(this.canvas);
        this.resize();
    }

    resize() {
        this.canvas.width = this.container.clientWidth;
        this.canvas.height = this.container.clientHeight;
    }

    bindEvents() {
        this.onResize = () => this.resize();
        this.onMouseDown = (e) => {
            this.drag.active = true;
            this.drag.x = e.clientX;
            this.drag.y = e.clientY;
        };
        this.onMouseUp = () => {
            this.drag.active = false;
        };
        this.onMouseMove = (e) => {
            if (!this.drag.active) return;
            const dx = e.clientX - this.drag.x;
            const dy = e.clientY - this.drag.y;
            this.camera.x -= dx / this.camera.zoom;
            this.camera.y -= dy / this.camera.zoom;
            this.drag.x = e.clientX;
            this.drag.y = e.clientY;
        };
        this.onWheel = (e) => {
            e.preventDefault();
            this.camera.zoom += e.deltaY * -0.001;
            this.camera.zoom = Math.min(Math.max(0.5, this.camera.zoom), 1.8);
        };
        this.onClick = (e) => this.handleClick(e);

        window.addEventListener('resize', this.onResize);
        window.addEventListener('mousemove', this.onMouseMove);
        window.addEventListener('mouseup', this.onMouseUp);
        this.canvas.addEventListener('mousedown', this.onMouseDown);
        this.canvas.addEventListener('wheel', this.onWheel, { passive: false });
        this.canvas.addEventListener('click', this.onClick);
    }

    worldToScreen(x, y) {
        return {
            x: (x - this.camera.x) * this.camera.zoom,
            y: (y - this.camera.y) * this.camera.zoom,
        };
    }

    screenToWorld(x, y) {
        return {
            x: x / this.camera.zoom + this.camera.x,
            y: y / this.camera.zoom + this.camera.y,
        };
    }

    setPlayerToCurrent() {
        const current = this.nodes.find((n) => n.id === this.progress.currentNode) || this.nodes[0];
        this.player.x = current.worldX;
        this.player.y = current.worldY;
        this.camera.x = Math.max(0, current.worldX - 450);
        this.camera.y = Math.max(0, current.worldY - 280);
    }

    loop() {
        if (this.destroyed) return;
        this.update();
        this.render();
        this.rafId = window.requestAnimationFrame(() => this.loop());
    }

    update() {
        this.camera.x += (this.player.x - this.camera.x - 420) * 0.05;
        this.camera.y += (this.player.y - this.camera.y - 260) * 0.05;
    }

    render() {
        const ctx = this.ctx;
        ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.drawConnections();
        this.drawNodes();
        this.drawPlayer();
    }

    drawConnections() {
        const ctx = this.ctx;
        ctx.strokeStyle = '#ffd166';
        ctx.lineWidth = 4;
        ctx.setLineDash([10, 10]);

        this.path.forEach((seg) => {
            const a = this.worldToScreen(seg.start.worldX, seg.start.worldY);
            const b = this.worldToScreen(seg.end.worldX, seg.end.worldY);
            const cp1 = this.worldToScreen(seg.cp1.x, seg.cp1.y);
            const cp2 = this.worldToScreen(seg.cp2.x, seg.cp2.y);
            ctx.beginPath();
            ctx.moveTo(a.x, a.y);
            ctx.bezierCurveTo(cp1.x, cp1.y, cp2.x, cp2.y, b.x, b.y);
            ctx.stroke();
        });

        ctx.setLineDash([]);
    }

    drawNodes() {
        const ctx = this.ctx;
        this.nodes.forEach((node) => {
            const unlocked = this.progress.unlocked.includes(node.id) && node.status !== 'locked';
            const pos = this.worldToScreen(node.worldX, node.worldY);
            const r = 30;

            ctx.beginPath();
            ctx.fillStyle = unlocked ? '#ff5b92' : '#8f93b4';
            ctx.arc(pos.x, pos.y, r, 0, Math.PI * 2);
            ctx.fill();

            ctx.fillStyle = '#ffffff';
            ctx.font = '700 12px Calibri, "Segoe UI", sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('LEVEL', pos.x, pos.y - 8);
            ctx.font = '700 24px Calibri, "Segoe UI", sans-serif';
            ctx.fillText(String(node.level), pos.x, pos.y + 15);

            ctx.font = '500 14px Calibri, "Segoe UI", sans-serif';
            ctx.fillText(node.title.slice(0, 14), pos.x, pos.y + 52);
        });
    }

    drawPlayer() {
        const ctx = this.ctx;
        const pos = this.worldToScreen(this.player.x, this.player.y);
        ctx.beginPath();
        ctx.fillStyle = '#ffffff';
        ctx.arc(pos.x, pos.y, 14, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = '#1f2445';
        ctx.font = '500 16px Calibri, "Segoe UI", sans-serif';
        ctx.fillText('🙂', pos.x, pos.y + 5);
    }

    handleClick(event) {
        const rect = this.canvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        const point = this.screenToWorld(x, y);

        const target = this.nodes.find((node) => {
            const dx = point.x - node.worldX;
            const dy = point.y - node.worldY;
            return Math.sqrt(dx * dx + dy * dy) <= 34;
        });

        if (!target) return;
        const unlocked = this.progress.unlocked.includes(target.id) && target.status !== 'locked';
        if (!unlocked) {
            playSound('locked');
            return;
        }

        this.moveToNode(target);
    }

    moveToNode(node) {
        this.targetNode = node;
        playSound('click');
        const startX = this.player.x;
        const startY = this.player.y;
        let t = 0;

        const animate = () => {
            t += 0.03;
            this.player.x = startX + (node.worldX - startX) * t;
            this.player.y = startY + (node.worldY - startY) * t;

            if (t < 1 && !this.destroyed) {
                window.requestAnimationFrame(animate);
                return;
            }

            this.completeNode(node);
        };

        animate();
    }

    completeNode(node) {
        this.progress.currentNode = node.id;
        this.progress.xp += 10;
        this.progress.stars[node.id] = Math.max(Number(this.progress.stars[node.id] || 0), 3);
        const nextId = node.id + 1;
        if (this.nodes.find((n) => n.id === nextId) && !this.progress.unlocked.includes(nextId)) {
            this.progress.unlocked.push(nextId);
        }

        this.onProgress?.(this.progress);
        showConfetti();
        playSound('win');
        this.onOpenUnit?.(node.unitId);
    }
}
