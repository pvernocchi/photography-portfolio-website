<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\CSRF;
use App\Core\Controller;
use App\Core\Language;
use App\Core\Session;
use App\Core\ThemeEngine;
use App\Models\Category;
use App\Models\Image;
use App\Models\Setting;

class FrontendController extends Controller
{
    public function home(): void
    {
        $this->render('frontend/home', [
            'title' => $this->metaTitle(),
            'categories' => array_slice(Category::visible(), 0, 6),
            'locale' => Language::locale(),
            'theme' => ThemeEngine::activeTheme(),
            'metaDescription' => $this->metaDescription(),
            'gaId' => (string) Setting::get('google_analytics_id', ''),
        ], 'frontend');
    }

    public function gallery(): void
    {
        $this->render('frontend/gallery/index', [
            'title' => __('gallery.title'),
            'categories' => Category::visible(),
            'locale' => Language::locale(),
            'theme' => ThemeEngine::activeTheme(),
            'metaDescription' => $this->metaDescription(),
            'gaId' => (string) Setting::get('google_analytics_id', ''),
        ], 'frontend');
    }

    public function category(string $slug): void
    {
        $category = Category::findBySlug($slug);
        if ($category === null) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $this->render('frontend/gallery/category', [
            'title' => $this->localizedName($category['name_es'] ?? '', $category['name_en'] ?? ''),
            'category' => $category,
            'images' => Image::byCategory((int) $category['id']),
            'locale' => Language::locale(),
            'theme' => ThemeEngine::activeTheme(),
            'metaDescription' => $this->metaDescription(),
            'gaId' => (string) Setting::get('google_analytics_id', ''),
        ], 'frontend');
    }

    public function about(): void
    {
        $locale = Language::locale();
        $content = (string) Setting::get('about_content_' . $locale, '<p>Placeholder about content.</p>');

        $this->render('frontend/about', [
            'title' => __('about.title'),
            'aboutContent' => $content,
            'locale' => $locale,
            'theme' => ThemeEngine::activeTheme(),
            'metaDescription' => $this->metaDescription(),
            'gaId' => (string) Setting::get('google_analytics_id', ''),
        ], 'frontend');
    }

    public function contact(): void
    {
        $this->render('frontend/contact', [
            'title' => __('contact.title'),
            'locale' => Language::locale(),
            'theme' => ThemeEngine::activeTheme(),
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
            'metaDescription' => $this->metaDescription(),
            'gaId' => (string) Setting::get('google_analytics_id', ''),
        ], 'frontend');
    }

    public function sendContact(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', __('contact.error'));
            $this->redirect('/contact');
        }

        if (trim((string) ($_POST['company'] ?? '')) !== '') {
            Session::flash('success', __('contact.success'));
            $this->redirect('/contact');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || $message === '') {
            Session::flash('error', __('contact.error'));
            $this->redirect('/contact');
        }

        $recipient = (string) Setting::get('contact_email', 'admin@vernocchi.es');
        $headers = [
            'From: ' . $recipient,
            'Reply-To: ' . $email,
            'Content-Type: text/plain; charset=UTF-8',
        ];

        $body = "Name: {$name}\nEmail: {$email}\n\n{$message}";
        $ok = mail($recipient, $subject, $body, implode("\r\n", $headers));

        Session::flash($ok ? 'success' : 'error', $ok ? __('contact.success') : __('contact.error'));
        $this->redirect('/contact');
    }

    public function switchLanguage(string $locale): void
    {
        Language::setLocale($locale);
        $back = (string) ($_SERVER['HTTP_REFERER'] ?? '/');
        $host = (string) parse_url((string) app_config('app.url', ''), PHP_URL_HOST);
        $backHost = (string) parse_url($back, PHP_URL_HOST);
        if ($backHost !== '' && $host !== '' && !hash_equals($host, $backHost)) {
            $back = '/';
        }

        $this->redirect(parse_url($back, PHP_URL_PATH) ?: '/');
    }

    public function themeCss(string $type = 'style'): void
    {
        $path = ThemeEngine::cssPath($type === 'dark' ? 'dark' : 'style');
        if ($path === null) {
            http_response_code(404);
            return;
        }

        header('Content-Type: text/css; charset=UTF-8');
        readfile($path);
    }

    private function metaTitle(): string
    {
        $locale = Language::locale();
        return (string) Setting::get('meta_title_' . $locale, (string) Setting::get('site_title', 'Vernocchi Photography'));
    }

    private function metaDescription(): string
    {
        $locale = Language::locale();
        return (string) Setting::get('meta_description_' . $locale, '');
    }

    private function localizedName(string $es, string $en): string
    {
        return Language::locale() === 'en' && $en !== '' ? $en : $es;
    }
}
