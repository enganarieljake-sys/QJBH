import './bootstrap';

const applyMobileTableLabels = () => {
    document.querySelectorAll('table').forEach((table) => {
        table.classList.add('data-table');
        const headers = Array.from(table.querySelectorAll('thead th')).map((header) =>
            header.textContent.trim()
        );

        table.querySelectorAll('tbody tr').forEach((row) => {
            Array.from(row.children).forEach((cell, index) => {
                if (cell.matches('td') && headers[index]) {
                    cell.dataset.label = headers[index];

                    if (['Status', 'Priority', 'Role', 'Approval'].includes(headers[index])) {
                        enhanceDataValue(cell);
                    }
                }
            });
        });
    });
};

const enhanceDataValue = (cell) => {
    if (cell.children.length || !cell.textContent.trim()) return;

    const value = cell.textContent.trim();
    const badge = document.createElement('span');
    const normalizedValue = value.toLowerCase().replace(/\s+/g, '-');

    badge.className = `data-badge data-badge--${normalizedValue}`;
    badge.textContent = value;
    cell.replaceChildren(badge);
};

const initializeFilterForms = () => {
    document.querySelectorAll('main form[method="GET"]').forEach((form) => {
        form.classList.add('filter-panel');

        const hasActiveFilter = Array.from(form.elements).some((element) =>
            element.name && element.value && element.type !== 'submit'
        );

        if (!hasActiveFilter) return;

        const resetLink = document.createElement('a');
        resetLink.href = window.location.pathname;
        resetLink.className = 'filter-reset';
        resetLink.textContent = 'Clear filters';
        resetLink.setAttribute('aria-label', 'Clear all filters');
        form.append(resetLink);
    });
};

const initializeMobileMenu = () => {
    const menu = document.querySelector('[data-mobile-menu]');

    if (!menu) return;

    const toggle = menu.querySelector('[data-mobile-menu-toggle]');
    const panel = menu.querySelector('[data-mobile-menu-panel]');

    const closeMenu = (returnFocus = false) => {
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open navigation menu');
        panel.hidden = true;
        if (returnFocus) toggle.focus();
    };

    toggle.addEventListener('click', () => {
        const willOpen = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(willOpen));
        toggle.setAttribute('aria-label', willOpen ? 'Close navigation menu' : 'Open navigation menu');
        panel.hidden = !willOpen;
    });

    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target)) closeMenu();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) closeMenu(true);
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) closeMenu();
    });
};

const initializeThemeToggle = () => {
    const root = document.documentElement;
    const toggles = document.querySelectorAll('[data-theme-toggle]');
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

    const applyTheme = (theme) => {
        root.dataset.theme = theme;
        root.style.colorScheme = theme;

        toggles.forEach((toggle) => {
            const nextTheme = theme === 'dark' ? 'light' : 'dark';
            const label = toggle.querySelector('[data-theme-label]');
            toggle.setAttribute('aria-label', `Switch to ${nextTheme} mode`);
            toggle.setAttribute('aria-pressed', String(theme === 'dark'));
            if (label) label.textContent = `${nextTheme[0].toUpperCase()}${nextTheme.slice(1)} mode`;
        });
    };

    applyTheme(root.dataset.theme || (mediaQuery.matches ? 'dark' : 'light'));

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('unistay-theme', nextTheme);
            applyTheme(nextTheme);
        });
    });

    mediaQuery.addEventListener('change', (event) => {
        if (!localStorage.getItem('unistay-theme')) applyTheme(event.matches ? 'dark' : 'light');
    });
};

const initializeNotificationCenters = () => {
    document.querySelectorAll('[data-notification-center]').forEach((center) => {
        const toggle = center.querySelector('[data-notification-toggle]');
        const panel = center.querySelector('[data-notification-panel]');

        const close = () => {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = panel.hidden;
            document.querySelectorAll('[data-notification-panel]').forEach((item) => { item.hidden = true; });
            panel.hidden = !willOpen;
            toggle.setAttribute('aria-expanded', String(willOpen));
        });

        center.addEventListener('click', (event) => event.stopPropagation());
        document.addEventListener('click', close);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !panel.hidden) {
                close();
                toggle.focus();
            }
        });
    });
};

