// Admin: migrated inline Blade scripts live here (no <script> blocks in Blade).

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function isAdmin() {
    return document.body?.dataset?.app === 'admin';
}

async function postJson(url, payload = {}) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));
    return { response, data };
}

function guessMessageEl(form) {
    if (!form?.id) return null;
    const id = form.id.replace(/-form$/, '-message');
    return document.getElementById(id);
}

function bindJsonForm(form, { preprocessPayload } = {}) {
    if (!form) return;
    const url = form.dataset.url || form.action;
    if (!url) return;

    const messageEl = guessMessageEl(form);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (messageEl) messageEl.textContent = 'Saving...';

        const formData = new FormData(form);
        let payload = Object.fromEntries(formData.entries());

        if (typeof preprocessPayload === 'function') {
            payload = preprocessPayload(payload) ?? payload;
        }

        try {
            const { response, data } = await postJson(url, payload);
            if (messageEl) messageEl.textContent = data?.message || (response.ok ? 'Saved.' : 'Request failed.');
            if (response.ok) {
                setTimeout(() => window.location.reload(), 800);
            }
        } catch (_) {
            if (messageEl) messageEl.textContent = 'Request failed.';
        }
    });
}

function initScheduleCalendar() {
    const scheduleList = document.getElementById('schedule-list');
    const url = scheduleList?.dataset?.url;
    if (!scheduleList || !url) return;

    const start = new Date();
    const end = new Date(start.getFullYear(), start.getMonth() + 1, 0);

    const params = new URLSearchParams({
        start: start.toISOString().slice(0, 10),
        end: end.toISOString().slice(0, 10),
    });

    fetch(`${url}?${params}`)
        .then((response) => response.json())
        .then((data) => {
            if (!Array.isArray(data) || data.length === 0) {
                scheduleList.textContent = 'No schedules found for this month.';
                return;
            }

            const items = data.map((item) => {
                const startDate = new Date(item.start).toLocaleDateString();
                return `<div class="flex items-center justify-between border border-slate-200 rounded px-4 py-3">
                    <div>
                        <div class="font-semibold">${item.title}</div>
                        <div class="text-xs text-slate-500">${startDate} | ${item.type}</div>
                    </div>
                    <a href="${item.url}" class="text-blue-600 hover:underline text-sm">View</a>
                </div>`;
            });

            scheduleList.innerHTML = `<div class="space-y-3">${items.join('')}</div>`;
        })
        .catch(() => {
            scheduleList.textContent = 'Failed to load schedules.';
        });
}

export function initAdminInlineMigrations() {
    if (!isAdmin()) return;

    // Generic admin JSON forms
    bindJsonForm(document.getElementById('synonym-form'), {
        preprocessPayload: (payload) => {
            if (typeof payload.synonyms === 'string') {
                payload.synonyms = payload.synonyms
                    .split(',')
                    .map((item) => item.trim())
                    .filter(Boolean);
            }
            return payload;
        },
    });
    bindJsonForm(document.getElementById('recommendation-form'));

    // Q&A moderation
    bindJsonForm(document.getElementById('question-moderate-form'));
    bindJsonForm(document.getElementById('question-answer-form'));
    document.querySelectorAll('form.answer-moderate-form[data-url]').forEach((form) => bindJsonForm(form));

    // Misc common forms (best-effort)
    [
        'template-form',
        'example-form',
        'customization-form',
        'badge-assign-form',
        'matrix-create-form',
        'pricing-import-form',
        'inventory-adjust-form',
    ].forEach((id) => bindJsonForm(document.getElementById(id)));

    initScheduleCalendar();
}

initAdminInlineMigrations();


