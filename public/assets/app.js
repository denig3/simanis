/*
 * STOKORA — Inventory & Order Management System
 * Frontend Interactive Controller & Role Simulation Engine
 */

'use strict';

const token = document.querySelector('meta[name="csrf-token"]')?.content;
const message = document.querySelector('#form-message');

// State Manager Global Prototipe
const AppState = {
    currentRole: 'admin', // 'admin' | 'sales' | 'warehouse'
    rolesInfo: {
        admin: { title: 'Admin System', initial: 'A', class: 'badge-purple' },
        sales: { title: 'Sales Staff', initial: 'S', class: 'badge-blue' },
        warehouse: { title: 'Staff Gudang', initial: 'W', class: 'badge-warning' },
    }
};

// 1. HTTP Request API Function
async function request(path, body = {}) {
    const response = await fetch(path, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token, 'Accept': 'application/json' },
        body: JSON.stringify(body),
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'Permintaan gagal. Silakan coba kembali.');
    return data;
}

// Tampilkan Pesan Toast Notifikasi
function showToast(text) {
    const toast = document.querySelector('#prototype-toast');
    if (!toast) return;
    toast.textContent = text;
    toast.hidden = false;
    setTimeout(() => { toast.hidden = true; }, 3000);
}

function showError(error) {
    if (!message) return;
    message.textContent = error instanceof TypeError || error instanceof SyntaxError
        ? 'Tidak dapat terhubung ke server. Periksa koneksi Anda dan coba lagi.'
        : error.message;
    message.hidden = false;
}

// 2. Event Listeners Auth (Login / Logout / Register)
document.querySelector('#toggle-password')?.addEventListener('click', (event) => {
    const input = document.querySelector('#password');
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    event.currentTarget.textContent = visible ? 'Sembunyikan' : 'Lihat';
    event.currentTarget.setAttribute('aria-pressed', String(visible));
});

document.querySelector('#login-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = document.querySelector('#login-button');
    if (button.disabled) return;
    if (message) message.hidden = true;
    button.disabled = true;
    button.textContent = 'Sedang masuk...';
    try {
        const fields = new FormData(event.currentTarget);
        const data = await request('/api/login', { email: fields.get('email'), password: fields.get('password') });
        window.location.assign(data.redirect);
    } catch (error) {
        showError(error);
        button.disabled = false;
        button.textContent = 'Masuk';
    }
});

document.querySelector('#logout-button')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    button.disabled = true;
    try {
        const data = await request('/api/logout');
        window.location.assign(data.redirect);
    } catch (error) {
        showError(error);
        button.disabled = false;
    }
});

document.querySelector('#register-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = document.querySelector('#register-button');
    if (button.disabled) return;
    if (message) message.hidden = true;
    button.disabled = true;
    button.textContent = 'Sedang mendaftar...';
    try {
        const fields = new FormData(event.currentTarget);
        if (fields.get('password') !== fields.get('password_confirmation')) {
            throw new Error('Konfirmasi password tidak sama.');
        }
        const data = await request('/api/register', Object.fromEntries(fields));
        window.location.assign(data.redirect);
    } catch (error) {
        showError(error);
        button.disabled = false;
        button.textContent = 'Daftar';
    }
});

// ==========================================
// 3. PROTOTYPE INTERACTIVE SIMULATION ENGINE
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    initSidebarToggle();
    initRoleSwitcher();
    initTabNavigation();
    initSubTabNavigation();
    updateRoleUI();
    initFlowGuideModal();
    initAddUserModal();
    initEditUserModal();
    initMasterDataModals();
    initEditMasterModals();
    initSalesOrderModals();
    initExportButtons();
});

// Inisialisasi Navigasi Sub-Tab Master Data (Produk, Kategori, Gudang, Supplier, Customer)
function initSubTabNavigation() {
    const subTabBtns = document.querySelectorAll('.sub-tab-btn');
    if (subTabBtns.length === 0) return;

    subTabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-subtab');
            if (!targetId) return;

            subTabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            document.querySelectorAll('.sub-tab-content').forEach(content => {
                content.hidden = (content.id !== targetId);
            });
        });
    });
}

function initExportButtons() {
    const bindBtn = (id, type) => {
        const btn = document.querySelector(id);
        if (btn) {
            btn.onclick = (e) => {
                e.preventDefault();
                if (window.SimulasiModule && typeof window.SimulasiModule.exportCSV === 'function') {
                    window.SimulasiModule.exportCSV(type);
                }
            };
        }
    };
    bindBtn('#btn-export-dash-admin', 'admin');
    bindBtn('#btn-export-dash-sales', 'sales');
    bindBtn('#btn-export-dash-warehouse', 'warehouse');
    bindBtn('#btn-export-master-admin', 'products');
    bindBtn('#btn-export-products', 'products');
    bindBtn('#btn-export-categories', 'categories');
    bindBtn('#btn-export-warehouses', 'warehouses');
    bindBtn('#btn-export-suppliers', 'suppliers');
    bindBtn('#btn-export-customers', 'customers');
    bindBtn('#btn-export-catalog', 'catalog');
    bindBtn('#btn-export-warehouse-stock', 'warehouse');
    bindBtn('#btn-export-so', 'so');
    bindBtn('#btn-export-po', 'po');
    bindBtn('#btn-export-ledger', 'ledger');
}

// Inisialisasi Modal Alur Operasional Sistem
function initFlowGuideModal() {
    const modal = document.querySelector('#modal-flow-guide');
    const openBtn = document.querySelector('#btn-flow-modal');
    const closeBtn = document.querySelector('#btn-close-flow-modal');
    if (!modal || !openBtn || !closeBtn) return;

    openBtn.addEventListener('click', () => {
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    });

    closeBtn.addEventListener('click', () => {
        modal.hidden = true;
        document.body.style.overflow = '';
    });

    // Tutup modal saat klik di luar modal-card (area overlay)
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.hidden = true;
            document.body.style.overflow = '';
        }
    });

    // Tutup modal dengan tombol Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) {
            modal.hidden = true;
            document.body.style.overflow = '';
        }
    });
}

