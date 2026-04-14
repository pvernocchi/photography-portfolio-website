<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Language;
use App\Core\Mailer;
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
        $locale = Language::locale();
        $siteTitle = (string) Setting::get('site_title', 'Vernocchi Photography');
        $contactEmail = (string) Setting::get('contact_email', '');
        $siteDescription = (string) Setting::get('site_description_' . $locale, '');
        $turnstileSiteKey = trim((string) Setting::get('turnstile_site_key', (string) app_config('turnstile.site_key', '')));

        $this->render('frontend/contact', [
            'title' => __('contact.title'),
            'siteTitle' => $siteTitle,
            'contactEmail' => $contactEmail,
            'siteDescription' => $siteDescription,
            'locale' => $locale,
            'theme' => ThemeEngine::activeTheme(),
            'metaDescription' => $this->metaDescription(),
            'gaId' => (string) Setting::get('google_analytics_id', ''),
            'csrfToken' => \App\Core\CSRF::token(),
            'turnstileSiteKey' => $turnstileSiteKey,
            'pageScripts' => ['/assets/js/contact-form.js'],
        ], 'frontend');
    }

    public function sendContact(): void
    {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !\App\Core\CSRF::verify((string) $_POST['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        // Honeypot check
        if (!empty($_POST['website'])) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => __('contact.success')]);
            return;
        }

        $turnstileSecretKey = trim((string) Setting::get('turnstile_secret_key', (string) app_config('turnstile.secret_key', '')));
        $turnstileToken = trim((string) ($_POST['cf-turnstile-response'] ?? ''));
        $remoteIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if ($turnstileSecretKey !== '') {
            if ($turnstileToken === '' || !$this->verifyTurnstileToken($turnstileToken, $turnstileSecretKey, $remoteIp)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => __('contact.captcha_error')]);
                return;
            }
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        // Basic validation
        if ($name === '' || $email === '' || $message === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => __('contact.error')]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => __('contact.error')]);
            return;
        }

        // Sanitize inputs to prevent email header injection
        $name = str_replace(["\r", "\n", "%0a", "%0d"], '', $name);
        $email = str_replace(["\r", "\n", "%0a", "%0d"], '', $email);
        $message = str_replace(["\r\n", "\r"], "\n", $message);

        $contactEmail = (string) Setting::get('contact_email', '');
        $siteTitle = (string) Setting::get('site_title', 'Photography Portfolio');
        
        if ($contactEmail === '') {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => __('contact.error')]);
            return;
        }

        // Prepare email with safe headers
        // Additional sanitization for subject line to prevent injection
        $safeName = mb_substr($name, 0, 50);
        $subject = str_replace(["\r", "\n"], '', 'Contact form: ' . $safeName);
        $body = "Contact Form Submission\n";
        $body .= "========================\n\n";
        $body .= "Name: {$name}\n";
        $body .= "Email: {$email}\n\n";
        $body .= "Message:\n{$message}\n\n";
        $body .= "Sent from: {$siteTitle}";
        
        $sent = Mailer::send(
            $contactEmail,
            $subject,
            $body,
            $email
        );

        header('Content-Type: application/json');
        if ($sent) {
            echo json_encode(['success' => true, 'message' => __('contact.success')]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => __('contact.error')]);
        }
    }

    private function verifyTurnstileToken(string $token, string $secretKey, string $remoteIp): bool
    {
        $payload = http_build_query([
            'secret' => $secretKey,
            'response' => $token,
            'remoteip' => $remoteIp,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 5,
            ],
        ]);

        try {
            $response = file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
        } catch (\Throwable $throwable) {
            error_log('Turnstile verification failed: ' . $throwable->getMessage());
            return false;
        }

        if ($response === false) {
            return false;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) && ($decoded['success'] ?? false) === true;
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
