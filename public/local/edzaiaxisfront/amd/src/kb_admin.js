// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin Knowledge Base management AMD module.
 *
 * Handles:
 *  - URL ingestion into KB
 *  - Plain-text ingestion into KB
 *  - Listing all KB items
 *  - Toggling KB items active/inactive
 *  - Deleting KB items
 *
 * @module     local_edzaiaxisfront/kb_admin
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Repository from 'local_edzaiaxisfront/repository';
import Notification from 'core/notification';

// In-memory cache of the last loaded items list.
// Keyed by axis_kb_item_id so the edit modal can look up full item data.
let _itemsMap = {};

export const init = async(cfg) => { // eslint-disable-line no-unused-vars
    // ── Tab switching ──────────────────────────────────────────────────────
    document.querySelectorAll('.edzai-kb-tab-link').forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const tab = e.target.dataset.tab;
            document.querySelectorAll('.edzai-kb-tab-link').forEach((l) => {
                l.classList.toggle('active', l.dataset.tab === tab);
            });
            document.getElementById('edzai-kb-tab-url')
                .classList.toggle('d-none', tab !== 'url');
            document.getElementById('edzai-kb-tab-text')
                .classList.toggle('d-none', tab !== 'text');
        });
    });

    // ── Add item panel toggle ──────────────────────────────────────────────
    document.getElementById('edzai-kb-add-btn')?.addEventListener('click', () => {
        const panel = document.getElementById('edzai-kb-add-panel');
        panel?.classList.toggle('d-none');
    });

    // ── URL submission ─────────────────────────────────────────────────────
    document.getElementById('edzai-kb-url-submit')?.addEventListener('click', async() => {
        const url     = document.getElementById('edzai-kb-url')?.value.trim();
        const title   = document.getElementById('edzai-kb-url-title')?.value.trim();
        const desc    = document.getElementById('edzai-kb-url-desc')?.value.trim();
        const docType = document.getElementById('edzai-kb-url-doctype')?.value || 'support';

        if (!url) {
            showStatus('Please enter a URL.', 'danger');
            return;
        }

        showStatus('Submitting URL for ingestion\u2026', 'info');
        try {
            await Repository.ingestKbUrl(url, title, desc, docType);
            showStatus('URL submitted. It will be available in the KB shortly.', 'success');
            document.getElementById('edzai-kb-url').value = '';
            document.getElementById('edzai-kb-url-title').value = '';
            document.getElementById('edzai-kb-url-desc').value = '';
            document.getElementById('edzai-kb-add-panel')?.classList.add('d-none');
            await loadItems();
        } catch (e) {
            showStatus('Error: ' + (e.message || 'Failed to ingest URL.'), 'danger');
        }
    });

    // ── Text + File combined submission ────────────────────────────────────
    // One button handles both: if a file is selected it takes priority;
    // otherwise the pasted text is used. Both require a title.
    document.getElementById('edzai-kb-text-submit')?.addEventListener('click', async() => {
        const title   = document.getElementById('edzai-kb-text-title')?.value.trim();
        const content = document.getElementById('edzai-kb-text-content')?.value.trim();
        const docType = document.getElementById('edzai-kb-text-doctype')?.value || 'support';
        const fileEl  = document.getElementById('edzai-kb-file');
        const file    = fileEl?.files?.[0];

        if (!title) {
            showStatus('Please enter a title.', 'danger');
            return;
        }
        if (!file && !content) {
            showStatus('Please paste some text or select a file to upload.', 'danger');
            return;
        }

        try {
            if (file) {
                // File takes priority when both are provided
                showStatus('Reading file\u2026', 'info');
                const base64 = await readFileAsBase64(file);
                showStatus('Uploading \u201c' + escHtml(file.name) + '\u201d for ingestion\u2026', 'info');
                await Repository.ingestKbFile(title, file.name, base64, docType);
                showStatus(
                    'File \u201c' + escHtml(file.name) + '\u201d submitted. It will be indexed shortly.',
                    'success',
                );
            } else {
                showStatus('Submitting text content for ingestion\u2026', 'info');
                await Repository.ingestKbText(title, content, '', docType);
                showStatus('Text submitted. It will be indexed shortly.', 'success');
            }

            document.getElementById('edzai-kb-text-title').value = '';
            document.getElementById('edzai-kb-text-content').value = '';
            if (fileEl) {
                fileEl.value = '';
            }
            document.getElementById('edzai-kb-add-panel')?.classList.add('d-none');
            await loadItems();
        } catch (e) {
            showStatus('Error: ' + (e.message || 'Failed to save content.'), 'danger');
        }
    });

    // ── Refresh button ─────────────────────────────────────────────────────
    document.getElementById('edzai-kb-refresh-btn')?.addEventListener('click', loadItems);

    // ── Fix KB Search (backfill is_active) button ──────────────────────────
    document.getElementById('edzai-kb-backfill-btn')?.addEventListener('click', async() => {
        const btn = document.getElementById('edzai-kb-backfill-btn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = '⏳ Running…';
        }
        try {
            const result = await Repository.kbBackfillActive();
            showStatus(
                `✅ ${result.message || 'Backfill complete.'}`,
                'success'
            );
        } catch (err) {
            showStatus('❌ Backfill failed: ' + (err.message || 'Unknown error.'), 'danger');
            Notification.exception(err);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = '🔧 Fix KB Search';
            }
        }
    });

    // ── Delegated events on tbody (toggle / delete) ────────────────────────
    document.getElementById('edzai-kb-tbody')?.addEventListener('click', async(e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) {
            return;
        }
        const action = btn.dataset.action;
        const itemId = btn.dataset.id;

        if (action === 'edit') {
            document.getElementById('edzai-kb-edit-id').value = itemId;
            document.getElementById('edzai-kb-edit-title-input').value = btn.dataset.title || '';
            document.getElementById('edzai-kb-edit-doctype').value = btn.dataset.doctype || 'support';

            const item = _itemsMap[itemId] || {};
            const contentGroup   = document.getElementById('edzai-kb-edit-content-group');
            const contentEl      = document.getElementById('edzai-kb-edit-content');
            const noContentWarn  = document.getElementById('edzai-kb-edit-no-content-warning');
            const helpEl         = document.getElementById('edzai-kb-edit-content-help');

            // Show content section for all non-URL items (URL items have a source_url set).
            const isUrlItem = !!(item.source_url && item.source_url.trim() !== '');
            if (contentGroup) {
                contentGroup.classList.toggle('d-none', isUrlItem);
            }
            if (!isUrlItem) {
                const hasContent = !!(item.content && item.content.trim() !== '');
                if (contentEl) {
                    contentEl.value = item.content || '';
                }
                // Warn when no stored content (old items / file uploads)
                if (noContentWarn) {
                    noContentWarn.classList.toggle('d-none', hasContent);
                }
                if (helpEl) {
                    helpEl.classList.toggle('d-none', !hasContent);
                }
            }

            const statusEl = document.getElementById('edzai-kb-edit-status');
            if (statusEl) {
                statusEl.className = 'd-none alert';
                statusEl.textContent = '';
            }
            showModal('edzai-kb-edit-modal');
        }

        if (action === 'toggle') {
            const isActive = btn.dataset.active === '1';
            btn.disabled = true;
            try {
                await Repository.toggleKbItem(itemId, !isActive);
                await loadItems();
            } catch (err) {
                Notification.exception(err);
                btn.disabled = false;
            }
        }

        if (action === 'delete') {
            // eslint-disable-next-line no-alert
            if (!window.confirm('Delete this KB item? This cannot be undone.')) {
                return;
            }
            btn.disabled = true;
            try {
                await Repository.deleteKbItem(itemId);
                await loadItems();
            } catch (err) {
                Notification.exception(err);
                btn.disabled = false;
            }
        }
    });

    // ── Edit modal — save ──────────────────────────────────────────────────
    document.getElementById('edzai-kb-edit-save')?.addEventListener('click', async() => {
        const id       = document.getElementById('edzai-kb-edit-id')?.value;
        const title    = document.getElementById('edzai-kb-edit-title-input')?.value.trim();
        const docType  = document.getElementById('edzai-kb-edit-doctype')?.value;
        const contentEl = document.getElementById('edzai-kb-edit-content');
        // Only send content if the textarea is visible (text items)
        const contentGroup = document.getElementById('edzai-kb-edit-content-group');
        const content  = (contentGroup && !contentGroup.classList.contains('d-none'))
            ? (contentEl?.value.trim() || '')
            : '';
        const statusEl = document.getElementById('edzai-kb-edit-status');

        if (!title) {
            if (statusEl) {
                statusEl.className = 'alert alert-danger';
                statusEl.textContent = 'Title cannot be empty.';
            }
            return;
        }

        const saveBtn = document.getElementById('edzai-kb-edit-save');
        if (saveBtn) {
            saveBtn.disabled = true;
        }
        try {
            await Repository.updateKbItem(id, title, docType, content);
            hideModal('edzai-kb-edit-modal');
            await loadItems();
        } catch (err) {
            if (statusEl) {
                statusEl.className = 'alert alert-danger';
                statusEl.textContent = 'Error: ' + (err.message || 'Could not save changes.');
            }
            Notification.exception(err);
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
            }
        }
    });

    // ── Edit modal — close (data-dismiss="modal" buttons) ─────────────────
    document.querySelectorAll('#edzai-kb-edit-modal [data-dismiss="modal"]').forEach((btn) => {
        btn.addEventListener('click', () => hideModal('edzai-kb-edit-modal'));
    });

    // ── Initial load ───────────────────────────────────────────────────────
    await loadItems();
};