// Inisialisasi Modal Tambah User Baru (SOD Matrix)
function initAddUserModal() {
    const modal = document.querySelector('#modal-add-user');
    const openBtn = document.querySelector('#btn-open-add-user');
    const closeBtn = document.querySelector('#btn-close-add-user');
    const cancelBtn = document.querySelector('#btn-cancel-add-user');
    const form = document.querySelector('#form-add-user');
    const errorBox = document.querySelector('#add-user-error');
    const submitBtn = document.querySelector('#btn-submit-add-user');

    if (!modal || !openBtn || !form) return;

    const closeModal = () => {
        modal.hidden = true;
        document.body.style.overflow = '';
        form.reset();
        if (errorBox) {
            errorBox.hidden = true;
            errorBox.textContent = '';
        }
    };

    openBtn.addEventListener('click', () => {
        if (AppState.currentRole !== 'admin') {
            showToast('❌ Akses ditolak: Hanya Admin yang berhak menambah user baru.');
            return;
        }
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        document.querySelector('#new-user-name')?.focus();
    });

    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Menyimpan...';
        }
        if (errorBox) errorBox.hidden = true;

        const formData = new FormData(form);
        const payload = {
            name: formData.get('name'),
            email: formData.get('email'),
            password: formData.get('password'),
            role: formData.get('role'),
            warehouse: formData.get('warehouse')
        };

        try {
            // Panggil API endpoint backend /api/users
            const response = await request('/api/users', payload);
            const user = response.user;

            // Tambahkan baris baru ke tabel pengguna secara dinamis
            const tbody = document.querySelector('#users-table-body');
            if (tbody) {
                const tr = document.createElement('tr');
                const roleBadgeClass = user.role === 'admin' ? 'badge-purple' : (user.role === 'sales' ? 'badge-blue' : 'badge-warning');
                const roleTitle = user.role === 'admin' ? 'Admin' : (user.role === 'sales' ? 'Sales Staff' : 'Warehouse Staff');
                const statusBadgeClass = user.status === 'active' ? 'badge-success' : 'badge-danger';
                const statusLabel = user.status_label || (user.status === 'active' ? 'Aktif' : 'Nonaktif');

                tr.id = `user-row-${user.id}`;
                tr.setAttribute('data-user-id', String(user.id));
                tr.setAttribute('data-user-code', user.code || `#USR-${user.id}`);
                tr.setAttribute('data-user-name', user.name);
                tr.setAttribute('data-user-email', user.email);
                tr.setAttribute('data-user-role', user.role);
                tr.setAttribute('data-user-warehouse', user.warehouse);
                tr.setAttribute('data-user-status', user.status);

                tr.innerHTML = `
                    <td>${user.code || `#USR-${user.id}`}</td>
                    <td class="user-cell-name"><strong>${escapeHtml(user.name)}</strong></td>
                    <td class="user-cell-email">${escapeHtml(user.email)}</td>
                    <td class="user-cell-role"><span class="badge ${roleBadgeClass}">${roleTitle}</span></td>
                    <td class="user-cell-warehouse">${escapeHtml(user.warehouse)}</td>
                    <td class="user-cell-status"><span class="badge ${statusBadgeClass}">${statusLabel}</span></td>
                    <td class="user-cell-action">
                        <button type="button" class="btn-sm btn-secondary btn-edit-user" data-user-id="${user.id}">✏️ Edit Role</button>
                    </td>
                `;
                tbody.appendChild(tr);
            }

            closeModal();
            showToast(`✅ Pengguna "${user.name}" (${user.role.toUpperCase()}) berhasil ditambahkan ke database!`);
        } catch (err) {
            if (errorBox) {
                errorBox.textContent = err.message || 'Gagal menambahkan user baru.';
                errorBox.hidden = false;
            } else {
                showToast(`❌ ${err.message}`);
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = '💾 Simpan Pengguna';
            }
        }
    });
}

// Inisialisasi Modal Edit & Hapus Pengguna (SOD Matrix)
function initEditUserModal() {
    const modal = document.querySelector('#modal-edit-user');
    const closeBtn = document.querySelector('#btn-close-edit-user');
    const cancelBtn = document.querySelector('#btn-cancel-edit-user');
    const deleteBtn = document.querySelector('#btn-delete-user');
    const form = document.querySelector('#form-edit-user');
    const errorBox = document.querySelector('#edit-user-error');
    const submitBtn = document.querySelector('#btn-submit-edit-user');

    const confirmBox = document.querySelector('#delete-user-confirm-box');
    const cancelConfirmBtn = document.querySelector('#btn-cancel-delete-confirm');
    const confirmDeleteBtn = document.querySelector('#btn-confirm-delete-action');

    if (!modal || !form) return;

    const closeModal = () => {
        modal.hidden = true;
        document.body.style.overflow = '';
        if (confirmBox) confirmBox.hidden = true;
        form.reset();
        if (errorBox) {
            errorBox.hidden = true;
            errorBox.textContent = '';
        }
    };

    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
    });

    // Event delegation untuk tombol Edit Role di tabel pengguna
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-edit-user');
        if (!btn) return;

        if (AppState.currentRole !== 'admin') {
            showToast('❌ Akses ditolak: Hanya Admin yang berhak mengedit data pengguna.');
            return;
        }

        const tr = btn.closest('tr');
        if (!tr) return;

        const userId = tr.getAttribute('data-user-id') || btn.getAttribute('data-user-id');
        const userCode = tr.getAttribute('data-user-code') || `#USR-${userId}`;
        const userName = tr.getAttribute('data-user-name') || '';
        const userEmail = tr.getAttribute('data-user-email') || '';
        const userRole = tr.getAttribute('data-user-role') || 'sales';
        const userWh = tr.getAttribute('data-user-warehouse') || 'Semua Gudang (Pusat)';
        const userStatus = tr.getAttribute('data-user-status') || 'active';

        // Isi form edit
        const idField = document.querySelector('#edit-user-id');
        const codeField = document.querySelector('#edit-user-code');
        const nameField = document.querySelector('#edit-user-name');
        const emailField = document.querySelector('#edit-user-email');
        const passField = document.querySelector('#edit-user-password');
        const roleField = document.querySelector('#edit-user-role');
        const whField = document.querySelector('#edit-user-warehouse');
        const statusField = document.querySelector('#edit-user-status');

        if (idField) idField.value = userId;
        if (codeField) codeField.value = userCode;
        if (nameField) nameField.value = userName;
        if (emailField) emailField.value = userEmail;
        if (passField) passField.value = '';
        if (roleField) roleField.value = userRole;
        if (whField) whField.value = userWh;
        if (statusField) statusField.value = userStatus;

        if (confirmBox) confirmBox.hidden = true;
        if (errorBox) {
            errorBox.hidden = true;
            errorBox.textContent = '';
        }

        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        nameField?.focus();
    });

    // Submit Update Data Pengguna
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Menyimpan...';
        }
        if (errorBox) errorBox.hidden = true;

        const formData = new FormData(form);
        const payload = {
            id: parseInt(formData.get('id'), 10),
            name: formData.get('name'),
            email: formData.get('email'),
            password: formData.get('password'),
            role: formData.get('role'),
            warehouse: formData.get('warehouse'),
            status: formData.get('status')
        };

        try {
            const response = await request('/api/users/update', payload);
            const user = response.user;

            // Update row di tabel DOM
            const tr = document.querySelector(`#user-row-${user.id}`);
            if (tr) {
                const roleBadgeClass = user.role === 'admin' ? 'badge-purple' : (user.role === 'sales' ? 'badge-blue' : 'badge-warning');
                const roleTitle = user.role === 'admin' ? 'Admin' : (user.role === 'sales' ? 'Sales Staff' : 'Warehouse Staff');
                const statusBadgeClass = user.status === 'active' ? 'badge-success' : 'badge-danger';
                const statusLabel = user.status_label || (user.status === 'active' ? 'Aktif' : 'Nonaktif');

                tr.setAttribute('data-user-name', user.name);
                tr.setAttribute('data-user-email', user.email);
                tr.setAttribute('data-user-role', user.role);
                tr.setAttribute('data-user-warehouse', user.warehouse);
                tr.setAttribute('data-user-status', user.status);

                const cellName = tr.querySelector('.user-cell-name');
                const cellEmail = tr.querySelector('.user-cell-email');
                const cellRole = tr.querySelector('.user-cell-role');
                const cellWh = tr.querySelector('.user-cell-warehouse');
                const cellStatus = tr.querySelector('.user-cell-status');

                if (cellName) cellName.innerHTML = `<strong>${escapeHtml(user.name)}</strong>`;
                if (cellEmail) cellEmail.textContent = user.email;
                if (cellRole) cellRole.innerHTML = `<span class="badge ${roleBadgeClass}">${roleTitle}</span>`;
                if (cellWh) cellWh.textContent = user.warehouse;
                if (cellStatus) cellStatus.innerHTML = `<span class="badge ${statusBadgeClass}">${statusLabel}</span>`;
            }

            closeModal();
            showToast(`✅ Data pengguna "${user.name}" (${user.role.toUpperCase()}) berhasil diperbarui di database!`);
        } catch (err) {
            if (errorBox) {
                errorBox.textContent = err.message || 'Gagal memperbarui pengguna.';
                errorBox.hidden = false;
            } else {
                showToast(`❌ ${err.message}`);
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = '💾 Simpan Perubahan';
            }
        }
    });

    // Tampilkan / sembunyikan kotak konfirmasi hapus pengguna (Anti-Block)
    deleteBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (confirmBox) {
            confirmBox.hidden = !confirmBox.hidden;
            if (!confirmBox.hidden) {
                confirmBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    });

    cancelConfirmBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        if (confirmBox) confirmBox.hidden = true;
    });

    // Eksekusi Hapus Pengguna secara pasti ke database
    confirmDeleteBtn?.addEventListener('click', async (e) => {
        e.preventDefault();
        const userId = parseInt(document.querySelector('#edit-user-id')?.value || '0', 10);
        const userName = document.querySelector('#edit-user-name')?.value || 'pengguna ini';

        if (!userId) {
            showToast('❌ ID pengguna tidak valid.');
            return;
        }

        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.textContent = 'Menghapus...';

        try {
            const response = await request('/api/users/delete', { id: userId });

            const tr = document.querySelector(`#user-row-${userId}`);
            tr?.remove();

            closeModal();
            showToast(`✅ ${response.message}`);
        } catch (err) {
            if (errorBox) {
                errorBox.textContent = err.message || 'Gagal menghapus pengguna.';
                errorBox.hidden = false;
            } else {
                showToast(`❌ ${err.message}`);
            }
        } finally {
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.textContent = 'Ya, Hapus Pengguna';
        }
    });
}