const urlBase64ToUint8Array = (value) => {
    const padding = '='.repeat((4 - value.length % 4) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(base64), (character) => character.charCodeAt(0));
};

const initializePushSettings = () => {
    document.querySelectorAll('[data-push-settings]').forEach(async (panel) => {
        const button = panel.querySelector('[data-push-toggle]');
        const testButton = panel.querySelector('[data-push-test]');
        const status = panel.querySelector('[data-push-status]');
        const message = panel.querySelector('[data-push-message]');
        const iosHelp = panel.querySelector('[data-ios-help]');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        const isAndroid = /Android/.test(navigator.userAgent);
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
        let registration;
        let subscription;
        let publicKey;

        const render = (state, text = '') => {
            const enabled = state === 'Enabled';
            status.textContent = state;
            status.classList.toggle('is-enabled', enabled);
            button.textContent = enabled
                ? 'Disable phone notifications'
                : (state === 'Subscription Error' ? 'Retry phone notifications' : 'Enable phone notifications');
            button.disabled = state === 'Unsupported';
            testButton.classList.toggle('hidden', !enabled);
            testButton.disabled = !enabled;
            if (text) message.textContent = text;
        };

        const withTimeout = (promise, milliseconds, text) => Promise.race([
            promise,
            new Promise((_, reject) => setTimeout(() => reject(new Error(text)), milliseconds)),
        ]);

        const subscriptionPayload = (item) => ({
            ...item.toJSON(),
            contentEncoding: PushManager.supportedContentEncodings?.[0] || 'aes128gcm',
        });

        const blockedInstructions = () => {
            if (isIos) {
                return 'Notifications are blocked. Open iPhone Settings → Notifications → QJBH App → turn on Allow Notifications. Then reopen QJBH from the Home Screen and refresh this page.';
            }
            if (isAndroid) {
                return 'Notifications are blocked. Long-press the QJBH app icon → App info → Notifications → Allow. If you use Chrome, also check Chrome → Settings → Site settings → Notifications. Then return here and refresh.';
            }
            return 'Notifications are blocked in this browser. Open the site permissions, allow Notifications for QJBH, then refresh this page.';
        };

        const request = async (url, method, body) => {
            const response = await fetch(url, {
                method,
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: body ? JSON.stringify(body) : undefined,
            });
            if (response.status === 419) {
                window.location.reload();
                throw new Error('Session expired');
            }
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'The notification setting could not be saved.');
            return data;
        };

        if (!window.isSecureContext || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            render('Unsupported', 'This browser or connection does not support secure Web Push notifications.');
            return;
        }

        if (isIos && !isStandalone) {
            render('Unsupported', 'On iPhone or iPad, add QJBH to your Home Screen and open it from the app icon. Web Push requires iOS/iPadOS 16.4 or later.');
            iosHelp.classList.remove('hidden');
            return;
        }

        try {
            registration = await navigator.serviceWorker.register('/sw.js');
            await registration.update().catch(() => {});
            registration = await withTimeout(navigator.serviceWorker.ready, 12000, 'The service worker did not become ready. Reload the app and try again.');
            const config = await request(panel.dataset.configUrl, 'GET');
            if (!config.enabled || !config.publicKey) throw new Error('Push notifications are not configured yet.');
            publicKey = config.publicKey;
            subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await request(panel.dataset.subscribeUrl, 'POST', subscriptionPayload(subscription));
                render('Enabled', 'This device is registered. Updates can arrive even when QJBH is closed.');
            } else if (Notification.permission === 'denied') {
                render('Blocked', blockedInstructions());
            } else {
                render(Notification.permission === 'granted' ? 'Subscription Error' : 'Permission Required',
                    Notification.permission === 'granted'
                        ? 'Permission is allowed, but this device has no active push subscription. Tap Enable phone notifications to repair it.'
                        : 'Tap Enable phone notifications, then choose Allow in the phone prompt.');
            }
        } catch (error) {
            render('Subscription Error', error.message || 'The notification subscription could not be checked.');
            return;
        }

        button.addEventListener('click', async () => {
            button.disabled = true;
            button.textContent = subscription ? 'Turning off…' : 'Turning on…';

            try {
                if (subscription && status.textContent === 'Enabled') {
                    await request(panel.dataset.unsubscribeUrl, 'DELETE', { endpoint: subscription.endpoint });
                    await subscription.unsubscribe();
                    subscription = null;
                    render(Notification.permission === 'denied' ? 'Blocked' : 'Permission Required',
                        'Phone notifications are disabled for this device. In-app history remains available.');
                    return;
                }

                if (subscription) {
                    await request(panel.dataset.subscribeUrl, 'POST', subscriptionPayload(subscription));
                    render('Enabled', 'This device subscription was saved again. Send a test notification to verify delivery.');
                    return;
                }

                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                      render(permission === 'denied' ? 'Blocked' : 'Permission Required',
                          permission === 'denied' ? blockedInstructions() : 'Permission was not granted. Tap Enable again when you are ready.');
                      return;
                }

                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicKey),
                });
                  await request(panel.dataset.subscribeUrl, 'POST', subscriptionPayload(subscription));
                  render('Enabled', 'This device is registered. Use Send test notification to verify delivery.');
            } catch (error) {
                  render(Notification.permission === 'denied' ? 'Blocked' : 'Subscription Error',
                      Notification.permission === 'denied' ? blockedInstructions() : (error.message || 'Could not register this device.'));
            }
        });

          testButton.addEventListener('click', async () => {
              if (!subscription) return;
              testButton.disabled = true;
              testButton.textContent = 'Sending test…';
              try {
                  const result = await request(panel.dataset.testUrl, 'POST', { endpoint: subscription.endpoint });
                  message.textContent = `${result.message} Check this device now.`;
              } catch (error) {
                  await request(panel.dataset.unsubscribeUrl, 'DELETE', { endpoint: subscription.endpoint }).catch(() => {});
                  await subscription.unsubscribe().catch(() => {});
                  subscription = null;
                  render('Subscription Error', error.message || 'The test notification could not be delivered.');
              } finally {
                  testButton.textContent = 'Send test notification';
                  if (subscription && status.textContent === 'Enabled') testButton.disabled = false;
              }
          });
    });
};

