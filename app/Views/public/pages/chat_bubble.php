<?php
    $chatTitle = trim((string) ($chatWidgetTitle ?? __('chat.title', 'Asistente INDE')));
    $chatSubtitle = trim((string) ($chatWidgetSubtitle ?? __('chat.subtitle', 'En línea ahora')));
    $chatPlaceholder = trim((string) ($chatWidgetPlaceholder ?? __('chat.placeholder', 'Escribe tu pregunta...')));
    $chatIntro = trim((string) ($chatWidgetIntro ?? __('chat.intro', '¿Qué empresa necesitas apoyar?')));
    $tarifaEndpoint = trim((string) ($chatWidgetTarifaSocialEndpoint ?? ''));
?>

<div class="chat-bubble-shell" data-chat-shell>
    <button class="chat-bubble-trigger" type="button" data-chat-open aria-label="<?= esc(__('chat.open.aria', 'Abrir asistente')) ?>">
        <i class="bi bi-robot"></i>
        <span><?= esc(__('chat.open.label', 'Ayuda')) ?></span>
    </button>

    <div class="chat-bubble-panel" data-chat-panel aria-hidden="true">
        <div class="chat-bubble-header">
            <div class="chat-bubble-header-title">
                <div class="chat-bubble-avatar">
                    <i class="bi bi-robot"></i>
                </div>
                <div>
                    <h3><?= esc($chatTitle) ?></h3>
                    <p><?= esc($chatSubtitle) ?></p>
                </div>
            </div>
            <div class="chat-bubble-header-actions">
                <button class="chat-bubble-icon-btn" type="button" data-chat-minimize title="Minimizar">
                    <i class="bi bi-dash-lg"></i>
                </button>
                <button class="chat-bubble-icon-btn" type="button" data-chat-maximize title="Ampliar">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
                <button class="chat-bubble-icon-btn" type="button" data-chat-close title="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <div class="chat-bubble-body" data-chat-body>
            <div class="chat-bubble-message chat-bubble-message-bot">
                <div class="chat-bubble-message-inner">
                    <p><?= esc($chatIntro) ?></p>
                </div>
            </div>

            <div class="chat-bubble-quick-actions" data-chat-quick-actions></div>

            <div class="chat-bubble-conversation" data-chat-conversation></div>

            <form class="chat-bubble-form" data-chat-form>
                <input type="text" name="message" data-chat-input placeholder="<?= esc($chatPlaceholder) ?>" autocomplete="off" required>
                <button type="submit" aria-label="Enviar mensaje">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    .chat-bubble-shell {
        position: fixed;
        right: 24px;
        bottom: 96px;
        z-index: 1400;
    }

    .chat-bubble-trigger {
        border: 0;
        border-radius: 999px;
        padding: 0.9rem 1.1rem;
        background: linear-gradient(135deg, #0b4f8a 0%, #1a936f 100%);
        color: #fff;
        box-shadow: 0 14px 30px rgba(7, 49, 71, .28);
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        font-weight: 700;
        cursor: pointer;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .chat-bubble-trigger:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(7, 49, 71, .34);
    }

    .chat-bubble-panel {
        position: absolute;
        right: 0;
        bottom: calc(100% + 14px);
        width: min(92vw, 360px);
        height: 470px;
        border-radius: 1.1rem;
        overflow: hidden;
        background: #fff;
        border: 1px solid rgba(11, 79, 138, .16);
        box-shadow: 0 20px 50px rgba(7, 49, 71, .2);
        display: flex;
        flex-direction: column;
        transform: translateY(12px) scale(.98);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: all .24s ease;
    }

    .chat-bubble-shell.is-open .chat-bubble-panel {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0) scale(1);
    }

    .chat-bubble-shell.is-collapsed .chat-bubble-panel {
        height: 74px;
    }

    .chat-bubble-shell.is-collapsed .chat-bubble-body {
        display: none;
    }

    .chat-bubble-shell.is-maximized .chat-bubble-panel {
        width: min(94vw, 430px);
        height: min(82vh, 620px);
    }

    .chat-bubble-header {
        background: linear-gradient(135deg, #0b4f8a 0%, #1668af 100%);
        color: #fff;
        padding: 0.85rem 0.95rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .chat-bubble-header-title {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        min-width: 0;
    }

    .chat-bubble-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.16);
        border: 1px solid rgba(255,255,255,.3);
        flex-shrink: 0;
    }

    .chat-bubble-header-title h3 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
    }

    .chat-bubble-header-title p {
        margin: 0.1rem 0 0;
        font-size: 0.72rem;
        color: rgba(255,255,255,.82);
    }

    .chat-bubble-header-actions {
        display: flex;
        gap: 0.35rem;
    }

    .chat-bubble-icon-btn {
        border: 0;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.16);
        color: #fff;
        cursor: pointer;
    }

    .chat-bubble-icon-btn:hover {
        background: rgba(255,255,255,.24);
    }

    .chat-bubble-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding: 0.9rem;
        background: linear-gradient(180deg, #f7fbff 0%, #ffffff 100%);
        overflow: hidden;
    }

    .chat-bubble-conversation {
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        padding-right: 0.2rem;
    }

    .chat-bubble-conversation::-webkit-scrollbar {
        width: 5px;
    }

    .chat-bubble-conversation::-webkit-scrollbar-thumb {
        background: rgba(11, 79, 138, .2);
        border-radius: 999px;
    }

    .chat-bubble-message {
        display: flex;
        max-width: 92%;
    }

    .chat-bubble-message-bot {
        justify-content: flex-start;
    }

    .chat-bubble-message-user {
        justify-content: flex-end;
        margin-left: auto;
    }

    .chat-bubble-message-inner {
        border-radius: 1rem 1rem 1rem 0.35rem;
        padding: 0.7rem 0.8rem;
        background: #fff;
        color: #13324a;
        border: 1px solid rgba(11, 79, 138, .12);
        box-shadow: 0 8px 20px rgba(11, 79, 138, .06);
        font-size: 0.84rem;
        line-height: 1.45;
    }

    .chat-bubble-message-user .chat-bubble-message-inner {
        border-radius: 1rem 1rem 0.35rem 1rem;
        background: linear-gradient(135deg, #0b4f8a 0%, #1a936f 100%);
        color: #fff;
        border-color: transparent;
    }

    .chat-bubble-quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    .chat-bubble-chip {
        border: 1px solid rgba(11, 79, 138, .14);
        background: #fff;
        color: #0b4f8a;
        border-radius: 999px;
        padding: 0.45rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
    }

    .chat-bubble-chip:hover {
        background: rgba(11, 79, 138, .06);
    }

    .chat-bubble-form {
        display: flex;
        gap: 0.5rem;
        margin-top: auto;
    }

    .chat-bubble-form input {
        flex: 1;
        border: 1px solid rgba(11, 79, 138, .18);
        border-radius: 999px;
        padding: 0.7rem 0.9rem;
        outline: none;
        font-size: 0.85rem;
    }

    .chat-bubble-form input:focus {
        border-color: #1a936f;
        box-shadow: 0 0 0 3px rgba(26, 147, 111, .12);
    }

    .chat-bubble-form button {
        border: 0;
        border-radius: 999px;
        padding: 0.7rem 0.9rem;
        background: linear-gradient(135deg, #0b4f8a 0%, #1a936f 100%);
        color: #fff;
        cursor: pointer;
    }

    @media (max-width: 576px) {
        .chat-bubble-shell {
            right: 14px;
            bottom: 88px;
            z-index: 1400;
        }

        .chat-bubble-panel {
            width: min(94vw, 320px);
            right: 0;
            bottom: calc(100% + 12px);
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const shell = document.querySelector('[data-chat-shell]');
        if (!shell) {
            return;
        }

        const trigger = shell.querySelector('[data-chat-open]');
        const panel = shell.querySelector('[data-chat-panel]');
        const minimizeBtn = shell.querySelector('[data-chat-minimize]');
        const maximizeBtn = shell.querySelector('[data-chat-maximize]');
        const closeBtn = shell.querySelector('[data-chat-close]');
        const form = shell.querySelector('[data-chat-form]');
        const input = shell.querySelector('[data-chat-input]');
        const conversation = shell.querySelector('[data-chat-conversation]');
        const quickActions = shell.querySelector('[data-chat-quick-actions]');
        const tarifaEndpoint = <?= json_encode($tarifaEndpoint) ?>;
        let activePrompt = null;

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function addMessage(text, isUser) {
            const wrapper = document.createElement('div');
            wrapper.className = 'chat-bubble-message ' + (isUser ? 'chat-bubble-message-user' : 'chat-bubble-message-bot');
            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble-message-inner';
            bubble.innerHTML = '<p>' + escapeHtml(text) + '</p>';
            wrapper.appendChild(bubble);
            conversation.appendChild(wrapper);
            conversation.scrollTop = conversation.scrollHeight;
        }

        function clearOptions() {
            quickActions.innerHTML = '';
        }

        function renderOptions(options, onSelect) {
            clearOptions();
            options.forEach(function (option) {
                const button = document.createElement('button');
                button.className = 'chat-bubble-chip';
                button.type = 'button';
                button.textContent = option.label;
                button.addEventListener('click', function () {
                    onSelect(option.value, option);
                });
                quickActions.appendChild(button);
            });
        }

        function setPrompt(handler) {
            activePrompt = handler;
        }

        function resetPrompt() {
            activePrompt = null;
        }

        async function postTarifaSocial(distribuidoraId, correlativo) {
            const csrfToken = (window.PortalConfig && window.PortalConfig.csrfToken) ? window.PortalConfig.csrfToken : '';
            const csrfName = (window.PortalConfig && window.PortalConfig.csrfName) ? window.PortalConfig.csrfName : 'csrf_test_name';
            const formData = new FormData();
            formData.append('distribuidora_id', String(distribuidoraId));
            formData.append('correlativo', String(correlativo));
            if (csrfToken) {
                formData.append(csrfName, csrfToken);
            }

            const response = await fetch(tarifaEndpoint, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const payload = await response.json().catch(function () {
                return {};
            });
            return payload;
        }

        function showCompanyOptions() {
            addMessage('¿Qué empresa necesitas apoyar?', false);
            renderOptions([
                { label: 'ETCEE', value: 'etcee' },
                { label: 'ECOE', value: 'ecoe' },
                { label: 'GERO', value: 'gero' }
            ], function (value) {
                if (value === 'ecoe') {
                    showEcoeOptions();
                } else if (value === 'etcee') {
                    addMessage('ETCEE está disponible para orientación sobre procesos institucionales y seguimiento de solicitudes.', false);
                    renderOptions([{ label: 'Volver al inicio', value: 'back' }], function (selectedValue) {
                        if (selectedValue === 'back') {
                            showCompanyOptions();
                        }
                    });
                } else if (value === 'gero') {
                    addMessage('GERO puede ayudarte con trámites relacionados con gestión de usuarios y acompañamiento de solicitudes.', false);
                    renderOptions([{ label: 'Volver al inicio', value: 'back' }], function (selectedValue) {
                        if (selectedValue === 'back') {
                            showCompanyOptions();
                        }
                    });
                }
            });
        }

        function showEcoeOptions() {
            addMessage('Has seleccionado ECOE. ¿Qué deseas consultar?', false);
            renderOptions([
                { label: '¿Quiénes lo reciben?', value: 'who' },
                { label: '¿Recibo el Beneficio Social?', value: 'benefit' },
                { label: 'Volver', value: 'back' }
            ], function (value) {
                if (value === 'who') {
                    addMessage('📊 Rangos de Tarifa Social: 0–100 kWh recibe aporte directo, 101–300 kWh aplica tarifa social preferencial y 301+ kWh corresponde a tarifa plena.', false);
                    renderOptions([{ label: 'Volver al inicio', value: 'back' }], function (selectedValue) {
                        if (selectedValue === 'back') {
                            showCompanyOptions();
                        }
                    });
                } else if (value === 'benefit') {
                    showDistribuidoraSelection();
                } else if (value === 'back') {
                    showCompanyOptions();
                }
            });
        }

        function showDistribuidoraSelection() {
            addMessage('Para verificar tu situación, primero dime cuál es tu empresa distribuidora.', false);
            renderOptions([
                { label: 'EEGSA', value: 'eegsa' },
                { label: 'DEOCSA', value: 'deocsa' },
                { label: 'DEORSA', value: 'deorsa' },
                { label: 'Otra', value: 'otra' }
            ], function (value) {
                if (value === 'otra') {
                    addMessage('⚠️ El aporte a la Tarifa Social aplica únicamente para clientes de EEGSA, DEOCSA y DEORSA.', false);
                    renderOptions([{ label: 'Volver al inicio', value: 'back' }], function (selectedValue) {
                        if (selectedValue === 'back') {
                            showCompanyOptions();
                        }
                    });
                    return;
                }

                const label = value.toUpperCase();
                addMessage('Ingresa tu NIS/Correlativo para continuar.', false);
                setPrompt(function (correlativo) {
                    const cleanValue = String(correlativo || '').trim();
                    if (!cleanValue) {
                        addMessage('Debes ingresar un NIS/Correlativo para continuar.', false);
                        showDistribuidoraSelection();
                        return;
                    }

                    addMessage('✅ Verificando tu información...', false);
                    (async function () {
                        try {
                            const payload = await postTarifaSocial(value, cleanValue);
                            const message = (payload && payload.message) ? String(payload.message) : 'Consulta completada. Puedes continuar con el proceso de ECOE.';
                            addMessage(message, false);
                        } catch (error) {
                            addMessage('No fue posible completar la consulta en este momento. Intenta nuevamente.', false);
                        }
                        renderOptions([{ label: 'Volver al inicio', value: 'back' }], function (selectedValue) {
                            if (selectedValue === 'back') {
                                showCompanyOptions();
                            }
                        });
                    })();
                });
            });
        }

        trigger.addEventListener('click', function () {
            shell.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            input.focus();
        });

        closeBtn.addEventListener('click', function () {
            shell.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
            resetPrompt();
        });

        minimizeBtn.addEventListener('click', function () {
            shell.classList.toggle('is-collapsed');
            shell.classList.remove('is-maximized');
        });

        maximizeBtn.addEventListener('click', function () {
            shell.classList.toggle('is-maximized');
            shell.classList.remove('is-collapsed');
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const value = input.value.trim();
            if (!value) {
                return;
            }

            addMessage(value, true);
            input.value = '';

            if (activePrompt) {
                const handler = activePrompt;
                resetPrompt();
                setTimeout(function () {
                    handler(value);
                }, 250);
                return;
            }

            setTimeout(function () {
                addMessage('Puedes elegir una opción del menú para continuar.', false);
            }, 250);
        });

        showCompanyOptions();
    });
</script>