// Inisialisasi Seluruh Modal Tambah Master Data (Produk, Kategori, Gudang, Supplier, Customer)
function initMasterDataModals() {
    function setupModal(modalId, openBtnId, closeBtnId, cancelBtnId, formId, errorBoxId, submitBtnId, apiEndpoint, onSuccess) {
        const modal = document.querySelector(modalId);
        const openBtn = document.querySelector(openBtnId);
        const closeBtn = document.querySelector(closeBtnId);
        const cancelBtn = document.querySelector(cancelBtnId);
        const form = document.querySelector(formId);
        const errorBox = document.querySelector(errorBoxId);
        const submitBtn = document.querySelector(submitBtnId);

        if (!modal || !openBtn || !form) return;

        const closeModal = () => {
            modal.hidden = true;
            document.body.style.overflow = '';
            form.reset();
            if (errorBox) {
                errorBox.hidden = true;
                errorBox.textContent = '';
            }
        };

        openBtn.addEventListener('click', () => {
            if (AppState.currentRole !== 'admin') {
                showToast('❌ Akses ditolak: Hanya Admin yang berhak menambah data master.');
                return;
            }
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            const firstInput = form.querySelector('input:not([type="hidden"]), select, textarea');
            firstInput?.focus();
        });

        closeBtn?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.hidden) closeModal();
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const originalBtnText = submitBtn?.textContent || 'Simpan';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Menyimpan...';
            }
            if (errorBox) {
                errorBox.hidden = true;
                errorBox.textContent = '';
            }

            try {
                const formData = new FormData(form);
                const payload = Object.fromEntries(formData.entries());
                const response = await request(apiEndpoint, payload);
                closeModal();
                showToast(`✅ ${response.message || 'Data berhasil disimpan.'}`);
                if (typeof onSuccess === 'function') {
                    onSuccess(response);
                }
            } catch (err) {
                if (errorBox) {
                    errorBox.textContent = err.message;
                    errorBox.hidden = false;
                } else {
                    showToast(`❌ ${err.message}`);
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            }
        });
    }

    // 1. Modal Tambah Master Produk
    setupModal(
        '#modal-add-product',
        '#btn-open-add-product',
        '#btn-close-add-product',
        '#btn-cancel-add-product',
        '#form-add-product',
        '#add-product-error',
        '#btn-submit-add-product',
        '/api/products/create',
        (res) => {
            const p = res.product;
            const tbody = document.querySelector('#products-table-body');
            const emptyRow = document.querySelector('#empty-products-row');
            if (emptyRow) emptyRow.remove();

            const tStock = parseInt(p.total_stock || 0, 10);
            const minStock = parseInt(p.min_stock_threshold || 10, 10);
            const bClass = tStock <= Math.floor(minStock / 2) ? 'badge-danger' : (tStock <= minStock ? 'badge-warning' : 'badge-success');
            const sText = tStock <= Math.floor(minStock / 2) ? 'Kritis' : (tStock <= minStock ? 'Reorder' : 'Aman');
            const formattedPrice = new Intl.NumberFormat('id-ID').format(p.selling_price || 0);

            const tr = document.createElement('tr');
            tr.id = `prod-row-${p.id}`;
            tr.setAttribute('data-id', String(p.id));
            tr.setAttribute('data-sku', p.sku);
            tr.setAttribute('data-name', p.name);
            tr.setAttribute('data-category-id', String(p.category_id));
            tr.setAttribute('data-category-name', p.category_name || '-');
            tr.setAttribute('data-purchase-price', String(p.purchase_price));
            tr.setAttribute('data-selling-price', String(p.selling_price));
            tr.setAttribute('data-unit', p.unit || 'unit');
            tr.setAttribute('data-min-stock', String(minStock));
            tr.setAttribute('data-is-active', '1');
            tr.innerHTML = `
                <td class="cell-sku"><code>${escapeHtml(p.sku)}</code></td>
                <td class="cell-name"><strong>${escapeHtml(p.name)}</strong></td>
                <td class="cell-category">${escapeHtml(p.category_name || '-')}</td>
                <td class="cell-selling-price">Rp ${formattedPrice}</td>
                <td class="cell-min-stock">${minStock} ${escapeHtml(p.unit || 'unit')}</td>
                <td class="cell-total-stock">${tStock} ${escapeHtml(p.unit || 'unit')} (<span class="badge ${bClass}">${sText}</span>)</td>
                <td class="cell-action" style="white-space: nowrap;">
                    <button type="button" class="btn-sm btn-secondary btn-edit-product" data-id="${p.id}">✏️ Edit</button>
                </td>
            `;
            tbody?.prepend(tr);

            const badge = document.querySelector('#badge-count-products');
            if (badge) {
                const count = parseInt(badge.textContent || '0', 10) + 1;
                badge.textContent = String(count);
            }
        }
    );

    // 2. Modal Tambah Master Kategori
    setupModal(
        '#modal-add-category',
        '#btn-open-add-category',
        '#btn-close-add-category',
        '#btn-cancel-add-category',
        '#form-add-category',
        '#add-category-error',
        '#btn-submit-add-category',
        '/api/categories/create',
        (res) => {
            const c = res.category;
            const tbody = document.querySelector('#categories-table-body');
            const emptyRow = document.querySelector('#empty-categories-row');
            if (emptyRow) emptyRow.remove();

            const tr = document.createElement('tr');
            tr.id = `cat-row-${c.id}`;
            tr.setAttribute('data-id', String(c.id));
            tr.setAttribute('data-code', c.code);
            tr.setAttribute('data-name', c.name);
            tr.setAttribute('data-description', c.description || '');
            tr.innerHTML = `
                <td class="cell-code"><code>${escapeHtml(c.code)}</code></td>
                <td class="cell-name"><strong>${escapeHtml(c.name)}</strong></td>
                <td class="cell-description">${escapeHtml(c.description || '-')}</td>
                <td><span class="badge badge-success">Aktif</span></td>
                <td class="cell-action" style="white-space: nowrap;">
                    <button type="button" class="btn-sm btn-secondary btn-edit-category" data-id="${c.id}">✏️ Edit</button>
                </td>
            `;
            tbody?.prepend(tr);

            // Tambahkan option baru ke dropdown kategori di modal produk (tambah & edit)
            ['#new-prod-category', '#edit-prod-category'].forEach(selId => {
                const sel = document.querySelector(selId);
                if (sel) {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = `${c.name} (${c.code})`;
                    sel.appendChild(opt);
                }
            });

            const badge = document.querySelector('#badge-count-categories');
            if (badge) {
                const count = parseInt(badge.textContent || '0', 10) + 1;
                badge.textContent = String(count);
            }
        }
    );

    // 3. Modal Tambah Master Lokasi Gudang
    setupModal(
        '#modal-add-warehouse',
        '#btn-open-add-warehouse',
        '#btn-close-add-warehouse',
        '#btn-cancel-add-warehouse',
        '#form-add-warehouse',
        '#add-warehouse-error',
        '#btn-submit-add-warehouse',
        '/api/warehouses/create',
        (res) => {
            const wh = res.warehouse;
            const tbody = document.querySelector('#warehouses-table-body');
            const emptyRow = document.querySelector('#empty-warehouses-row');
            if (emptyRow) emptyRow.remove();

            const tr = document.createElement('tr');
            tr.id = `wh-row-${wh.id}`;
            tr.setAttribute('data-id', String(wh.id));
            tr.setAttribute('data-code', wh.code);
            tr.setAttribute('data-name', wh.name);
            tr.setAttribute('data-city', wh.city);
            tr.setAttribute('data-address', wh.address || '');
            tr.setAttribute('data-is-active', '1');
            tr.innerHTML = `
                <td class="cell-code"><code>${escapeHtml(wh.code)}</code></td>
                <td class="cell-name"><strong>${escapeHtml(wh.name)}</strong></td>
                <td class="cell-city">${escapeHtml(wh.city)}</td>
                <td class="cell-address">${escapeHtml(wh.address || '-')}</td>
                <td class="cell-status"><span class="badge badge-success">Aktif Beroperasi</span></td>
                <td class="cell-action" style="white-space: nowrap;">
                    <button type="button" class="btn-sm btn-secondary btn-edit-warehouse" data-id="${wh.id}">✏️ Edit</button>
                </td>
            `;
            tbody?.prepend(tr);

            const badge = document.querySelector('#badge-count-warehouses');
            if (badge) {
                const count = parseInt(badge.textContent || '0', 10) + 1;
                badge.textContent = String(count);
            }
        }
    );

    // 4. Modal Tambah Master Supplier
    setupModal(
        '#modal-add-supplier',
        '#btn-open-add-supplier',
        '#btn-close-add-supplier',
        '#btn-cancel-add-supplier',
        '#form-add-supplier',
        '#add-supplier-error',
        '#btn-submit-add-supplier',
        '/api/suppliers/create',
        (res) => {
            const sup = res.supplier;
            const tbody = document.querySelector('#suppliers-table-body');
            const emptyRow = document.querySelector('#empty-suppliers-row');
            if (emptyRow) emptyRow.remove();

            const tr = document.createElement('tr');
            tr.id = `sup-row-${sup.id}`;
            tr.setAttribute('data-id', String(sup.id));
            tr.setAttribute('data-code', sup.code);
            tr.setAttribute('data-name', sup.name);
            tr.setAttribute('data-contact', sup.contact_person || '');
            tr.setAttribute('data-phone', sup.phone || '');
            tr.setAttribute('data-email', sup.email || '');
            tr.setAttribute('data-address', sup.address || '');
            tr.innerHTML = `
                <td class="cell-code"><code>${escapeHtml(sup.code)}</code></td>
                <td class="cell-name"><strong>${escapeHtml(sup.name)}</strong></td>
                <td class="cell-contact">${escapeHtml(sup.contact_person || '-')}</td>
                <td class="cell-phone">${escapeHtml(sup.phone || '-')}</td>
                <td class="cell-email">${escapeHtml(sup.email || '-')}</td>
                <td class="cell-address">${escapeHtml(sup.address || '-')}</td>
                <td class="cell-action" style="white-space: nowrap;">
                    <button type="button" class="btn-sm btn-secondary btn-edit-supplier" data-id="${sup.id}">✏️ Edit</button>
                </td>
            `;
            tbody?.prepend(tr);

            const badge = document.querySelector('#badge-count-suppliers');
            if (badge) {
                const count = parseInt(badge.textContent || '0', 10) + 1;
                badge.textContent = String(count);
            }
        }
    );

    // 5. Modal Tambah Master Customer
    setupModal(
        '#modal-add-customer',
        '#btn-open-add-customer',
        '#btn-close-add-customer',
        '#btn-cancel-add-customer',
        '#form-add-customer',
        '#add-customer-error',
        '#btn-submit-add-customer',
        '/api/customers/create',
        (res) => {
            const cust = res.customer;
            const tbody = document.querySelector('#customers-table-body');
            const emptyRow = document.querySelector('#empty-customers-row');
            if (emptyRow) emptyRow.remove();

            const tr = document.createElement('tr');
            tr.id = `cust-row-${cust.id}`;
            tr.setAttribute('data-id', String(cust.id));
            tr.setAttribute('data-code', cust.code);
            tr.setAttribute('data-name', cust.name);
            tr.setAttribute('data-contact', cust.contact_person || '');
            tr.setAttribute('data-phone', cust.phone || '');
            tr.setAttribute('data-email', cust.email || '');
            tr.setAttribute('data-address', cust.address || '');
            tr.innerHTML = `
                <td class="cell-code"><code>${escapeHtml(cust.code)}</code></td>
                <td class="cell-name"><strong>${escapeHtml(cust.name)}</strong></td>
                <td class="cell-contact">${escapeHtml(cust.contact_person || '-')}</td>
                <td class="cell-phone">${escapeHtml(cust.phone || '-')}</td>
                <td class="cell-email">${escapeHtml(cust.email || '-')}</td>
                <td class="cell-address">${escapeHtml(cust.address || '-')}</td>
                <td class="cell-action" style="white-space: nowrap;">
                    <button type="button" class="btn-sm btn-secondary btn-edit-customer" data-id="${cust.id}">✏️ Edit</button>
                </td>
            `;
            tbody?.prepend(tr);

            const badge = document.querySelector('#badge-count-customers');
            if (badge) {
                const count = parseInt(badge.textContent || '0', 10) + 1;
                badge.textContent = String(count);
            }
        }
    );
}

