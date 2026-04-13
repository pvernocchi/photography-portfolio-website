<?php declare(strict_types=1); ?>
<div class="contact-page">
    <header class="page-header">
        <h1><?= e(__('contact.title')) ?></h1>
        <p class="page-subtitle"><?= e(__('contact.subtitle')) ?></p>
    </header>

    <div class="contact-container">
        <form class="contact-form" method="post" action="/contact/send" id="contact-form">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            
            <!-- Honeypot field for spam protection -->
            <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off">
            
            <div class="form-group">
                <label for="name" class="form-label"><?= e(__('contact.name')) ?></label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-input" 
                    required
                    autocomplete="name"
                >
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label"><?= e(__('contact.email')) ?></label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input" 
                    required
                    autocomplete="email"
                >
            </div>
            
            <div class="form-group">
                <label for="message" class="form-label"><?= e(__('contact.message')) ?></label>
                <textarea 
                    id="message" 
                    name="message" 
                    class="form-textarea" 
                    rows="6" 
                    required
                ></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-front btn-primary">
                    <?= e(__('contact.submit')) ?>
                </button>
            </div>
        </form>
        
        <div class="contact-info">
            <div class="card-front">
                <h2><?= e($siteTitle ?? 'Contact Information') ?></h2>
                <div class="contact-details">
                    <?php if (!empty($contactEmail)): ?>
                    <p class="contact-detail">
                        <svg class="contact-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>
                    </p>
                    <?php endif; ?>
                    
                    <p class="contact-description">
                        <?= e($siteDescription ?? '') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div id="contact-status" class="contact-status" hidden></div>
</div>
