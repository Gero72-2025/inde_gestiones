<script>
(function () {
    const form = document.getElementById('tsForm');
    const distSelect = document.getElementById('tsDistribuidora');
    const nisInput = document.getElementById('tsNis');
    const submitBtn = document.getElementById('tsSubmit');
    const resultado = document.getElementById('tsResultado');
    const reportModal = document.getElementById('tsReportModal');
    const reportStatus = document.getElementById('tsReportStatus');
    const reportOptions = document.getElementById('tsReportOptions');
    const generateReportButton = document.getElementById('tsGenerateReport');

    if (!form || !resultado) {
        return;
    }

    const getCipherKey = () => window.PortalConfig?.cipherKey || '';
    const getCsrfToken = () => window.PortalConfig?.csrfToken || '';
    const getCsrfName = () => window.PortalConfig?.csrfName || 'csrf_test_name';

    const ENDPOINTS = {
        consultarNis: <?= json_encode(site_url('api/ecoe/ts/consultar-nis')) ?>,
        crearTicket: <?= json_encode(site_url('api/ecoe/ts/crear-ticket')) ?>,
        reportAvailability: <?= json_encode(site_url('api/ecoe/ts/reporte-disponibilidad')) ?>,
        reportPeriod: <?= json_encode(site_url('api/ecoe/ts/reporte-periodo')) ?>,
    };

    const reportText = <?= json_encode([
        'open' => __('tarifaSocial.report.open', 'Descargar comprobante PDF'),
        'receipt' => __('tarifaSocial.report.receipt', 'Descargar comprobante de solicitud PDF'),
        'q1' => __('tarifaSocial.report.q1', 'Primer trimestre (Enero - Marzo)'),
        'q2' => __('tarifaSocial.report.q2', 'Segundo trimestre (Abril - Junio)'),
        'q3' => __('tarifaSocial.report.q3', 'Tercer trimestre (Julio - Septiembre)'),
        'q4' => __('tarifaSocial.report.q4', 'Cuarto trimestre (Octubre - Diciembre)'),
        'last6' => __('tarifaSocial.report.last6', 'Últimos 6 meses'),
        'year' => __('tarifaSocial.report.year', 'Año completo'),
        'loadingAvailability' => __('tarifaSocial.report.loadingAvailability', 'Validando períodos disponibles...'),
        'available' => __('tarifaSocial.report.available', 'Información disponible'),
        'unavailable' => __('tarifaSocial.report.unavailable', 'Sin tablas o datos para este período'),
        'validationIncomplete' => __('tarifaSocial.report.validationIncomplete', 'No se pudo comprobar la disponibilidad en este momento.'),
        'availabilityError' => __('tarifaSocial.report.availabilityError', 'No fue posible validar los períodos. Intenta de nuevo.'),
        'selectPeriod' => __('tarifaSocial.report.selectPeriod', 'Selecciona un período disponible.'),
        'loadingReport' => __('tarifaSocial.report.loadingReport', 'Consultando datos y generando el PDF...'),
        'reportError' => __('tarifaSocial.report.reportError', 'No fue posible generar el reporte.'),
        'pdfTitle' => __('tarifaSocial.report.pdfTitle', 'Reporte de Tarifa Social'),
        'nis' => __('tarifaSocial.report.nis', 'NIS / Correlativo'),
        'distributor' => __('tarifaSocial.report.distributor', 'Distribuidora'),
        'period' => __('tarifaSocial.report.period', 'Período'),
        'month' => __('tarifaSocial.report.month', 'Mes'),
        'consumption' => __('tarifaSocial.report.consumption', 'Consumo kWh'),
        'fullRate' => __('tarifaSocial.report.fullRate', 'Factura plena'),
        'socialRate' => __('tarifaSocial.report.socialRate', 'Factura social'),
        'indeContribution' => __('tarifaSocial.report.indeContribution', 'Aporte INDE'),
        'benefit' => __('tarifaSocial.report.benefit', 'Beneficio tarifa social'),
        'savings' => __('tarifaSocial.report.savings', 'Ahorro'),
        'total' => __('tarifaSocial.report.total', 'Total'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    const reportRanges = [
        { key: 'q1', label: reportText.q1 },
        { key: 'q2', label: reportText.q2 },
        { key: 'q3', label: reportText.q3 },
        { key: 'q4', label: reportText.q4 },
        { key: 'last6', label: reportText.last6 },
        { key: 'year', label: `${reportText.year} (${new Date().getFullYear()})` },
    ];

    let selectedReportRange = '';
    let reportContext = null;
    let previouslyFocusedElement = null;
    let pdfLibrariesPromise = null;

    function isBase64Like(value) {
        if (typeof value !== 'string') {
            return false;
        }
        const str = value.trim();
        if (str.length < 32 || str.length % 4 !== 0) {
            return false;
        }
        return /^[A-Za-z0-9+/=]+$/.test(str);
    }

    function b64ToBytes(base64) {
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes;
    }

    let cryptoCache = null;

    async function getCryptoMaterials() {
        if (cryptoCache) {
            return cryptoCache;
        }

        let material;
        const cipherKey = getCipherKey();
        if (cipherKey.startsWith('base64:')) {
            material = b64ToBytes(cipherKey.substring(7));
        } else {
            material = new TextEncoder().encode(cipherKey);
        }

        if (material.length !== 32) {
            material = new Uint8Array(await crypto.subtle.digest('SHA-256', material));
        }

        const aesKey = await crypto.subtle.importKey('raw', material, 'AES-CBC', false, ['decrypt']);
        const hmacKey = await crypto.subtle.importKey('raw', material, { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);

        cryptoCache = { aesKey, hmacKey };
        return cryptoCache;
    }

    function bytesEqual(a, b) {
        if (a.length !== b.length) {
            return false;
        }
        let diff = 0;
        for (let i = 0; i < a.length; i++) {
            diff |= a[i] ^ b[i];
        }
        return diff === 0;
    }

    async function decryptCipherPayload(payload) {
        const raw = b64ToBytes(payload);
        if (raw.length <= 48) {
            throw new Error('El payload cifrado no tiene un formato válido.');
        }

        const iv = raw.slice(0, 16);
        const mac = raw.slice(16, 48);
        const cipher = raw.slice(48);
        const { aesKey, hmacKey } = await getCryptoMaterials();

        const macInput = new Uint8Array(iv.length + cipher.length);
        macInput.set(iv, 0);
        macInput.set(cipher, iv.length);

        const expectedMac = new Uint8Array(await crypto.subtle.sign('HMAC', hmacKey, macInput));
        if (!bytesEqual(mac, expectedMac)) {
            throw new Error('No se pudo validar la integridad del payload cifrado.');
        }

        const plain = await crypto.subtle.decrypt({ name: 'AES-CBC', iv }, aesKey, cipher);
        return JSON.parse(new TextDecoder().decode(plain));
    }

    async function decryptResponse(payload) {
        const normalized = typeof payload === 'string' ? payload.trim() : payload;
        if (typeof normalized !== 'string') {
            return normalized;
        }

        if (getCipherKey() && isBase64Like(normalized)) {
            const decrypted = await decryptCipherPayload(normalized);
            if (typeof decrypted === 'string') {
                const text = decrypted.trim();
                try {
                    return JSON.parse(text);
                } catch (_) {
                    return text;
                }
            }
            return decrypted;
        }

        try {
            return JSON.parse(normalized);
        } catch (_) {
            return normalized;
        }
    }

    async function parseServerResponse(rawText) {
        const body = (rawText !== undefined && rawText !== null ? rawText : '').trim();
        if (body === '') {
            throw new Error('Respuesta vacía del servidor.');
        }

        let decoded;
        try {
            decoded = JSON.parse(body);
        } catch (_) {
            decoded = null;
        }

        if (decoded && typeof decoded === 'object') {
            if (decoded.payload !== undefined) {
                if (!getCipherKey()) {
                    throw new Error('Se recibió una respuesta cifrada pero no se encontró la clave de descifrado.');
                }
                return await decryptResponse(decoded.payload);
            }
            return decoded;
        }

        const shortMsg = body.length > 180 ? body.slice(0, 180) + '…' : body;
        throw new Error(shortMsg);
    }

    async function postAjax(url, formData) {
        const csrfToken = getCsrfToken();
        const csrfName = getCsrfName();

        if (csrfToken && formData instanceof FormData && !formData.has(csrfName)) {
            formData.append(csrfName, csrfToken);
        }

        const res = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const raw = await res.text();
        return await parseServerResponse(raw);
    }

    function formatErrorMessage(error) {
        return error && error.message ? error.message : String(error);
    }

    function buildYearOptions(years) {
        return (Array.isArray(years) ? years : [])
            .filter((value) => value !== null && value !== '' && value !== undefined)
            .map((value) => String(value));
    }

    async function queryTarifaSocial(distribuidoraId, correlativo, anio = null) {
        const fd = new FormData();
        fd.append('distribuidora_id', String(distribuidoraId));
        fd.append('correlativo', correlativo);
        if (anio) {
            fd.append('anio', String(anio));
        }

        const res = await postAjax(ENDPOINTS.consultarNis, fd);
        const data = (res && typeof res === 'object' && res.data !== undefined) ? res.data : res;

        if (res && res.ok === false) {
            throw new Error(data && data.message ? data.message : 'Error en la consulta.');
        }

        return data;
    }

    async function createTicket(distribuidoraId, correlativo, values) {
        const fd = new FormData();
        fd.append('distribuidora_id', String(distribuidoraId));
        fd.append('correlativo', String(correlativo));
        fd.append('nombre', values.nombre || '');
        fd.append('direccion', values.direccion || '');
        fd.append('dpi', values.dpi || '');
        fd.append('telefono', values.telefono || '');

        ['dpi_frontal', 'dpi_reverso', 'factura', 'fachada1', 'fachada2'].forEach((name) => {
            const file = values[name];
            if (file instanceof File) {
                fd.append(name, file);
            }
        });

        const res = await postAjax(ENDPOINTS.crearTicket, fd);
        const data = (res && typeof res === 'object' && res.data !== undefined) ? res.data : res;

        if (res && res.ok === false) {
            throw new Error(data && data.message ? data.message : 'Error al enviar la solicitud.');
        }

        return data;
    }

    async function queryReportEndpoint(endpoint, distribuidoraId, correlativo, range = '') {
        const fd = new FormData();
        fd.append('distribuidora_id', String(distribuidoraId));
        fd.append('correlativo', String(correlativo));
        if (range) {
            fd.append('periodo', range);
        }

        const res = await postAjax(endpoint, fd);
        const data = (res && typeof res === 'object' && res.data !== undefined) ? res.data : res;
        if (res && res.ok === false) {
            throw new Error(data && data.message ? data.message : reportText.reportError);
        }
        return data;
    }

    function closeReportModal() {
        if (!reportModal) return;
        reportModal.hidden = true;
        reportModal.setAttribute('aria-hidden', 'true');
        selectedReportRange = '';
        if (generateReportButton) generateReportButton.disabled = true;
        previouslyFocusedElement?.focus();
    }

    function selectReportRange(rangeKey, selectedButton) {
        selectedReportRange = rangeKey;
        reportOptions.querySelectorAll('[role="radio"]').forEach((button) => {
            const selected = button === selectedButton;
            button.setAttribute('aria-checked', selected ? 'true' : 'false');
            button.tabIndex = selected ? 0 : -1;
        });
        generateReportButton.disabled = false;
        reportStatus.textContent = reportText.selectPeriod;
    }

    function renderReportOptions(availability, validationIncomplete = false) {
        reportOptions.replaceChildren();
        let availableCount = 0;

        reportRanges.forEach((range) => {
            const state = availability?.[range.key] || {};
            const isAvailable = state.available === true;
            if (isAvailable) availableCount++;

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ts-report-option';
            button.setAttribute('role', 'radio');
            button.setAttribute('aria-checked', 'false');
            button.tabIndex = -1;
            button.disabled = !isAvailable;

            const title = document.createElement('strong');
            title.textContent = range.label;
            const detail = document.createElement('span');
            detail.textContent = isAvailable
                ? reportText.available
                : (validationIncomplete ? reportText.validationIncomplete : reportText.unavailable);
            button.append(title, detail);

            button.addEventListener('click', () => selectReportRange(range.key, button));
            button.addEventListener('keydown', (event) => {
                if (!['ArrowDown', 'ArrowRight', 'ArrowUp', 'ArrowLeft'].includes(event.key)) return;
                event.preventDefault();
                const enabled = Array.from(reportOptions.querySelectorAll('[role="radio"]')).filter((option) => !option.disabled);
                const currentIndex = enabled.indexOf(button);
                const step = ['ArrowDown', 'ArrowRight'].includes(event.key) ? 1 : -1;
                const next = enabled[(currentIndex + step + enabled.length) % enabled.length];
                next?.focus();
                if (next) selectReportRange(next.dataset.range, next);
            });
            button.dataset.range = range.key;
            reportOptions.appendChild(button);
        });

        generateReportButton.disabled = true;
        reportStatus.textContent = validationIncomplete
            ? reportText.validationIncomplete
            : (availableCount > 0 ? reportText.selectPeriod : reportText.unavailable);
    }

    async function openReportModal(distribuidoraId, correlativo, nombreUsuario) {
        if (!reportModal) return;
        reportContext = { distribuidoraId, correlativo, nombreUsuario };
        previouslyFocusedElement = document.activeElement;
        selectedReportRange = '';
        generateReportButton.disabled = true;
        reportOptions.replaceChildren();
        reportStatus.textContent = reportText.loadingAvailability;
        reportModal.hidden = false;
        reportModal.setAttribute('aria-hidden', 'false');
        reportModal.querySelector('.ts-report-dialog')?.focus();

        try {
            const data = await queryReportEndpoint(ENDPOINTS.reportAvailability, distribuidoraId, correlativo);
            renderReportOptions(data.periods, data.validation_incomplete === true);
        } catch (error) {
            reportStatus.textContent = `${reportText.availabilityError} ${formatErrorMessage(error)}`;
            reportOptions.replaceChildren();
        }
    }

    async function ensurePdfLibraries() {
        if (!pdfLibrariesPromise) {
            pdfLibrariesPromise = (async () => {
                if (!window.jspdf?.jsPDF) {
                    await loadScript('https://cdn.jsdelivr.net/npm/jspdf@2.5.2/dist/jspdf.umd.min.js');
                }
                if (!window.jspdf?.jsPDF?.API?.autoTable) {
                    await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js');
                }
                if (!window.jspdf?.jsPDF?.API?.autoTable) {
                    throw new Error('No se pudo cargar la tabla del PDF.');
                }
            })();
        }
        return pdfLibrariesPromise;
    }

    async function generarPDF(data, rangeLabel) {
        await ensurePdfLibraries();
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        const months = Array.isArray(data.meses) ? data.meses : [];
        const totals = months.reduce((sum, month) => ({
            fullRate: sum.fullRate + numberValue(month.costo_tarifa_plena),
            socialRate: sum.socialRate + numberValue(month.costo_tarifa_social),
            contribution: sum.contribution + Math.abs(numberValue(month.aporte_inde)),
            benefit: sum.benefit + numberValue(month.beneficio_tarifa_social),
            savings: sum.savings + numberValue(month.ahorro),
        }), { fullRate: 0, socialRate: 0, contribution: 0, benefit: 0, savings: 0 });

        pdf.setFontSize(16);
        pdf.text(reportText.pdfTitle, 14, 16);
        pdf.setFontSize(10);
        pdf.text(`${reportText.nis}: ${data.nis || reportContext.correlativo}`, 14, 24);
        pdf.text(`${reportText.distributor}: ${data.distribuidora || ''}`, 14, 30);
        pdf.text(`${reportText.period}: ${rangeLabel}`, 14, 36);
        if (reportContext.nombreUsuario) pdf.text(String(reportContext.nombreUsuario), 14, 42);

        pdf.autoTable({
            startY: 48,
            head: [[reportText.month, reportText.consumption, reportText.fullRate, reportText.socialRate, reportText.indeContribution, reportText.benefit, reportText.savings]],
            body: months.map((month) => [
                String(month.mes || ''),
                formatKwh(month.consumo_kwh),
                formatMoney(month.costo_tarifa_plena),
                formatMoney(month.costo_tarifa_social),
                formatMoney(Math.abs(numberValue(month.aporte_inde))),
                formatMoney(month.beneficio_tarifa_social),
                formatMoney(month.ahorro),
            ]),
            foot: [[reportText.total, '', formatMoney(totals.fullRate), formatMoney(totals.socialRate), formatMoney(totals.contribution), formatMoney(totals.benefit), formatMoney(totals.savings)]],
            styles: { fontSize: 8, cellPadding: 3 },
            headStyles: { fillColor: [0, 102, 63] },
            footStyles: { fillColor: [0, 51, 102], fontStyle: 'bold' },
            columnStyles: { 1: { halign: 'right' }, 2: { halign: 'right' }, 3: { halign: 'right' }, 4: { halign: 'right' }, 5: { halign: 'right' }, 6: { halign: 'right' } },
        });

        const safeNis = String(data.nis || reportContext.correlativo).replace(/[^a-z0-9_-]/gi, '_');
        pdf.save(`reporte-tarifa-social-${safeNis}-${data.periodo}.pdf`);
    }

    reportModal?.querySelectorAll('[data-report-close]').forEach((element) => element.addEventListener('click', closeReportModal));
    document.getElementById('tsReportCancel')?.addEventListener('click', closeReportModal);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && reportModal && !reportModal.hidden) closeReportModal();
    });
    generateReportButton?.addEventListener('click', async () => {
        if (!reportContext || !selectedReportRange) return;
        generateReportButton.disabled = true;
        reportStatus.textContent = reportText.loadingReport;
        try {
            const data = await queryReportEndpoint(
                ENDPOINTS.reportPeriod,
                reportContext.distribuidoraId,
                reportContext.correlativo,
                selectedReportRange
            );
            const range = reportRanges.find((item) => item.key === selectedReportRange);
            await generarPDF(data, range?.label || selectedReportRange);
            closeReportModal();
        } catch (error) {
            reportStatus.textContent = `${reportText.reportError} ${formatErrorMessage(error)}`;
            generateReportButton.disabled = false;
        }
    });

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getResultMessage(data) {
        if (data?.rango_tarifa === 'no_social') {
            return { clase: 'alerta', texto: `El consumo promedio (${Number(data.consumo_promedio || 0).toFixed(0)} kWh) supera el límite de Tarifa Social.` };
        }

        if (data?.tiene_aporte === true) {
            return { clase: 'info', texto: 'El usuario recibe el Aporte Social del INDE y el beneficio de Tarifa Social.' };
        }

        return { clase: 'info', texto: 'El usuario recibe el beneficio de Tarifa Social, pero no tiene Aporte Social directo asignado.' };
    }

    let chartHistorial = null;
    let chartApilada = null;
    let chartSinAporte = null;
    let chartLibrariesPromise = null;
    let chartDataLabelsRegistered = false;

    const chartScripts = {
        chart: 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js',
        labels: 'https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js',
    };

    function destroyCharts() {
        [chartHistorial, chartApilada, chartSinAporte].forEach((chart) => {
            if (chart) {
                chart.destroy();
            }
        });
        chartHistorial = null;
        chartApilada = null;
        chartSinAporte = null;
    }

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const existing = document.querySelector(`script[src="${src}"]`);
            if (existing) {
                if (existing.dataset.loaded === 'true') {
                    resolve();
                } else {
                    existing.addEventListener('load', resolve, { once: true });
                    existing.addEventListener('error', reject, { once: true });
                }
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = () => {
                script.dataset.loaded = 'true';
                resolve();
            };
            script.onerror = () => reject(new Error('No fue posible cargar una librería requerida.'));
            document.head.appendChild(script);
        });
    }

    async function ensureChartLibraries() {
        if (!chartLibrariesPromise) {
            chartLibrariesPromise = (async () => {
                if (!window.Chart) {
                    await loadScript(chartScripts.chart);
                }
                if (!window.ChartDataLabels) {
                    await loadScript(chartScripts.labels);
                }
                if (window.ChartDataLabels && !chartDataLabelsRegistered) {
                    Chart.register(ChartDataLabels);
                    chartDataLabelsRegistered = true;
                }
            })();
        }
        return chartLibrariesPromise;
    }

    function numberValue(value) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatMoney(value) {
        return `Q${numberValue(value).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function formatKwh(value) {
        return `${numberValue(value).toFixed(0)} kWh`;
    }

    function consumptionColor(value) {
        const kwh = numberValue(value);
        if (kwh === 0 || kwh > 100) return '#c0392b';
        if (kwh <= 60) return '#00733d';
        if (kwh <= 88) return '#3fa65c';
        return '#d9a441';
    }

    function normalizeMonths(data) {
        return (Array.isArray(data.meses) ? data.meses : []).map((month) => {
            const costoTarifaPlena = numberValue(month.costo_tarifa_plena);
            const costoTarifaSocial = numberValue(month.costo_tarifa_social);
            const beneficioDeclarado = month.beneficio_tarifa_social;
            const beneficio = beneficioDeclarado !== undefined && beneficioDeclarado !== null
                ? numberValue(beneficioDeclarado)
                : (data.rango_tarifa === 'no_social' ? 0 : Math.max(0, costoTarifaPlena - costoTarifaSocial));

            return {
                mes: String(month.mes || 'Sin mes'),
                consumo_kwh: numberValue(month.consumo_kwh),
                costo_tarifa_plena: costoTarifaPlena,
                costo_tarifa_social: costoTarifaSocial,
                costo_tarifa_no_social: numberValue(month.costo_tarifa_no_social),
                aporte_inde: numberValue(month.aporte_inde),
                beneficio_tarifa_social: beneficio,
                ahorro: numberValue(month.ahorro),
            };
        });
    }

    function monthlyTable(months) {
        const rows = months.map((month) => `
            <tr>
                <td>${escapeHtml(month.mes)}</td>
                <td class="num" style="color:${consumptionColor(month.consumo_kwh)};font-weight:700;">${formatKwh(month.consumo_kwh)}</td>
                <td class="num">${formatMoney(Math.abs(month.aporte_inde))}</td>
                <td class="num">${formatMoney(month.beneficio_tarifa_social)}</td>
                <td class="num" style="color:var(--verde);font-weight:700;">${formatMoney(month.ahorro)}</td>
            </tr>`).join('');

        return `
            <div class="as-detalle-mensual">
                <table class="as-detalle-tabla">
                    <thead><tr><th>Mes</th><th class="num">Consumo</th><th class="num">Aporte INDE</th><th class="num">Beneficio Tarifa Social</th><th class="num">Ahorro</th></tr></thead>
                    <tbody>${rows || '<tr><td colspan="5">No hay datos mensuales disponibles.</td></tr>'}</tbody>
                </table>
            </div>`;
    }

    function chartDatasets(months, includeAporte = true) {
        const datasets = [
            { label: 'Factura sin Tarifa Social', data: months.map((month) => month.costo_tarifa_plena), backgroundColor: '#00733d', borderRadius: 4 },
            { label: 'Factura con Tarifa Social', data: months.map((month) => month.costo_tarifa_social), backgroundColor: '#1a6bbf', borderRadius: 4 },
        ];
        if (includeAporte) {
            datasets.push({ label: 'Aporte INDE', data: months.map((month) => Math.abs(month.aporte_inde)), backgroundColor: '#4de88a', borderRadius: 4 });
        }
        return datasets;
    }

    function createChart(canvasId, months, horizontal, includeAporte) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !window.Chart) return null;

        return new Chart(canvas, {
            type: 'bar',
            data: { labels: months.map((month) => month.mes), datasets: chartDatasets(months, includeAporte) },
            options: {
                indexAxis: horizontal ? 'y' : 'x',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { 
                        callbacks: { 
                            label: (context) => `${context.dataset.label}: ${formatMoney(horizontal ? context.parsed.x : context.parsed.y)}` 
                        } 
                    },
                    datalabels: {
                        display: Boolean(window.ChartDataLabels),
                        labels: {
                            titulo: {
                                color: '#ffffff',
                                anchor: 'center',
                                align: 'center',
                                rotation: horizontal ? 0 : -90, // Vertical (-90) solo en Gráfica 1, horizontal (0) en Gráfica 2
                                font: { weight: '800', size: 10 },
                                formatter: (value, context) => context.dataset.label.replace('Factura ', '').replace(' Social', '')
                            },
                            monto: {
                                color: '#2d3748', // Color oscuro o visible para el valor monetario sobre/al final de la barra
                                anchor: 'end',
                                align: 'end',
                                offset: 4,
                                font: { weight: '800', size: 10 },
                                formatter: (value) => formatMoney(value)
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: horizontal ? 'linear' : 'category',
                        beginAtZero: horizontal,
                        grid: { display: !horizontal },
                        ticks: horizontal ? { callback: (value) => formatMoney(value) } : {},
                    },
                    y: {
                        type: horizontal ? 'category' : 'linear',
                        beginAtZero: !horizontal,
                        grid: { display: horizontal },
                        ticks: horizontal ? {} : { callback: (value) => formatMoney(value) },
                    },
                },
            }
        });
    }

    async function renderChartForTab(tab, months) {
        try {
            await ensureChartLibraries();
            if (tab === 'grafica1' && !chartHistorial) {
                chartHistorial = createChart('chartHistorial', months, false, true);
            }
            if (tab === 'grafica2' && !chartApilada) {
                chartApilada = createChart('chartApilada', months, true, true);
            }
            if (tab === 'sin-aporte' && !chartSinAporte) {
                chartSinAporte = createChart('chartSinAporte', months, false, false);
            }
        } catch (error) {
            const target = document.getElementById(`chart-error-${tab}`);
            if (target) target.textContent = formatErrorMessage(error);
        }
    }

    function activateTab(tab, months) {
        document.querySelectorAll('.as-tab-content').forEach((element) => element.classList.toggle('active', element.id === `tab-${tab}`));
        document.querySelectorAll('.as-tab-btn').forEach((element) => element.classList.toggle('active', element.dataset.tab === tab));
        if (tab === 'grafica1' || tab === 'grafica2') renderChartForTab(tab, months);
    }

    function renderSolicitudForm(distribuidoraId, correlativo) {
        return `
            <div class="as-form-solicitud active" id="tsSolicitudForm">
                <p class="as-actividad">Completa tus datos para solicitar el Aporte a la Tarifa Social. Debes adjuntar tus documentos obligatorios.</p>
                <div class="form-group"><label>Nombre completo</label><input type="text" id="tsNombre" required></div>
                <div class="form-group"><label>Dirección completa</label><textarea id="tsDireccion" rows="2" required></textarea></div>
                <div class="form-group"><label>DPI (13 dígitos)</label><input type="text" id="tsDpi" inputmode="numeric" maxlength="13" required></div>
                <div class="form-group"><label>Teléfono (opcional)</label><input type="text" id="tsTelefono" inputmode="numeric"></div>
                <div class="form-group"><label>DPI Frente</label><input type="file" id="tsDpiFrontal" accept="image/jpeg,image/png,image/webp" required></div>
                <div class="form-group"><label>DPI Reverso</label><input type="file" id="tsDpiReverso" accept="image/jpeg,image/png,image/webp" required></div>
                <div class="form-group"><label>Factura de Luz</label><input type="file" id="tsFactura" accept="image/jpeg,image/png,image/webp" required></div>
                <div class="form-group"><label>Fachada (foto 1, opcional)</label><input type="file" id="tsFachada1" accept="image/jpeg,image/png,image/webp"></div>
                <div class="form-group"><label>Fachada (foto 2, opcional)</label><input type="file" id="tsFachada2" accept="image/jpeg,image/png,image/webp"></div>
                <button type="button" class="as-btn" id="tsEnviarSolicitud">Enviar solicitud</button>
                <div id="tsSolicitudMensaje"></div>
            </div>
        `;
    }

    function wireSolicitudForm(distribuidoraId, correlativo) {
        const btn = document.getElementById('tsEnviarSolicitud');
        if (!btn) {
            return;
        }

        btn.addEventListener('click', async () => {
            const values = {
                nombre: document.getElementById('tsNombre')?.value.trim() || '',
                direccion: document.getElementById('tsDireccion')?.value.trim() || '',
                dpi: document.getElementById('tsDpi')?.value.trim() || '',
                telefono: document.getElementById('tsTelefono')?.value.trim() || '',
                dpi_frontal: document.getElementById('tsDpiFrontal')?.files[0] || null,
                dpi_reverso: document.getElementById('tsDpiReverso')?.files[0] || null,
                factura: document.getElementById('tsFactura')?.files[0] || null,
                fachada1: document.getElementById('tsFachada1')?.files[0] || null,
                fachada2: document.getElementById('tsFachada2')?.files[0] || null,
            };

            const mensajeEl = document.getElementById('tsSolicitudMensaje');
            if (!values.nombre || !values.direccion || values.dpi.length < 13 || !values.dpi_frontal || !values.dpi_reverso || !values.factura) {
                mensajeEl.innerHTML = '<p class="as-error">Completa todos los campos obligatorios y adjunta los documentos requeridos.</p>';
                return;
            }

            btn.disabled = true;
            mensajeEl.innerHTML = '<p class="as-loading">Enviando tu solicitud…</p>';

            try {
                const data = await createTicket(distribuidoraId, correlativo, values);
                let html = `<div class="as-ticket"><div class="num">${escapeHtml(data.codigo_referencia || '')}</div><div>${escapeHtml(data.message || 'Solicitud enviada correctamente.')}</div>`;
                if (data.pdf_download_url) {
                    html += `<a class="as-btn-pdf" href="${escapeHtml(data.pdf_download_url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(reportText.receipt)}</a>`;
                }
                html += '</div>';
                mensajeEl.innerHTML = html;
            } catch (error) {
                mensajeEl.innerHTML = `<p class="as-error">${escapeHtml(formatErrorMessage(error))}</p>`;
                btn.disabled = false;
            }
        });
    }

    async function renderHistorialYears(distribuidoraId, correlativo, years) {
        const wrap = document.createElement('div');
        wrap.innerHTML = `
            <p class="as-actividad">Selecciona el año del historial que deseas revisar:</p>
            <select id="tsHistorialAnio" style="margin-bottom:12px;"></select>
        `;
        const select = wrap.querySelector('#tsHistorialAnio');
        years.forEach((year) => {
            const opt = document.createElement('option');
            opt.value = year;
            opt.textContent = year;
            select.appendChild(opt);
        });

        const linkHolder = document.createElement('div');
        wrap.appendChild(linkHolder);

        async function loadYear(year) {
            linkHolder.innerHTML = '<p class="as-loading">Consultando historial…</p>';
            try {
                const data = await queryTarifaSocial(distribuidoraId, correlativo, year);
                if (data.historial_encontrado && data.historial_pdf_url) {
                    linkHolder.innerHTML = `<a class="as-btn-pdf" href="${escapeHtml(data.historial_pdf_url)}" target="_blank" rel="noopener noreferrer">Descargar Historial ${escapeHtml(year)}</a>`;
                } else {
                    linkHolder.innerHTML = `<p class="as-mensaje info"><p>No se encontró historial para el año ${escapeHtml(year)}.</p></p>`;
                }
            } catch (error) {
                linkHolder.innerHTML = `<p class="as-error">${escapeHtml(formatErrorMessage(error))}</p>`;
            }
        }

        if (select) {
            select.addEventListener('change', () => loadYear(select.value));
        }
        await loadYear(years[0]);

        return wrap;
    }

    async function renderResultado(distribuidoraId, correlativo, data) {
        destroyCharts();
        const months = normalizeMonths(data);
        const msg = getResultMessage(data);
        const totalAporte = months.reduce((sum, month) => sum + Math.abs(month.aporte_inde), 0);
        const beneficioTarifa = numberValue(data.beneficio_tarifa_social_total)
            || months.reduce((sum, month) => sum + month.beneficio_tarifa_social, 0);
        const beneficioTotal = numberValue(data.ahorro_total) || beneficioTarifa + totalAporte;
        const header = `
            <p class="as-nombre">${escapeHtml(data.nombre_usuario || correlativo)}</p>
            <p class="as-actividad">Actividad económica: <span>${escapeHtml(data.activ_economica || 'No disponible')}</span></p>
            <p class="as-actividad">Consumo promedio: <span>${formatKwh(data.consumo_promedio)}</span></p>
            <div class="as-beneficio-hero"><div class="as-beneficio-hero-label">Beneficio Total</div><div class="as-beneficio-hero-val">${formatMoney(beneficioTotal)}</div></div>`;

        if (data.tiene_aporte === true) {
            resultado.innerHTML = `${header}
                <div class="as-tabs">
                    <button type="button" class="as-tab-btn active" data-tab="resumen">Resumen</button>
                    <button type="button" class="as-tab-btn" data-tab="grafica1">Gráfica 1</button>
                    <button type="button" class="as-tab-btn" data-tab="grafica2">Gráfica 2</button>
                </div>
                <div class="as-tab-content active" id="tab-resumen">
                    <div class="as-formula-final-row">
                        <div class="as-formula-box"><div class="as-formula-label">Beneficio de Tarifa Social</div><div class="as-formula-val verde">${formatMoney(beneficioTarifa)}</div></div>
                        <span class="as-formula-op">+</span>
                        <div class="as-formula-box"><div class="as-formula-label">Beneficio de Aporte Social</div><div class="as-formula-val verde">${formatMoney(totalAporte)}</div></div>
                        <span class="as-formula-op">=</span>
                        <div class="as-formula-final"><div class="as-formula-label">Beneficio Social</div><div class="as-formula-val">${formatMoney(beneficioTotal)}</div></div>
                    </div>
                    <div class="as-mensaje ${msg.clase}"><p>${escapeHtml(msg.texto)}</p></div>
                    ${monthlyTable(months)}
                    <div class="as-resumen-final"><button type="button" class="as-btn-pdf as-btn-secundario" data-tab-target="grafica1">Ver gráfica de consumos</button><button class="as-btn-pdf" id="tsOpenReportModal" type="button">${escapeHtml(reportText.open)}</button><div class="as-resumen-final-total"><div class="label">Beneficio Total</div><div class="val">${formatMoney(beneficioTotal)}</div></div></div>
                </div>
                <div class="as-tab-content" id="tab-grafica1"><p class="as-actividad">Historial de consumos en los últimos 3 meses</p><div class="as-legend"><span><i style="background:#00733d"></i>Factura sin Tarifa Social</span><span><i style="background:#1a6bbf"></i>Factura con Tarifa Social</span><span><i style="background:#4de88a"></i>Aporte INDE</span></div><div class="as-chart-box"><canvas id="chartHistorial" aria-label="Historial mensual de beneficios"></canvas></div><p class="as-error" id="chart-error-grafica1"></p></div>
                <div class="as-tab-content" id="tab-grafica2"><p class="as-actividad">Historial de consumos en los últimos 3 meses, vista horizontal</p><div class="as-legend"><span><i style="background:#00733d"></i>Factura sin Tarifa Social</span><span><i style="background:#1a6bbf"></i>Factura con Tarifa Social</span><span><i style="background:#4de88a"></i>Aporte INDE</span></div><div class="as-chart-box horizontal"><canvas id="chartApilada" aria-label="Historial mensual horizontal de beneficios"></canvas></div><p class="as-error" id="chart-error-grafica2"></p></div>`;
        } else {
            resultado.innerHTML = `${header}
                <div class="as-mensaje ${msg.clase}"><p>${escapeHtml(msg.texto)}</p></div>
                <div class="as-stats-3"><div class="as-stat-box"><div class="as-stat-label">Factura sin Tarifa Social</div><div class="as-stat-val">${formatMoney(data.costo_no_social_total)}</div></div><span class="as-stats-op">−</span><div class="as-stat-box"><div class="as-stat-label">Factura con Tarifa Social</div><div class="as-stat-val">${formatMoney(data.costo_social_total)}</div></div><span class="as-stats-op">=</span><div class="as-stat-box"><div class="as-stat-label">Usted ahorró</div><div class="as-stat-val verde">${formatMoney(beneficioTotal)}</div></div></div>
                <p class="as-actividad">Precio sin Tarifa Social vs. precio con Tarifa Social por mes</p><div class="as-legend"><span><i style="background:#00733d"></i>Sin Tarifa Social</span><span><i style="background:#1a6bbf"></i>Con Tarifa Social</span></div><div class="as-chart-box"><canvas id="chartSinAporte" aria-label="Comparación mensual de tarifa social"></canvas></div><p class="as-error" id="chart-error-sin-aporte"></p>${monthlyTable(months)}`;
            renderChartForTab('sin-aporte', months);
        }

        resultado.querySelectorAll('.as-tab-btn').forEach((button) => {
            if (button) {
                button.addEventListener('click', () => activateTab(button.dataset.tab, months));
            }
        });
        resultado.querySelectorAll('[data-tab-target]').forEach((button) => {
            if (button) {
                button.addEventListener('click', () => activateTab(button.dataset.tabTarget, months));
            }
        });

        document.getElementById('tsOpenReportModal')?.addEventListener('click', () => {
            openReportModal(distribuidoraId, correlativo, data.nombre_usuario || '');
        });

    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const distribuidoraId = parseInt(distSelect?.value || '0', 10);
        const correlativo = (nisInput?.value || '').trim();

        if (!distribuidoraId || !correlativo) {
            resultado.innerHTML = '<p class="as-error">Selecciona tu distribuidora e ingresa tu NIS o correlativo.</p>';
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
        }
        resultado.innerHTML = '<p class="as-loading">Consultando...</p>';

        try {
            const data = await queryTarifaSocial(distribuidoraId, correlativo);
            await renderResultado(distribuidoraId, correlativo, data);
        } catch (error) {
            resultado.innerHTML = `<p class="as-error">${escapeHtml(formatErrorMessage(error))}</p>`;
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        }
    });
})();
</script>