// Inisialisasi Seluruh Modal Edit & Hapus Master Data (Produk, Kategori, Gudang, Supplier, Customer)
function initEditMasterModals() {
    // Helper umum untuk menginisialisasi modal edit & hapus sub-menu
    function setupMasterEdit({
        modalId,
        formId,
        errorBoxId,
        submitBtnId,
        closeBtnId,
        cancelBtnId,
        deleteBtnId,
        confirmBoxId,
        cancelConfirmBtnId,
        confirmDeleteBtnId,
        editBtnClass,
        deleteBtnClass,
        updateEndpoint,
        deleteEndpoint,
        onPopulate,
        getPayload,
        onUpdated,
        onDeleted
    }) {
        const modal = document.querySelector(modalId);
        const form = document.querySelector(formId);
        const errorBox = document.querySelector(errorBoxId);
        const submitBtn = document.querySelector(submitBtnId);
        const closeBtn = document.querySelector(closeBtnId);
        const cancelBtn = document.querySelector(cancelBtnId);
        const deleteBtn = document.querySelector(deleteBtnId);
        const confirmBox = document.querySelector(confirmBoxId);
        const cancelConfirmBtn = document.querySelector(cancelConfirmBtnId);
        const confirmDeleteBtn = document.querySelector(confirmDeleteBtnId);

        if (!modal || !form) return;

        let activeTr = null;

        const closeModal = () => {
            modal.hidden = true;
            document.body.style.overflow = '';
            if (confirmBox) confirmBox.hidden = true;
            form.reset();
            if (errorBox) {
                errorBox.hidden = true;
                errorBox.textContent = '';
            }
            activeTr = null;
        };

        closeBtn?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);
        cancelConfirmBtn?.addEventListener('click', () => {
            if (confirmBox) confirmBox.hidden = true;
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.hidden) closeModal();
        });

        // Toggle konfirmasi hapus di dalam modal
        deleteBtn?.addEventListener('click', () => {
            if (confirmBox) {
                confirmBox.hidden = !confirmBox.hidden;
            }
        });

        // Event delegation untuk tombol Edit di tabel
        document.addEventListener('click', (e) => {
            const editBtn = e.target.closest(`.${editBtnClass}`);
            const deleteBtn = e.target.closest(`.${deleteBtnClass}`);
            if (!editBtn && !deleteBtn) return;

            if (AppState.currentRole !== 'admin') {
                showToast('❌ Akses ditolak: Hanya Admin System yang berhak mengedit atau menghapus data master.');
                return;
            }

            const tr = (editBtn || deleteBtn).closest('tr');
            if (!tr) return;

            activeTr = tr;
            onPopulate(tr);

            if (confirmBox) {
                // Jika user mengklik tombol "🗑️ Hapus" langsung di baris tabel, langsung tampilkan konfirmasi hapus
                confirmBox.hidden = !deleteBtn;
            }
            if (errorBox) {
                errorBox.hidden = true;
                errorBox.textContent = '';
            }

            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            const firstInput = form.querySelector('input:not([type="hidden"]), select, textarea');
            firstInput?.focus();
        });

        // Submit Update Form
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const originalBtnText = submitBtn?.textContent || 'Simpan Perubahan';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Menyimpan...';
            }
            if (errorBox) {
                errorBox.hidden = true;
                errorBox.textContent = '';
            }

            try {
                const payload = getPayload(form);
                const response = await request(updateEndpoint, payload);
                if (activeTr && typeof onUpdated === 'function') {
                    onUpdated(response, activeTr);
                }
                closeModal();
                showToast(`✅ ${response.message || 'Data berhasil diperbarui.'}`);
            } catch (err) {
                if (errorBox) {
                    errorBox.textContent = err.message;
                    errorBox.hidden = false;
                } else {
                    showToast(`❌ ${err.message}`);
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            }
        });

        // Submit Hapus Data
        confirmDeleteBtn?.addEventListener('click', async (e) => {
            e.preventDefault();
            const idInput = form.querySelector('input[name="id"]');
            const idVal = parseInt(idInput?.value || '0', 10);
            if (!idVal) {
                showToast('❌ ID data tidak valid.');
                return;
            }

            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.textContent = 'Menghapus...';
            if (errorBox) {
                errorBox.hidden = true;
                errorBox.textContent = '';
            }

            try {
                const response = await request(deleteEndpoint, { id: idVal });
                if (activeTr && typeof onDeleted === 'function') {
                    onDeleted(idVal, activeTr);
                }
                closeModal();
                showToast(`✅ ${response.message || 'Data berhasil dihapus.'}`);
            } catch (err) {
                if (errorBox) {
                    errorBox.textContent = err.message;
                    errorBox.hidden = false;
                } else {
                    showToast(`❌ ${err.message}`);
                }
            } finally {
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.textContent = 'Ya, Hapus';
            }
        });
    }

    // 1. Setup Edit & Hapus Master Produk
    setupMasterEdit({
        modalId: '#modal-edit-product',
        formId: '#form-edit-product',
        errorBoxId: '#edit-product-error',
        submitBtnId: '#btn-submit-edit-product',
        closeBtnId: '#btn-close-edit-product',
        cancelBtnId: '#btn-cancel-edit-product',
        deleteBtnId: '#btn-delete-product',
        confirmBoxId: '#delete-product-confirm-box',
        cancelConfirmBtnId: '#btn-cancel-delete-product-confirm',
        confirmDeleteBtnId: '#btn-confirm-delete-product-action',
        editBtnClass: 'btn-edit-product',
        deleteBtnClass: 'btn-delete-product',
        updateEndpoint: '/api/products/update',
        deleteEndpoint: '/api/products/delete',
        onPopulate: (tr) => {
            const f = document.querySelector('#form-edit-product');
            if (!f) return;
            f.querySelector('#edit-prod-id').value = tr.getAttribute('data-id') || '';
            f.querySelector('#edit-prod-sku').value = tr.getAttribute('data-sku') || '';
            f.querySelector('#edit-prod-name').value = tr.getAttribute('data-name') || '';
            f.querySelector('#edit-prod-category').value = tr.getAttribute('data-category-id') || '';
            f.querySelector('#edit-prod-unit').value = tr.getAttribute('data-unit') || 'unit';
            f.querySelector('#edit-prod-purchase-price').value = tr.getAttribute('data-purchase-price') || '0';
            f.querySelector('#edit-prod-selling-price').value = tr.getAttribute('data-selling-price') || '0';
            f.querySelector('#edit-prod-min-stock').value = tr.getAttribute('data-min-stock') || '10';
            f.querySelector('#edit-prod-status').value = tr.getAttribute('data-is-active') || '1';
        },
        getPayload: (form) => {
            const fd = new FormData(form);
            return {
                id: parseInt(fd.get('id'), 10),
                sku: fd.get('sku'),
                name: fd.get('name'),
                category_id: parseInt(fd.get('category_id'), 10),
                unit: fd.get('unit'),
                purchase_price: parseFloat(fd.get('purchase_price')),
                selling_price: parseFloat(fd.get('selling_price')),
                min_stock_threshold: parseInt(fd.get('min_stock_threshold'), 10),
                is_active: parseInt(fd.get('is_active'), 10)
            };
        },
        onUpdated: (res, tr) => {
            const p = res.product;
            tr.setAttribute('data-sku', p.sku);
            tr.setAttribute('data-name', p.name);
            tr.setAttribute('data-category-id', String(p.category_id));
            tr.setAttribute('data-category-name', p.category_name);
            tr.setAttribute('data-purchase-price', String(p.purchase_price));
            tr.setAttribute('data-selling-price', String(p.selling_price));
            tr.setAttribute('data-unit', p.unit);
            tr.setAttribute('data-min-stock', String(p.min_stock_threshold));
            tr.setAttribute('data-is-active', String(p.is_active));

            const tStock = parseInt(p.total_stock || 0, 10);
            const minStock = parseInt(p.min_stock_threshold || 10, 10);
            const bClass = tStock <= Math.floor(minStock / 2) ? 'badge-danger' : (tStock <= minStock ? 'badge-warning' : 'badge-success');
            const sText = tStock <= Math.floor(minStock / 2) ? 'Kritis' : (tStock <= minStock ? 'Reorder' : 'Aman');
            const formattedPrice = new Intl.NumberFormat('id-ID').format(p.selling_price || 0);

            const skuCell = tr.querySelector('.cell-sku');
            const nameCell = tr.querySelector('.cell-name');
            const catCell = tr.querySelector('.cell-category');
            const priceCell = tr.querySelector('.cell-selling-price');
            const minCell = tr.querySelector('.cell-min-stock');
            const stockCell = tr.querySelector('.cell-total-stock');

            if (skuCell) skuCell.innerHTML = `<code>${escapeHtml(p.sku)}</code>`;
            if (nameCell) nameCell.innerHTML = `<strong>${escapeHtml(p.name)}</strong>`;
            if (catCell) catCell.textContent = p.category_name;
            if (priceCell) priceCell.textContent = `Rp ${formattedPrice}`;
            if (minCell) minCell.textContent = `${minStock} ${p.unit}`;
            if (stockCell) stockCell.innerHTML = `${tStock} ${p.unit} (<span class="badge ${bClass}">${sText}</span>)`;
        },
        onDeleted: (id, tr) => {
            tr.remove();
            const badge = document.querySelector('#badge-count-products');
            if (badge) {
                const count = Math.max(0, parseInt(badge.textContent || '1', 10) - 1);
                badge.textContent = String(count);
            }
        }
    });

    // 2. Setup Edit & Hapus Kategori
    setupMasterEdit({
        modalId: '#modal-edit-category',
        formId: '#form-edit-category',
        errorBoxId: '#edit-category-error',
        submitBtnId: '#btn-submit-edit-category',
        closeBtnId: '#btn-close-edit-category',
        cancelBtnId: '#btn-cancel-edit-category',
        deleteBtnId: '#btn-delete-category',
        confirmBoxId: '#delete-category-confirm-box',
        cancelConfirmBtnId: '#btn-cancel-delete-category-confirm',
        confirmDeleteBtnId: '#btn-confirm-delete-category-action',
        editBtnClass: 'btn-edit-category',
        deleteBtnClass: 'btn-delete-category',
        updateEndpoint: '/api/categories/update',
        deleteEndpoint: '/api/categories/delete',
        onPopulate: (tr) => {
            const f = document.querySelector('#form-edit-category');
            if (!f) return;
            f.querySelector('#edit-cat-id').value = tr.getAttribute('data-id') || '';
            f.querySelector('#edit-cat-code').value = tr.getAttribute('data-code') || '';
            f.querySelector('#edit-cat-name').value = tr.getAttribute('data-name') || '';
            f.querySelector('#edit-cat-desc').value = tr.getAttribute('data-description') || '';
        },
        getPayload: (form) => {
            const fd = new FormData(form);
            return {
                id: parseInt(fd.get('id'), 10),
                code: fd.get('code'),
                name: fd.get('name'),
                description: fd.get('description')
            };
        },
        onUpdated: (res, tr) => {
            const c = res.category;
            tr.setAttribute('data-code', c.code);
            tr.setAttribute('data-name', c.name);
            tr.setAttribute('data-description', c.description || '');

            const codeCell = tr.querySelector('.cell-code');
            const nameCell = tr.querySelector('.cell-name');
            const descCell = tr.querySelector('.cell-description');

            if (codeCell) codeCell.innerHTML = `<code>${escapeHtml(c.code)}</code>`;
            if (nameCell) nameCell.innerHTML = `<strong>${escapeHtml(c.name)}</strong>`;
            if (descCell) descCell.textContent = c.description || '-';

            // Perbarui option di dropdown kategori modal produk
            ['#new-prod-category', '#edit-prod-category'].forEach(selId => {
                const opt = document.querySelector(`${selId} option[value="${c.id}"]`);
                if (opt) {
                    opt.textContent = `${c.name} (${c.code})`;
                }
            });
        },
        onDeleted: (id, tr) => {
            tr.remove();
            ['#new-prod-category', '#edit-prod-category'].forEach(selId => {
                const opt = document.querySelector(`${selId} option[value="${id}"]`);
                if (opt) opt.remove();
            });
            const badge = document.querySelector('#badge-count-categories');
            if (badge) {
                const count = Math.max(0, parseInt(badge.textContent || '1', 10) - 1);
                badge.textContent = String(count);
            }
        }
    });

    // 3. Setup Edit & Hapus Lokasi Gudang
    setupMasterEdit({
        modalId: '#modal-edit-warehouse',
        formId: '#form-edit-warehouse',
        errorBoxId: '#edit-warehouse-error',
        submitBtnId: '#btn-submit-edit-warehouse',
        closeBtnId: '#btn-close-edit-warehouse',
        cancelBtnId: '#btn-cancel-edit-warehouse',
        deleteBtnId: '#btn-delete-warehouse',
        confirmBoxId: '#delete-warehouse-confirm-box',
        cancelConfirmBtnId: '#btn-cancel-delete-warehouse-confirm',
        confirmDeleteBtnId: '#btn-confirm-delete-warehouse-action',
        editBtnClass: 'btn-edit-warehouse',
        deleteBtnClass: 'btn-delete-warehouse',
        updateEndpoint: '/api/warehouses/update',
        deleteEndpoint: '/api/warehouses/delete',
        onPopulate: (tr) => {
            const f = document.querySelector('#form-edit-warehouse');
            if (!f) return;
            f.querySelector('#edit-wh-id').value = tr.getAttribute('data-id') || '';
            f.querySelector('#edit-wh-code').value = tr.getAttribute('data-code') || '';
            f.querySelector('#edit-wh-name').value = tr.getAttribute('data-name') || '';
            f.querySelector('#edit-wh-city').value = tr.getAttribute('data-city') || '';
            f.querySelector('#edit-wh-address').value = tr.getAttribute('data-address') || '';
            f.querySelector('#edit-wh-status').value = tr.getAttribute('data-is-active') || '1';
        },
        getPayload: (form) => {
            const fd = new FormData(form);
            return {
                id: parseInt(fd.get('id'), 10),
                code: fd.get('code'),
                name: fd.get('name'),
                city: fd.get('city'),
                address: fd.get('address'),
                is_active: parseInt(fd.get('is_active'), 10)
            };
        },
        onUpdated: (res, tr) => {
            const wh = res.warehouse;
            tr.setAttribute('data-code', wh.code);
            tr.setAttribute('data-name', wh.name);
            tr.setAttribute('data-city', wh.city);
            tr.setAttribute('data-address', wh.address || '');
            tr.setAttribute('data-is-active', String(wh.is_active));

            const codeCell = tr.querySelector('.cell-code');
            const nameCell = tr.querySelector('.cell-name');
            const cityCell = tr.querySelector('.cell-city');
            const addrCell = tr.querySelector('.cell-address');
            const statusCell = tr.querySelector('.cell-status');

            if (codeCell) codeCell.innerHTML = `<code>${escapeHtml(wh.code)}</code>`;
            if (nameCell) nameCell.innerHTML = `<strong>${escapeHtml(wh.name)}</strong>`;
            if (cityCell) cityCell.textContent = wh.city;
            if (addrCell) addrCell.textContent = wh.address || '-';
            if (statusCell) {
                const isActive = wh.is_active === 1;
                statusCell.innerHTML = `<span class="badge ${isActive ? 'badge-success' : 'badge-danger'}">${isActive ? 'Aktif Beroperasi' : 'Nonaktif'}</span>`;
            }
        },
        onDeleted: (id, tr) => {
            tr.remove();
            const badge = document.querySelector('#badge-count-warehouses');
            if (badge) {
                const count = Math.max(0, parseInt(badge.textContent || '1', 10) - 1);
                badge.textContent = String(count);
            }
        }
    });

    // 4. Setup Edit & Hapus Supplier
    setupMasterEdit({
        modalId: '#modal-edit-supplier',
        formId: '#form-edit-supplier',
        errorBoxId: '#edit-supplier-error',
        submitBtnId: '#btn-submit-edit-supplier',
        closeBtnId: '#btn-close-edit-supplier',
        cancelBtnId: '#btn-cancel-edit-supplier',
        deleteBtnId: '#btn-delete-supplier',
        confirmBoxId: '#delete-supplier-confirm-box',
        cancelConfirmBtnId: '#btn-cancel-delete-supplier-confirm',
        confirmDeleteBtnId: '#btn-confirm-delete-supplier-action',
        editBtnClass: 'btn-edit-supplier',
        deleteBtnClass: 'btn-delete-supplier',
        updateEndpoint: '/api/suppliers/update',
        deleteEndpoint: '/api/suppliers/delete',
        onPopulate: (tr) => {
            const f = document.querySelector('#form-edit-supplier');
            if (!f) return;
            f.querySelector('#edit-sup-id').value = tr.getAttribute('data-id') || '';
            f.querySelector('#edit-sup-code').value = tr.getAttribute('data-code') || '';
            f.querySelector('#edit-sup-name').value = tr.getAttribute('data-name') || '';
            f.querySelector('#edit-sup-contact').value = tr.getAttribute('data-contact') || '';
            f.querySelector('#edit-sup-phone').value = tr.getAttribute('data-phone') || '';
            f.querySelector('#edit-sup-email').value = tr.getAttribute('data-email') || '';
            f.querySelector('#edit-sup-address').value = tr.getAttribute('data-address') || '';
        },
        getPayload: (form) => {
            const fd = new FormData(form);
            return {
                id: parseInt(fd.get('id'), 10),
                code: fd.get('code'),
                name: fd.get('name'),
                contact_person: fd.get('contact_person'),
                phone: fd.get('phone'),
                email: fd.get('email'),
                address: fd.get('address')
            };
        },
        onUpdated: (res, tr) => {
            const sup = res.supplier;
            tr.setAttribute('data-code', sup.code);
            tr.setAttribute('data-name', sup.name);
            tr.setAttribute('data-contact', sup.contact_person || '');
            tr.setAttribute('data-phone', sup.phone || '');
            tr.setAttribute('data-email', sup.email || '');
            tr.setAttribute('data-address', sup.address || '');

            const codeCell = tr.querySelector('.cell-code');
            const nameCell = tr.querySelector('.cell-name');
            const contactCell = tr.querySelector('.cell-contact');
            const phoneCell = tr.querySelector('.cell-phone');
            const emailCell = tr.querySelector('.cell-email');
            const addrCell = tr.querySelector('.cell-address');

            if (codeCell) codeCell.innerHTML = `<code>${escapeHtml(sup.code)}</code>`;
            if (nameCell) nameCell.innerHTML = `<strong>${escapeHtml(sup.name)}</strong>`;
            if (contactCell) contactCell.textContent = sup.contact_person || '-';
            if (phoneCell) phoneCell.textContent = sup.phone || '-';
            if (emailCell) emailCell.textContent = sup.email || '-';
            if (addrCell) addrCell.textContent = sup.address || '-';
        },
        onDeleted: (id, tr) => {
            tr.remove();
            const badge = document.querySelector('#badge-count-suppliers');
            if (badge) {
                const count = Math.max(0, parseInt(badge.textContent || '1', 10) - 1);
                badge.textContent = String(count);
            }
        }
    });

    // 5. Setup Edit & Hapus Customer
    setupMasterEdit({
        modalId: '#modal-edit-customer',
        formId: '#form-edit-customer',
        errorBoxId: '#edit-customer-error',
        submitBtnId: '#btn-submit-edit-customer',
        closeBtnId: '#btn-close-edit-customer',
        cancelBtnId: '#btn-cancel-edit-customer',
        deleteBtnId: '#btn-delete-customer',
        confirmBoxId: '#delete-customer-confirm-box',
        cancelConfirmBtnId: '#btn-cancel-delete-customer-confirm',
        confirmDeleteBtnId: '#btn-confirm-delete-customer-action',
        editBtnClass: 'btn-edit-customer',
        deleteBtnClass: 'btn-delete-customer',
        updateEndpoint: '/api/customers/update',
        deleteEndpoint: '/api/customers/delete',
        onPopulate: (tr) => {
            const f = document.querySelector('#form-edit-customer');
            if (!f) return;
            f.querySelector('#edit-cust-id').value = tr.getAttribute('data-id') || '';
            f.querySelector('#edit-cust-code').value = tr.getAttribute('data-code') || '';
            f.querySelector('#edit-cust-name').value = tr.getAttribute('data-name') || '';
            f.querySelector('#edit-cust-contact').value = tr.getAttribute('data-contact') || '';
            f.querySelector('#edit-cust-phone').value = tr.getAttribute('data-phone') || '';
            f.querySelector('#edit-cust-email').value = tr.getAttribute('data-email') || '';
            f.querySelector('#edit-cust-address').value = tr.getAttribute('data-address') || '';
        },
        getPayload: (form) => {
            const fd = new FormData(form);
            return {
                id: parseInt(fd.get('id'), 10),
                code: fd.get('code'),
                name: fd.get('name'),
                contact_person: fd.get('contact_person'),
                phone: fd.get('phone'),
                email: fd.get('email'),
                address: fd.get('address')
            };
        },
        onUpdated: (res, tr) => {
            const cust = res.customer;
            tr.setAttribute('data-code', cust.code);
            tr.setAttribute('data-name', cust.name);
            tr.setAttribute('data-contact', cust.contact_person || '');
            tr.setAttribute('data-phone', cust.phone || '');
            tr.setAttribute('data-email', cust.email || '');
            tr.setAttribute('data-address', cust.address || '');

            const codeCell = tr.querySelector('.cell-code');
            const nameCell = tr.querySelector('.cell-name');
            const contactCell = tr.querySelector('.cell-contact');
            const phoneCell = tr.querySelector('.cell-phone');
            const emailCell = tr.querySelector('.cell-email');
            const addrCell = tr.querySelector('.cell-address');

            if (codeCell) codeCell.innerHTML = `<code>${escapeHtml(cust.code)}</code>`;
            if (nameCell) nameCell.innerHTML = `<strong>${escapeHtml(cust.name)}</strong>`;
            if (contactCell) contactCell.textContent = cust.contact_person || '-';
            if (phoneCell) phoneCell.textContent = cust.phone || '-';
            if (emailCell) emailCell.textContent = cust.email || '-';
            if (addrCell) addrCell.textContent = cust.address || '-';
        },
        onDeleted: (id, tr) => {
            tr.remove();
            const badge = document.querySelector('#badge-count-customers');
            if (badge) {
                const count = Math.max(0, parseInt(badge.textContent || '1', 10) - 1);
                badge.textContent = String(count);
            }
        }
    });
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Inisialisasi Modal Buat Sales Order Baru & Detail Sales Order
function initSalesOrderModals() {
    const modalAdd = document.querySelector('#modal-add-so');
    const btnOpenAdd = document.querySelector('#btn-create-so');
    const btnCloseAdd = document.querySelector('#btn-close-add-so');
    const btnCancelAdd = document.querySelector('#btn-cancel-add-so');
    const formAdd = document.querySelector('#form-add-so');
    const errorAdd = document.querySelector('#add-so-error');
    const btnAddRow = document.querySelector('#btn-add-so-item-row');
    const tbodyItems = document.querySelector('#so-items-tbody');
    const grandTotalText = document.querySelector('#so-grand-total-text');
    const btnSaveDraft = document.querySelector('#btn-save-draft-so');

    const modalDetail = document.querySelector('#modal-detail-so');
    const btnCloseDetail = document.querySelector('#btn-close-detail-so');
    const btnCloseDetailAction = document.querySelector('#btn-close-detail-so-action');

    let catalogProducts = [];
    try {
        const catalogEl = document.querySelector('#products-catalog-data');
        if (catalogEl && catalogEl.textContent) {
            catalogProducts = JSON.parse(catalogEl.textContent);
        }
    } catch (e) {
        console.error('Gagal membaca data katalog produk:', e);
    }

    const updateGrandTotal = () => {
        let total = 0;
        tbodyItems?.querySelectorAll('tr').forEach(tr => {
            const subtotal = parseFloat(tr.dataset.subtotal || '0');
            total += subtotal;
        });
        if (grandTotalText) {
            grandTotalText.textContent = 'Rp ' + total.toLocaleString('id-ID');
        }
    };

    const addItemRow = (productId = '', qty = 1) => {
        if (!tbodyItems) return;
        const tr = document.createElement('tr');
        tr.dataset.subtotal = '0';

        let optionsHtml = '<option value="">-- Pilih Produk --</option>';
        catalogProducts.forEach(p => {
            const selected = String(p.id) === String(productId) ? 'selected' : '';
            optionsHtml += `<option value="${p.id}" data-price="${p.price}" data-stock="${p.stock}" ${selected}>${escapeHtml(p.name)} (${escapeHtml(p.sku)}) [Stok: ${p.stock}]</option>`;
        });

        tr.innerHTML = `
            <td>
                <select class="input so-item-product" style="width:100%; padding: 8px 12px; font-size:13.5px; border-radius: var(--radius-sm); background:#fff;" required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="number" class="input so-item-qty" min="1" value="${qty}" style="width:100%; padding: 8px 10px; font-size:13.5px; border-radius: var(--radius-sm); text-align:right;" required>
            </td>
            <td>
                <input type="number" class="input so-item-price" min="0" step="500" value="0" style="width:100%; padding: 8px 10px; font-size:13.5px; border-radius: var(--radius-sm); text-align:right;" required>
            </td>
            <td style="text-align:right; font-weight:700; font-size:14px; color:var(--text-primary); padding-right:12px;" class="so-item-subtotal-cell">
                Rp 0
            </td>
            <td style="text-align:center;">
                <button type="button" class="btn-sm btn-danger btn-remove-row" style="padding: 4px 10px; font-size: 14px; border-radius:4px;" title="Hapus Baris">&times;</button>
            </td>
        `;

        const prodSelect = tr.querySelector('.so-item-product');
        const qtyInput = tr.querySelector('.so-item-qty');
        const priceInput = tr.querySelector('.so-item-price');
        const subtotalCell = tr.querySelector('.so-item-subtotal-cell');
        const btnRemove = tr.querySelector('.btn-remove-row');

        const calcRow = () => {
            const q = Math.max(1, parseInt(qtyInput.value || '1', 10));
            const p = Math.max(0, parseFloat(priceInput.value || '0'));
            const sub = q * p;
            tr.dataset.subtotal = String(sub);
            if (subtotalCell) subtotalCell.textContent = 'Rp ' + sub.toLocaleString('id-ID');
            updateGrandTotal();
        };

        prodSelect.addEventListener('change', () => {
            const selectedOpt = prodSelect.options[prodSelect.selectedIndex];
            const defPrice = parseFloat(selectedOpt?.dataset.price || '0');
            priceInput.value = defPrice;
            calcRow();
        });

        qtyInput.addEventListener('input', calcRow);
        priceInput.addEventListener('input', calcRow);

        btnRemove.addEventListener('click', () => {
            if (tbodyItems.querySelectorAll('tr').length <= 1) {
                showToast('⚠️ Pesanan wajib memiliki minimal 1 item produk.');
                return;
            }
            tr.remove();
            updateGrandTotal();
        });

        tbodyItems.appendChild(tr);
        if (productId) {
            prodSelect.dispatchEvent(new Event('change'));
        }
    };

    btnAddRow?.addEventListener('click', () => addItemRow());

    btnOpenAdd?.addEventListener('click', () => {
        if (AppState.currentRole === 'warehouse') {
            showToast('❌ Akses Ditolak: Staff Gudang tidak memiliki hak membuat Sales Order.');
            return;
        }
        formAdd?.reset();
        if (errorAdd) errorAdd.hidden = true;
        if (tbodyItems) tbodyItems.innerHTML = '';
        addItemRow();
        if (modalAdd) modalAdd.hidden = false;
        document.body.style.overflow = 'hidden';
    });

    const closeAddModal = () => {
        if (modalAdd) modalAdd.hidden = true;
        document.body.style.overflow = '';
    };

    btnCloseAdd?.addEventListener('click', closeAddModal);
    btnCancelAdd?.addEventListener('click', closeAddModal);

    modalAdd?.addEventListener('click', (e) => {
        if (e.target === modalAdd) closeAddModal();
    });

    let isSubmittingImmediately = true;
    btnSaveDraft?.addEventListener('click', () => {
        isSubmittingImmediately = false;
        formAdd?.requestSubmit();
    });

    formAdd?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (errorAdd) errorAdd.hidden = true;

        const custId = parseInt(document.querySelector('#new-so-customer')?.value || '0', 10);
        const whId = parseInt(document.querySelector('#new-so-warehouse')?.value || '0', 10);
        const notes = document.querySelector('#new-so-notes')?.value || '';

        if (!custId) {
            if (errorAdd) { errorAdd.textContent = 'Pilih Customer terlebih dahulu.'; errorAdd.hidden = false; }
            return;
        }
        if (!whId) {
            if (errorAdd) { errorAdd.textContent = 'Pilih Gudang Pengiriman terlebih dahulu.'; errorAdd.hidden = false; }
            return;
        }

        const items = [];
        let itemError = null;
        tbodyItems?.querySelectorAll('tr').forEach(tr => {
            const prodId = parseInt(tr.querySelector('.so-item-product')?.value || '0', 10);
            const qty = parseInt(tr.querySelector('.so-item-qty')?.value || '0', 10);
            const unitPrice = parseFloat(tr.querySelector('.so-item-price')?.value || '0');

            if (!prodId) {
                itemError = 'Pilih produk pada setiap baris item.';
            } else if (qty <= 0) {
                itemError = 'Kuantitas produk harus lebih dari 0.';
            }
            items.push({ product_id: prodId, quantity: qty, unit_price: unitPrice });
        });

        if (itemError) {
            if (errorAdd) { errorAdd.textContent = itemError; errorAdd.hidden = false; }
            return;
        }
        if (items.length === 0) {
            if (errorAdd) { errorAdd.textContent = 'Tambahkan minimal 1 item produk.'; errorAdd.hidden = false; }
            return;
        }

        const submitBtn = isSubmittingImmediately ? document.querySelector('#btn-submit-approval-so') : btnSaveDraft;
        const originalText = submitBtn ? submitBtn.textContent : '';
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Menyimpan...'; }

        try {
            const res = await request('/api/orders/sales/create', {
                customer_id: custId,
                warehouse_id: whId,
                notes: notes,
                items: items,
                submit_immediately: isSubmittingImmediately
            });

            closeAddModal();
            showToast(isSubmittingImmediately 
                ? `✅ Sales Order #${res.order_id} berhasil dibuat dan diajukan ke Admin!` 
                : `✅ Sales Order #${res.order_id} berhasil disimpan sebagai Draft!`
            );
            setTimeout(() => window.location.reload(), 600);
        } catch (err) {
            if (errorAdd) {
                errorAdd.textContent = err.message;
                errorAdd.hidden = false;
            }
        } finally {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = originalText; }
            isSubmittingImmediately = true;
        }
    });

    const closeDetailModal = () => {
        if (modalDetail) modalDetail.hidden = true;
        document.body.style.overflow = '';
    };
    btnCloseDetail?.addEventListener('click', closeDetailModal);
    btnCloseDetailAction?.addEventListener('click', closeDetailModal);
    modalDetail?.addEventListener('click', (e) => {
        if (e.target === modalDetail) closeDetailModal();
    });
}

