<?php
declare(strict_types=1);

use App\Core\CSRF;

$appName = (string) app_config('app.name', 'Vernocchi Photography');
?>
<div class="auth-wrap">
    <section class="auth-card">
        <div class="auth-brand">
            <div class="auth-brand-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                    <circle cx="12" cy="13" r="3"/>
                </svg>
            </div>
            <span class="auth-brand-name"><?= e($appName) ?></span>
        </div>

        <h1>Admin Login</h1>
        <p>Sign in to manage your portfolio.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/admin/login" class="form-stack">
            <?= CSRF::field() ?>
            <label>
                Username
                <input type="text" name="username" required autocomplete="username">
            </label>
            <label>
                Password
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <label class="checkbox-row">
                <input type="checkbox" name="remember" value="1">
                Remember me for 30 days
            </label>
            <button type="submit" class="btn btn-primary">Sign in</button>
        </form>
    </section>
</div>
