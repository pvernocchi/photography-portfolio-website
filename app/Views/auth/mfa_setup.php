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
            <span class="auth-brand-name">Enable MFA</span>
        </div>

        <h1>Authenticator Setup</h1>
        <p>Scan this QR code with Microsoft Authenticator (or any TOTP-compatible app), then enter the 6-digit code to confirm setup.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="qr-block">
            <img src="<?= e($qrUrl) ?>" alt="MFA QR Code" width="200" height="200">
            <details>
                <summary>Show manual key</summary>
                <p><strong>Manual key:</strong> <code><?= e($secret) ?></code></p>
            </details>
        </div>

        <form method="post" action="/admin/mfa/setup" class="form-stack">
            <?= CSRF::field() ?>
            <label>
                Verification code
                <input type="text" name="code" required inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" autofocus>
            </label>
            <button type="submit" class="btn btn-primary">Enable MFA</button>
        </form>
    </section>
</div>
