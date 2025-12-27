// Admin bundle create/edit form builder (migrated from inline Blade scripts).
// Runs only when #bundleForm exists.

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function initBundleForm() {
    const form = document.getElementById('bundleForm');
    if (!form) return;

    const csrf = csrfToken();
    const itemsContainer = document.getElementById('itemsContainer');
    const pricesContainer = document.getElementById('pricesContainer');
    const errorsBox = document.getElementById('formErrors');

    const existing = (() => {
        try {
            return JSON.parse(form.dataset.existing || 'null');
        } catch (_) {
            return null;
        }
    })();

    function parseIntOrNull(value) {
        const n = parseInt(value, 10);
        return Number.isNaN(n) ? null : n;
    }

    function boolVal(input) {
        return !!(input && input.checked);
    }

    function renderItemRow(item = {}) {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-1 md:grid-cols-6 gap-3 border rounded p-3 item-row';
        row.innerHTML = `
            <div>
                <label class="text-xs text-gray-600">Product ID *</label>
                <input type="number" data-field="product_id" class="w-full border rounded px-2 py-1" value="${item.product_id ?? ''}" required>
            </div>
            <div>
                <label class="text-xs text-gray-600">Variant ID</label>
                <input type="number" data-field="product_variant_id" class="w-full border rounded px-2 py-1" value="${item.product_variant_id ?? ''}">
            </div>
            <div>
                <label class="text-xs text-gray-600">Quantity *</label>
                <input type="number" data-field="quantity" class="w-full border rounded px-2 py-1" value="${item.quantity ?? 1}" min="1" required>
            </div>
            <div>
                <label class="text-xs text-gray-600">Min Qty</label>
                <input type="number" data-field="min_quantity" class="w-full border rounded px-2 py-1" value="${item.min_quantity ?? 1}" min="1">
            </div>
            <div>
                <label class="text-xs text-gray-600">Max Qty</label>
                <input type="number" data-field="max_quantity" class="w-full border rounded px-2 py-1" value="${item.max_quantity ?? ''}" min="1">
            </div>
            <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-1 text-xs">
                    <input type="checkbox" data-field="is_required" ${item.is_required ?? true ? 'checked' : ''}> Required
                </label>
                <label class="inline-flex items-center gap-1 text-xs">
                    <input type="checkbox" data-field="is_default" ${item.is_default ? 'checked' : ''}> Default
                </label>
            </div>
            <div>
                <label class="text-xs text-gray-600">Price Override (cents)</label>
                <input type="number" data-field="price_override" class="w-full border rounded px-2 py-1" value="${item.price_override ?? ''}">
            </div>
            <div>
                <label class="text-xs text-gray-600">Discount (cents)</label>
                <input type="number" data-field="discount_amount" class="w-full border rounded px-2 py-1" value="${item.discount_amount ?? ''}">
            </div>
            <div>
                <label class="text-xs text-gray-600">Display Order</label>
                <input type="number" data-field="display_order" class="w-full border rounded px-2 py-1" value="${item.display_order ?? 0}" min="0">
            </div>
            <div class="md:col-span-2">
                <label class="text-xs text-gray-600">Notes</label>
                <input type="text" data-field="notes" class="w-full border rounded px-2 py-1" value="${item.notes ?? ''}">
            </div>
            <div class="flex items-center justify-end md:col-span-4">
                <button type="button" class="text-red-600 text-sm remove-item">Remove</button>
            </div>
        `;
        row.querySelector('.remove-item').addEventListener('click', () => row.remove());
        itemsContainer.appendChild(row);
    }

    function renderPriceRow(price = {}) {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-1 md:grid-cols-5 gap-3 border rounded p-3 price-row';
        row.innerHTML = `
            <div>
                <label class="text-xs text-gray-600">Currency ID *</label>
                <input type="number" data-field="currency_id" class="w-full border rounded px-2 py-1" value="${price.currency_id ?? ''}">
            </div>
            <div>
                <label class="text-xs text-gray-600">Customer Group ID</label>
                <input type="number" data-field="customer_group_id" class="w-full border rounded px-2 py-1" value="${price.customer_group_id ?? ''}">
            </div>
            <div>
                <label class="text-xs text-gray-600">Price (cents) *</label>
                <input type="number" data-field="price" class="w-full border rounded px-2 py-1" value="${price.price ?? ''}">
            </div>
            <div>
                <label class="text-xs text-gray-600">Compare At (cents)</label>
                <input type="number" data-field="compare_at_price" class="w-full border rounded px-2 py-1" value="${price.compare_at_price ?? ''}">
            </div>
            <div class="flex items-center gap-2">
                <div>
                    <label class="text-xs text-gray-600">Min Qty</label>
                    <input type="number" data-field="min_quantity" class="w-full border rounded px-2 py-1" value="${price.min_quantity ?? 1}" min="1">
                </div>
                <div>
                    <label class="text-xs text-gray-600">Max Qty</label>
                    <input type="number" data-field="max_quantity" class="w-full border rounded px-2 py-1" value="${price.max_quantity ?? ''}" min="1">
                </div>
                <button type="button" class="text-red-600 text-sm remove-price self-end">Remove</button>
            </div>
        `;
        row.querySelector('.remove-price').addEventListener('click', () => row.remove());
        pricesContainer.appendChild(row);
    }

    function collectRows(container, selector) {
        return Array.from(container.querySelectorAll(selector))
            .map((row) => {
                const get = (field) => {
                    const el = row.querySelector(`[data-field="${field}"]`);
                    if (!el) return null;
                    if (el.type === 'checkbox') return el.checked;
                    const val = el.value?.trim();
                    return val === '' ? null : val;
                };

                return {
                    product_id: parseIntOrNull(get('product_id')),
                    product_variant_id: parseIntOrNull(get('product_variant_id')),
                    quantity: parseIntOrNull(get('quantity')) ?? 1,
                    min_quantity: parseIntOrNull(get('min_quantity')),
                    max_quantity: parseIntOrNull(get('max_quantity')),
                    is_required: !!get('is_required'),
                    is_default: !!get('is_default'),
                    price_override: parseIntOrNull(get('price_override')),
                    discount_amount: parseIntOrNull(get('discount_amount')),
                    display_order: parseIntOrNull(get('display_order')),
                    notes: get('notes'),
                };
            })
            .filter((item) => item.product_id);
    }

    function collectPrices() {
        return Array.from(pricesContainer.querySelectorAll('.price-row'))
            .map((row) => {
                const get = (field) => {
                    const el = row.querySelector(`[data-field="${field}"]`);
                    const val = el?.value?.trim();
                    return val === '' ? null : val;
                };

                return {
                    currency_id: parseIntOrNull(get('currency_id')),
                    customer_group_id: parseIntOrNull(get('customer_group_id')),
                    price: parseIntOrNull(get('price')),
                    compare_at_price: parseIntOrNull(get('compare_at_price')),
                    min_quantity: parseIntOrNull(get('min_quantity')),
                    max_quantity: parseIntOrNull(get('max_quantity')),
                };
            })
            .filter((price) => price.currency_id && price.price !== null);
    }

    function serializeForm() {
        const data = new FormData(form);
        return {
            product_id: parseIntOrNull(data.get('product_id')),
            sku: data.get('sku') || null,
            name: data.get('name') || '',
            slug: data.get('slug') || null,
            description: data.get('description') || null,
            pricing_type: data.get('pricing_type') || 'fixed',
            discount_amount: parseIntOrNull(data.get('discount_amount')),
            bundle_price: parseIntOrNull(data.get('bundle_price')),
            inventory_type: data.get('inventory_type') || 'component',
            stock: parseIntOrNull(data.get('stock')) ?? 0,
            min_quantity: parseIntOrNull(data.get('min_quantity')) ?? 1,
            max_quantity: parseIntOrNull(data.get('max_quantity')),
            display_order: parseIntOrNull(data.get('display_order')) ?? 0,
            is_active: !!data.get('is_active'),
            is_featured: !!data.get('is_featured'),
            allow_customization: !!data.get('allow_customization'),
            show_individual_prices: !!data.get('show_individual_prices'),
            show_savings: !!data.get('show_savings'),
            items: collectRows(itemsContainer, '.item-row'),
            prices: collectPrices(),
        };
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorsBox.classList.add('hidden');
        errorsBox.innerHTML = '';

        const payload = serializeForm();
        if (!payload.items.length) {
            errorsBox.textContent = 'Please add at least one bundle item.';
            errorsBox.classList.remove('hidden');
            return;
        }

        try {
            const res = await fetch(form.dataset.submitUrl, {
                method: form.dataset.method || 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                const messages = [];
                if (data?.errors) {
                    Object.values(data.errors).forEach((errList) => messages.push(...errList));
                } else if (data?.message) {
                    messages.push(data.message);
                } else {
                    messages.push('Unable to save bundle. Please review the form.');
                }
                errorsBox.innerHTML = messages.map((m) => `<div>${m}</div>`).join('');
                errorsBox.classList.remove('hidden');
                return;
            }

            window.location.href = form.dataset.redirectUrl;
        } catch (error) {
            errorsBox.textContent = 'Unexpected error while saving. Please try again.';
            errorsBox.classList.remove('hidden');
            console.error(error);
        }
    });

    document.getElementById('addItemBtn')?.addEventListener('click', () => renderItemRow());
    document.getElementById('addPriceBtn')?.addEventListener('click', () => renderPriceRow());

    // Seed initial rows (edit vs create)
    if (existing?.items?.length) {
        existing.items.forEach((item) => renderItemRow(item));
    }
    if (existing?.prices?.length) {
        existing.prices.forEach((price) => renderPriceRow(price));
    }
    if (!existing?.items?.length && itemsContainer && itemsContainer.children.length === 0) {
        renderItemRow();
    }
}

export function initAdminBundleForm() {
    if (document.body?.dataset?.app !== 'admin') return;
    initBundleForm();
}

initAdminBundleForm();


