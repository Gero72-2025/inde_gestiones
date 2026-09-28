<style>
.sni-shell {
    position: relative;
    width: 100%;
}

.sni-panel,
.sni-map-card {
    background: #fff;
    border: 1px solid #d6e2ec;
    border-radius: 1rem;
    box-shadow: 0 10px 24px rgba(13, 57, 86, 0.08);
}

.sni-map-card {
    position: relative;
    overflow: hidden;
    background: #dceaf6;
    border-radius: 1.2rem;
}

#sniMapCanvas {
    width: 100%;
    height: 70vh;
    min-height: 520px;
    background: #dceaf6;
    position: relative;
    overflow: hidden;
}

.sni-floating-legend {
    position: absolute;
    top: 1rem;
    left: 1rem;
    z-index: 2000;
    width: min(340px, calc(100% - 2rem));
    max-height: calc(100% - 2rem);
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(125, 156, 177, 0.3);
    border-radius: 1rem;
    box-shadow: 0 14px 28px rgba(15, 42, 70, 0.12);
    padding: 0.8rem 0.8rem 0.75rem;
}

.sni-floating-legend.is-minimized {
    width: 220px;
}

.sni-floating-legend.is-minimized .sni-legend-content {
    display: none;
}

.sni-floating-legend.is-minimized .sni-status-message {
    display: none;
}

.sni-legend-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.sni-legend-title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #1d2d3d;
}

.sni-legend-toggle-btn {
    border: 1px solid #d2dde7;
    background: rgba(241, 246, 251, 0.9);
    color: #1f5f9f;
    width: 2rem;
    height: 2rem;
    border-radius: 0.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    transition: all 0.2s ease;
}

.sni-legend-toggle-btn:hover {
    background: #e7f1fb;
    border-color: #8db6db;
}

.sni-legend-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    min-height: 0;
}

.sni-legend-list {
    display: grid;
    gap: 0.5rem;
    max-height: min(52vh, 520px);
    overflow: auto;
    padding-right: 0.1rem;
}

.sni-map-toolbar {
    position: absolute;
    top: 1rem;
    right: 4.25rem;
    z-index: 1600;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    pointer-events: none;
}

.sni-control-cluster {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    padding: 0.4rem;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(125, 156, 177, 0.3);
    border-radius: 0.9rem;
    box-shadow: 0 12px 24px rgba(15, 42, 70, 0.1);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    pointer-events: auto;
}

.sni-control-btn {
    border: 1px solid #d2dde7;
    background: #fff;
    border-radius: 0.65rem;
    padding: 0.45rem 0.7rem;
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1.2;
    color: #28598b;
    transition: all 0.2s ease;
}

.sni-control-btn:hover {
    background: #eff7ff;
    border-color: #8db6db;
}

.sni-control-btn.btn-primary,
.sni-control-btn.btn-outline-primary.active,
.sni-control-btn.is-active {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

.sni-zoom-controls {
    position: absolute;
    right: 1rem;
    bottom: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    z-index: 1500;
}

.sni-zoom-btn {
    width: 40px;
    height: 40px;
    border: 1px solid rgba(125, 156, 177, 0.5);
    background: rgba(255, 255, 255, 0.94);
    border-radius: 0.65rem;
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #455a64;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(16, 56, 94, 0.12);
}

.sni-zoom-btn:hover {
    background: #fff;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
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
    background: rgba(248, 251, 255, 0.92);
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

.sni-status-message {
    margin: 0;
    color: #5c6b76;
    font-size: 0.78rem;
}

@media (max-width: 991.98px) {
    #sniMapCanvas {
        min-height: 420px;
        height: 62vh;
    }

    .sni-map-toolbar {
        right: 0.8rem;
        top: 0.8rem;
    }

    .sni-floating-legend {
        left: 0.8rem;
        top: 0.8rem;
        width: min(280px, calc(100% - 1.6rem));
    }
}

@media (max-width: 575.98px) {
    .sni-map-toolbar {
        top: auto;
        bottom: 0.8rem;
        right: 0.8rem;
        left: 0.8rem;
        justify-content: flex-start;
    }

    .sni-control-cluster {
        width: 100%;
        justify-content: space-between;
    }

    .sni-control-btn {
        flex: 1 1 32%;
        min-width: 0;
    }
}
</style>

<section class="sni-shell">
    <div class="sni-map-card">
        <div id="sniMapCanvas" role="img" aria-label="<?= esc(lang('Portal.sniTitle')) ?>">
            <aside id="sniFloatingLegend" class="sni-floating-legend" aria-label="Leyenda del mapa">
                <div class="sni-legend-header">
                    <h2 class="sni-legend-title"><?= esc(lang('Portal.sniLegendTitle')) ?></h2>
                    <button id="sniLegendToggleBtn" type="button" class="sni-legend-toggle-btn" aria-expanded="true" aria-controls="sniLegendContent" title="Minimizar leyenda">
                        <span class="sni-legend-toggle-icon">−</span>
                    </button>
                </div>

                <div id="sniLegendContent" class="sni-legend-content">
                    <div id="sniLegendList" class="sni-legend-list">
                        <div class="small text-secondary"><?= esc(__('sni.legend.loading', 'Cargando simbologias...')) ?></div>
                    </div>
                </div>

                <p id="sniStatusMessage" class="sni-status-message"></p>
            </aside>

            <!-- <div class="sni-map-toolbar" aria-label="Controles del mapa">
                <div class="sni-control-cluster">
                    <button id="sniMode2dBtn" type="button" class="sni-control-btn btn btn-outline-primary"><?= esc(lang('Portal.sniMode2D')) ?></button>
                    <button id="sniMode3dBtn" type="button" class="sni-control-btn btn btn-outline-primary"><?= esc(lang('Portal.sniMode3D')) ?></button>
                    <button id="sniReloadBtn" type="button" class="sni-control-btn btn btn-primary"><?= esc(lang('Portal.sniReload')) ?></button>
                </div>
            </div> -->

            <!-- Controles de zoom nativos de Cesium -->
            <div class="sni-zoom-controls">
                <button id="sniZoomInBtn" class="sni-zoom-btn" type="button" title="<?= esc(__('sni.zoom.in', 'Acercar (+)')) ?>" aria-label="<?= esc(__('sni.zoom.in.aria', 'Zoom In')) ?>">+</button>
                <button id="sniZoomOutBtn" class="sni-zoom-btn" type="button" title="<?= esc(__('sni.zoom.out', 'Alejar (-)')) ?>" aria-label="<?= esc(__('sni.zoom.out.aria', 'Zoom Out')) ?>">&minus;</button>
            </div>
        </div>
    </div>
</section>
