<?php
declare(strict_types=1);

use App\Core\CSRF;
?>
<div class="auth-wrap">
    <section class="card auth-card">
        <h1>Admin Login</h1>
        <p class="muted">Sign in to manage your portfolio.</p>

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
                Remember me
            </label>
            <button type="submit" class="btn btn-primary">Sign in</button>
        </form>
    </section>
</div>