const initializePaymentForms = () => {
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const label = button.textContent;
            try {
                await navigator.clipboard.writeText(button.dataset.copy);
                button.textContent = 'Copied!';
            } catch {
                button.textContent = button.dataset.copy;
            }
            setTimeout(() => { button.textContent = label; }, 1800);
        });
    });

    document.querySelectorAll('[data-receipt-input]').forEach((input) => {
        const preview = input.parentElement.querySelector('[data-receipt-preview]');
        input.addEventListener('change', () => {
            const [file] = input.files;
            if (!preview) return;
            if (preview.src) URL.revokeObjectURL(preview.src);
            preview.hidden = !file;
            preview.classList.toggle('hidden', !file);
            if (file) preview.src = URL.createObjectURL(file);
        });
    });

    document.querySelectorAll('[data-avatar-input]').forEach((input) => {
        const frame = input.form?.querySelector('.profile-avatar-frame');
        const preview = frame?.querySelector('[data-avatar-preview]');
        input.addEventListener('change', () => {
            const [file] = input.files;
            if (!preview || !file) return;
            if (preview.src.startsWith('blob:')) URL.revokeObjectURL(preview.src);
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
        });
    });

    const billSelect = document.querySelector('[data-bill-select]');
    const amountInput = document.querySelector('[data-amount-input]');
    billSelect?.addEventListener('change', () => {
        const balance = billSelect.selectedOptions[0]?.dataset.balance;
        if (amountInput && balance) amountInput.value = balance;
    });
};

const initializeTenantTemplates = () => {
    document.querySelectorAll('[data-assistant]').forEach((assistant) => {
        const dialog = assistant.querySelector('[data-tenant-template-dialog]');
        const open = assistant.querySelector('[data-tenant-template-open]');
        const close = assistant.querySelector('[data-tenant-template-close]');

        if (!dialog || !open || !close) return;

        open.addEventListener('click', () => dialog.showModal());
        close.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    });
};

