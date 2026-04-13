'use strict';

/**
 * Image Loading Enhancement
 * Adds loading states and fade-in effects for lazy-loaded images
 */
(function () {
  /**
   * Mark image as loaded with fade-in effect
   */
  function handleImageLoad(img) {
    img.classList.add('loaded');
    const card = img.closest('.gallery-card');
    if (card) {
      card.classList.remove('is-loading');
    }
  }
  
  /**
   * Setup loading state for images
   */
  function setupImageLoading() {
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    
    lazyImages.forEach(img => {
      const card = img.closest('.gallery-card');
      
      // Add loading state to card
      if (card && !img.complete) {
        card.classList.add('is-loading');
      }
      
      // Handle load event
      if (img.complete && img.naturalHeight > 0) {
        handleImageLoad(img);
      } else {
        img.addEventListener('load', () => handleImageLoad(img), { once: true });
        img.addEventListener('error', () => {
          if (card) {
            card.classList.remove('is-loading');
          }
        }, { once: true });
      }
    });
  }
  
  /**
   * Intersection Observer for fade-in animation on scroll
   */
  function setupScrollAnimations() {
    // Check if browser supports Intersection Observer
    if (!('IntersectionObserver' in window)) {
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
          
          // Trigger animation
          requestAnimationFrame(() => {
            entry.target.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
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
      setupImageLoading();
      setupScrollAnimations();
    });
  } else {
    setupImageLoading();
    setupScrollAnimations();
  }
})();
