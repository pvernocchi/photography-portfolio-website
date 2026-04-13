'use strict';

(function () {
    var STORAGE_KEY = 'cookie_consent';
    var banner = document.getElementById('cookie-banner');

    if (!banner) return;

    var gaId = banner.dataset.gaId || '';
    var acceptBtn = document.getElementById('cookie-accept');
    var rejectBtn = document.getElementById('cookie-reject');

    function loadGA() {
        if (!gaId) return;
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + gaId;
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
        window.gtag('js', new Date());
        window.gtag('config', gaId);
    }

    var consent = null;
    try {
        consent = localStorage.getItem(STORAGE_KEY);
    } catch (e) {
        // localStorage unavailable (e.g. private browsing with strict settings)
    }

    if (consent === 'accepted') {
        loadGA();
    } else if (consent !== 'rejected') {
        banner.hidden = false;
    }

    if (acceptBtn) {
        acceptBtn.addEventListener('click', function () {
            try { localStorage.setItem(STORAGE_KEY, 'accepted'); } catch (e) { /* ignore */ }
            banner.hidden = true;
            loadGA();
        });
    }

    if (rejectBtn) {
        rejectBtn.addEventListener('click', function () {
            try { localStorage.setItem(STORAGE_KEY, 'rejected'); } catch (e) { /* ignore */ }
            banner.hidden = true;
        });
    }
})();
