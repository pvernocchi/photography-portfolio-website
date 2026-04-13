<?php
declare(strict_types=1);

$siteTitle = (string) \App\Models\Setting::get('site_title', 'Vernocchi Photography');
$titleTag = trim((string) ($title ?? $siteTitle));
$metaDescription = (string) ($metaDescription ?? '');
$locale = (string) ($locale ?? 'es');
$canonical = rtrim((string) app_config('app.url', ''), '/') . ($_SERVER['REQUEST_URI'] ?? '/');
$ogImage = (string) \App\Models\Setting::get('og_image', '');
$gaId = trim((string) ($gaId ?? ''));
?>
<!doctype html>
<html lang="<?= e($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titleTag) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta property="og:title" content="<?= e($titleTag) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="<?= e($locale === 'es' ? 'es_ES' : 'en_GB') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <link rel="stylesheet" href="/assets/css/frontend.css">
    <link rel="stylesheet" href="/theme/style.css">
    <link rel="stylesheet" href="/theme/dark.css">
    <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark');}</script>
    <?php if ($gaId !== ''): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', '<?= e($gaId) ?>');
        </script>
    <?php endif; ?>
</head>
<body>
<?php include BASE_PATH . '/app/Views/frontend/partials/nav.php'; ?>
<main class="front-main"><?= $content ?></main>
<?php include BASE_PATH . '/app/Views/frontend/partials/footer.php'; ?>
<script src="/assets/js/lightbox.js" defer></script>
<script>
(function(){
    var btn = document.querySelector('.theme-toggle');
    if (btn && document.documentElement.classList.contains('dark')) { btn.textContent = '☀️'; }
    btn && btn.addEventListener('click', function() {
        var isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        this.textContent = isDark ? '☀️' : '🌙';
    });
})();
</script>
<?php include BASE_PATH . '/app/Views/frontend/partials/image-protection.php'; ?>
</body>
</html>
