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
:root {
    --azul: #003366;
    --azul-claro: #1a6bbf;
    --verde: #008f39;
    --verde-claro: #00b347;
    --fondo: #f0f4f8;
    --texto: #1a2a3a;
    --texto-suave: #4a5a6a;
    --sombra: rgba(0, 51, 102, 0.12);
}

.ts-presentacion-page {
    background: linear-gradient(180deg, #f8fbff 0%, var(--fondo) 100%);
    padding-bottom: 2rem;
}

.as-hero {
    background: linear-gradient(160deg, var(--azul) 0%, var(--azul-claro) 100%);
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

@media (max-width: 900px) {
    .as-layout { grid-template-columns: 1fr; }
}

@media (max-width: 600px) {
    .as-form select { flex: 1 1 100%; }
}
</style>

<div class="ts-presentacion-page">
    <section class="as-hero">
        <h1>Consulta tu beneficio</h1>
        <p>Ingresa tu distribuidora y tu número de NIS o correlativo para ver los beneficios que recibes del INDE y cuánto representan en tu factura.</p>
    </section>

    <div class="contenedor">
        <div class="as-layout">
            <div class="as-info">
                <h3>Rangos de Tarifa</h3>

                <div class="as-rango">
                    <span class="as-rango-badge" style="background: var(--verde);">0-100 kWh</span>
                    <span class="as-rango-txt"><strong>Aporte a la Tarifa Social:</strong> el INDE beneficia a los hogares con consumo menor a 100 kWh con un subsidio directo a su factura.</span>
                </div>

                <div class="as-rango">
                    <span class="as-rango-badge" style="background: var(--azul-claro);">101-300 kWh</span>
                    <span class="as-rango-txt"><strong>Tarifa Social:</strong> corresponde a usuarios con consumo intermedio, con acceso a precios preferenciales.</span>
                </div>

                <div class="as-rango">
                    <span class="as-rango-badge" style="background: #8a8a8a;">301+ kWh</span>
                    <span class="as-rango-txt"><strong>Tarifa No Social:</strong> aplica a consumidores con mayor uso y precios de tarifa plena.</span>
                </div>

                <p class="as-info-nota">El aporte directo aplica únicamente a los usuarios sin actividades comerciales, mercantiles, profesionales, institucionales, sociales u otras con fines lucrativos.</p>
            </div>

            <div class="as-caja">
                <div class="as-form">
                    <select>
                        <option value="DEOCSA">DEOCSA</option>
                    </select>
                    <input type="text" placeholder="Ingresa tu número de NIS o correlativo" />
                    <button class="as-btn" type="button">Consultar</button>
                </div>

                <div class="as-resultado">
                    <div class="as-placeholder">
                        <!-- <p>La vista de presentación está lista. Aquí se mostrará el resultado cuando se conecte la integración.</p> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
