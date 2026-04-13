'use strict';

/**
 * Contact Form Handler
 * Handles form submission with AJAX
 */
(function () {
  const form = document.getElementById('contact-form');
  const statusDiv = document.getElementById('contact-status');
  
  if (!form || !statusDiv) return;
  
  /**
   * Show status message
   */
  function showStatus(message, isSuccess) {
    statusDiv.textContent = message;
    statusDiv.className = 'contact-status ' + (isSuccess ? 'success' : 'error');
    statusDiv.hidden = false;
    
    // Scroll to status message
    statusDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Auto-hide after 5 seconds if successful
    if (isSuccess) {
      setTimeout(() => {
        statusDiv.hidden = true;
      }, 5000);
    }
  }
  
  /**
   * Handle form submission
   */
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.textContent;
    
    // Disable submit button and show loading state
    submitButton.disabled = true;
    submitButton.textContent = '...';
    statusDiv.hidden = true;
    
    try {
      const formData = new FormData(form);
      
      const response = await fetch('/contact/send', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      
      const data = await response.json();
      
      if (response.ok && data.success) {
        showStatus(data.message, true);
        form.reset();
      } else {
        showStatus(data.message || 'An error occurred', false);
      }
    } catch (error) {
      showStatus('An error occurred. Please try again.', false);
      console.error('Contact form error:', error);
    } finally {
      // Re-enable submit button
      submitButton.disabled = false;
      submitButton.textContent = originalButtonText;
    }
  });
})();
