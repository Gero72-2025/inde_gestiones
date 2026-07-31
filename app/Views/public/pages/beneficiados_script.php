<script>
    let cryptoCache = null;

    function b64ToBytes(base64) {
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
        return bytes;
    }

    function bytesToB64(bytes) {
        let binary = '';
        for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
        return btoa(binary);
    }

    function concatBytes(...chunks) {
        const total = chunks.reduce((sum, chunk) => sum + chunk.length, 0);
        const merged = new Uint8Array(total);
        let offset = 0;
        chunks.forEach((chunk) => {
            merged.set(chunk, offset);
            offset += chunk.length;
        });
        return merged;
    }

    async function getCryptoMaterials() {
        if (cryptoCache) return cryptoCache;

        let material;
        if (window.PortalConfig.cipherKey.startsWith('base64:')) {
            material = b64ToBytes(window.PortalConfig.cipherKey.substring(7));
        } else {
            material = new TextEncoder().encode(window.PortalConfig.cipherKey);
        }

        if (material.length !== 32) {
            material = new Uint8Array(await crypto.subtle.digest('SHA-256', material));
        }

        const aesKey = await crypto.subtle.importKey('raw', material, 'AES-CBC', false, ['encrypt', 'decrypt']);
        const hmacKey = await crypto.subtle.importKey('raw', material, { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);
        cryptoCache = { aesKey, hmacKey };

        return cryptoCache;
    }

    async function encryptEnvelope(payload) {
        const { aesKey, hmacKey } = await getCryptoMaterials();
        const iv = crypto.getRandomValues(new Uint8Array(16));
        const plain = new TextEncoder().encode(JSON.stringify(payload));
        const cipher = new Uint8Array(await crypto.subtle.encrypt({ name: 'AES-CBC', iv }, aesKey, plain));
        const mac = new Uint8Array(await crypto.subtle.sign('HMAC', hmacKey, concatBytes(iv, cipher)));

        return {
            encrypted: true,
            algorithm: 'AES-256-CBC',
            payload: bytesToB64(concatBytes(iv, mac, cipher))
        };
    }

    async function decryptEnvelope(envelope) {
        if (!envelope || !envelope.encrypted) return envelope;

        const { aesKey } = await getCryptoMaterials();
        const bytes = b64ToBytes(envelope.payload || '');
        const iv = bytes.slice(0, 16);
        const cipher = bytes.slice(48);
        const plain = await crypto.subtle.decrypt({ name: 'AES-CBC', iv }, aesKey, cipher);

        return JSON.parse(new TextDecoder().decode(plain));
    }

    async function refreshCaptcha() {
        const response = await fetch(window.PortalConfig.endpoints.captcha, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const result = await response.json();
        document.getElementById('captchaQuestion').textContent = result?.data?.question || '';
        document.getElementById('captchaToken').value = result?.data?.token || '';
        document.getElementById('captchaAnswer').value = '';
    }

    function showResult(message) {
        document.getElementById('dpiResultText').textContent = message;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('dpiResultModal')).show();
    }

    document.getElementById('captchaRefresh').addEventListener('click', refreshCaptcha);

    document.getElementById('dpiForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        const dpi = (document.getElementById('dpiInput').value || '').replace(/\D+/g, '');
        const captchaAnswer = (document.getElementById('captchaAnswer').value || '').replace(/\D+/g, '');
        const captchaToken = document.getElementById('captchaToken').value || '';

        const envelope = await encryptEnvelope({ dpi, captcha_answer: captchaAnswer, captcha_token: captchaToken });
        const response = await fetch(window.PortalConfig.endpoints.beneficiados, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.PortalConfig.csrfToken
            },
            body: JSON.stringify(envelope)
        });

        const raw = await response.json();
        const payload = await decryptEnvelope(raw);

        if (payload.captcha && payload.captcha.question) {
            document.getElementById('captchaQuestion').textContent = payload.captcha.question;
            document.getElementById('captchaToken').value = payload.captcha.token;
            document.getElementById('captchaAnswer').value = '';
        }

        showResult(payload.message || 'Consulta procesada.');
    });
</script>
