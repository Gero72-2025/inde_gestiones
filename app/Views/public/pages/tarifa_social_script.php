<script>
(function () {
    const form = document.getElementById('tsForm');
    const distSelect = document.getElementById('tsDistribuidora');
    const nisInput = document.getElementById('tsNis');
    const submitBtn = document.getElementById('tsSubmit');
    const resultado = document.getElementById('tsResultado');

    if (!form || !resultado) {
        return;
    }

    const getCipherKey = () => window.PortalConfig?.cipherKey || '';
    const getCsrfToken = () => window.PortalConfig?.csrfToken || '';
    const getCsrfName = () => window.PortalConfig?.csrfName || 'csrf_test_name';

    const ENDPOINTS = {
        consultarNis: <?= json_encode(site_url('api/ecoe/ts/consultar-nis')) ?>,
        crearTicket: <?= json_encode(site_url('api/ecoe/ts/crear-ticket')) ?>,
    };

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

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getResultMessage(data) {
        const encontrado = data && data.encontrado === true;
        const bloqueado = data && data.bloqueado === true;

        if (encontrado) {
            return bloqueado
                ? { clase: 'alerta', texto: 'El correlativo consultado supera el límite de consumo permitido para Tarifa Social. No eres elegible para el aporte.' }
                : { clase: 'info', texto: 'El correlativo ingresado se encuentra bloqueado para solicitar el aporte a Tarifa Social.' };
        }

        return { clase: 'info', texto: 'El correlativo ingresado no se encuentra bloqueado para solicitar el aporte a Tarifa Social.' };
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
                    html += `<a class="as-btn-pdf" href="${escapeHtml(data.pdf_download_url)}" target="_blank" rel="noopener noreferrer">Descargar comprobante PDF</a>`;
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

        select.addEventListener('change', () => loadYear(select.value));
        await loadYear(years[0]);

        return wrap;
    }

    async function renderResultado(distribuidoraId, correlativo, data) {
        const msg = getResultMessage(data);
        let html = '';

        if (data.nombre_usuario) {
            html += `<p class="as-nombre">${escapeHtml(data.nombre_usuario)}</p>`;
            html += `<p class="as-actividad">Actividad económica: <span>${escapeHtml(data.activ_economica || 'No disponible')}</span></p>`;
            if (data.consumo_kwh !== undefined) {
                html += `<p class="as-actividad">Consumo promedio: <span>${Number(data.consumo_kwh).toFixed(0)} kWh</span></p>`;
            }
        }

        html += `<div class="as-mensaje ${msg.clase}"><p>${escapeHtml(msg.texto)}</p></div>`;

        resultado.innerHTML = html;

        const historialYears = buildYearOptions(data.historial_years);

        if (data.historial_encontrado && historialYears.length > 0) {
            const yearsBlock = await renderHistorialYears(distribuidoraId, correlativo, historialYears);
            resultado.appendChild(yearsBlock);
        } else if (data.historial_encontrado && data.historial_pdf_url) {
            const link = document.createElement('a');
            link.className = 'as-btn-pdf';
            link.href = data.historial_pdf_url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.textContent = 'Descargar Historial';
            resultado.appendChild(link);
        }

        if (data.encontrado === false) {
            const formWrap = document.createElement('div');
            formWrap.innerHTML = renderSolicitudForm(distribuidoraId, correlativo);
            resultado.appendChild(formWrap.firstElementChild);
            wireSolicitudForm(distribuidoraId, correlativo);
        }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const distribuidoraId = parseInt(distSelect?.value || '0', 10);
        const correlativo = (nisInput?.value || '').trim();

        if (!distribuidoraId || !correlativo) {
            resultado.innerHTML = '<p class="as-error">Selecciona tu distribuidora e ingresa tu NIS o correlativo.</p>';
            return;
        }

        submitBtn.disabled = true;
        resultado.innerHTML = '<p class="as-loading">Consultando...</p>';

        try {
            const data = await queryTarifaSocial(distribuidoraId, correlativo);
            await renderResultado(distribuidoraId, correlativo, data);
        } catch (error) {
            resultado.innerHTML = `<p class="as-error">${escapeHtml(formatErrorMessage(error))}</p>`;
        } finally {
            submitBtn.disabled = false;
        }
    });
})();
</script>
