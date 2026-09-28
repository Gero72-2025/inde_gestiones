<?php
/**
 * Página de Chat ECOE Flotante
 * Se integra en el layout principal con hero y main
 *
 * Variables esperadas:
 *  $distribuidoras – array de distribuidoras activas para uso en consulta de NIS
 */
$distribuidoras = array_values((array) ($distribuidoras ?? []));
$distribuidoraMap = [];
foreach ($distribuidoras as $dist) {
    $nombre = trim(strtolower((string) ($dist['nombre'] ?? '')));
    $id = isset($dist['id']) ? (int) $dist['id'] : 0;
    if ($nombre !== '' && $id > 0) {
        $distribuidoraMap[$nombre] = $id;
    }
}
?>

<style>
    /* Variables de colores */
    :root {
        --primary: #003366;
        --primary-light: #1a6bbf;
        --secondary: #00b347;
        --secondary-dark: #008f39;
        --danger: #e53935;
        --text-dark: #1a2a3a;
        --text-light: #4a5a6a;
        --border-light: rgba(0, 51, 102, 0.12);
        --bg-light: #f0f4f8;
    }

    /* Chat Container - Flotante pero en contexto */
    .ecoe-chat-widget {
        position: relative;
        width: 100%;
        /* max-width: 500px; */
        margin: 0 auto;
        height: 600px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 16px 60px rgba(0, 51, 102, 0.2);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid rgba(0, 51, 102, 0.12);
    }

    /* Encabezado */
    .chat-header-widget {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: white;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 4px 14px rgba(0, 51, 102, 0.15);
        flex-shrink: 0;
    }

    .chat-header-left {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
    }

    .chat-avatar {
        width: 38px;
        height: 38px;
        background: rgba(255, 255, 255, 0.15);
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .chat-header-info h3 {
        font-size: 0.9rem;
        font-weight: 700;
        margin: 0;
        line-height: 1.2;
    }

    .chat-header-sub {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 2px;
    }

    .online-dot {
        width: 5px;
        height: 5px;
        background: #4de88a;
        border-radius: 50%;
        animation: pulse 1.8s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }

    .chat-header-actions {
        display: flex;
        gap: 6px;
    }

    .chat-btn-icon {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        padding: 0;
    }

    .chat-btn-icon:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: scale(1.05);
    }

    .chat-btn-icon svg {
        width: 14px;
        height: 14px;
    }

    /* Área de mensajes */
    .chat-messages-widget {
        flex: 1;
        overflow-y: auto;
        padding: 16px 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        scroll-behavior: smooth;
        background: #fff;
    }

    .chat-messages-widget::-webkit-scrollbar {
        width: 5px;
    }

    .chat-messages-widget::-webkit-scrollbar-track {
        background: transparent;
    }

    .chat-messages-widget::-webkit-scrollbar-thumb {
        background: rgba(0, 51, 102, 0.15);
        border-radius: 3px;
    }

    /* Mensajes */
    .msg-group {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .msg-avatar {
        width: 28px;
        height: 28px;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 0.65rem;
        flex-shrink: 0;
    }

    .msg-bubble {
        max-width: 70%;
        padding: 9px 12px;
        border-radius: 4px 12px 12px 12px;
        font-size: 0.8rem;
        line-height: 1.5;
        box-shadow: 0 2px 8px rgba(0, 51, 102, 0.1);
    }

    .msg-bot .msg-bubble {
        background: white;
        color: var(--text-dark);
        border: 1px solid var(--border-light);
    }

    .msg-user {
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    .msg-user .msg-bubble {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: white;
        border-radius: 12px 12px 4px 12px;
    }

    .msg-time {
        font-size: 0.6rem;
        color: rgba(0, 0, 0, 0.3);
        margin-top: 3px;
        text-align: right;
    }

    .msg-user .msg-time {
        color: rgba(255, 255, 255, 0.5);
    }

    /* Opciones */
    .msg-options {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin: 6px 0;
        padding-left: 36px;
        animation: slideIn 0.3s ease;
    }

    .option-btn {
        background: white;
        border: 1.5px solid rgba(0, 51, 102, 0.2);
        color: var(--primary);
        padding: 5px 10px;
        border-radius: 18px;
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .option-btn:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        transform: translateY(-1px);
        box-shadow: 0 3px 10px rgba(0, 51, 102, 0.25);
    }

    .option-btn.success {
        border-color: rgba(0, 143, 57, 0.35);
        color: var(--secondary-dark);
    }

    .option-btn.success:hover {
        background: var(--secondary);
        border-color: var(--secondary);
        color: white;
    }

    /* Formularios */
    .msg-form {
        background: white;
        border-radius: 8px;
        padding: 10px 12px;
        margin: 6px 0;
        margin-left: 36px;
        border: 1px solid var(--border-light);
        border-top: 3px solid var(--secondary);
        box-shadow: 0 2px 8px rgba(0, 51, 102, 0.08);
        animation: slideIn 0.3s ease;
    }

    .form-group {
        margin-bottom: 8px;
    }

    .form-group label {
        display: block;
        font-size: 0.65rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 3px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        border: 1px solid rgba(0, 51, 102, 0.2);
        border-radius: 6px;
        padding: 6px 8px;
        font-size: 0.75rem;
        font-family: inherit;
        color: var(--text-dark);
        outline: none;
        background: #f8fafc;
        transition: all 0.2s;
    }

    .form-group input,
    .form-group select {
        height: 32px;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 90px;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: var(--primary);
        background: white;
    }

    .form-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 8px;
    }

    .form-submit,
    .form-cancel {
        width: 100%;
        border: none;
        border-radius: 6px;
        padding: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        transition: all 0.2s;
    }

    .form-submit {
        background: linear-gradient(135deg, var(--secondary), #00b347);
        color: white;
        margin-top: 0;
    }

    .form-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 143, 57, 0.3);
    }

    .form-cancel {
        background: white;
        color: var(--primary);
        border: 1px solid rgba(0, 51, 102, 0.2);
    }

    .form-cancel:hover {
        background: rgba(0, 51, 102, 0.06);
    }

    .form-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 143, 57, 0.3);
    }

    /* Ticket */
    .msg-ticket {
        background: linear-gradient(135deg, rgba(0, 143, 57, 0.08), rgba(0, 77, 153, 0.05));
        border: 1px solid rgba(0, 143, 57, 0.25);
        border-radius: 8px;
        padding: 10px 12px;
        margin-left: 36px;
        animation: slideIn 0.3s ease;
    }

    .ticket-number {
        font-size: 1rem;
        font-weight: 800;
        color: var(--primary);
    }

    .ticket-label {
        font-size: 0.65rem;
        color: var(--text-light);
        margin-top: 3px;
    }

    .ticket-badge {
        display: inline-block;
        background: var(--secondary);
        color: white;
        font-size: 0.6rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 100px;
        margin-top: 6px;
        text-transform: uppercase;
    }

    /* Footer - COMENTADO (Por mejorar con nueva funcionalidad) */
    /*
    .chat-footer-widget {
        background: white;
        border-top: 1px solid var(--border-light);
        padding: 10px 12px;
        display: flex;
        gap: 6px;
        align-items: center;
        flex-shrink: 0;
        box-shadow: 0 -2px 8px rgba(0, 51, 102, 0.05);
    }

    .chat-input-widget {
        flex: 1;
        border: 1.5px solid rgba(0, 51, 102, 0.15);
        border-radius: 18px;
        padding: 8px 12px;
        font-size: 0.75rem;
        font-family: inherit;
        outline: none;
        transition: border-color 0.2s;
    }

    .chat-input-widget:focus {
        border-color: var(--primary);
        background: #f8fafc;
    }

    .chat-input-widget::placeholder {
        color: #9eaab5;
    }

    .chat-send-widget {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border: none;
        border-radius: 50%;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: all 0.2s;
        padding: 0;
    }

    .chat-send-widget:hover {
        transform: scale(1.08);
        box-shadow: 0 4px 12px rgba(0, 51, 102, 0.25);
    }

    .chat-send-widget svg {
        width: 14px;
        height: 14px;
    }
    */

    /* Gateway introducción */
    .ecoe-intro {
        background: linear-gradient(135deg, rgba(15, 109, 143, 0.08), rgba(26, 147, 111, 0.08));
        border: 1px solid rgba(15, 109, 143, 0.12);
        border-radius: 1.5rem;
        padding: 20px;
        margin-bottom: 24px;
        text-align: center;
    }

    .ecoe-intro h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 10px;
        color: var(--text-dark);
    }

    .ecoe-intro p {
        color: var(--text-light);
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    /* Responsivo */
    @media (max-width: 768px) {
        .ecoe-chat-widget {
            max-width: 100%;
            height: 500px;
        }

        .chat-messages-widget {
            padding: 12px 10px;
        }

        /* .chat-footer-widget {
            padding: 8px 10px;
        } */

        .msg-options {
            padding-left: 34px;
        }

        .msg-form {
            margin-left: 34px;
        }

        .msg-ticket {
            margin-left: 34px;
        }

        .ecoe-intro {
            padding: 16px;
        }

        .ecoe-intro h2 {
            font-size: 1.2rem;
        }
    }

    @media (max-width: 480px) {
        .ecoe-chat-widget {
            height: 400px;
            border-radius: 12px;
        }

        .chat-header-widget {
            padding: 10px 12px;
        }

        .chat-header-info h3 {
            font-size: 0.8rem;
        }

        .chat-messages-widget {
            padding: 10px 8px;
            gap: 8px;
        }

        /* .chat-footer-widget {
            padding: 8px;
        } */

        .msg-bubble {
            max-width: 85%;
            font-size: 0.75rem;
        }

        .msg-options {
            gap: 5px;
            padding-left: 32px;
        }

        .option-btn {
            padding: 4px 8px;
            font-size: 0.65rem;
        }
    }
</style>

<!-- Introducción -->
<div class="ecoe-intro animate__animated animate__fadeIn">
    <h2>💬 Asistente Virtual INDE · ECOE</h2>
    <p>Bienvenido. Selecciona una opción abajo y te ayudaremos con tu consulta</p>
</div>

<!-- Widget Chat Flotante -->
<div class="ecoe-chat-widget">
    <!-- Header -->
    <div class="chat-header-widget">
        <div class="chat-header-left">
            <div class="chat-avatar">🤖</div>
            <div>
                <h3>Asistente INDE · ECOE</h3>
                <div class="chat-header-sub">
                    <span class="online-dot"></span>
                    <span>En línea</span>
                </div>
            </div>
        </div>
        <div class="chat-header-actions">
            <button class="chat-btn-icon" id="chatRestart" title="Reiniciar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 .49-4.2"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Mensajes -->
    <div class="chat-messages-widget" id="chatMsgs"></div>

    <!-- Footer - COMENTADO (Por mejorar con nueva funcionalidad) -->
    <!--
    <div class="chat-footer-widget">
        <input class="chat-input-widget" id="chatInput" type="text" placeholder="Escribe aquí…" autocomplete="off" />
        <button class="chat-send-widget" id="chatSend" title="Enviar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
        </button>
    </div>
    -->
</div>

<script>
    // Alias de elementos
    const chatMsgs = document.getElementById('chatMsgs');
    // const chatInput = document.getElementById('chatInput');  // COMENTADO
    // const chatSend = document.getElementById('chatSend');  // COMENTADO
    const chatRestart = document.getElementById('chatRestart');

    const getCipherKey = () => window.PortalConfig?.cipherKey || '';
    const getCsrfToken = () => window.PortalConfig?.csrfToken || '';
    const getCsrfName = () => window.PortalConfig?.csrfName || 'csrf_test_name';
    const ENDPOINTS = {
        consultarNis: <?= json_encode(site_url('api/ecoe/ts/consultar-nis')) ?>,
        crearTicket: <?= json_encode(site_url('api/ecoe/ts/crear-ticket')) ?>,
    };

    const DISTRIBUIDORAS = <?= json_encode($distribuidoraMap) ?>;

    function isMobileDevice() {
        const mobileUserAgent = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const smallScreen = window.innerWidth <= 768;
        return mobileUserAgent || smallScreen;
    }

    function getDistribuidoraId(key) {
        const slug = String(key).trim().toLowerCase();
        return DISTRIBUIDORAS[slug] || null;
    }

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
    let cryptoAvailable = null;

    function isCryptoAvailable() {
        if (cryptoAvailable !== null) {
            return cryptoAvailable;
        }
        try {
            cryptoAvailable = !!(crypto && crypto.subtle && crypto.subtle.digest);
        } catch {
            cryptoAvailable = false;
        }
        return cryptoAvailable;
    }

    function isLocalEnvironment() {
        const host = window.location.hostname;
        return host === 'localhost' || 
               host === '127.0.0.1' || 
               host.startsWith('192.168.') || 
               host.startsWith('10.') ||
               host.startsWith('172.');
    }

    async function getCryptoMaterials() {
        if (cryptoCache) {
            return cryptoCache;
        }

        if (!isCryptoAvailable()) {
            const isLocal = isLocalEnvironment();
            const protocol = window.location.protocol;
            let errorMsg = 'Web Crypto API no disponible.';
            
            if (isLocal && protocol === 'http:') {
                errorMsg += ' Para ambiente local, configura HTTPS con un certificado auto-firmado o usa localhost.';
            } else if (!isLocal) {
                errorMsg += ' Por favor accede mediante HTTPS.';
            }
            
            throw new Error(errorMsg);
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

        const cipherKey = getCipherKey();
        if (cipherKey && isBase64Like(normalized)) {
            if (typeof CryptoHelper !== 'undefined') {
                const dec = await CryptoHelper.decrypt(normalized, cipherKey);

                if (typeof dec === 'string') {
                    const text = dec.trim();
                    if (text === '') {
                        throw new Error('El payload descifrado está vacío.');
                    }
                    if (isBase64Like(text)) {
                        throw new Error('No se pudo descifrar la respuesta del servidor (problema con la clave de cifrado).');
                    }
                    try {
                        return JSON.parse(text);
                    } catch (_) {
                        return text;
                    }
                }

                return dec;
            }

            const decrypted = await decryptCipherPayload(normalized);
            if (typeof decrypted === 'string') {
                const text = decrypted.trim();
                if (text === '') {
                    throw new Error('El payload descifrado está vacío.');
                }
                if (isBase64Like(text)) {
                    throw new Error('No se pudo descifrar la respuesta del servidor (problema con la clave de cifrado).');
                }
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

        if (isBase64Like(body)) {
            if (!getCipherKey()) {
                throw new Error('Se recibió una respuesta cifrada pero no se encontró la clave de descifrado.');
            }
            return await decryptResponse(body);
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

    function getSuccessMessage(payload) {
        const result = (payload && typeof payload === 'object')
            ? (payload.data !== undefined ? payload.data : payload)
            : {};

        const encontrado = result.encontrado === true;
        const bloqueado = result.bloqueado === true;

        if (encontrado) {
            return bloqueado ?
                'El correlativo consultado supera el limite de consumo permitido para Tarifa Social. No eres elegible para el aporte.' :
                'El correlativo ingresado se encuentra bloqueado para solicitar el aporte a Tarifa Social.';
        }

        return 'El correlativo ingresado no se encuentra bloqueado para solicitar el aporte a Tarifa Social.';
    }

    async function queryTarifaSocial(distribuidoraId, correlativo, anio = null) {
        const fd = new FormData();
        fd.append('distribuidora_id', String(distribuidoraId));
        fd.append('correlativo', correlativo);
        if (anio) {
            fd.append('anio', String(anio));
        }

        const res = await postAjax(ENDPOINTS.consultarNis, fd);
        let data = (res && typeof res === 'object' && res.data !== undefined) ? res.data : res;

        if (typeof data === 'string' && getCipherKey() && isBase64Like(data)) {
            const decrypted = await decryptResponse(data);
            data = (decrypted && typeof decrypted === 'object' && decrypted.data !== undefined) ? decrypted.data : decrypted;
        }

        if (res && res.ok === false) {
            throw new Error(data && data.message ? data.message : 'Error en la consulta.');
        }

        return data;
    }

    function buildYearOptions(years) {
        return (Array.isArray(years) ? years : [])
            .filter(value => value !== null && value !== '' && value !== undefined)
            .map(value => ({ id: String(value), label: String(value) }));
    }

    async function showYearSelection(distribuidoraId, correlativo, resultado) {
        const historyYears = buildYearOptions(resultado && typeof resultado === 'object' ? resultado.historial_years : []);

        if (!historyYears.length) {
            return false;
        }

        await addBot('Selecciona el año del historial para revisar los datos:', 300);
        await addOptions(historyYears, async selectedYear => {
            const selectedYearValue = String(selectedYear ?? '').trim();
            await addBot(`Consultando historial del año <strong>${escapeHtml(selectedYearValue || 'sin año')}</strong>...`, 300);
            try {
                const selectedResult = await queryTarifaSocial(distribuidoraId, correlativo, selectedYearValue);
                const selectedHistorial = selectedResult && typeof selectedResult === 'object' ? selectedResult.historial : null;
                const selectedFound = Boolean(selectedResult && selectedResult.historial_encontrado);
                const selectedPdfUrl = selectedResult && typeof selectedResult === 'object' ? selectedResult.historial_pdf_url : null;

                if (selectedFound && selectedPdfUrl) {
                    await addBot(`✅ Historial encontrado para <strong>${escapeHtml(selectedYearValue || 'sin año')}</strong>.<br><a href="${escapeHtml(selectedPdfUrl)}" target="_blank" rel="noopener noreferrer">Descargar Historial</a>`, 400);
                } else {
                    await addBot(`No se encontró historial para el año <strong>${escapeHtml(selectedYearValue || 'sin año')}</strong>.`, 400);
                }
            } catch (error) {
                await addBot(`❌ Error al consultar el historial del año <strong>${escapeHtml(selectedYearValue || 'sin año')}</strong>: <strong>${formatErrorMessage(error)}</strong>`, 400);
            }
        });

        return true;
    }

    // Helpers
    const now = () => {
        const d = new Date();
        return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
    };

    const scroll = () => {
        setTimeout(() => {
            chatMsgs.scrollTop = chatMsgs.scrollHeight;
        }, 50);
    };

    // Agregar mensaje bot
    async function addBot(html, delay = 0) {
        return new Promise(resolve => {
            setTimeout(() => {
                const msgDiv = document.createElement('div');
                msgDiv.className = 'msg-group msg-bot';
                msgDiv.innerHTML = `
                    <div class="msg-avatar">IN</div>
                    <div class="msg-bubble">
                        <div style="display:flex; gap:3px;">
                            <span style="width:4px;height:4px;background:#b0bec5;border-radius:50%;animation:typing 1.2s infinite;"></span>
                            <span style="width:4px;height:4px;background:#b0bec5;border-radius:50%;animation:typing 1.2s infinite 0.2s;"></span>
                            <span style="width:4px;height:4px;background:#b0bec5;border-radius:50%;animation:typing 1.2s infinite 0.4s;"></span>
                        </div>
                    </div>
                `;
                chatMsgs.appendChild(msgDiv);
                scroll();

                setTimeout(() => {
                    msgDiv.querySelector('.msg-bubble').innerHTML = html + `<div class="msg-time">${now()}</div>`;
                    scroll();
                    resolve();
                }, 500);
            }, delay);
        });
    }

    // Agregar mensaje usuario
    function addUser(text) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'msg-group msg-user';
        msgDiv.innerHTML = `
            <div class="msg-bubble">${text}<div class="msg-time">${now()}</div></div>
        `;
        chatMsgs.appendChild(msgDiv);
        scroll();
    }

    // Agregar opciones
    function addOptions(opts, onSelect) {
        return new Promise(resolve => {
            setTimeout(() => {
                const optionsDiv = document.createElement('div');
                optionsDiv.className = 'msg-options';
                opts.forEach(opt => {
                    const btn = document.createElement('button');
                    btn.className = 'option-btn' + (opt.success ? ' success' : '');
                    btn.textContent = opt.label;
                    btn.onclick = async () => {
                        addUser(opt.label);
                        optionsDiv.querySelectorAll('button').forEach(button => {
                            button.disabled = true;
                            button.classList.add('is-disabled');
                        });
                        optionsDiv.remove();
                        try {
                            await onSelect(opt.id);
                        } finally {
                            resolve();
                        }
                    };
                    optionsDiv.appendChild(btn);
                });
                chatMsgs.appendChild(optionsDiv);
                scroll();
            }, 650);
        });
    }

    // Agregar formulario
    function addForm(fields, onSubmit, onCancel) {
        return new Promise(resolve => {
            setTimeout(() => {
                const formDiv = document.createElement('div');
                formDiv.className = 'msg-form';
                const formElem = document.createElement('form');
                formElem.className = 'chat-form';
                formElem.noValidate = true;

                let html = '';
                fields.forEach(f => {
                    const requiredAttr = f.required ? 'required' : '';
                    if (f.type === 'select') {
                        html += `
                            <div class="form-group">
                                <label>${f.label}</label>
                                <select id="form-${f.id}" ${requiredAttr}>
                                    <option value="">Selecciona una opción...</option>
                                    ${f.options.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                                </select>
                            </div>
                        `;
                    } else if (f.type === 'textarea') {
                        html += `
                            <div class="form-group">
                                <label>${f.label}</label>
                                <textarea id="form-${f.id}" placeholder="${f.placeholder || ''}" ${requiredAttr}></textarea>
                            </div>
                        `;
                    } else if (f.type === 'file') {
                        const acceptAttr = f.accept ? `accept="${f.accept}"` : '';
                        let captureAttr = '';
                        if (isMobileDevice() && f.capture) {
                            captureAttr = f.capture === true ? 'capture' : `capture="${f.capture}"`;
                        }
                        html += `
                            <div class="form-group">
                                <label>${f.label}</label>
                                <input type="file" id="form-${f.id}" ${acceptAttr} ${captureAttr} ${requiredAttr} />
                            </div>
                        `;
                    } else {
                        const inputMode = f.inputmode ? `inputmode="${f.inputmode}"` : '';
                        html += `
                            <div class="form-group">
                                <label>${f.label}</label>
                                <input type="${f.type || 'text'}" id="form-${f.id}" placeholder="${f.placeholder || ''}" ${requiredAttr} ${inputMode} />
                            </div>
                        `;
                    }
                });
                html += '<div class="form-actions">';
                html += '<button class="form-submit">✓ Enviar</button>';
                if (typeof onCancel === 'function') {
                    html += '<button type="button" class="form-cancel">↩ Cancelar</button>';
                }
                html += '</div>';

                formElem.innerHTML = html;
                formDiv.appendChild(formElem);

                formElem.addEventListener('submit', event => {
                    event.preventDefault();
                    if (!formElem.checkValidity()) {
                        formElem.classList.add('was-validated');
                        return;
                    }

                    const vals = {};
                    let invalid = false;

                    fields.forEach(f => {
                        const input = formElem.querySelector('#form-' + f.id);
                        if (!input) {
                            vals[f.id] = '';
                            return;
                        }

                        if (f.type === 'file') {
                            const file = input.files && input.files[0] ? input.files[0] : null;
                            vals[f.id] = file;
                            if (f.required && !file) {
                                invalid = true;
                            }
                            return;
                        }

                        vals[f.id] = (input.value || '').trim();
                    });

                    if (invalid) {
                        formElem.classList.add('was-validated');
                        return;
                    }

                    formDiv.remove();
                    onSubmit(vals);
                    resolve(vals);
                });

                if (typeof onCancel === 'function') {
                    const cancelButton = formElem.querySelector('.form-cancel');
                    if (cancelButton) {
                        cancelButton.addEventListener('click', () => {
                            formDiv.remove();
                            onCancel();
                            resolve(null);
                        });
                    }
                }

                chatMsgs.appendChild(formDiv);
                scroll();
            }, 650);
        });
    }

    async function createTicketFromChat(distribuidoraId, correlativo, values) {
        const fd = new FormData();
        fd.append('distribuidora_id', String(distribuidoraId));
        fd.append('correlativo', String(correlativo));
        fd.append('nombre', values.nombre || '');
        fd.append('direccion', values.direccion || '');
        fd.append('dpi', values.dpi || '');
        fd.append('telefono', values.telefono || '');

        ['dpi_frontal', 'dpi_reverso', 'factura', 'fachada1', 'fachada2'].forEach(name => {
            const file = values[name];
            if (file instanceof File) {
                fd.append(name, file);
            }
        });

        const res = await postAjax(ENDPOINTS.crearTicket, fd);
        const data = res.data ?? res;

        if (res && res.ok === false) {
            throw new Error(data && data.message ? data.message : 'Error al enviar la solicitud.');
        }

        return data;
    }

    async function showTarifaSocialFormFromChat(distribuidoraId, correlativo) {
        await addBot('Ahora puedes completar el formulario de solicitud para Tarifa Social desde aquí. Asegúrate de adjuntar tus documentos obligatorios.', 300);
        await addForm([
            { id: 'nombre', label: 'Nombre completo', required: true },
            { id: 'direccion', type: 'textarea', label: 'Dirección completa', required: true },
            { id: 'dpi', label: 'DPI (13 dígitos)', placeholder: 'Ej. 1234567890123', inputmode: 'numeric', required: true },
            { id: 'telefono', label: 'Teléfono', placeholder: 'Opcional' },
            { id: 'dpi_frontal', type: 'file', label: 'DPI Frente', accept: '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp', capture: 'environment', required: true },
            { id: 'dpi_reverso', type: 'file', label: 'DPI Reverso', accept: '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp', capture: 'environment', required: true },
            { id: 'factura', type: 'file', label: 'Factura de Luz', accept: '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp', capture: true, required: true },
            { id: 'fachada1', type: 'file', label: 'Fachada (foto 1)', accept: '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp', capture: true },
            { id: 'fachada2', type: 'file', label: 'Fachada (foto 2)', accept: '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp', capture: true },
        ], async vals => {
            await addBot('Enviando tu solicitud de Tarifa Social…', 300);
            try {
                const data = await createTicketFromChat(distribuidoraId, correlativo, vals);
                let message = '✅ Solicitud enviada correctamente.';
                if (data.codigo_referencia) {
                    message += `<br>Tu código de referencia es <strong>${escapeHtml(data.codigo_referencia)}</strong>`;
                }
                if (data.pdf_download_url) {
                    message += `<br><a href="${escapeHtml(data.pdf_download_url)}" target="_blank" rel="noopener noreferrer">Descargar comprobante PDF</a>`;
                }
                await addBot(message, 400);
            } catch (error) {
                await addBot(`❌ Error al enviar la solicitud: <strong>${formatErrorMessage(error)}</strong>`, 400);
            }
            endFlow();
        }, () => {
            addBot('Formulario cancelado. Puedes continuar con el chat.', 300);
            endFlow();
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Agregar ticket
    function addTicket(num, tipo) {
        const ticketDiv = document.createElement('div');
        ticketDiv.className = 'msg-ticket';
        ticketDiv.innerHTML = `
            <div style="font-size:0.65em;color:#4a5a6a;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">Ticket generado</div>
            <div class="ticket-number">#${num}</div>
            <div class="ticket-label">${tipo}</div>
            <div class="ticket-badge">✓ Recibido</div>
        `;
        chatMsgs.appendChild(ticketDiv);
        scroll();
    }

    // Flujo principal
    async function startFlow() {
        chatMsgs.innerHTML = '';
        await addBot('¡Hola! 👋 Bienvenido al asistente virtual del <strong>INDE · ECOE</strong>.<br>¿En qué puedo ayudarte hoy?', 200);
        addOptions([
            { id: 'ecoe', label: '1. Tarifa Social (ECOE)' },
            { id: 'gu', label: '2. Grandes Usuarios' },
            { id: 'eem', label: '3. Empresas Eléctricas Municipales' },
        ], id => {
            if (id === 'ecoe') flowECOE();
            else if (id === 'gu') flowGU();
            else if (id === 'eem') flowEEM();
        });
    }

    // Flujo ECOE/Tarifa Social
    async function flowECOE() {
        await addBot('Has seleccionado <strong>Tarifa Social (ECOE)</strong>.<br>¿Qué deseas consultar?', 300);
        addOptions([
            { id: 'quien', label: '1. ¿Quiénes lo reciben?' },
            { id: 'porque', label: '2. ¿Recibo el Beneficio Social?' },
            { id: 'back', label: '↩ Menú principal' },
        ], id => {
            if (id === 'back') startFlow();
            else if (id === 'quien') flowQuienRecibe();
            else if (id === 'porque') flowPorqueNo();
        });
    }

    async function flowQuienRecibe() {
        await addBot('📊 <strong>Rangos de Tarifa</strong>', 300);
        
        await addBot('<strong style="color: #008f39;">0-100 kWh</strong><br><strong>Aporte a la Tarifa Social:</strong> el INDE beneficia al 69.23% de la población (alrededor de más de 2.35 millones de hogares beneficiados) con un subsidio directo a su factura.', 400);
        
        await addBot('<strong style="color: #1a6bbf;">101-300 kWh</strong><br><strong>Tarifa Social:</strong> el INDE vende a precio preferencial a este sector de la población, con la tarifa aprobada por la CNEE. Corresponde al 26.48% de la población, equivalente a 899 mil hogares beneficiados.', 400);
        
        await addBot('<strong style="color: #8a8a8a;">301+ kWh</strong><br><strong>Tarifa No Social:</strong> el INDE vende a precios de tarifa plena, definidos por la CNEE. Representa el 4.29% de la población, equivalente a 146 mil hogares en este rango.', 400);
        
        await addBot('✅ <strong>Nota importante:</strong> El aporte directo aplica únicamente a los usuarios sin actividades comerciales, mercantiles, profesionales, institucionales, sociales u otras con fines lucrativos.', 300);
        
        addOptions([
            { id: 'back', label: '↩ Volver' },
            { id: 'inicio', label: '🏠 Menú principal' },
        ], id => id === 'back' ? flowECOE() : startFlow());
    }

    async function flowPorqueNo() {
        await addBot('Para verificar tu situación, primero dime: ¿cuál es tu <strong>empresa distribuidora</strong>?', 300);
        addOptions([
            { id: 'deocsa', label: 'DEOCSA', key: 4 },
            { id: 'deorsa', label: 'DEORSA', key: 3 },
            { id: 'eegsa', label: 'EEGSA', key: 1 },
            { id: 'emmj', label: 'EMM JOYABAJ', key: 2 },
            { id: 'emmr', label: 'EMM RETALHULEU', key: 5 },
            { id: 'emmi', label: 'EMM IXCAN', key: 6 },
            { id: 'otro', label: 'Otra distribuidora', key: 7 },
        ], async id => {
            if (id === 'otro') {
                await addBot('⚠️ El Aporte a la Tarifa Social del INDE <strong>aplica únicamente</strong> para clientes de <strong>EEGSA, DEOCSA y DEORSA</strong>.<br><br>Si tu distribuidora es diferente, lamentablemente no eres beneficiario de este programa.', 300);
                endFlow();
                return;
            }

            const distribuidoraId = getDistribuidoraId(id);
            if (!distribuidoraId) {
                await addBot('No pude determinar la distribuidora seleccionada. Por favor inténtalo nuevamente.', 300);
                return flowECOE();
            }

            await addBot(`Has seleccionado <strong>${id.toUpperCase()}</strong>. Ingrese tu información para proceder con la verificación.`, 300);
            addForm([
                { id: 'nis', label: 'NIS/Correlativo', placeholder: 'Tu identificador' },
            ], async vals => {
                const correlativo = (vals.nis || '').trim();
                if (!correlativo) {
                    await addBot('Debes ingresar tu NIS/Correlativo para continuar.', 300);
                    return flowPorqueNo();
                }

                await addBot(`✅ Datos recibidos. Verificando tu NIS: <strong>${correlativo}</strong>...`, 300);
                try {
                    const resultado = await queryTarifaSocial(distribuidoraId, correlativo);
                    const historial = resultado && typeof resultado === 'object' ? resultado.historial : null;
                    const historialEncontrado = Boolean(resultado && resultado.historial_encontrado);
                    const historialPdfUrl = resultado && typeof resultado === 'object' ? resultado.historial_pdf_url : null;

                    if (historial) {
                        console.info('[ECOE historial]', { correlativo, historial });
                        if (historial.ok === false) {
                            await addBot('⚠️ No fue posible consultar el historial externo de ECOE en este momento. La verificación continúa normalmente.', 300);
                        } else if (Array.isArray(historial.rows) && historial.rows.length > 0) {
                            console.info('[ECOE historial rows]', historial.rows);
                        } else {
                            console.info('[ECOE historial rows]', 'Sin resultados para el procedimiento almacenado.');
                        }
                    }

                    if (historialEncontrado) {
                        const years = buildYearOptions(resultado && typeof resultado === 'object' ? resultado.historial_years : []);
                        if (years.length > 0) {
                            await addBot(`✅ El correlativo ingresado ya cuenta con ayuda social.<br>Selecciona el año del historial que deseas revisar:`, 400);
                            await addOptions(years, async selectedYear => {
                                const selectedYearValue = String(selectedYear ?? '').trim();
                                await addBot(`✅ Consultando historial del año <strong>${escapeHtml(selectedYearValue || 'sin año')}</strong>...`, 300);
                                try {
                                    const selectedResult = await queryTarifaSocial(distribuidoraId, correlativo, selectedYearValue);
                                    const selectedPdfUrl = selectedResult && typeof selectedResult === 'object' ? selectedResult.historial_pdf_url : null;
                                    const selectedFound = Boolean(selectedResult && selectedResult.historial_encontrado);

                                    if (selectedFound && selectedPdfUrl) {
                                        await addBot(`✅ Historial encontrado para <strong>${escapeHtml(selectedYearValue || 'sin año')}</strong>.<br><a href="${escapeHtml(selectedPdfUrl)}" target="_blank" rel="noopener noreferrer">Descargar Historial</a>`, 400);
                                    } else {
                                        await addBot(`No se encontró historial para el año <strong>${escapeHtml(selectedYearValue || 'sin año')}</strong>.`, 400);
                                    }
                                } catch (error) {
                                    await addBot(`❌ Error al consultar el historial del año <strong>${escapeHtml(selectedYearValue || 'sin año')}</strong>: <strong>${formatErrorMessage(error)}</strong>`, 400);
                                }
                            });
                        } else if (historialPdfUrl) {
                            await addBot(`✅ El correlativo ingresado ya cuenta con ayuda social.<br><a href="${escapeHtml(historialPdfUrl)}" target="_blank" rel="noopener noreferrer">Descargar Historial</a>`, 400);
                        } else {
                            await addBot('✅ El correlativo ingresado ya cuenta con ayuda social.', 400);
                        }
                    } else {
                        await addBot(getSuccessMessage(resultado), 400);
                    }

                    if (resultado && resultado.encontrado === false) {
                        await addOptions([
                            { id: 'fill', label: '📝 Llenar Formulario', success: true },
                            { id: 'inicio', label: '🏠 Volver al inicio' },
                            { id: 'salir', label: '👋 Finalizar chat' },
                        ], async id => {
                            if (id === 'fill') {
                                await showTarifaSocialFormFromChat(distribuidoraId, correlativo);
                            } else if (id === 'inicio') {
                                startFlow();
                            } else {
                                await addBot('¡Gracias por contactar al <strong>INDE</strong>! Que tenga un excelente día. 😊', 300);
                            }
                        });
                        return;
                    }
                } catch (error) {
                    await addBot(`❌ Error al consultar el NIS: <strong>${formatErrorMessage(error)}</strong>`, 400);
                }
                endFlow();
            }, () => flowPorqueNo());
        });
            // await addBot('Completa el siguiente formulario para verificar tu cuenta:', 300);
            // addForm([
            //     { id: 'nis', label: 'NIS/Correlativo', placeholder: 'Tu identificador' },
            //     { id: 'consumo', label: 'Consumo (kWh)', type: 'number', placeholder: '75' },
            //     { id: 'email', label: 'Email de contacto', type: 'email', placeholder: 'tu@email.com' },
            // ], async vals => {
            //     await addBot(`✅ Datos recibidos. Verificando tu NIS: <strong>${vals.nis}</strong>...`, 300);
            //     addTicket('TS-' + Math.floor(10000 + Math.random() * 90000), 'Consulta Tarifa Social');
            //     endFlow();
            // });
        
    }

    // Flujo Suspensiones
    async function flowSusp() {
        await addBot('Has seleccionado <strong>Suspensiones Programadas</strong>.', 300);
        addOptions([
            { id: 'ver', label: '📅 Ver suspensiones de la semana' },
            { id: 'back', label: '↩ Menú principal' },
        ], async id => {
            if (id === 'back') startFlow();
            else {
                await addBot('🔌 <strong>Suspensiones programadas:</strong><br><br>📍 Zona 1, Guatemala — Mié, 08:00–14:00<br>📍 Retalhuleu — Jue, 07:00–12:00<br>📍 Zacapa — Vie, 09:00–15:00<br><br><em>Información actualizada semanalmente.</em>', 300);
                endFlow();
            }
        });
    }

    // Flujo Grandes Usuarios
    async function flowGU() {
        await addBot('Has seleccionado <strong>Grandes Usuarios</strong>. ¿Cuál es tu situación?', 300);
        addOptions([
            { id: 'nuevo', label: '🆕 Quiero recibir oferta de suministro' },
            { id: 'actual', label: '✅ Ya soy Gran Usuario' },
            { id: 'back', label: '↩ Menú principal' },
        ], async id => {
            if (id === 'back') startFlow();
            else if (id === 'nuevo') {
                await addBot('Completa el formulario para solicitar una oferta:', 300);
                addForm([
                    { id: 'empresa', label: 'Nombre de la empresa', placeholder: 'Razón social' },
                    { id: 'kw', label: 'Demanda estimada (kW)', type: 'number', placeholder: '500' },
                    { id: 'email', label: 'Email', type: 'email', placeholder: 'contacto@empresa.com' },
                    { id: 'tel', label: 'Teléfono', type: 'tel', placeholder: '2222-0000' },
                ], async vals => {
                    await addBot('✅ Solicitud GU-1 recibida. Un asesor te contactará pronto.', 300);
                    addTicket('GU1-' + Math.floor(10000 + Math.random() * 90000), 'Solicitud Oferta de Suministro — Grandes Usuarios');
                    endFlow();
                });
            } else {
                await addBot('Completa el formulario para gestionar tu cuenta:', 300);
                addForm([
                    { id: 'amm', label: 'No. de Registro AMM', placeholder: 'Número de registro' },
                    { id: 'empresa', label: 'Nombre de la empresa', placeholder: 'Razón social' },
                    { id: 'gestion', label: 'Gestión requerida', placeholder: 'Describa brevemente' },
                    { id: 'email', label: 'Email', type: 'email', placeholder: 'contacto@empresa.com' },
                ], async vals => {
                    await addBot('✅ Formulario GU-2 enviado. El equipo de ECOE lo revisará.', 300);
                    addTicket('GU2-' + Math.floor(10000 + Math.random() * 90000), 'Gestión Cuenta Gran Usuario');
                    endFlow();
                });
            }
        });
    }

    // Flujo EEM
    async function flowEEM() {
        await addBot('Has seleccionado <strong>Empresas Eléctricas Municipales</strong>.<br>¿Qué gestión necesitas?', 300);
        addOptions([
            { id: 'con', label: '🔌 Nueva Conexión' },
            { id: 'seg', label: '📂 Seguimiento de Expediente' },
            { id: 'cap', label: '🎓 Capacitación Técnica' },
            { id: 'back', label: '↩ Menú principal' },
        ], async id => {
            if (id === 'back') startFlow();
            else if (id === 'con') {
                await addBot('Completa el <strong>Formulario EEM-1</strong> para solicitar nueva conexión:', 300);
                addForm([
                    { id: 'eem', label: 'Empresa Eléctrica Municipal', type: 'select', options: ['EEM Quetzaltenango', 'EEM San Marcos', 'EMRE', 'Otra'] },
                    { id: 'punto', label: 'Punto de Entrega', placeholder: 'Ubicación exacta' },
                    { id: 'kva', label: 'Capacidad solicitada (kVA)', type: 'number', placeholder: '250' },
                    { id: 'email', label: 'Email de contacto', type: 'email', placeholder: 'eem@municipio.gob.gt' },
                ], async vals => {
                    await addBot('✅ Solicitud enviada correctamente.', 300);
                    addTicket('EEM1-' + Math.floor(10000 + Math.random() * 90000), 'Nueva Conexión — EEM');
                    endFlow();
                });
            } else if (id === 'seg') {
                await addBot('Ingresa tu número de gestión para dar seguimiento:', 300);
                addForm([
                    { id: 'gestion', label: 'No. de Gestión', placeholder: 'Ej. EEM-2024-00123' },
                    { id: 'eem', label: 'Nombre de la EEM', placeholder: 'Empresa Eléctrica Municipal' },
                ], async vals => {
                    await addBot(`✅ Consultando expediente <strong>${vals.gestion}</strong>...<br>📊 Estado: <strong>En revisión</strong>`, 300);
                    endFlow();
                });
            } else {
                await addBot('Completa el <strong>Formulario EEM-3</strong> para solicitar capacitación técnica:', 300);
                addForm([
                    { id: 'eem', label: 'Empresa Eléctrica Municipal', placeholder: 'Nombre de la EEM' },
                    { id: 'tema', label: 'Tema de capacitación', placeholder: 'Ej. Mantenim. de líneas' },
                    { id: 'participantes', label: 'No. de participantes', type: 'number', placeholder: '10' },
                    { id: 'contacto', label: 'Email de contacto', type: 'email', placeholder: 'capacitacion@eem.gob.gt' },
                ], async vals => {
                    await addBot('✅ Solicitud de capacitación recibida.', 300);
                    addTicket('EEM3-' + Math.floor(10000 + Math.random() * 90000), 'Capacitación Técnica — EEM');
                    endFlow();
                });
            }
        });
    }

    // Finalizar flujo
    async function endFlow() {
        addOptions([
            { id: 'inicio', label: '🏠 Volver al inicio', success: true },
            { id: 'salir', label: '👋 Finalizar chat' },
        ], async id => {
            if (id === 'inicio') startFlow();
            else await addBot('¡Gracias por contactar al <strong>INDE</strong>! Que tenga un excelente día. 😊', 300);
        });
    }

    // Eventos
    /* COMENTADO - Por mejorar con nueva funcionalidad
    chatSend.onclick = () => {
        const val = chatInput.value.trim();
        if (!val) return;
        addUser(val);
        chatInput.value = '';
        setTimeout(() => {
            addBot('Usa las opciones del menú para navegar. Si tienes una consulta específica, un asesor de ECOE estará disponible para ayudarte.', 300);
        }, 400);
    };

    chatInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') chatSend.click();
    });
    */

    if (chatRestart) {
        chatRestart.addEventListener('click', startFlow);
    }

    // Iniciar
    startFlow();
</script>

<style>
    @keyframes typing {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-4px); }
    }
</style>