// Inisialisasi Buka/Tutup (Toggle Collapse) Sidebar Menu Dinamis
function initSidebarToggle() {
    const toggleBtn = document.querySelector('#sidebar-toggle-btn');
    const workspace = document.querySelector('.app-workspace');
    if (!toggleBtn || !workspace) return;

    // Baca status tersimpan di localStorage
    const savedState = localStorage.getItem('stokora_sidebar_collapsed');
    if (savedState === 'true') {
        workspace.classList.add('sidebar-collapsed');
    }

    toggleBtn.addEventListener('click', () => {
        workspace.classList.toggle('sidebar-collapsed');
        const isCollapsed = workspace.classList.contains('sidebar-collapsed');
        localStorage.setItem('stokora_sidebar_collapsed', isCollapsed ? 'true' : 'false');
        showToast(isCollapsed ? '◀ Sidebar ditutup (Konten otomatis melebar)' : '▶ Sidebar dibuka');
    });
}

// Inisialisasi Switcher Peran
function initRoleSwitcher() {
    const buttons = document.querySelectorAll('.role-btn');
    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const role = btn.getAttribute('data-role');
            if (role === AppState.currentRole) return;
            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            AppState.currentRole = role;
            updateRoleUI();
            showToast(`Peran berpindah ke: ${AppState.rolesInfo[role].title}`);
        });
    });
}

