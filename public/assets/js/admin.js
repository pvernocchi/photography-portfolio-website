'use strict';

window.VernocchiAdmin = {
  csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
};
