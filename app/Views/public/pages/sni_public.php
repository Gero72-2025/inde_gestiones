<style>
.sni-shell {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 1rem;
}

.sni-panel,
.sni-map-card {
    background: #fff;
    border: 1px solid #d6e2ec;
    border-radius: 1rem;
    box-shadow: 0 10px 24px rgba(13, 57, 86, 0.08);
}

.sni-panel {
    padding: 1rem;
}

.sni-map-card {
    overflow: hidden;
}

#sniMapCanvas {
    width: 100%;
    height: 70vh;
    min-height: 520px;
    background: #dceaf6;
    position: relative;
}

.sni-zoom-controls {
    position: absolute;
    right: 50px;
    bottom: 20px;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    z-index: 100;
}

.sni-zoom-btn {
    width: 40px;
    height: 40px;
    border: 1px solid #b0bec5;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 4px;
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #455a64;
    transition: all 0.2s ease;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
}

.sni-zoom-btn:hover {
    background: #fff;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    color: #1565c0;
}

.sni-zoom-btn:active {
    transform: scale(0.95);
}

.sni-legend-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 0.5rem;
}

.sni-legend-system {
    border: 1px solid #d6e2ec;
    border-radius: 0.7rem;
    background: #f8fbff;
    padding: 0.45rem;
}

.sni-legend-system-header {
    width: 100%;
    border: 0;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.2rem 0.1rem;
    text-align: left;
    cursor: pointer;
}

.sni-legend-system-header.is-off {
    opacity: 0.55;
}

.sni-legend-system-title {
    font-weight: 600;
    color: #2f4858;
    font-size: 0.92rem;
}

.sni-legend-system-tools {
    display: inline-flex;
    gap: 0.25rem;
    align-items: center;
}

.sni-legend-system-collapse {
    border: 0;
    background: transparent;
    color: #5f7688;
    width: 1.5rem;
    height: 1.5rem;
    line-height: 1;
    border-radius: 0.35rem;
}

.sni-legend-system-collapse:hover {
    background: #e8f1fa;
}

.sni-legend-system.is-collapsed .sni-legend-system-children {
    display: none;
}

.sni-legend-system-children {
    margin-top: 0.45rem;
    display: grid;
    gap: 0.35rem;
}

.sni-legend-child {
    margin-left: 0.45rem;
}

.sni-legend-toggle {
    width: 100%;
    border: 1px solid #d6e2ec;
    background: #f8fbff;
    border-radius: 0.65rem;
    padding: 0.45rem 0.55rem;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s ease;
}

.sni-legend-toggle:hover {
    border-color: #7aa9d2;
    background: #eef6fd;
}

.sni-legend-toggle.is-off {
    opacity: 0.5;
    background: #f5f6f7;
}

.sni-legend-icon {
    width: 1.2rem;
    height: 1.2rem;
    border-radius: 0.2rem;
    object-fit: contain;
    border: 1px solid #d9dde2;
    background: #fff;
}

.sni-line-chip {
    width: 2.2rem;
    height: 0.36rem;
    border-radius: 999px;
    display: inline-block;
}

.sni-icon-chip {
    width: 1.2rem;
    height: 1.2rem;
    border-radius: 50%;
    background: #f59f00;
    display: inline-block;
}

@media (max-width: 991.98px) {
    .sni-shell {
        grid-template-columns: 1fr;
    }

    #sniMapCanvas {
        min-height: 420px;
        height: 62vh;
    }
}
</style>

<section class="sni-shell">
    <aside class="sni-panel">
        <h2 class="h5 mb-3"><?= esc(lang('Portal.sniLegendTitle')) ?></h2>

        <div id="sniLegendList" class="d-grid gap-2">
            <div class="small text-secondary">Cargando simbologias...</div>
        </div>

        <hr>

        <div class="d-grid gap-2">
            <button id="sniMode2dBtn" type="button" class="btn btn-outline-primary"><?= esc(lang('Portal.sniMode2D')) ?></button>
            <button id="sniMode3dBtn" type="button" class="btn btn-outline-primary"><?= esc(lang('Portal.sniMode3D')) ?></button>
            <button id="sniReloadBtn" type="button" class="btn btn-primary"><?= esc(lang('Portal.sniReload')) ?></button>
        </div>

        <p id="sniStatusMessage" class="small text-secondary mt-3 mb-0"></p>
    </aside>

    <div class="sni-map-card">
        <div id="sniMapCanvas" role="img" aria-label="<?= esc(lang('Portal.sniTitle')) ?>">
            <!-- Controles de zoom nativos de Cesium -->
            <div class="sni-zoom-controls">
                <button id="sniZoomInBtn" class="sni-zoom-btn" type="button" title="Acercar (+)" aria-label="Zoom In">+</button>
                <button id="sniZoomOutBtn" class="sni-zoom-btn" type="button" title="Alejar (−)" aria-label="Zoom Out">−</button>
            </div>
        </div>
    </div>
</section>