// Inisialisasi Navigasi Tab
function initTabNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach((item) => {
        item.addEventListener('click', () => {
            if (item.classList.contains('disabled')) {
                showToast('❌ Akses ditolak: Menu ini hanya untuk peran Admin System.');
                return;
            }
            const targetTab = item.getAttribute('data-tab');
            navItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
            document.querySelector(`#${targetTab}`)?.classList.add('active');
        });
    });
}

// Update Elemen UI Berdasarkan Peran Aktif
function updateRoleUI() {
    const role = AppState.currentRole;
    const roleMeta = AppState.rolesInfo[role];

    // Update Topbar Profile
    const roleBadge = document.querySelector('#current-role-badge');
    const avatar = document.querySelector('#user-avatar-initial');
    if (roleBadge) {
        roleBadge.textContent = roleMeta.title;
        roleBadge.className = `user-role-label ${roleMeta.class}`;
    }
    if (avatar) avatar.textContent = roleMeta.initial;

    // Filter Menu Navigasi Berdasarkan Peran (Admin-only menus)
    const restrictedNavs = document.querySelectorAll('.nav-item.role-restricted');
    restrictedNavs.forEach((nav) => {
        const allowedRole = nav.getAttribute('data-allowed');
        if (role !== allowedRole) {
            nav.classList.add('disabled');
        } else {
            nav.classList.remove('disabled');
        }
    });

    // 1. Sidebar Tab Purchase Orders: Disembunyikan sepenuhnya untuk peran Sales
    const navPo = document.querySelector('#nav-po');
    if (navPo) {
        if (role === 'sales') {
            navPo.style.display = 'none';
        } else {
            navPo.style.display = '';
        }
    }

    // 2. Dynamic Master Data Nav (Icon & Label)
    const navMasterText = document.querySelector('#nav-master-text');
    const navMasterIcon = document.querySelector('#nav-master-icon');
    if (navMasterText && navMasterIcon) {
        if (role === 'admin') {
            navMasterText.textContent = 'Master Data & Gudang';
            navMasterIcon.textContent = '🗄️';
        } else if (role === 'sales') {
            navMasterText.textContent = 'Katalog Produk';
            navMasterIcon.textContent = '📖';
        } else if (role === 'warehouse') {
            navMasterText.textContent = 'Produk & Stok';
            navMasterIcon.textContent = '🏬';
        }
    }

    // 3. Switch Ringkasan Dashboard View (Admin / Sales / Warehouse)
    const dashViews = {
        admin: document.querySelector('#dash-view-admin'),
        sales: document.querySelector('#dash-view-sales'),
        warehouse: document.querySelector('#dash-view-warehouse')
    };
    Object.keys(dashViews).forEach(key => {
        if (dashViews[key]) {
            dashViews[key].hidden = (key !== role);
        }
    });

    // Header Title & Subtitle Dashboard
    const dashTitle = document.querySelector('#dash-title');
    const dashSubtitle = document.querySelector('#dash-subtitle');
    if (dashTitle && dashSubtitle) {
        if (role === 'admin') {
            dashTitle.textContent = 'Ringkasan Dashboard — Admin';
            dashSubtitle.textContent = 'Gambaran umum seluruh data produk, stok, dan status pesanan berjalan.';
        } else if (role === 'sales') {
            dashTitle.textContent = 'Ringkasan Dashboard — Sales Staff';
            dashSubtitle.textContent = 'Monitoring pesanan penjualan milik Anda dan status pemenuhan ke customer.';
        } else if (role === 'warehouse') {
            dashTitle.textContent = 'Ringkasan Dashboard — Staff Gudang';
            dashSubtitle.textContent = 'Fokus antrean Goods Issue (pengiriman) dan Goods Receipt (penerimaan barang).';
        }
    }

    // 4. Switch Master Data View
    const masterViews = {
        admin: document.querySelector('#master-view-admin'),
        sales: document.querySelector('#master-view-sales'),
        warehouse: document.querySelector('#master-view-warehouse')
    };
    Object.keys(masterViews).forEach(key => {
        if (masterViews[key]) {
            masterViews[key].hidden = (key !== role);
        }
    });

    // 5. Tombol Buat Sales Order (Hanya Admin & Sales, Warehouse diblokir)
    const btnCreateSo = document.querySelector('#btn-create-so');
    if (btnCreateSo) {
        if (role === 'warehouse') {
            btnCreateSo.style.display = 'none';
        } else {
            btnCreateSo.style.display = '';
        }
    }

    // 6. Tombol Buat / Usulkan Purchase Order
    const btnCreatePo = document.querySelector('#btn-create-po');
    if (btnCreatePo) {
        if (role === 'sales') {
            btnCreatePo.style.display = 'none';
        } else if (role === 'warehouse') {
            btnCreatePo.style.display = '';
            btnCreatePo.textContent = '📋 Usulkan Purchase Order (PO)';
        } else {
            btnCreatePo.style.display = '';
            btnCreatePo.textContent = '🚛 Buat Purchase Order (PO) Baru';
        }
    }

    // Jika tab aktif dilarang atau tersembunyi untuk peran ini, kembalikan ke Ringkasan Dashboard
    const activeTab = document.querySelector('.nav-item.active');
    if (activeTab) {
        const isRestricted = activeTab.classList.contains('disabled');
        const isHiddenPo = (role === 'sales' && activeTab.getAttribute('data-tab') === 'tab-purchase-orders');
        if (isRestricted || isHiddenPo) {
            document.querySelector('.nav-item[data-tab="tab-dashboard"]')?.click();
        }
    }

    // Update Tombol Aksi Pada Tabel Sales Order & Purchase Order Sesuai SOD (Segregation of Duties)
    renderSalesOrderActions();
    renderPurchaseOrderActions();
}

