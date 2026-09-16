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

.as-tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 14px;
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
                    <div class="as-tips-grid">
                        <div class="as-tip-card"><i class="bi bi-lightbulb"></i><span><?= esc(__('tarifaSocial.tips.item1', 'Usa focos ahorradores o LED en toda tu vivienda.')) ?></span></div>
                        <div class="as-tip-card"><i class="bi bi-plug"></i><span><?= esc(__('tarifaSocial.tips.item2', 'Desconecta aparatos electr\u00f3nicos que no est\u00e9s usando.')) ?></span></div>
                        <div class="as-tip-card"><i class="bi bi-sun"></i><span><?= esc(__('tarifaSocial.tips.item3', 'Aprovecha la luz natural durante el d\u00eda.')) ?></span></div>
                        <div class="as-tip-card"><i class="bi bi-thermometer-half"></i><span><?= esc(__('tarifaSocial.tips.item4', 'Evita el uso prolongado de planchas y calentadores.')) ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