const EYE_ICON = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
const EYE_OFF_ICON = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-2.2 3.1M6.6 6.6C3.7 8.5 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18"/></svg>';

const initializePasswordToggles = () => {
    document.querySelectorAll('input[type="password"]').forEach((input) => {
        if (input.dataset.passwordToggle) return;
        input.dataset.passwordToggle = 'true';

        const wrapper = document.createElement('div');
        wrapper.className = 'password-field';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'password-toggle';
        wrapper.appendChild(button);

        const render = (visible) => {
            button.innerHTML = visible ? EYE_OFF_ICON : EYE_ICON;
            button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(visible));
            button.title = visible ? 'Hide password' : 'Show password';
        };

        render(false);
        button.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            render(visible);
        });

        // Never submit the form with the password still revealed in the field type.
        input.form?.addEventListener('submit', () => {
            input.type = 'password';
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        applyMobileTableLabels();
        initializeFilterForms();
        initializeMobileMenu();
        initializeThemeToggle();
        initializeNotificationCenters();
        initializePushSettings();
        initializePaymentForms();
        initializeTenantTemplates();
        initializePasswordToggles();
    });
} else {
    applyMobileTableLabels();
    initializeFilterForms();
    initializeMobileMenu();
    initializeThemeToggle();
    initializeNotificationCenters();
    initializePushSettings();
    initializePaymentForms();
    initializeTenantTemplates();
    initializePasswordToggles();
}

// Installable app (PWA): register the service worker on secure origins.
if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