// Render Tombol Aksi Tabel Sales Order Sesuai Peran
function renderSalesOrderActions() {
    const role = AppState.currentRole;
    document.querySelectorAll('#so-table-body tr[data-so-id]').forEach(tr => {
        const id = tr.getAttribute('data-so-id');
        const status = tr.getAttribute('data-so-status');
        const act = tr.querySelector('.action-cell') || tr.querySelector(`#so-action-${id}`);
        if (!act) return;

        let buttons = `<button type="button" class="btn-sm btn-secondary" data-action="view-so-detail" data-so-id="${id}" onclick="SimulasiModule.viewSODetail('${id}')" title="Lihat Rincian Pesanan">👁️ Detail</button> `;

        if (status === 'draft') {
            if (role === 'admin' || role === 'sales') {
                buttons += `
                    <button type="button" class="btn-sm btn-primary" data-action="submit-so" data-so-id="${id}" onclick="SimulasiModule.submitSO('${id}')" title="Ajukan ke Admin untuk ditinjau">🚀 Ajukan</button>
                    <button type="button" class="btn-sm btn-danger" data-action="cancel-so" data-so-id="${id}" onclick="SimulasiModule.cancelSO('${id}')" title="Batalkan order draft">❌ Batal</button>
                `;
            } else {
                buttons += `<span class="text-muted"><small>Draft Order</small></span>`;
            }
        } else if (status === 'pending_approval') {
            if (role === 'admin') {
                buttons += `
                    <button type="button" class="btn-sm btn-success" data-action="approve-so" data-so-id="${id}" onclick="SimulasiModule.approveSO('${id}')" title="Setujui Sales Order">✅ Setujui</button>
                    <button type="button" class="btn-sm btn-danger" data-action="reject-so" data-so-id="${id}" onclick="SimulasiModule.rejectSO('${id}')" title="Tolak Sales Order">❌ Tolak</button>
                `;
            } else if (role === 'sales') {
                buttons += `
                    <span class="text-amber"><small>🔒 Menunggu Admin</small></span>
                    <button type="button" class="btn-sm btn-danger" data-action="cancel-so" data-so-id="${id}" onclick="SimulasiModule.cancelSO('${id}')" title="Batalkan pengajuan">❌ Batal</button>
                `;
            } else if (role === 'warehouse') {
                buttons += `<span class="text-muted"><small>🔒 Menunggu Approval Admin</small></span>`;
            }
        } else if (status === 'approved') {
            if (role === 'warehouse' || role === 'admin') {
                buttons += `
                    <button type="button" class="btn-sm btn-primary" data-action="process-gi" data-so-id="${id}" onclick="SimulasiModule.processGoodsIssue('${id}')" title="Keluarkan barang fisik & potong stok">📦 Goods Issue</button>
                `;
            } else {
                buttons += `<span class="text-blue"><small>Disetujui. Siap kirim.</small></span>`;
            }
        } else if (status === 'fulfilled') {
            buttons += `<span class="text-success"><small>✅ Terpenuhi</small></span>`;
        } else if (status === 'cancelled' || status === 'rejected') {
            buttons += `<span class="text-danger"><small>❌ Batal/Ditolak</small></span>`;
        } else {
            buttons += `<span class="text-muted"><small>${escapeHtml(status)}</small></span>`;
        }

        act.innerHTML = buttons;
    });
}

// Render Tombol Aksi Tabel Purchase Order Sesuai Peran
function renderPurchaseOrderActions() {
    const role = AppState.currentRole;
    document.querySelectorAll('#po-table-body tr[data-po-id]').forEach(tr => {
        const id = tr.getAttribute('data-po-id');
        const status = tr.getAttribute('data-po-status');
        const act = tr.querySelector(`#po-action-${id}`) || tr.querySelector('td:last-child');
        if (!act) return;

        if (status === 'sent_to_supplier' || status === 'proposed') {
            if (role === 'warehouse' || role === 'admin') {
                act.innerHTML = `
                    <button class="btn-sm btn-success" data-action="process-gr" data-po-id="${id}" onclick="SimulasiModule.processGoodsReceipt('${id}')">📥 Catat Goods Receipt (Tambah Stok)</button>
                `;
            } else {
                act.innerHTML = `<span class="text-muted"><small>Hanya Staff Gudang yang dapat mencatat barang masuk.</small></span>`;
            }
        } else if (status === 'goods_received') {
            act.innerHTML = `<span class="text-muted"><small>Stok bertambah di Ledger</small></span>`;
        }
    });
}

// Delegated Click Handler untuk semua tindakan peran (Jaminan jalan di semua browser & CSP)
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const action = btn.getAttribute('data-action');
    const soId = btn.getAttribute('data-so-id');
    const poId = btn.getAttribute('data-po-id');

    if (!window.SimulasiModule) return;

    if (action === 'view-so-detail' && soId) {
        e.preventDefault();
        window.SimulasiModule.viewSODetail(soId);
    } else if (action === 'approve-so' && soId) {
        e.preventDefault();
        window.SimulasiModule.approveSO(soId);
    } else if (action === 'reject-so' && soId) {
        e.preventDefault();
        window.SimulasiModule.rejectSO(soId);
    } else if (action === 'submit-so' && soId) {
        e.preventDefault();
        window.SimulasiModule.submitSO(soId);
    } else if (action === 'cancel-so' && soId) {
        e.preventDefault();
        window.SimulasiModule.cancelSO(soId);
    } else if (action === 'process-gi' && soId) {
        e.preventDefault();
        window.SimulasiModule.processGoodsIssue(soId);
    } else if (action === 'process-gr' && poId) {
        e.preventDefault();
        window.SimulasiModule.processGoodsReceipt(poId);
    } else if (action === 'create-po') {
        e.preventDefault();
        window.SimulasiModule.createPurchaseOrder();
    } else if (action === 'create-so') {
        e.preventDefault();
        window.SimulasiModule.createSalesOrder();
    }
});

