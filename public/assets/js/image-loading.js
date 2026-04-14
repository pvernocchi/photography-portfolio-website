'use strict';

/**
 * Image Loading Enhancement
 * Adds fade-in effects for gallery cards on scroll
 */
(function () {

  /**
   * Intersection Observer for fade-in animation on scroll
   */
  function setupScrollAnimations() {
    // Check if browser supports Intersection Observer
    if (!('IntersectionObserver' in window)) {
      return;
    }

    // Respect OS-level reduced-motion preference (e.g. Windows accessibility settings)
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return;
    }
    
    const cards = document.querySelectorAll('.gallery-card');
    
    const observerOptions = {
      root: null,
      rootMargin: '50px',
      threshold: 0.1
    };
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '0';
          entry.target.style.transform = 'translateY(20px)';
          
          // Use double requestAnimationFrame to ensure the browser has
          // painted the initial state before starting the transition.
          // A single rAF can be batched with the initial style change on
          // desktop browsers (Edge/Chrome on Windows), causing the
          // transition to be skipped entirely.
          requestAnimationFrame(() => {
            requestAnimationFrame(() => {
              entry.target.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
              entry.target.style.opacity = '1';
              entry.target.style.transform = 'translateY(0)';
            });
          });
          
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);
    
    cards.forEach(card => {
      observer.observe(card);
    });
  }
  
  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      setupScrollAnimations();
    });
  } else {
    setupScrollAnimations();
  }
})();
