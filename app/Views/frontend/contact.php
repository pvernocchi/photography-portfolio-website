<?php declare(strict_types=1); use App\Core\CSRF; ?>
<section class="card-front">
    <h1><?= e(__('contact.title')) ?></h1>
    <?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form class="form-stack" method="post" action="/contact">
        <?= CSRF::field() ?>
        <input type="text" name="company" tabindex="-1" autocomplete="off" class="hp-field" aria-hidden="true">
        <label><?= e(__('contact.name')) ?><input type="text" name="name" required></label>
        <label><?= e(__('contact.email')) ?><input type="email" name="email" required></label>
        <label><?= e(__('contact.subject')) ?><input type="text" name="subject" required></label>
        <label><?= e(__('contact.message')) ?><textarea name="message" rows="6" required></textarea></label>
        <button class="btn-front" type="submit"><?= e(__('contact.send')) ?></button>
    </form>
</section>
