// Интерактивный редактор топологии сети на SVG.
// Узлы перетаскиваются мышью, соединяются в «режиме соединения»,
// свойства редактируются в боковой панели. Все изменения сразу
// сохраняются на сервер через JSON-API.

const cfg = window.topologyConfig;
if (cfg) {
    const SVG_NS = "http://www.w3.org/2000/svg";
    const W = 150;
    const H = 64;

    const ICONS = {
        internet: "🌐",
        router: "📡",
        switch: "🔀",
        server: "🗄️",
        workstation: "💻",
        access_point: "📶",
        printer: "🖨️",
        other: "🔧",
    };

    const canvas = document.getElementById("topology-canvas");
    const nodesLayer = document.getElementById("nodes-layer");
    const linksLayer = document.getElementById("links-layer");
    const statusEl = document.getElementById("topology-status");

    let nodes = cfg.nodes.slice();
    let links = cfg.links.slice();

    let connectMode = false;
    let connectSource = null;
    let selectedId = null;

    // ---- zoom (масштаб рабочей области) ------------------------------

    const ZOOM_MIN = 0.4;
    const ZOOM_MAX = 2.5;
    const ZOOM_STEP = 0.1;
    const zoomStorageKey = `topology-zoom-${cfg.diagramId || "default"}`;

    let zoom = parseFloat(localStorage.getItem(zoomStorageKey)) || 1;
    const zoomLabel = document.getElementById("zoom-label");

    function applyZoom() {
        zoom = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, zoom));
        canvas.style.transformOrigin = "0 0";
        canvas.style.transform = `scale(${zoom})`;
        if (zoomLabel) zoomLabel.textContent = Math.round(zoom * 100) + "%";
        localStorage.setItem(zoomStorageKey, zoom);
    }

    document.getElementById("zoom-in")?.addEventListener("click", () => {
        zoom += ZOOM_STEP;
        applyZoom();
    });
    document.getElementById("zoom-out")?.addEventListener("click", () => {
        zoom -= ZOOM_STEP;
        applyZoom();
    });
    document.getElementById("zoom-reset")?.addEventListener("click", () => {
        zoom = 1;
        applyZoom();
    });

    // Ctrl/Cmd + колесо мыши над холстом — тоже масштабирует область.
    document.getElementById("canvas-scroll")?.addEventListener(
        "wheel",
        (evt) => {
            if (!evt.ctrlKey && !evt.metaKey) return;
            evt.preventDefault();
            zoom += evt.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP;
            applyZoom();
        },
        { passive: false },
    );

    // ---- helpers ----------------------------------------------------

    function url(template, id) {
        return template.replace("__ID__", id);
    }

    function setStatus(text) {
        if (!statusEl) return;
        statusEl.textContent = text;
        if (text) {
            clearTimeout(setStatus._t);
            setStatus._t = setTimeout(() => (statusEl.textContent = ""), 2000);
        }
    }

    async function api(method, endpoint, body) {
        const res = await fetch(endpoint, {
            method,
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": cfg.csrf,
            },
            credentials: "same-origin",
            body: body ? JSON.stringify(body) : undefined,
        });
        if (!res.ok) {
            let message = "Ошибка сохранения";
            try {
                const data = await res.json();
                message = data.message || message;
            } catch (e) {}
            throw new Error(message);
        }
        return res.status === 204 ? {} : res.json();
    }

    function nodeById(id) {
        return nodes.find((n) => n.id === id);
    }

    function nodeScale(node) {
        return node.scale || 1;
    }

    function center(node) {
        const s = nodeScale(node);
        return { x: node.pos_x + (W * s) / 2, y: node.pos_y + (H * s) / 2 };
    }

    // Экранные координаты события → координаты SVG (учёт прокрутки/масштаба).
    function svgPoint(evt) {
        const pt = canvas.createSVGPoint();
        pt.x = evt.clientX;
        pt.y = evt.clientY;
        return pt.matrixTransform(canvas.getScreenCTM().inverse());
    }

    function el(name, attrs = {}) {
        const node = document.createElementNS(SVG_NS, name);
        for (const [k, v] of Object.entries(attrs)) {
            node.setAttribute(k, v);
        }
        return node;
    }

    function truncate(text, max) {
        return text.length > max ? text.slice(0, max - 1) + "…" : text;
    }

    // ---- rendering --------------------------------------------------

    function render() {
        renderLinks();
        renderNodes();
        growCanvasToFit();
    }

    // Пределы рабочей области расширяются, когда узел выходит за них
    // (перетаскиванием или увеличением индивидуального масштаба), а не
    // остаются фиксированными.
    function growCanvasToFit() {
        const margin = 200;
        let maxX = 2000;
        let maxY = 1400;
        for (const node of nodes) {
            const s = nodeScale(node);
            maxX = Math.max(maxX, node.pos_x + W * s + margin);
            maxY = Math.max(maxY, node.pos_y + H * s + margin);
        }
        canvas.setAttribute("width", Math.round(maxX));
        canvas.setAttribute("height", Math.round(maxY));
    }

    function renderNodes() {
        nodesLayer.innerHTML = "";
        for (const node of nodes) {
            const s = nodeScale(node);
            const g = el("g", {
                class: "topo-node",
                "data-id": node.id,
                transform: `translate(${node.pos_x},${node.pos_y}) scale(${s})`,
            });
            g.style.cursor = "move";

            const stroke =
                node.id === selectedId
                    ? "#2563eb"
                    : node.id === (connectSource && connectSource.id)
                      ? "#f59e0b"
                      : "#94a3b8";

            g.appendChild(
                el("rect", {
                    width: W,
                    height: H,
                    rx: 8,
                    fill: "#ffffff",
                    stroke,
                    "stroke-width": node.id === selectedId ? 2.5 : 1.5,
                }),
            );

            const icon = el("text", { x: 12, y: 30, "font-size": 22 });
            icon.textContent = ICONS[node.type] || ICONS.other;
            g.appendChild(icon);

            const label = el("text", {
                x: 42,
                y: 27,
                "font-size": 13,
                "font-weight": 600,
                fill: "#0f172a",
            });
            label.textContent = truncate(node.label || "Узел", 16);
            g.appendChild(label);

            const sub = node.ip_address || node.room_label || "";
            if (sub) {
                const subEl = el("text", {
                    x: 42,
                    y: 46,
                    "font-size": 11,
                    fill: "#64748b",
                });
                subEl.textContent = truncate(sub, 18);
                g.appendChild(subEl);
            }

            nodesLayer.appendChild(g);
        }
    }

    function renderLinks() {
        linksLayer.innerHTML = "";
        for (const link of links) {
            const s = nodeById(link.source_id);
            const t = nodeById(link.target_id);
            if (!s || !t) continue;
            const a = center(s);
            const b = center(t);

            const line = el("line", {
                class: "topo-link",
                "data-id": link.id,
                x1: a.x,
                y1: a.y,
                x2: b.x,
                y2: b.y,
                stroke: "#64748b",
                "stroke-width": 2,
            });
            line.style.cursor = "pointer";
            linksLayer.appendChild(line);

            if (link.label) {
                const lbl = el("text", {
                    x: (a.x + b.x) / 2,
                    y: (a.y + b.y) / 2 - 4,
                    "font-size": 11,
                    fill: "#475569",
                    "text-anchor": "middle",
                });
                lbl.textContent = link.label;
                linksLayer.appendChild(lbl);
            }
        }
    }

    // ---- drag -------------------------------------------------------

    let drag = null;

    function onNodePointerDown(evt) {
        const g = evt.target.closest(".topo-node");
        if (!g) return;
        const id = Number(g.dataset.id);
        const node = nodeById(id);
        if (!node) return;

        const p = svgPoint(evt);
        drag = {
            id,
            node,
            offsetX: p.x - node.pos_x,
            offsetY: p.y - node.pos_y,
            moved: false,
            startX: node.pos_x,
            startY: node.pos_y,
        };
        evt.preventDefault();
    }

    function onPointerMove(evt) {
        if (!drag) return;
        const p = svgPoint(evt);
        drag.node.pos_x = Math.max(0, Math.round(p.x - drag.offsetX));
        drag.node.pos_y = Math.max(0, Math.round(p.y - drag.offsetY));
        if (
            Math.abs(drag.node.pos_x - drag.startX) > 3 ||
            Math.abs(drag.node.pos_y - drag.startY) > 3
        ) {
            drag.moved = true;
        }
        render();
    }

    async function onPointerUp() {
        if (!drag) return;
        const d = drag;
        drag = null;

        if (d.moved) {
            // Перетащили — сохраняем позицию.
            try {
                await api("PUT", url(cfg.urls.nodeUpdate, d.id), {
                    pos_x: d.node.pos_x,
                    pos_y: d.node.pos_y,
                });
                setStatus("Сохранено");
            } catch (e) {
                setStatus(e.message);
            }
        } else {
            // Клик без перемещения — выбор узла или соединение.
            handleNodeClick(d.node);
        }
    }

    // ---- click / connect / select ----------------------------------

    function handleNodeClick(node) {
        if (connectMode) {
            if (!connectSource) {
                connectSource = node;
                render();
                setStatus("Выберите второй узел");
            } else if (connectSource.id === node.id) {
                connectSource = null;
                render();
            } else {
                createLink(connectSource, node);
                connectSource = null;
            }
            return;
        }
        selectNode(node);
    }

    async function createLink(source, target) {
        try {
            const link = await api("POST", cfg.urls.linkStore, {
                source_id: source.id,
                target_id: target.id,
            });
            links.push(link);
            render();
            setStatus("Связь добавлена");
        } catch (e) {
            setStatus(e.message);
        }
    }

    // ---- panel ------------------------------------------------------

    const panel = document.getElementById("node-panel");
    const pLabel = document.getElementById("panel-label");
    const pType = document.getElementById("panel-type");
    const pIp = document.getElementById("panel-ip");
    const pRoom = document.getElementById("panel-room");
    const pScale = document.getElementById("panel-scale");

    function selectNode(node) {
        selectedId = node.id;
        pLabel.value = node.label || "";
        pType.value = node.type || "other";
        pIp.value = node.ip_address || "";
        pRoom.value = node.room_id || "";
        if (pScale) pScale.value = String(nodeScale(node));
        panel.classList.remove("hidden");
        render();
    }

    function closePanel() {
        selectedId = null;
        panel.classList.add("hidden");
        render();
    }

    document.getElementById("panel-close").addEventListener("click", closePanel);

    document.getElementById("panel-save").addEventListener("click", async () => {
        const node = nodeById(selectedId);
        if (!node) return;
        try {
            const updated = await api("PUT", url(cfg.urls.nodeUpdate, node.id), {
                label: pLabel.value || "Узел",
                type: pType.value,
                ip_address: pIp.value || null,
                room_id: pRoom.value || null,
                scale: pScale ? parseFloat(pScale.value) || 1 : undefined,
            });
            Object.assign(node, updated);
            render();
            setStatus("Сохранено");
        } catch (e) {
            setStatus(e.message);
        }
    });

    document
        .getElementById("panel-delete")
        .addEventListener("click", async () => {
            const node = nodeById(selectedId);
            if (!node) return;
            if (!confirm("Удалить узел и все его связи?")) return;
            try {
                await api("DELETE", url(cfg.urls.nodeDestroy, node.id));
                nodes = nodes.filter((n) => n.id !== node.id);
                links = links.filter(
                    (l) =>
                        l.source_id !== node.id && l.target_id !== node.id,
                );
                closePanel();
                setStatus("Узел удалён");
            } catch (e) {
                setStatus(e.message);
            }
        });

    // ---- toolbar ----------------------------------------------------

    document
        .getElementById("add-node-btn")
        .addEventListener("click", async () => {
            const type = document.getElementById("node-type").value;
            const offset = (nodes.length % 8) * 26;
            try {
                const node = await api("POST", cfg.urls.nodeStore, {
                    label: cfg.types[type] || "Узел",
                    type,
                    pos_x: 80 + offset,
                    pos_y: 80 + offset,
                });
                nodes.push(node);
                render();
                selectNode(node);
                setStatus("Узел добавлен");
            } catch (e) {
                setStatus(e.message);
            }
        });

    const connectBtn = document.getElementById("connect-btn");
    connectBtn.addEventListener("click", () => {
        connectMode = !connectMode;
        connectSource = null;
        connectBtn.classList.toggle("btn-primary", connectMode);
        connectBtn.classList.toggle("btn-secondary", !connectMode);
        connectBtn.textContent = connectMode
            ? "Соединение: вкл"
            : "Режим соединения";
        setStatus(connectMode ? "Выберите первый узел" : "");
        render();
    });

    // ---- link deletion ----------------------------------------------

    linksLayer.addEventListener("click", async (evt) => {
        const line = evt.target.closest(".topo-link");
        if (!line) return;
        const id = Number(line.dataset.id);
        if (!confirm("Удалить связь?")) return;
        try {
            await api("DELETE", url(cfg.urls.linkDestroy, id));
            links = links.filter((l) => l.id !== id);
            render();
            setStatus("Связь удалена");
        } catch (e) {
            setStatus(e.message);
        }
    });

    // ---- wiring -----------------------------------------------------

    nodesLayer.addEventListener("mousedown", onNodePointerDown);
    document.addEventListener("mousemove", onPointerMove);
    document.addEventListener("mouseup", onPointerUp);

    applyZoom();
    render();
}
