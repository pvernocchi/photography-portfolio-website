'use strict';

window.VernocchiAdmin = {
  csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
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
