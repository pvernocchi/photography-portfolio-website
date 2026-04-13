<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ImageProcessor;
use App\Models\Image;
use App\Models\Setting;

class ImageServeController extends Controller
{
    public function thumbnail(string $id): void
    {
        $this->serve((int) $id, 'thumbnails');
    }

    public function display(string $id): void
    {
        $this->serve((int) $id, 'display', true);
    }

    private function serve(int $id, string $folder, bool $watermark = false): void
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $appHost = (string) parse_url((string) app_config('app.url', ''), PHP_URL_HOST);
        $refererHost = (string) parse_url($referer, PHP_URL_HOST);

        if ($refererHost === '' || $appHost === '' || !hash_equals($appHost, $refererHost)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $image = Image::find($id);
        if ($image === null) {
            http_response_code(404);
            return;
        }

        $path = BASE_PATH . '/storage/' . $folder . '/' . (int) $image['category_id'] . '/' . $image['filename'];
        if (!is_file($path)) {
            http_response_code(404);
            return;
        }

        header('Cache-Control: no-store, no-cache');
        header('Content-Type: image/jpeg');
        header('Content-Disposition: inline');
        header('X-Content-Type-Options: nosniff');

        if ($watermark && Setting::get('watermark_enabled', '0') === '1') {
            $resource = imagecreatefromjpeg($path);
            if ($resource !== false) {
                ImageProcessor::addWatermark($resource, [
                    'text' => Setting::get('watermark_text', 'vernocchi.es'),
                    'position' => Setting::get('watermark_position', 'bottom-right'),
                    'opacity' => (int) Setting::get('watermark_opacity', '30'),
                    'font_size' => (int) Setting::get('watermark_font_size', '16'),
                ]);
                imagejpeg($resource, null, 85);
                imagedestroy($resource);
                return;
            }
        }

        readfile($path);
    }
}