// QJ Bot chat: send questions without reloading; forms still work without JS.
document.querySelectorAll('[data-assistant]').forEach((assistant) => {
    const log = assistant.querySelector('[data-assistant-log]');
    const remaining = document.querySelector('[data-assistant-remaining]');
    const userAvatar = assistant.dataset.userAvatar;
    const controls = () => assistant.querySelectorAll('button, input:not([type="hidden"])');
    const scrollDown = () => { log.scrollTop = log.scrollHeight; };

    // One chat row: QJ Bot on the left, the admin on the right.
    const row = (role, content) => {
        const element = document.createElement('div');
        element.className = `assistant__row assistant__row--${role}`;
        let avatar;
        if (role === 'assistant') {
            avatar = document.createElement('span');
            avatar.className = 'assistant__avatar qj-blob';
            avatar.setAttribute('role', 'img');
            avatar.setAttribute('aria-label', 'QJ Bot');
            avatar.innerHTML = '<span class="qj-blob__eye qj-blob__eye--left" aria-hidden="true"></span><span class="qj-blob__eye qj-blob__eye--right" aria-hidden="true"></span>';
        } else {
            avatar = document.createElement('img');
            avatar.className = 'assistant__avatar';
            avatar.src = userAvatar;
            avatar.alt = 'You';
        }
        const message = document.createElement('div');
        message.className = 'assistant__msg';
        message.append(content);
        element.append(avatar, message);
        log.append(element);
        scrollDown();
        return element;
    };

    const text = (value) => {
        const paragraph = document.createElement('p');
        paragraph.textContent = value;
        return paragraph;
    };

    const thinkingLine = () => {
        const line = document.createElement('div');
        line.className = 'assistant__thinking';
        line.innerHTML = '<span class="assistant__dots" aria-hidden="true"><span></span><span></span><span></span></span>';
        line.append('QJ Bot is thinking…');
        return line;
    };

    // Confirm / Cancel / Undo on a bot action card: the server returns the updated card.
    const submitCard = async (form) => {
        const card = form.closest('[data-action-card]');
        card.querySelectorAll('button').forEach((button) => { button.disabled = true; });

        try {
            const response = await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(form) });
            if (response.status === 419) {
                window.location.reload();
                return;
            }
            const data = await response.json();
            card.outerHTML = data.html;
        } catch {
            card.querySelectorAll('button').forEach((button) => { button.disabled = false; });
            alert('No connection. Please try again.');
        }
    };

    log.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-action-form]');
        if (!form) return;
        event.preventDefault();
        submitCard(form);
    });

    // Several proposals at once (e.g. "generate bills then remind everyone") get one button.
    const addConfirmAll = (group) => {
        const pending = () => [...group.querySelectorAll('.action-card--pending form[action$="/confirm"]')];
        if (pending().length < 2 || group.querySelector('[data-confirm-all]')) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'action-card__confirm action-cards__all';
        button.dataset.confirmAll = '';
        button.textContent = `Confirm tanan (${pending().length})`;
        button.addEventListener('click', async () => {
            button.disabled = true;
            for (const form of pending()) {
                await submitCard(form);
            }
            button.remove();
        });
        group.append(button);
    };

    log.querySelectorAll('[data-action-cards]').forEach(addConfirmAll);

    const send = async (form, question) => {
        // Read the fields before disabling them; disabled inputs are left out of FormData.
        const body = new FormData(form);
        row('user', text(question));
        const thinking = row('assistant', thinkingLine());
        controls().forEach((control) => { control.disabled = true; });

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body,
            });

            if (response.status === 419) {
                window.location.reload();
                return;
            }

            const data = await response.json().catch(() => ({}));
            thinking.remove();

            if (response.ok) {
                const reply = row('assistant', text(data.answer));
                if (data.actions_html) {
                    const cards = document.createElement('div');
                    cards.className = 'action-cards';
                    cards.dataset.actionCards = '';
                    cards.innerHTML = data.actions_html;
                    reply.querySelector('.assistant__msg').append(cards);
                    addConfirmAll(cards);
                    scrollDown();
                }
                if (remaining) remaining.textContent = data.remaining === null ? 'AI ready' : `${data.remaining} AI left`;
            } else if (response.status === 429) {
                row('assistant', text('Too many questions at once. Please wait a minute.'));
            } else {
                row('assistant', text(data.message || 'Something went wrong. Please try again.'));
            }
        } catch {
            thinking.remove();
            row('assistant', text('No connection. Check your internet and try again.'));
        } finally {
            controls().forEach((control) => { control.disabled = false; });
        }
    };

    assistant.querySelector('[data-assistant-form]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const input = event.target.querySelector('textarea[name="question"]');
        const question = input.value.trim();
        if (!question) return;
        // send() reads the form data before its first await, so clearing right after is safe.
        send(event.target, question).then(() => input.focus());
        input.value = '';
        fitQuestion();
    });

    // Enter sends; Shift+Enter starts a new line. The box grows with what's typed.
    const question = assistant.querySelector('textarea[name="question"]');
    const fitQuestion = () => {
        if (!question) return;
        question.style.height = 'auto';
        question.style.height = `${question.scrollHeight}px`;
    };
    question?.addEventListener('input', fitQuestion);
    question?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            question.form.requestSubmit();
        }
    });

    assistant.querySelectorAll('[data-assistant-quick]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            send(form, form.querySelector('button').dataset.question);
        });
    });

    scrollDown();
});

// The installed phone app can sit in the background for hours, leaving forms
// with an expired security token (419). Reload when coming back after a while,
// unless something has been typed that a reload would lose.
(() => {
    const STALE_AFTER = 30 * 60 * 1000;
    let hiddenAt = null;

    const hasTypedText = () => [...document.querySelectorAll('input:not([type="hidden"]):not([type="search"]), textarea')]
        .some((field) => field.value !== field.defaultValue);

    const reloadIfStale = (since) => {
        if (since !== null && Date.now() - since > STALE_AFTER && !hasTypedText()) {
            window.location.reload();
        }
    };

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            hiddenAt = Date.now();
        } else {
            reloadIfStale(hiddenAt);
            hiddenAt = null;
        }
    });

    // iOS restores the app from memory without a visibility change sometimes.
    const loadedAt = Date.now();
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) reloadIfStale(loadedAt);
    });
})();

