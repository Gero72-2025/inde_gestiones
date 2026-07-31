<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<style>
/* ── ETCEE Cortes Público – UI Enhancement ───────────────────── */
.cortes-hero {
    background: linear-gradient(135deg, #1a56db 0%, #0e3c8a 100%);
    border-radius: 1rem;
    padding: 1.75rem 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 6px 24px rgba(13,110,253,.22);
}
.cortes-filter-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: .75rem;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    margin-bottom: 1.5rem;
}
.cortes-filter-card .form-label { font-size: .825rem; }
#cortesCalendar {
    border-radius: .75rem;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 2px 14px rgba(0,0,0,.09);
    padding: .75rem;
}
.fc .fc-toolbar-title { font-size: 1.15rem !important; font-weight: 700; color: #1a3a6e; }
.fc .fc-event {
    border-radius: .4rem !important;
    font-size: .78rem !important;
    font-weight: 600 !important;
    cursor: pointer;
    box-shadow: 0 1px 4px rgba(0,0,0,.18);
    transition: filter .15s, transform .1s;
    border: none !important;
    padding: .1rem .35rem;
}
.fc .fc-event:hover { filter: brightness(1.1); transform: translateY(-1px); }
.fc .fc-daygrid-day-number { font-weight: 600; color: #344767; }
.fc .fc-day-today { background: rgba(13,110,253,.06) !important; }
.corte-detail-modal .modal-header { border-radius: .35rem .35rem 0 0; color: #fff; transition: background .2s; }
.corte-detail-modal .modal-header .btn-close { filter: invert(1); }
.corte-detail-modal .detail-row {
    display: flex;
    align-items: flex-start;
    gap: .85rem;
    padding: .75rem 0;
    border-bottom: 1px solid #f0f2f5;
}
.corte-detail-modal .detail-row:last-child { border-bottom: none; }
.corte-detail-modal .detail-icon {
    width: 2.2rem; height: 2.2rem;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 1rem;
}
</style>

<section>
    <div class="cortes-hero d-flex flex-wrap align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center bg-white bg-opacity-10" style="width:3.5rem;height:3.5rem;">
            <i class="bi bi-lightning-charge-fill text-warning fs-3"></i>
        </div>
        <div>
            <h2 class="h4 mb-0 text-white fw-bold"><?= esc(lang('Portal.cortesTitle')) ?></h2>
            <p class="text-white-50 mb-0 small"><?= esc(lang('Portal.cortesHelp')) ?></p>
        </div>
    </div>

    <div class="cortes-filter-card">
        <div class="d-flex align-items-center mb-3">
            <i class="bi bi-funnel-fill text-primary me-2"></i>
            <span class="fw-semibold text-secondary text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">Filtrar calendario</span>
        </div>
        <form id="cortesForm" class="row g-3">
            <div class="col-12 col-md-5">
                <label class="form-label fw-semibold" for="departamentoInput"><i class="bi bi-geo-alt me-1 text-primary"></i><?= esc(lang('Portal.departmentFilter')) ?></label>
                <input id="departamentoInput" class="form-control" placeholder="<?= esc(lang('Portal.departmentPlaceholder')) ?>">
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label fw-semibold" for="municipioInput"><i class="bi bi-pin-map me-1 text-primary"></i><?= esc(lang('Portal.tableMunicipality')) ?></label>
                <input id="municipioInput" class="form-control" placeholder="Opcional">
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i><?= esc(lang('Portal.listButton')) ?></button>
            </div>
        </form>
    </div>

    <div id="cortesCalendar" class="mb-4"></div>
</section>

<div class="modal fade" id="corteDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered corte-detail-modal">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" id="corteDetailHeader" style="background:#1a56db;">
                <div>
                    <h5 class="modal-title mb-1" id="corteDetailTitle"></h5>
                    <span id="corteDetailStatusBadge" class="badge bg-white bg-opacity-25 text-white small"></span>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="detail-row">
                    <div class="detail-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-calendar-event"></i></div>
                    <div>
                        <div class="small text-secondary">Inicio del corte</div>
                        <strong id="corteDetailStart"></strong>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-calendar-check"></i></div>
                    <div>
                        <div class="small text-secondary">Finalización estimada</div>
                        <strong id="corteDetailEnd"></strong>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-icon bg-success bg-opacity-10 text-success"><i class="bi bi-geo-alt-fill"></i></div>
                    <div>
                        <div class="small text-secondary">Zonas afectadas</div>
                        <span id="corteDetailLocations"></span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-icon bg-info bg-opacity-10 text-info"><i class="bi bi-card-text"></i></div>
                    <div>
                        <div class="small text-secondary">Descripción</div>
                        <span id="corteDetailDescription"></span>
                    </div>
                </div>
                <div id="corteDetailStatus" class="d-none"></div>
            </div>
            <div class="modal-footer border-0 py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal"><i class="bi bi-x me-1"></i>Cerrar</button>
            </div>
        </div>
    </div>
</div>
