'use strict';

/**
 * Mobile Menu Toggle
 * Handles proper mobile navigation toggle with accessibility
 */
(function () {
  const menuToggle = document.querySelector('.menu-toggle');
  const menuLinks = document.querySelector('.menu-links');
  const nav = document.querySelector('.front-nav');
  
  if (!menuToggle || !menuLinks || !nav) return;
  
  let isOpen = false;
  
  /**
   * Toggle menu open/closed state
   */
  function toggleMenu(forceClose = false) {
    isOpen = forceClose ? false : !isOpen;
    
    menuLinks.classList.toggle('is-open', isOpen);
    menuToggle.setAttribute('aria-expanded', String(isOpen));
    menuToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
    
    // Prevent body scroll when menu is open on mobile
    if (window.innerWidth <= 780) {
      document.body.style.overflow = isOpen ? 'hidden' : '';
    }
  }
  
  /**
   * Handle menu toggle button click
   */
  menuToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleMenu();
  });
  
  /**
   * Close menu when clicking outside
   */
  document.addEventListener('click', (e) => {
    if (isOpen && !nav.contains(e.target)) {
      toggleMenu(true);
    }
  });
  
  /**
   * Close menu on Escape key
   */
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && isOpen) {
      toggleMenu(true);
      menuToggle.focus();
    }
  });
  
  /**
   * Toggle gallery dropdown on mobile via click/tap
   */
  const dropdownToggle = menuLinks.querySelector('.nav-dropdown-toggle');
  const dropdown = menuLinks.querySelector('.nav-dropdown');
  if (dropdownToggle && dropdown) {
    dropdownToggle.addEventListener('click', (e) => {
      if (window.innerWidth <= 780) {
        e.preventDefault();
        e.stopPropagation();
        dropdown.classList.toggle('is-open');
      }
    });
  }

  /**
   * Close menu when selecting a menu item
   */
  const menuItems = menuLinks.querySelectorAll('a');
  menuItems.forEach(item => {
    item.addEventListener('click', () => {
      if (isOpen) {
        if (dropdown) dropdown.classList.remove('is-open');
        toggleMenu(true);
      }
    });
  });
  
  /**
   * Reset menu state on window resize
   */
  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      if (window.innerWidth > 780 && isOpen) {
        if (dropdown) dropdown.classList.remove('is-open');
        toggleMenu(true);
        document.body.style.overflow = '';
      }
    }, 150);
  });
  
  // Initialize ARIA attributes
  menuToggle.setAttribute('aria-expanded', 'false');
  menuToggle.setAttribute('aria-controls', 'menu-links');
  menuLinks.setAttribute('id', 'menu-links');
})();