// Analytics page: Chart.js is loaded only here, so other pages stay light.
const analytics = document.querySelector('[data-analytics]');
if (analytics) {
    import('chart.js/auto').then(({ default: Chart }) => {
        const data = JSON.parse(analytics.querySelector('[data-analytics-data]').textContent);
        const text = getComputedStyle(document.body).color;
        const grid = 'rgba(148, 163, 184, 0.2)';
        const peso = (value) => `₱${Number(value).toLocaleString('en-PH', { maximumFractionDigits: 0 })}`;
        Chart.defaults.color = text;
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

        const trend = analytics.querySelector('[data-chart="trend"]');
        if (trend) {
            new Chart(trend, {
                type: 'bar',
                data: {
                    labels: data.trend.labels,
                    datasets: [
                        { label: 'Income', data: data.trend.income, backgroundColor: '#0879ed', borderRadius: 8 },
                        { label: 'Expenses', data: data.trend.expenses, backgroundColor: '#e11d48', borderRadius: 8 },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { tooltip: { callbacks: { label: (item) => `${item.dataset.label}: ${peso(item.raw)}` } } },
                    scales: { y: { beginAtZero: true, grid: { color: grid }, ticks: { callback: peso } }, x: { grid: { display: false } } },
                },
            });
        }

        analytics.querySelectorAll('canvas[data-colors]').forEach((canvas) => {
            const values = data[canvas.dataset.chart];
            const money = canvas.dataset.chart === 'methods';
            new Chart(canvas, {
                type: money ? 'pie' : 'doughnut',
                data: {
                    labels: Object.keys(values),
                    datasets: [{ data: Object.values(values), backgroundColor: canvas.dataset.colors.split(','), borderWidth: 0 }],
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: money ? 0 : '62%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (item) => `${item.label}: ${money ? peso(item.raw) : item.raw}` } },
                    },
                },
            });
        });
    });
}

// Preview the dashboard card before saving a new color.
document.querySelectorAll('[data-dashboard-color-setting]').forEach((setting) => {
    const preview = setting.querySelector('[data-dashboard-color-preview]');
    const name = setting.querySelector('[data-dashboard-color-name]');

    setting.querySelectorAll('[data-dashboard-color-input]').forEach((input) => {
        input.addEventListener('change', () => {
            preview.dataset.dashboardColor = input.value;
            name.textContent = input.value.charAt(0).toUpperCase() + input.value.slice(1);
        });
    });
});

// Scan the QR on an official receipt (camera or a saved image) to open it again.
const scannerDialog = document.querySelector('[data-qr-scanner]');
if (scannerDialog) {
    const video = scannerDialog.querySelector('[data-qr-video]');
    const status = scannerDialog.querySelector('[data-qr-status]');
    const fileInput = scannerDialog.querySelector('[data-qr-file]');
    let scanner = null;
    let QrScanner = null;

    const say = (message) => { status.textContent = message; };

    const stop = () => {
        scanner?.stop();
        scanner?.destroy();
        scanner = null;
    };

    // Only our own receipt links are opened; the signed link is kept exactly as scanned.
    const handle = (text) => {
        let url;
        try { url = new URL(text); } catch { url = null; }

        if (url && url.pathname.startsWith('/receipts/verify/') && url.searchParams.has('signature')) {
            say('Receipt found. Opening…');
            stop();
            window.location.href = url.href;
            return;
        }

        say('That QR code is not a Queen Jackie receipt.');
    };

    const load = async () => {
        QrScanner ??= (await import('qr-scanner')).default;
        return QrScanner;
    };

    document.querySelector('[data-qr-open]')?.addEventListener('click', async () => {
        scannerDialog.showModal();
        say('Starting the camera…');

        try {
            const Scanner = await load();
            scanner = new Scanner(video, (result) => handle(result.data), { returnDetailedScanResult: true, highlightScanRegion: true, highlightCodeOutline: true });
            await scanner.start();
            say('Point your camera at the QR code on your receipt.');
        } catch {
            say('The camera is not available. Choose a photo or a downloaded receipt below instead.');
        }
    });

    fileInput.addEventListener('change', async () => {
        const [file] = fileInput.files;
        if (!file) return;
        say('Reading the image…');

        try {
            const Scanner = await load();
            const result = await Scanner.scanImage(file, { returnDetailedScanResult: true });
            handle(result.data);
        } catch {
            say('No QR code found in that image. Try a clearer photo.');
        } finally {
            fileInput.value = '';
        }
    });

    scannerDialog.querySelector('[data-qr-close]').addEventListener('click', () => scannerDialog.close());
    scannerDialog.addEventListener('close', stop);
}
