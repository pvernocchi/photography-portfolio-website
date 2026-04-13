<?php
declare(strict_types=1);

use App\Core\CSRF;
?>
<div class="auth-wrap">
    <section class="card auth-card">
        <h1>Multi-factor verification</h1>
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

        <p class="mt-16"><a href="/admin/login">Back to login</a></p>
    </section>
</div>