// Modul Operasi Simulasi Interaktif
window.SimulasiModule = {
    // 1. Sales Order Workflow
    viewSODetail: async (id) => {
        const modal = document.querySelector('#modal-detail-so');
        const loading = document.querySelector('#detail-so-loading');
        const content = document.querySelector('#detail-so-content');
        if (!modal) return;

        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        if (loading) loading.hidden = false;
        if (content) content.hidden = true;

        try {
            const res = await request('/api/orders/sales/detail', { order_id: parseInt(id, 10) });
            const so = res.data.order;
            const items = res.data.items || [];

            document.querySelector('#detail-so-title').textContent = `📦 Detail Sales Order #${so.so_number || id}`;
            const badge = document.querySelector('#detail-so-badge');
            if (badge) {
                badge.textContent = so.status;
                const badgeMap = {
                    'draft': 'badge-secondary',
                    'pending_approval': 'badge-warning',
                    'approved': 'badge-blue',
                    'rejected': 'badge-danger',
                    'fulfilled': 'badge-success',
                    'cancelled': 'badge-secondary'
                };
                badge.className = 'badge ' + (badgeMap[so.status] || 'badge-secondary');
            }

            document.querySelector('#detail-so-number').textContent = '#' + (so.so_number || id);
            document.querySelector('#detail-so-customer').textContent = so.customer_name || '-';
            document.querySelector('#detail-so-warehouse').textContent = so.warehouse_name || '-';
            document.querySelector('#detail-so-creator').textContent = so.creator_name || '-';
            document.querySelector('#detail-so-approver').textContent = so.approver_name || '- (Belum ada approval)';
            document.querySelector('#detail-so-date').textContent = so.created_at || '-';

            const notesBox = document.querySelector('#detail-so-notes-container');
            const notesText = document.querySelector('#detail-so-notes');
            if (so.notes && so.notes.trim()) {
                if (notesBox) notesBox.hidden = false;
                if (notesText) notesText.textContent = so.notes;
            } else {
                if (notesBox) notesBox.hidden = true;
            }

            const itemsTbody = document.querySelector('#detail-so-items-body');
            if (itemsTbody) {
                itemsTbody.innerHTML = '';
                let total = 0;
                items.forEach(it => {
                    const row = document.createElement('tr');
                    const qty = parseInt(it.quantity, 10);
                    const price = parseFloat(it.unit_price);
                    const subtotal = parseFloat(it.subtotal || (qty * price));
                    total += subtotal;
                    row.innerHTML = `
                        <td><strong>${escapeHtml(it.sku || '-')}</strong></td>
                        <td>${escapeHtml(it.product_name || '-')}</td>
                        <td style="text-align: right;">${qty.toLocaleString('id-ID')} unit</td>
                        <td style="text-align: right;">Rp ${price.toLocaleString('id-ID')}</td>
                        <td style="text-align: right; font-weight: 600;">Rp ${subtotal.toLocaleString('id-ID')}</td>
                    `;
                    itemsTbody.appendChild(row);
                });
                document.querySelector('#detail-so-total').textContent = 'Rp ' + total.toLocaleString('id-ID');
            }

            // Render Contextual Actions in Detail Modal
            const actContainer = document.querySelector('#detail-so-contextual-actions');
            if (actContainer) {
                actContainer.innerHTML = '';
                const role = AppState.currentRole;
                if (so.status === 'draft') {
                    actContainer.innerHTML = `
                        <button type="button" class="modal-btn modal-btn-primary" data-action="submit-so" data-so-id="${id}" onclick="SimulasiModule.submitSO('${id}')">🚀 Ajukan Persetujuan</button>
                        <button type="button" class="modal-btn modal-btn-danger" data-action="cancel-so" data-so-id="${id}" onclick="SimulasiModule.cancelSO('${id}')">❌ Batalkan Order</button>
                    `;
                } else if (so.status === 'pending_approval') {
                    if (role === 'admin') {
                        actContainer.innerHTML = `
                            <button type="button" class="modal-btn modal-btn-primary" data-action="approve-so" data-so-id="${id}" onclick="SimulasiModule.approveSO('${id}')">✅ Setujui (Approve)</button>
                            <button type="button" class="modal-btn modal-btn-danger" data-action="reject-so" data-so-id="${id}" onclick="SimulasiModule.rejectSO('${id}')">❌ Tolak Order</button>
                        `;
                    } else if (role === 'sales') {
                        actContainer.innerHTML = `
                            <span class="text-amber" style="font-size:12px; margin-right:8px; align-self:center;">🔒 Menunggu Persetujuan Admin</span>
                            <button type="button" class="modal-btn modal-btn-danger" data-action="cancel-so" data-so-id="${id}" onclick="SimulasiModule.cancelSO('${id}')">❌ Batalkan Order</button>
                        `;
                    }
                } else if (so.status === 'approved') {
                    if (role === 'warehouse' || role === 'admin') {
                        actContainer.innerHTML = `
                            <button type="button" class="modal-btn modal-btn-primary" data-action="process-gi" data-so-id="${id}" onclick="SimulasiModule.processGoodsIssue('${id}')">📦 Proses Goods Issue (Kirim Barang)</button>
                        `;
                    }
                }
            }

            if (loading) loading.hidden = true;
            if (content) content.hidden = false;
        } catch (err) {
            showToast('❌ Gagal memuat detail SO: ' + err.message);
            if (loading) loading.textContent = 'Gagal memuat: ' + err.message;
        }
    },

    submitSO: async (id) => {
        try {
            await request('/api/orders/sales/submit', { order_id: parseInt(id, 10) });
            showToast(`🚀 Sales Order #${id} berhasil diajukan untuk ditinjau Admin!`);
            setTimeout(() => window.location.reload(), 600);
        } catch (err) {
            showToast('❌ ' + err.message);
        }
    },

    cancelSO: async (id) => {
        if (!confirm(`Yakin ingin membatalkan Sales Order #${id}?`)) return;
        try {
            await request('/api/orders/sales/cancel', { order_id: parseInt(id, 10) });
            showToast(`❌ Sales Order #${id} berhasil dibatalkan.`);
            setTimeout(() => window.location.reload(), 600);
        } catch (err) {
            showToast('❌ ' + err.message);
        }
    },

    createSalesOrder: async () => {
        document.querySelector('#btn-create-so')?.click();
    },

    approveSO: async (id) => {
        if (AppState.currentRole !== 'admin') {
            showToast('❌ Akses Ditolak: Hanya Admin yang berhak menyetujui Sales Order (Prinsip SOD).');
            return;
        }
        try {
            await request('/api/orders/sales/approve', { order_id: parseInt(id, 10) });
            showToast(`✅ Sales Order #${id} DISETUJUI oleh Admin. Siap diproses Goods Issue oleh Staff Gudang.`);
            setTimeout(() => window.location.reload(), 600);
        } catch (err) {
            showToast('❌ ' + err.message);
        }
    },

    rejectSO: async (id) => {
        if (AppState.currentRole !== 'admin') {
            showToast('❌ Akses Ditolak: Hanya Admin yang berhak menolak Sales Order.');
            return;
        }
        const reason = prompt('Masukkan alasan penolakan Sales Order:', 'Spesifikasi tidak sesuai / kuota tidak mencukupi');
        if (reason === null) return;
        try {
            await request('/api/orders/sales/reject', { order_id: parseInt(id, 10), reason: reason });
            showToast(`❌ Sales Order #${id} DITOLAK.`);
            setTimeout(() => window.location.reload(), 600);
        } catch (err) {
            showToast('❌ ' + err.message);
        }
    },

    processGoodsIssue: async (id) => {
        if (AppState.currentRole !== 'warehouse' && AppState.currentRole !== 'admin') {
            showToast('⚠️ Hanya Staff Gudang yang dapat memproses Goods Issue.');
            return;
        }
        try {
            await request('/api/orders/sales/fulfill', { order_id: parseInt(id, 10) });
            showToast(`📦 Goods Issue #${id} sukses! Stok terpotong atomik dan tercatat di Stock Ledger.`);
            setTimeout(() => window.location.reload(), 600);
        } catch (err) {
            showToast('❌ ' + err.message);
        }
    },

    // 2. Purchase Order & Goods Receipt Workflow
    createPurchaseOrder: async () => {
        if (AppState.currentRole === 'sales') {
            showToast('❌ Akses Ditolak: Sales Staff tidak memiliki akses ke pengadaan / Purchase Order.');
            return;
        }
        try {
            const res = await request('/api/orders/purchase/create', {
                supplier_id: 1,
                warehouse_id: 1,
                notes: 'Pengadaan pengisian stok',
                items: [
                    { product_id: 2, quantity: 15, unit_price: 3200000.0 }
                ]
            });
            const newPoId = res.order_id || Math.floor(500 + Math.random() * 400);
            await request('/api/orders/purchase/order', { order_id: newPoId });

            const tbody = document.querySelector('#po-table-body');
            const newRow = document.createElement('tr');
            newRow.id = `po-row-${newPoId}`;
            newRow.setAttribute('data-po-id', String(newPoId));
            newRow.setAttribute('data-po-status', 'sent_to_supplier');
            const isWh = AppState.currentRole === 'warehouse';
            newRow.innerHTML = `
                <td><strong>#PO-2026-${newPoId}</strong></td>
                <td>PT Supplier Utama Indonesia</td>
                <td>Gudang Utama Jakarta</td>
                <td>Monitor 27 Inch (15 unit)</td>
                <td>${AppState.rolesInfo[AppState.currentRole].title}</td>
                <td><span class="badge badge-blue" id="po-status-${newPoId}">sent_to_supplier</span></td>
                <td id="po-action-${newPoId}"></td>
            `;
            tbody?.prepend(newRow);
            renderPurchaseOrderActions();
            showToast(isWh 
                ? `📋 Usulan PO #PO-2026-${newPoId} berhasil diajukan oleh Staff Gudang!` 
                : `🚛 Purchase Order #PO-2026-${newPoId} berhasil diterbitkan di database!`);
        } catch (err) {
            showToast('❌ ' + err.message);
        }
    },

    processGoodsReceipt: async (id) => {
        if (AppState.currentRole !== 'warehouse' && AppState.currentRole !== 'admin') {
            showToast('⚠️ Hanya Staff Gudang yang dapat mencatat penerimaan barang (Goods Receipt).');
            return;
        }
        try {
            await request('/api/orders/purchase/receive', { order_id: parseInt(id, 10) });
            const row = document.querySelector(`#po-row-${id}`);
            if (row) row.setAttribute('data-po-status', 'goods_received');
            const statusBadge = document.querySelector(`#po-status-${id}`);
            if (statusBadge) {
                statusBadge.className = 'badge badge-success';
                statusBadge.textContent = 'goods_received';
            }
            renderPurchaseOrderActions();
            showToast(`📥 Goods Receipt #PO-2026-${id} berhasil! Stok fisik bertambah di database.`);
        } catch (err) {
            showToast('❌ ' + err.message);
        }
    },

    // 3. Export CSV Functionality (Download File CSV Resmi Berbasis Database)
    exportCSV: (type) => {
        try {
            const url = `/export/csv?type=${encodeURIComponent(type)}`;
            const fileMap = {
                products: 'daftar_master_produk.csv',
                categories: 'daftar_kategori_produk.csv',
                warehouses: 'daftar_lokasi_gudang.csv',
                suppliers: 'daftar_supplier.csv',
                customers: 'daftar_customer.csv',
                admin: 'ringkasan_stok_semua_gudang.csv',
                sales: 'daftar_sales_orders.csv',
                so: 'daftar_sales_orders.csv',
                po: 'daftar_purchase_orders.csv',
                ledger: 'kartu_stok_ledger_audit.csv',
                catalog: 'katalog_harga_produk.csv',
                warehouse: 'laporan_stok_gudang.csv'
            };
            const filename = fileMap[type] || 'data_ekspor.csv';
            showToast(`📥 Mengunduh file ${filename}...`);

            window.location.href = url;
        } catch (err) {
            console.error('Error saat ekspor CSV:', err);
            showToast('❌ Gagal mengekspor data: ' + err.message);
        }
    }
};
