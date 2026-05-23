export function buildNodesFromUnits(units) {
    return units.map((unit, index) => ({
        id: index + 1,
        unitId: unit.id,
        level: unit.level,
        title: unit.title,
        x: Number(unit.x),
        y: Number(unit.y),
        worldX: Number(unit.x) * 20,
        worldY: Number(unit.y) * 12,
        status: unit.locked ? 'locked' : 'active',
        type: 'lesson',
    }));
}

export function buildBezierPath(nodes) {
    const path = [];

    for (let i = 0; i < nodes.length - 1; i += 1) {
        const a = nodes[i];
        const b = nodes[i + 1];
        const cp1 = { x: a.worldX + 140, y: a.worldY - 170 };
        const cp2 = { x: b.worldX - 140, y: b.worldY + 170 };

        path.push({
            start: a,
            cp1,
            cp2,
            end: b,
        });
    }

    return path;
}
