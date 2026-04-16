<?php
declare(strict_types=1);

use App\Core\CSRF;
?>
<div class="auth-wrap">
    <section class="auth-card">
        <div class="auth-brand">
            <div class="auth-brand-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <span class="auth-brand-name">Two-Factor Auth</span>
        </div>

        <h1>Verification</h1>
        <p>Enter your 6-digit authentication code.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/admin/mfa/verify" class="form-stack">
            <?= CSRF::field() ?>
            <label>
                Authentication code
                <input type="text" name="code" required inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" autofocus>
            </label>
            <button type="submit" class="btn btn-primary">Verify</button>
        </form>

        <p class="mt-16" style="font-size:0.8rem"><a href="/admin/login">← Back to login</a></p>
    </section>
</div>