const loadItems = async() => {
    const loading = document.getElementById('edzai-kb-loading');
    const empty   = document.getElementById('edzai-kb-empty');
    const table   = document.getElementById('edzai-kb-table');
    const tbody   = document.getElementById('edzai-kb-tbody');

    if (loading) {
        loading.classList.remove('d-none');
    }
    if (empty) {
        empty.classList.add('d-none');
    }
    if (table) {
        table.classList.add('d-none');
    }

    try {
        const items = await Repository.listKbItems();

        if (loading) {
            loading.classList.add('d-none');
        }

        if (!items || items.length === 0) {
            if (empty) {
                empty.classList.remove('d-none');
            }
            return;
        }

        if (table) {
            table.classList.remove('d-none');
        }
        // Cache items by axis_kb_item_id for the edit modal
        _itemsMap = {};
        items.forEach((item) => {
            _itemsMap[item.axis_kb_item_id] = item;
        });
        if (tbody) {
            tbody.innerHTML = items.map((item) => buildRow(item)).join('');
        }
    } catch (e) {
        if (loading) {
            loading.classList.add('d-none');
        }
        Notification.exception(e);
    }
};

const buildRow = (item) => {
    const statusBadge = item.status === 'ready'
        ? '<span class="badge badge-success">Ready</span>'
        : item.status === 'processing'
        ? '<span class="badge badge-warning">Processing\u2026</span>'
        : item.status === 'failed'
        ? '<span class="badge badge-danger">Failed</span>'
        : `<span class="badge badge-secondary">${escHtml(item.status)}</span>`;

    const activeBadge = item.is_active
        ? '<span class="badge badge-primary ml-1">Active</span>'
        : '<span class="badge badge-light text-muted ml-1">Inactive</span>';

    const sourceDisplay = item.source_url
        ? `<a href="${escHtml(item.source_url)}" target="_blank"
               class="text-truncate d-inline-block"
               style="max-width:200px">${escHtml(item.source_url)}</a>`
        : '<span class="text-muted">\u2014</span>';

    const toggleLabel = item.is_active ? 'Deactivate' : 'Activate';
    const toggleClass = item.is_active ? 'btn-outline-warning' : 'btn-outline-success';

    return `
    <tr>
      <td>
        <strong>${escHtml(item.title || 'Untitled')}</strong>
        ${activeBadge}
      </td>
      <td>${sourceDisplay}</td>
      <td>${statusBadge}</td>
      <td><small class="text-muted">${formatDate(item.created_at)}</small></td>
      <td class="text-right">
        <button class="btn btn-sm btn-outline-secondary mr-1"
                data-action="edit" data-id="${escHtml(item.axis_kb_item_id)}"
                data-title="${escHtml(item.title || '')}"
                data-doctype="${escHtml(item.doc_type || 'support')}">
          ✏️ Edit
        </button>
        <button class="btn btn-sm ${toggleClass} mr-1"
                data-action="toggle" data-id="${escHtml(item.axis_kb_item_id)}"
                data-active="${item.is_active ? '1' : '0'}">
          ${toggleLabel}
        </button>
        <button class="btn btn-sm btn-outline-danger"
                data-action="delete" data-id="${escHtml(item.axis_kb_item_id)}">
          Delete
        </button>
      </td>
    </tr>`;
};

