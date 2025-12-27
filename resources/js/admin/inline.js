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
    const explicit = form?.dataset?.messageId;
    if (explicit) return document.getElementById(explicit);
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

        try {
            // Multipart forms must be sent as FormData.
            const isMultipart = (form.enctype || '').includes('multipart/form-data');

            let response;
            let data;

            if (isMultipart) {
                const formData = new FormData(form);
                response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });
                data = await response.json().catch(() => ({}));
            } else {
                const formData = new FormData(form);
                let payload = Object.fromEntries(formData.entries());
                if (typeof preprocessPayload === 'function') {
                    payload = preprocessPayload(payload) ?? payload;
                }
                ({ response, data } = await postJson(url, payload));
            }

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

function initReviewModeration() {
    const bulkForm = document.getElementById('bulkActionsForm');
    const responseForm = document.getElementById('responseForm');
    const responseModal = document.getElementById('responseModal');
    const responseText = document.getElementById('responseText');
    const responseError = document.getElementById('responseError');

    if (!bulkForm && !responseForm) return;

    function selectedReviewIds() {
        return Array.from(document.querySelectorAll('.review-checkbox:checked')).map((cb) => cb.value);
    }

    window.selectAll = function selectAll() {
        document.querySelectorAll('.review-checkbox').forEach((cb) => (cb.checked = true));
    };

    window.deselectAll = function deselectAll() {
        document.querySelectorAll('.review-checkbox').forEach((cb) => (cb.checked = false));
    };

    function submitBulk(url, verbLabel) {
        const selected = selectedReviewIds();
        if (selected.length === 0) {
            alert('Please select at least one review.');
            return;
        }
        if (!confirm(`${verbLabel} ${selected.length} review(s)?`)) return;

        bulkForm.action = url;

        // Remove previous injected inputs
        bulkForm.querySelectorAll('input[name="review_ids[]"].js-injected').forEach((el) => el.remove());
        selected.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'review_ids[]';
            input.value = id;
            input.className = 'js-injected';
            bulkForm.appendChild(input);
        });

        bulkForm.submit();
    }

    window.bulkApprove = function bulkApprove() {
        const url = bulkForm?.dataset?.bulkApproveUrl;
        if (url) submitBulk(url, 'Approve');
    };

    window.bulkReject = function bulkReject() {
        const url = bulkForm?.dataset?.bulkRejectUrl;
        if (url) submitBulk(url, 'Reject');
    };

    window.openResponseModal = function openResponseModal(reviewId, currentResponse = '') {
        if (!responseModal || !responseForm || !responseText) return;

        const template = responseForm.dataset.actionTemplate;
        if (template) {
            responseForm.action = template.replace('__REVIEW__', String(reviewId));
        }
        responseText.value = currentResponse || '';
        responseModal.classList.remove('hidden');
    };

    window.closeResponseModal = function closeResponseModal() {
        if (!responseModal) return;
        responseModal.classList.add('hidden');
        if (responseText) responseText.value = '';
        if (responseError) responseError.classList.add('hidden');
    };

    responseForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(responseForm);
        const response = await fetch(responseForm.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        const data = await response.json().catch(() => ({}));
        if (response.ok) {
            window.closeResponseModal();
            location.reload();
            return;
        }

        if (responseError) {
            responseError.textContent = data?.message || 'An error occurred';
            responseError.classList.remove('hidden');
        }
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

    // Generic delete buttons (data-url) used by admin tables.
    document.querySelectorAll('[data-url].customization-delete').forEach((button) => {
        button.addEventListener('click', async () => {
            if (!confirm('Delete this customization?')) return;
            try {
                const response = await fetch(button.dataset.url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Failed to delete customization.');
                }
            } catch (_) {
                alert('Failed to delete customization.');
            }
        });
    });

    document.querySelectorAll('[data-url].badge-remove').forEach((button) => {
        button.addEventListener('click', async () => {
            if (!confirm('Remove this badge?')) return;
            try {
                const response = await fetch(button.dataset.url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Failed to remove badge.');
                }
            } catch (_) {
                alert('Failed to remove badge.');
            }
        });
    });

    initScheduleCalendar();
    initReviewModeration();
}

initAdminInlineMigrations();


