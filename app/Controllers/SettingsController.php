<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\CSRF;
use App\Core\Controller;
use App\Core\Session;
use App\Core\ThemeEngine;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function index(): void
    {
        $this->render('admin/settings/index', [
            'title' => 'Settings',
            'settings' => Setting::getAll(),
            'themes' => ThemeEngine::themes(),
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
        ]);
    }

    public function updateGeneral(): void
    {
        $this->guardCsrf('/admin/settings');
        $this->saveMany(['site_title', 'site_description_es', 'site_description_en', 'default_language', 'contact_email', 'turnstile_site_key', 'turnstile_secret_key']);
        $this->done('General settings updated.');
    }

    public function updateTheme(): void
    {
        $this->guardCsrf('/admin/settings');
        $theme = trim((string) ($_POST['active_theme'] ?? 'minimal-light'));
        if (!ThemeEngine::exists($theme)) {
            Session::flash('error', 'Invalid theme.');
            $this->redirect('/admin/settings');
        }

        Setting::set('active_theme', $theme);
        $this->done('Theme updated.');
    }

    public function updateAbout(): void
    {
        $this->guardCsrf('/admin/settings');
        $this->saveMany(['about_content_es', 'about_content_en', 'about_photo']);
        $this->done('About settings updated.');
    }

    public function updateWatermark(): void
    {
        $this->guardCsrf('/admin/settings');
        Setting::set('watermark_enabled', isset($_POST['watermark_enabled']) ? '1' : '0');
        $this->saveMany(['watermark_text', 'watermark_position', 'watermark_opacity', 'watermark_font_size']);
        $this->done('Watermark settings updated.');
    }

    public function updateAnalytics(): void
    {
        $this->guardCsrf('/admin/settings');
        $this->saveMany(['google_analytics_id']);
        $this->done('Analytics settings updated.');
    }

    public function updateSeo(): void
    {
        $this->guardCsrf('/admin/settings');
        $this->saveMany(['meta_title_es', 'meta_title_en', 'meta_description_es', 'meta_description_en', 'og_image']);
        $this->done('SEO settings updated.');
    }

    private function saveMany(array $keys): void
    {
        foreach ($keys as $key) {
            Setting::set($key, trim((string) ($_POST[$key] ?? '')));
        }
    }

    private function done(string $message): void
    {
        Session::flash('success', $message);
        $this->redirect('/admin/settings');
    }

    private function guardCsrf(string $redirect): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token.');
            $this->redirect($redirect);
        }
    }
}
