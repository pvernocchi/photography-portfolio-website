<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;

class SitemapController extends Controller
{
    public function index(): void
    {
        $base = rtrim((string) app_config('app.url', ''), '/');
        $urls = [
            ['loc' => $base . '/', 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => $base . '/gallery', 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => $base . '/about', 'changefreq' => 'monthly', 'priority' => '0.6'],
        ];

        foreach (Category::visible() as $category) {
            $urls[] = [
                'loc' => $base . '/gallery/' . $category['slug'],
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        header('Content-Type: application/xml; charset=UTF-8');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $item) {
            echo '<url>';
            echo '<loc>' . e($item['loc']) . '</loc>';
            echo '<lastmod>' . date('c') . '</lastmod>';
            echo '<changefreq>' . $item['changefreq'] . '</changefreq>';
            echo '<priority>' . $item['priority'] . '</priority>';
            echo '</url>';
        }
        echo '</urlset>';
    }
}