const showStatus = (msg, type) => {
    const el = document.getElementById('edzai-kb-add-status');
    if (!el) {
        return;
    }
    el.className = `edzai-kb-add-status mt-3 alert alert-${type}`;
    el.textContent = msg;
    el.classList.remove('d-none');
    if (type === 'success') {
        window.setTimeout(() => el.classList.add('d-none'), 4000);
    }
};

const formatDate = (ts) => {
    if (!ts) {
        return '\u2014';
    }
    try {
        return new Date(ts * 1000).toLocaleDateString();
    } catch {
        return ts;
    }
};

const escHtml = (s) =>
    String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

// ── Bootstrap 4 modal show / hide (no jQuery required) ────────────────────────
const showModal = (id) => {
    const modal = document.getElementById(id);
    if (!modal) {
        return;
    }
    modal.style.display = 'block';
    modal.classList.add('show');
    document.body.classList.add('modal-open');
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.dataset.modalFor = id;
    document.body.appendChild(backdrop);
};

const hideModal = (id) => {
    const modal = document.getElementById(id);
    if (modal) {
        modal.style.display = '';
        modal.classList.remove('show');
    }
    document.body.classList.remove('modal-open');
    document.querySelectorAll(`.modal-backdrop[data-modal-for="${id}"]`).forEach((el) => el.remove());
};

const readFileAsBase64 = (file) => new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => {
        // FileReader result is "data:<mime>;base64,<data>" — strip the prefix.
        const base64 = reader.result.split(',')[1];
        resolve(base64);
    };
    reader.onerror = () => reject(new Error('Failed to read file'));
    reader.readAsDataURL(file);
});
