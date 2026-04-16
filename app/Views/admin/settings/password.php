<?php
declare(strict_types=1);
use App\Core\CSRF;
?>
<header class="page-header">
    <h1>Change Password</h1>
    <p>Update your admin account password</p>
</header>
<?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="/admin/settings/password" class="card form-stack">
    <?= CSRF::field() ?>
    <label>Current password<input type="password" name="current_password" required></label>
    <label>New password (min 12 chars)<input type="password" name="new_password" minlength="12" required></label>
    <label>Confirm new password<input type="password" name="new_password_confirmation" minlength="12" required></label>
    <button class="btn btn-primary" type="submit">Update Password</button>
</form>
