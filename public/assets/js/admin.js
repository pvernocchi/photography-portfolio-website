'use strict';

window.VernocchiAdmin = {
  csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
};

/* ── Mobile nav drawer ───────────────────────────────────────────────────── */
(function () {
  const hamburger = document.getElementById('nav-hamburger');
  const drawer = document.getElementById('nav-drawer');
  const overlay = document.getElementById('nav-drawer-overlay');
  const closeBtn = document.getElementById('nav-drawer-close');
  if (!hamburger || !drawer) return;

  function openDrawer() {
    drawer.classList.add('open');
    hamburger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    closeBtn?.focus();
  }
  function closeDrawer() {
    drawer.classList.remove('open');
    hamburger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    hamburger.focus();
  }

  hamburger.addEventListener('click', openDrawer);
  closeBtn?.addEventListener('click', closeDrawer);
  overlay?.addEventListener('click', closeDrawer);
  drawer.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeDrawer();
  });
}());

window.AdminBulkSelect = {
  init(gridSel, formSel, barSel, countSel, idsSel, selectAllSel, selectNoneSel) {
    const grid = document.querySelector(gridSel);
    const form = document.querySelector(formSel);
    const bar = document.querySelector(barSel);
    const countEl = document.querySelector(countSel);
    const idsContainer = document.querySelector(idsSel);
    const selectAllBtn = document.querySelector(selectAllSel);
    const selectNoneBtn = document.querySelector(selectNoneSel);
    if (!grid || !form || !bar) return;

    function getCheckboxes() {
      return [...grid.querySelectorAll('.image-card-checkbox')];
    }

    function plural(n, word) {
      return n + ' ' + word + (n !== 1 ? 's' : '');
    }

    function updateBar() {
      const checked = grid.querySelectorAll('.image-card-checkbox:checked');
      const count = checked.length;
      bar.hidden = count === 0;
      if (countEl) countEl.textContent = plural(count, 'image') + ' selected';
      if (selectNoneBtn) selectNoneBtn.style.display = count > 0 ? '' : 'none';

      if (idsContainer) {
        idsContainer.innerHTML = '';
        checked.forEach((cb) => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'ids[]';
          input.value = cb.value;
          idsContainer.appendChild(input);
        });
      }
    }

    grid.addEventListener('change', (e) => {
      const cb = e.target.closest('.image-card-checkbox');
      if (!cb) return;
      cb.closest('.image-card')?.classList.toggle('image-card--selected', cb.checked);
      updateBar();
    });

    // Prevent drag from starting when clicking the checkbox label
    grid.addEventListener('dragstart', (e) => {
      if (e.target.closest('.image-card-select')) {
        e.preventDefault();
      }
    });

    if (selectAllBtn) {
      selectAllBtn.addEventListener('click', () => {
        getCheckboxes().forEach((cb) => {
          cb.checked = true;
          cb.closest('.image-card')?.classList.add('image-card--selected');
        });
        updateBar();
      });
    }

    if (selectNoneBtn) {
      selectNoneBtn.addEventListener('click', () => {
        getCheckboxes().forEach((cb) => {
          cb.checked = false;
          cb.closest('.image-card')?.classList.remove('image-card--selected');
        });
        updateBar();
      });
    }

    form.addEventListener('submit', (e) => {
      const checked = grid.querySelectorAll('.image-card-checkbox:checked');
      if (checked.length === 0) {
        e.preventDefault();
        return;
      }
      // e.submitter is supported in all modern browsers; fall back to data attribute set on click.
      const action = e.submitter?.value || form.dataset.pendingAction || '';
      if (action === 'delete') {
        if (!confirm('Permanently delete ' + plural(checked.length, 'image') + '? This cannot be undone.')) {
          e.preventDefault();
        }
      } else if (action === 'assign_to_category') {
        const assignInput = form.querySelector('[name="assign_category_id"]') || form.querySelector('[name="category_id"]');
        const hasCategory = assignInput && Number(assignInput.value) > 0;
        if (!hasCategory) {
          e.preventDefault();
          alert('Please select a gallery first.');
          return;
        }
        if (!confirm('Assign ' + plural(checked.length, 'image') + ' to the selected gallery?')) {
          e.preventDefault();
        }
      } else if (action === 'remove_from_category') {
        const categoryInput = form.querySelector('[name="category_id"]');
        const hasCategory = categoryInput && Number(categoryInput.value) > 0;
        if (!hasCategory) {
          e.preventDefault();
          alert('Please select a gallery first.');
          return;
        }
        if (!confirm('Remove ' + plural(checked.length, 'image') + ' from the selected gallery?')) {
          e.preventDefault();
        }
      }
    });

    // Track which submit button was clicked as a reliable fallback for e.submitter.
    form.querySelectorAll('[type="submit"]').forEach((btn) => {
      btn.addEventListener('click', () => { form.dataset.pendingAction = btn.value; });
    });
  }
};

window.AdminReorder = {
  bind(selector, endpoint) {
    const container = document.querySelector(selector);
    if (!container) return;
    let dragEl = null;

    const items = () => [...container.querySelectorAll('.sortable-item')];

    container.addEventListener('dragstart', (e) => {
      dragEl = e.target.closest('.sortable-item');
      if (!dragEl) return;
      e.dataTransfer.effectAllowed = 'move';
    });

    container.addEventListener('dragover', (e) => {
      e.preventDefault();
      const target = e.target.closest('.sortable-item');
      if (!dragEl || !target || target === dragEl) return;
      const rect = target.getBoundingClientRect();
      const next = (e.clientY - rect.top) > rect.height / 2;
      container.insertBefore(dragEl, next ? target.nextSibling : target);
    });

    container.addEventListener('dragend', async () => {
      const ids = items().map((el) => el.dataset.id);
      const payload = new URLSearchParams();
      payload.set('csrf_token', window.VernocchiAdmin.csrfToken);
      ids.forEach((id) => payload.append('ids[]', id));
      await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: payload.toString() });
    });
  }
};

document.addEventListener('DOMContentLoaded', () => {
  const nameEn = document.getElementById('category-name-en');
  const slug = document.getElementById('category-slug');
  if (nameEn && slug) {
    nameEn.addEventListener('input', () => {
      if (slug.dataset.manual === '1') return;
      slug.value = nameEn.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    });
    slug.addEventListener('input', () => { slug.dataset.manual = '1'; });
  }

  const tabButtons = [...document.querySelectorAll('.tab-btn')];
  if (tabButtons.length > 0) {
    tabButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        tabButtons.forEach((el) => el.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach((panel) => panel.classList.remove('active'));
        btn.classList.add('active');
        document.querySelector(`[data-panel="${btn.dataset.tab}"]`)?.classList.add('active');
      });
    });
  }
});
