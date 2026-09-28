<?php
/**
 * Vista Pública – Tarifa Social ECOE
 * Renderizada dentro de app/Views/public/layout.php via PublicPortalController.
 *
 * Variables esperadas:
 *  $distribuidoras  – array de ecoe_distribuidoras activas
 *  $ajaxCipherKey   – clave AES del .env (pasada desde el controlador)
 */
$distribuidoras = (array) ($distribuidoras ?? []);
$consejosAhorro = (array) ($consejosAhorro ?? []);
$ajaxCipherKey  = (string) ($ajaxCipherKey ?? '');
?>

<!-- ── Estilos propios ─────────────────────────────────────────────────────── -->
<style>
    /* Fuente propia de esta pagina; librerias de graficas/PDF se retiraron por no estar cableadas al backend real */
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap');

:root {
    --azul: #003366;
    --azul-claro: #1a6bbf;
    --verde: #008f39;
    --verde-claro: #00b347;
    --verde-oscuro: #005c24;
    --fondo: #f0f4f8;
    --texto: #1a2a3a;
    --texto-suave: #4a5a6a;
    --sombra: rgba(0, 51, 102, 0.12);
}

.ts-presentacion-page {
    background: linear-gradient(180deg, #f8fbff 0%, var(--fondo) 100%);
    padding-bottom: 2rem;
    font-family: 'Montserrat', 'Sora', sans-serif;
}

.ts-presentacion-page h1,
.ts-presentacion-page h2,
.ts-presentacion-page h3,
.ts-presentacion-page h4 {
    font-family: 'Montserrat', 'Sora', sans-serif;
}

.as-hero {
    background: #0B192E;
    padding: 70px 0 50px;
    text-align: center;
    color: white;
}

.as-hero h1 {
    color: white;
    margin-bottom: 10px;
    font-size: clamp(2rem, 3.4vw, 2.8rem);
    font-weight: 800;
}

.as-hero p {
    color: rgba(255,255,255,0.8);
    max-width: 680px;
    margin: 0 auto;
    line-height: 1.7;
}

.contenedor {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

.as-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 24px;
    align-items: start;
    margin: -40px auto 60px;
    position: relative;
    z-index: 2;
}

.as-info {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 30px var(--sombra);
    padding: 26px 24px;
    border-top: 4px solid var(--verde);
}

.as-info h3 {
    color: var(--azul);
    font-size: 1em;
    margin-bottom: 14px;
}

.as-rango {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
    align-items: flex-start;
}

.as-rango-badge {
    flex-shrink: 0;
    font-size: 0.68em;
    font-weight: 800;
    padding: 4px 8px;
    border-radius: 6px;
    color: white;
    white-space: nowrap;
}

.as-rango-txt {
    font-size: 0.82em;
    color: var(--texto-suave);
    line-height: 1.4;
}

.as-rango-txt strong {
    color: var(--texto);
}

.as-info-nota {
    font-size: 0.74em;
    color: var(--texto-suave);
    border-top: 1px solid var(--fondo);
    padding-top: 12px;
    margin-top: 4px;
    line-height: 1.5;
}

.as-subrangos {
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid var(--fondo);
}

.as-subrangos-titulo {
    font-size: 0.78em;
    font-weight: 700;
    color: var(--azul);
    margin-bottom: 10px;
    display: block;
}

.as-subrango {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.78em;
    color: var(--texto-suave);
    margin-bottom: 7px;
}

.as-subrango-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.as-subrango-warning {
    display: flex;
    gap: 7px;
    align-items: flex-start;
    font-size: 0.72em;
    color: #8a5a00;
    background: rgba(255, 176, 32, 0.1);
    border: 1px solid rgba(255, 176, 32, 0.3);
    border-radius: 8px;
    padding: 8px 10px;
    margin-top: 8px;
    line-height: 1.4;
}

.as-caja {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 30px var(--sombra);
    padding: 36px;
}

.as-form {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.as-form select,
.as-form input {
    font-family: inherit;
    font-size: 0.95em;
    padding: 12px 14px;
    border-radius: 8px;
    border: 1.5px solid rgba(0, 51, 102, 0.15);
    outline: none;
}

.as-form select {
    flex: 0 0 160px;
}

.as-form input {
    flex: 1;
    min-width: 180px;
}

.as-form select:focus,
.as-form input:focus {
    border-color: var(--azul-claro);
}

.as-btn {
    background: var(--verde);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 28px;
    font-weight: 700;
    font-size: 0.95em;
    cursor: pointer;
    transition: background 0.2s;
}

.as-btn:hover {
    background: var(--verde-claro);
}

.as-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.as-resultado {
    margin-top: 28px;
}

.as-placeholder {
    border: 1px dashed rgba(0, 51, 102, 0.2);
    border-radius: 14px;
    padding: 24px;
    background: linear-gradient(135deg, rgba(0, 51, 102, 0.03), rgba(0, 143, 57, 0.03));
    color: var(--texto-suave);
    text-align: center;
}

.as-loading {
    color: var(--texto-suave);
    font-size: 0.9em;
}

.as-error {
    color: #c0392b;
    font-size: 0.9em;
    margin-top: 16px;
}

.as-beneficio-hero {
    background: linear-gradient(135deg, rgba(0,143,57,0.12), rgba(0,179,71,0.05));
    border-radius: 20px;
    padding: 28px 24px;
    text-align: center;
    margin: 18px 0 24px;
}

.as-beneficio-hero-label {
    color: var(--azul);
    font-size: 1.35rem;
    font-weight: 800;
    text-transform: uppercase;
}

.as-beneficio-hero-val {
    color: var(--verde);
    font-size: 2.8rem;
    font-weight: 800;
    line-height: 1;
    margin-top: 8px;
}

.as-tabs {
    display: flex;
    gap: 4px;
    border-bottom: 2px solid var(--fondo);
    margin: 22px 0 20px;
    flex-wrap: wrap;
}

.as-tab-btn {
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--texto-suave);
    cursor: pointer;
    font-family: inherit;
    font-size: 0.82em;
    font-weight: 700;
    margin-bottom: -2px;
    padding: 10px 16px;
}

.as-tab-btn.active {
    border-bottom-color: var(--verde);
    color: var(--azul);
}

.as-tab-content { display: none; }
.as-tab-content.active { display: block; }

.as-formula-final-row,
.as-stats-3,
.as-resumen-final {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.as-formula-final-row { justify-content: center; margin: 4px 0 20px; }

.as-formula-box,
.as-stat-box {
    background: var(--fondo);
    border-radius: 12px;
    padding: 16px;
    text-align: center;
}

.as-formula-box { min-width: 170px; }
.as-stat-box { flex: 1; min-width: 140px; text-align: left; }

.as-formula-label,
.as-stat-label,
.as-resumen-final-total .label {
    color: var(--texto-suave);
    font-size: 0.72em;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
}

.as-formula-val,
.as-stat-val,
.as-resumen-final-total .val {
    color: var(--azul);
    font-size: 1.35em;
    font-weight: 800;
    margin-top: 4px;
}

.as-formula-val.verde,
.as-stat-val.verde,
.as-resumen-final-total .val { color: var(--verde); }

.as-formula-op,
.as-stats-op { color: var(--verde); font-size: 1.5em; font-weight: 800; }

.as-formula-final,
.as-resumen-final-total {
    background: linear-gradient(135deg, rgba(0,143,57,0.1), rgba(0,179,71,0.04));
    border: 2px solid var(--verde);
    border-radius: 12px;
    padding: 16px 22px;
    text-align: center;
}

.as-formula-final .as-formula-val { color: var(--verde); font-size: 1.8rem; }
.as-resumen-final { justify-content: space-between; margin-top: 22px; }
.as-resumen-final-btns { display: flex; flex-wrap: wrap; gap: 10px; }

.as-detalle-mensual { margin-top: 22px; overflow-x: auto; }
.as-detalle-tabla { border-collapse: collapse; font-size: 0.84em; min-width: 650px; width: 100%; }
.as-detalle-tabla th,
.as-detalle-tabla td { border-bottom: 1px solid var(--fondo); padding: 8px 10px; text-align: left; }
.as-detalle-tabla th { color: var(--texto-suave); font-size: 0.84em; font-weight: 600; }
.as-detalle-tabla .num { text-align: right; }

.as-legend { color: var(--texto-suave); display: flex; flex-wrap: wrap; font-size: 0.82em; gap: 18px; margin: 20px 0 10px; }
.as-legend span { align-items: center; display: flex; gap: 6px; }
.as-legend i { border-radius: 2px; display: inline-block; height: 10px; width: 10px; }
.as-chart-box { height: 300px; position: relative; width: 100%; }
.as-chart-box.horizontal { height: 380px; }

@media (max-width: 600px) {
    .as-beneficio-hero-val { font-size: 2.2rem; }
    .as-formula-final-row { align-items: stretch; flex-direction: column; }
    .as-formula-op { line-height: 1; }
    .as-resumen-final { align-items: stretch; flex-direction: column; }
}

.as-nombre {
    color: var(--azul);
    font-weight: 700;
    font-size: 1.1em;
    margin-bottom: 4px;
}

.as-actividad {
    font-size: 0.82em;
    color: var(--texto-suave);
    margin-bottom: 18px;
}

.as-actividad span {
    background: var(--fondo);
    padding: 2px 9px;
    border-radius: 6px;
    font-weight: 600;
    color: var(--azul);
}

.as-mensaje {
    border-radius: 0 12px 12px 0;
    padding: 16px 20px;
    border-left: 4px solid var(--verde);
    background: linear-gradient(135deg, rgba(0,51,102,0.05), rgba(0,143,57,0.05));
    margin-bottom: 12px;
}

.as-mensaje.alerta {
    border-left-color: #c0392b;
    background: linear-gradient(135deg, rgba(192,57,43,0.05), rgba(192,57,43,0.02));
}

.as-mensaje.info {
    border-left-color: #1a6bbf;
    background: linear-gradient(135deg, rgba(26,107,191,0.06), rgba(26,107,191,0.02));
}

.as-mensaje p {
    margin: 0;
    font-size: 0.92em;
}

.as-btn-pdf {
    background: var(--azul);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 22px;
    font-weight: 700;
    font-size: 0.88em;
    cursor: pointer;
    margin-top: 22px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    transition: background 0.2s;
}

.as-btn-pdf:hover {
    background: var(--azul-claro);
    color: white;
}

.as-btn-secundario {
    background: white;
    color: var(--azul);
    border: 1.5px solid var(--azul-claro);
}

.as-btn-secundario:hover {
    background: var(--fondo);
}

.as-form-solicitud {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid var(--fondo);
    display: none;
}

.as-form-solicitud.active {
    display: block;
}

.as-form-solicitud .form-group {
    margin-bottom: 12px;
}

.as-form-solicitud label {
    display: block;
    font-size: 0.78em;
    font-weight: 700;
    color: var(--azul);
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.as-form-solicitud input,
.as-form-solicitud textarea {
    width: 100%;
    font-family: inherit;
    font-size: 0.9em;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1.5px solid rgba(0, 51, 102, 0.15);
    outline: none;
}

.as-form-solicitud input:focus,
.as-form-solicitud textarea:focus {
    border-color: var(--azul-claro);
}

.as-ticket {
    background: linear-gradient(135deg, rgba(0,143,57,0.07), rgba(0,77,153,0.04));
    border: 1px solid rgba(0,143,57,0.22);
    border-radius: 12px;
    padding: 16px 18px;
    margin-top: 16px;
}

.as-ticket .num {
    font-size: 1.1em;
    font-weight: 800;
    color: var(--azul);
}

.as-tips {
    margin-top: 28px;
    padding-top: 24px;
    border-top: 1px solid var(--fondo);
}

.as-tips-titulo {
    text-align: center;
    color: var(--azul);
    font-size: 1em;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 14px;
}

.as-tips-carousel {
    max-width: 728px;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    background: var(--fondo);
}

.as-tips-carousel .carousel-item {
    min-height: 220px;
    text-align: center;
}

.as-tip-image {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
}

.as-tips-carousel .as-tip-card {
    min-height: 72px;
    align-items: center;
    justify-content: center;
    border-radius: 0;
    text-align: center;
}

.as-tip-card {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    background: var(--fondo);
    border-radius: 12px;
    padding: 14px 16px;
    font-size: 0.82em;
    color: var(--texto-suave);
    line-height: 1.4;
}

.as-tip-card i {
    color: var(--verde);
    font-size: 1.1em;
    flex-shrink: 0;
}

.ts-report-modal[hidden] { display: none; }
.ts-report-modal {
    align-items: center;
    background: rgba(8, 24, 39, 0.68);
    display: flex;
    inset: 0;
    justify-content: center;
    padding: 16px;
    position: fixed;
    z-index: 1080;
}
.ts-report-backdrop { inset: 0; position: absolute; }
.ts-report-dialog {
    background: #fff;
    border-top: 5px solid var(--verde);
    border-radius: 12px;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
    max-height: min(760px, calc(100vh - 32px));
    max-width: 680px;
    overflow-y: auto;
    padding: 24px;
    position: relative;
    width: 100%;
    z-index: 1;
}
.ts-report-header,
.ts-report-footer { align-items: center; display: flex; gap: 12px; justify-content: space-between; }
.ts-report-header { margin-bottom: 14px; }
.ts-report-header h2 { color: var(--azul); font-size: 1.15rem; font-weight: 800; margin: 0; }
.ts-report-close { background: transparent; border: 0; color: var(--texto-suave); cursor: pointer; font-size: 1.6rem; line-height: 1; padding: 4px 8px; }
.ts-report-status { color: var(--texto-suave); font-size: 0.88rem; min-height: 24px; }
.ts-report-options { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin: 14px 0 20px; }
.ts-report-option {
    background: #f5f8fa;
    border: 1px solid #d5e0e5;
    border-radius: 8px;
    color: var(--texto);
    cursor: pointer;
    min-height: 72px;
    padding: 12px 14px;
    text-align: left;
}
.ts-report-option strong,
.ts-report-option span { display: block; }
.ts-report-option strong { color: var(--azul); font-size: 0.88rem; }
.ts-report-option span { color: var(--texto-suave); font-size: 0.76rem; margin-top: 4px; }
.ts-report-option[aria-checked="true"] { background: #e8f5ed; border-color: var(--verde); box-shadow: inset 0 0 0 1px var(--verde); }
.ts-report-option:disabled { cursor: not-allowed; opacity: 0.52; }
.ts-report-footer { justify-content: flex-end; }
.ts-report-generate { background: var(--verde); border: 0; border-radius: 7px; color: #fff; cursor: pointer; font: inherit; font-size: 0.9rem; font-weight: 700; padding: 10px 18px; }
.ts-report-generate:disabled { cursor: not-allowed; opacity: 0.5; }
.ts-report-cancel { background: transparent; border: 1px solid #aebdc5; border-radius: 7px; color: var(--texto); cursor: pointer; font: inherit; font-size: 0.9rem; padding: 9px 15px; }
@media (max-width: 520px) {
    .ts-report-dialog { padding: 18px; }
    .ts-report-options { grid-template-columns: 1fr; }
}

@media (max-width: 900px) {
    .as-layout { grid-template-columns: 1fr; }
}

@media (max-width: 600px) {
    .as-form select { flex: 1 1 100%; }
}
</style>

<div class="ts-presentacion-page">
    <section class="as-hero">
        <h1><?= esc(__('tarifaSocial.hero.title', 'Consulta tu beneficio')) ?></h1>
        <!-- <p><?= esc(__('tarifaSocial.hero.subtitle', 'Ingresa tu distribuidora y tu n\u00famero de NIS o correlativo para ver los beneficios que recibes del INDE y cu\u00e1nto representan en tu factura.')) ?></p> -->
    </section>

    <div class="contenedor">
        <div class="as-layout">
            <div class="as-info">
                <h3><?= esc(__('tarifaSocial.ranges.title', 'Rangos de Tarifa')) ?></h3>

                <div class="as-rango">
                    <span class="as-rango-badge" style="background: var(--azul-claro);">1 - 300 kWh</span>
                    <span class="as-rango-txt"><strong><?= esc(__('tarifaSocial.ranges.range1.label', 'TARIFA SOCIAL:')) ?></strong> <?= esc(__('tarifaSocial.ranges.range1.text', 'el INDE vende energ\u00eda el\u00e9ctrica a las distribuidoras con un Pliego Tarifario especial autorizado por la Comisi\u00f3n Nacional de Energ\u00eda El\u00e9ctrica -CNEE-, para beneficiar a los usuarios que consumen hasta 300 kWh al mes.')) ?></span>
                </div>

                <div class="as-rango">
                    <span class="as-rango-badge" style="background: var(--verde);">1 - 100 kWh</span>
                    <span class="as-rango-txt"><strong><?= esc(__('tarifaSocial.ranges.range2.label', 'APORTE SOCIAL:')) ?></strong> <?= esc(__('tarifaSocial.ranges.range2.text', 'el INDE apoya a la poblaci\u00f3n guatemalteca otorg\u00e1ndole un descuento adicional al precio de la energ\u00eda el\u00e9ctrica en su factura de Tarifa Social, beneficiando a m\u00e1s de 2 millones 350 mil usuarios al mes, en toda la Rep\u00fablica.')) ?></span>
                </div>

                <div class="as-rango">
                    <span class="as-rango-badge" style="background: var(--verde-oscuro);">301+ kWh</span>
                    <span class="as-rango-txt"><strong><?= esc(__('tarifaSocial.ranges.range3.label', 'TARIFA NO SOCIAL:')) ?></strong> <?= esc(__('tarifaSocial.ranges.range3.text', 'el INDE vende energ\u00eda el\u00e9ctrica a las distribuidoras con precios de Tarifa Plena, aprobados por la Comisi\u00f3n Nacional de Energ\u00eda El\u00e9ctrica -CNEE-, para abastecer a los usuarios que consumen de 301 kWh o m\u00e1s al mes.')) ?></span>
                </div>

                <div class="as-subrangos">
                    <span class="as-subrangos-titulo"><?= esc(__('tarifaSocial.subranges.title', 'Dentro del Aporte Social, entre menos consuma, mayor beneficio recibe:')) ?></span>
                    <div class="as-subrango">
                        <span class="as-subrango-dot" style="background:#00733d;"></span>
                        <?= esc(__('tarifaSocial.subranges.item1', '1 - 60 kWh: mayor beneficio de Aporte Social')) ?>
                    </div>
                    <div class="as-subrango">
                        <span class="as-subrango-dot" style="background:#3fa65c;"></span>
                        <?= esc(__('tarifaSocial.subranges.item2', '61 - 88 kWh: buen beneficio de Aporte Social')) ?>
                    </div>
                    <div class="as-subrango">
                        <span class="as-subrango-dot" style="background:#d9a441;"></span>
                        <?= esc(__('tarifaSocial.subranges.item3', '89 - 100 kWh: beneficio m\u00ednimo, cerca del l\u00edmite')) ?>
                    </div>
                    <div class="as-subrango-warning">
                        ⚠️ <?= esc(__('tarifaSocial.subranges.warning', 'Si su consumo supera los 100 kWh, ya no aplica el Aporte Social directo (aunque puede seguir aplicando a la Tarifa Social hasta 300 kWh).')) ?>
                    </div>
                </div>

                <p class="as-info-nota"><?= esc(__('tarifaSocial.ranges.note', 'El Aporte Social aplica \u00fanicamente a los usuarios sin actividades comerciales, mercantiles, profesionales, gubernamentales, entidades con fines de lucro y actividades religiosas.')) ?></p>
            </div>

            <div class="as-caja">
                <form id="tsForm" class="as-form">
                    <select id="tsDistribuidora" name="distribuidora_id" required>
                        <?php if ($distribuidoras !== []): ?>
                            <?php foreach ($distribuidoras as $distribuidora): ?>
                                <option value="<?= (int) ($distribuidora['id'] ?? 0) ?>"><?= esc((string) ($distribuidora['nombre'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <input type="text" id="tsNis" name="correlativo" autocomplete="off" placeholder="<?= esc(__('tarifaSocial.form.placeholder', 'Ingresa tu n\u00famero de NIS o correlativo')) ?>" required />
                    <button class="as-btn" id="tsSubmit" type="submit"><?= esc(__('tarifaSocial.form.submit', 'Consultar')) ?></button>
                </form>

                <div class="as-resultado" id="tsResultado">
                    <div class="as-placeholder">
                        <!-- <p><?= esc(__('tarifaSocial.form.placeholderResult', 'Ingresa tu distribuidora y tu NIS o correlativo para ver tu beneficio.')) ?></p> -->
                    </div>
                </div>

                <div class="as-tips">
                    <p class="as-tips-titulo"><?= esc(__('tarifaSocial.tips.title', 'Consejos de Ahorro')) ?></p>
                    <div id="consejosCarousel" class="carousel slide as-tips-carousel" data-bs-ride="carousel">
                        <div class="carousel-inner">
                        <?php foreach ($consejosAhorro as $consejoIndex => $consejo): ?>
                            <div class="carousel-item <?= $consejoIndex === 0 ? 'active' : '' ?>">
                                <?php if (! empty($consejo['imagen'])): ?>
                                    <img class="as-tip-image" src="<?= esc(base_url((string) $consejo['imagen'])) ?>" alt="<?= esc(__((string) ($consejo['clave'] ?? ''), (string) ($consejo['texto_base'] ?? ''))) ?>">
                                <?php endif; ?>
                                <!-- <div class="as-tip-card">
                                    <span><?= esc(__((string) ($consejo['clave'] ?? ''), (string) ($consejo['texto_base'] ?? ''))) ?></span>
                                </div> -->
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <?php if (count($consejosAhorro) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#consejosCarousel" data-bs-slide="prev" aria-label="Anterior">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#consejosCarousel" data-bs-slide="next" aria-label="Siguiente">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="ts-report-modal" id="tsReportModal" hidden aria-hidden="true">
    <div class="ts-report-backdrop" data-report-close></div>
    <section class="ts-report-dialog" role="dialog" aria-modal="true" aria-labelledby="tsReportTitle" tabindex="-1">
        <div class="ts-report-header">
            <h2 id="tsReportTitle"><?= esc(__('tarifaSocial.report.title', 'Descargar reporte PDF')) ?></h2>
            <button class="ts-report-close" type="button" data-report-close aria-label="<?= esc(__('tarifaSocial.report.close', 'Cerrar')) ?>">&times;</button>
        </div>
        <p class="ts-report-status" id="tsReportStatus" role="status" aria-live="polite"></p>
        <div class="ts-report-options" id="tsReportOptions" role="radiogroup" aria-label="<?= esc(__('tarifaSocial.report.chooseRange', 'Selecciona un período')) ?>"></div>
        <div class="ts-report-footer">
            <button class="ts-report-cancel" id="tsReportCancel" type="button"><?= esc(__('tarifaSocial.report.cancel', 'Cancelar')) ?></button>
            <button class="ts-report-generate" id="tsGenerateReport" type="button" disabled><?= esc(__('tarifaSocial.report.generate', 'Generar reporte')) ?></button>
        </div>
    </section>
</div>
