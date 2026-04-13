(function () {
    var btn = document.getElementById('theme-toggle');
    if (!btn) return;

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        if (theme === 'dark') {
            btn.textContent = '☀';
            btn.setAttribute('aria-label', 'Switch to light mode');
        } else {
            btn.textContent = '🌙';
            btn.setAttribute('aria-label', 'Switch to dark mode');
        }
        localStorage.setItem('theme', theme);
    }

    applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');

    btn.addEventListener('click', function () {
        applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });
}());
