<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<style>
/* ── ETCEE Cortes Público – UI Enhancement ───────────────────── */
.cortes-hero {
    background: #181630;
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
.corte-detail-modal { --corte-ink: #1c3762; --corte-green: #71966a; z-index: 1300; }
.corte-detail-modal .modal-dialog { max-width: min(1080px, calc(100vw - 2rem)); max-height: calc(100vh - 3rem); }
.corte-detail-modal .modal-content {
    max-height: calc(100vh - 3rem);
    overflow-y: auto;
    aspect-ratio: 16 / 9;
    border: 1px solid #a9c7d7 !important;
    border-radius: .35rem;
    color: var(--corte-ink);
    background: #f8fdff url("<?= base_url('assets/img/tarjeta-mantenimiento/FondoMantenimientos.png') ?>") center / 100% 100% no-repeat;
}
.corte-detail-modal .maintenance-card { position: relative; display: flex; min-height: 100%; flex-direction: column; 
/* isolation: isolate; */
}
.corte-detail-modal .maintenance-card::before {
    position: absolute;
    z-index: -1;
    inset: 0;
    content: "";
    background: rgba(255, 255, 255, .48);
}
.corte-detail-modal .maintenance-header { position: relative; min-height: 7.5rem; padding: 1rem 8.5rem .75rem; text-align: center; }
.corte-detail-modal #corteDetailStatusBadge {
    position: absolute;
    top: 6rem;
    right: 4rem;
    display: grid;
    width: 3.25rem;
    aspect-ratio: 1;
    place-items: center;
    padding: 0;
    border: 2px solid #fff;
    border-radius: .35rem !important;
    box-shadow: 0 2px 8px rgba(20, 47, 77, .25);
    font-size: 1.2rem;
    font-weight: 800;
    line-height: 1;
}
.corte-detail-modal .maintenance-heading { max-width: 32rem; margin: 0 auto; color: var(--corte-green); font-size: clamp(1.25rem, 2.7vw, 2rem); font-weight: 700; line-height: 1.18; }
.corte-detail-modal .maintenance-title { margin: .55rem 0 0; font-size: .92rem; font-weight: 600; }
.corte-detail-modal .maintenance-body { padding: .25rem clamp(1.25rem, 7vw, 5.5rem) .4rem; }
.corte-detail-modal .maintenance-department { margin: 0 0 .9rem; text-align: center; font-size: clamp(1.25rem, 3vw, 2rem); font-weight: 700; }
.corte-detail-modal .maintenance-department strong { display: block; font-size: 1.28em; line-height: 1.1; text-transform: uppercase; }
.corte-detail-modal .affected-heading { display: inline-block; margin-bottom: .65rem; padding-bottom: .28rem; border-bottom: 3px solid #86a94d; font-size: 1.05rem; letter-spacing: .025em; }
.corte-detail-modal .affected-row { display: flex; align-items: flex-start; gap: .8rem; }
.corte-detail-modal .affected-pin { flex: 0 0 auto; margin-top: .1rem; color: #d45a0b; font-size: 2.1rem; line-height: 1; }
.corte-detail-modal .affected-label { display: block; margin-bottom: .2rem; font-size: 1.08rem; font-weight: 700; }
.corte-detail-modal .affected-locations { margin: 0; font-size: clamp(.98rem, 1.8vw, 1.28rem); line-height: 1.35; }
.corte-detail-modal .maintenance-footer { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; padding: .4rem clamp(1.25rem, 7vw, 5.5rem) 4.5rem; }
.corte-detail-modal .schedule-item { display: flex; align-items: center; gap: .8rem; min-width: 0; font-size: clamp(.95rem, 1.8vw, 1.2rem); font-weight: 700; }
.corte-detail-modal .schedule-icon { flex: 0 0 auto; color: #2864a8; font-size: 2.8rem; line-height: 1; }
.corte-detail-modal .schedule-value { overflow-wrap: anywhere; }
.corte-detail-modal .modal-close { position: absolute; z-index: 2; top: .65rem; left: .65rem; }
@media (max-width: 575.98px) {
    .corte-detail-modal .modal-dialog { max-width: calc(100vw - 1rem); margin: .5rem auto; }
    .corte-detail-modal .maintenance-header { min-height: 9rem; padding: 4.1rem 1rem .75rem; }
    .corte-detail-modal .modal-content { aspect-ratio: auto; }
    .corte-detail-modal #corteDetailStatusBadge { top: .6rem; right: 9rem; width: 2.6rem; font-size: 1rem; }
    .corte-detail-modal .maintenance-department { margin-bottom: 1rem; }
    .corte-detail-modal .maintenance-footer { grid-template-columns: 1fr; gap: .55rem; }
    .corte-detail-modal .schedule-icon { font-size: 2rem; }
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
            <span class="fw-semibold text-secondary text-uppercase" style="font-size:.75rem;letter-spacing:.05em;"><?= esc(__('cortes.filter.title', 'Filtrar calendario')) ?></span>
        </div>
        <form id="cortesForm" class="row g-3">
            <div class="col-12 col-md-5">
                <label class="form-label fw-semibold" for="departamentoSelect"><i class="bi bi-geo-alt me-1 text-primary"></i><?= esc(lang('Portal.departmentFilter')) ?></label>
                <select id="departamentoSelect" class="form-select">
                    <option value=""><?= esc(__('cortes.filter.allDepartments', 'Todos los departamentos')) ?></option>
                    <?php foreach (($departamentos ?? []) as $departamentoOption): ?>
                        <option value="<?= (int) ($departamentoOption['id'] ?? 0) ?>"><?= esc((string) ($departamentoOption['nombre'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label fw-semibold" for="municipioSelect"><i class="bi bi-pin-map me-1 text-primary"></i><?= esc(lang('Portal.tableMunicipality')) ?></label>
                <select id="municipioSelect" class="form-select">
                    <option value=""><?= esc(__('cortes.filter.allMunicipalities', 'Todos los municipios')) ?></option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i><?= esc(lang('Portal.listButton')) ?></button>
            </div>
        </form>
    </div>

    <div id="cortesCalendar" class="mb-4"></div>
</section>


<div class="modal fade corte-detail-modal" id="corteDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <article class="maintenance-card">
                <button type="button" class="btn-close modal-close" data-bs-dismiss="modal" aria-label="<?= esc(__('cortes.detail.close', 'Cerrar')) ?>"></button>
                <header class="maintenance-header">
                    <h2 class="maintenance-heading"><?= esc(__('cortes.detail.maintenanceHeading', 'Suspensión del servicio de energía eléctrica por mantenimiento')) ?></h2>
                    <!-- <p class="maintenance-title" id="corteDetailTitle"></p> -->
                    <span id="corteDetailStatusBadge" class="badge rounded-pill text-bg-primary"></span>
                </header>
                <div class="maintenance-body">
                    <p class="maintenance-department"><?= esc(__('cortes.detail.department', 'Departamento de')) ?><strong id="corteDetailDepartment"></strong></p>
                    <h3 class="affected-heading"><?= esc(__('cortes.detail.affectedPlaces', 'LUGARES AFECTADOS:')) ?></h3>
                    <div class="affected-row">
                        <i class="bi bi-geo-alt-fill affected-pin" aria-hidden="true"></i>
                        <div>
                            <span class="affected-label"><?= esc(__('cortes.detail.municipalities', 'Municipios de:')) ?></span>
                            <p class="affected-locations" id="corteDetailLocations"></p>
                        </div>
                    </div>
                    <h3 class="affected-heading"><?= esc(__('cortes.detail.description', 'TIPO DE MANTENIMIENTO:')) ?></h3>
                    <div class="affected-row">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <div>
                            <p class="" id="corteDetailDescription"></p>
                        </div>
                    </div>
                </div>
                <footer class="maintenance-footer">
                    <div class="schedule-item">
                        <i class="bi bi-calendar3 schedule-icon" aria-hidden="true"></i>
                        <span class="schedule-value" id="corteDetailDate"></span>
                    </div>
                    <div class="schedule-item">
                        <i class="bi bi-clock schedule-icon" aria-hidden="true"></i>
                        <span class="schedule-value" id="corteDetailTime"></span>
                    </div>
                </footer>
            </article>
        </div>
    </div>
</div>
