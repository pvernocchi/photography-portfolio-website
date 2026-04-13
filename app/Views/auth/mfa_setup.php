<?php
declare(strict_types=1);

use App\Core\CSRF;
?>
<div class="auth-wrap">
    <section class="card auth-card">
        <h1>Set up Microsoft Authenticator</h1>
        <p>Scan this QR code with Microsoft Authenticator, then enter the 6-digit code to confirm setup.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="qr-block">
            <img src="<?= e($qrUrl) ?>" alt="MFA QR Code" width="220" height="220">
            <p><strong>Manual key:</strong> <code><?= e($secret) ?></code></p>
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
