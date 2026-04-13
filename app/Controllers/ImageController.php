<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\CSRF;
use App\Core\Controller;
use App\Core\ImageProcessor;
use App\Core\Session;
use App\Models\Category;
use App\Models\Image;

class ImageController extends Controller
{
    public function index(string $id): void
    {
        $category = Category::find((int) $id);
        if ($category === null) {
            Session::flash('error', 'Category not found.');
            $this->redirect('/admin/categories');
        }

        $this->render('admin/images/index', [
            'title' => 'Images',
            'category' => $category,
            'images' => Image::byCategory((int) $id),
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
        ]);
    }

    public function showUpload(string $id): void
    {
        $category = Category::find((int) $id);
        if ($category === null) {
            $this->redirect('/admin/categories');
        }

        $this->render('admin/images/upload', [
            'title' => 'Upload Images',
            'category' => $category,
            'error' => Session::flash('error'),
        ]);
    }

    public function upload(string $id): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token.');
            $this->redirect('/admin/categories/' . (int) $id . '/images/upload');
        }

        $categoryId = (int) $id;
        $files = $_FILES['images'] ?? null;
        if (!is_array($files) || !isset($files['name']) || !is_array($files['name'])) {
            Session::flash('error', 'Please select at least one JPG file.');
            $this->redirect('/admin/categories/' . $categoryId . '/images/upload');
        }

        foreach ($files['name'] as $index => $originalName) {
            $tmpFile = (string) ($files['tmp_name'][$index] ?? '');
            $size = (int) ($files['size'][$index] ?? 0);
            $error = (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);

            if ($error !== UPLOAD_ERR_OK || $tmpFile === '' || $size < 1) {
                continue;
            }
            if ($size > 20 * 1024 * 1024) {
                continue;
            }

            $safeName = preg_replace('/[^a-z0-9\-]/', '-', strtolower(pathinfo((string) $originalName, PATHINFO_FILENAME))) ?: 'image';
            $filename = $safeName . '-' . bin2hex(random_bytes(6)) . '.jpg';

            try {
                $meta = ImageProcessor::processUpload($tmpFile, $filename, $categoryId);
                Image::create([
                    'category_id' => $categoryId,
                    'filename' => $filename,
                    'original_filename' => (string) $originalName,
                    'width' => $meta['width'],
                    'height' => $meta['height'],
                    'file_size' => $meta['file_size'],
                ]);
            } catch (\Throwable) {
                continue;
            }
        }

        Session::flash('success', 'Upload completed.');
        $this->redirect('/admin/categories/' . $categoryId . '/images');
    }

    public function edit(string $id): void
    {
        $image = Image::find((int) $id);
        if ($image === null) {
            Session::flash('error', 'Image not found.');
            $this->redirect('/admin/categories');
        }

        $this->render('admin/images/edit', [
            'title' => 'Edit Image',
            'image' => $image,
            'error' => Session::flash('error'),
        ]);
    }

    public function update(string $id): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token.');
            $this->redirect('/admin/images/' . (int) $id . '/edit');
        }

        $imageId = (int) $id;
        $image = Image::find($imageId);
        if ($image === null) {
            $this->redirect('/admin/categories');
        }

        Image::updateMeta($imageId, [
            'title_es' => trim((string) ($_POST['title_es'] ?? '')),
            'title_en' => trim((string) ($_POST['title_en'] ?? '')),
            'alt_es' => trim((string) ($_POST['alt_es'] ?? '')),
            'alt_en' => trim((string) ($_POST['alt_en'] ?? '')),
        ]);

        Session::flash('success', 'Image metadata updated.');
        $this->redirect('/admin/categories/' . (int) $image['category_id'] . '/images');
    }

    public function delete(string $id): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token.');
            $this->redirect('/admin/categories');
        }

        $image = Image::find((int) $id);
        if ($image === null) {
            $this->redirect('/admin/categories');
        }

        ImageProcessor::deleteImages((string) $image['filename'], (int) $image['category_id']);
        Image::delete((int) $id);

        Session::flash('success', 'Image deleted.');
        $this->redirect('/admin/categories/' . (int) $image['category_id'] . '/images');
    }

    public function reorder(string $id): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(422);
            echo json_encode(['ok' => false]);
            return;
        }

        $ids = $_POST['ids'] ?? [];
        Image::reorder((int) $id, is_array($ids) ? array_map('intval', $ids) : []);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    public function setCover(string $id): void
    {
        header('Content-Type: application/json');
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(422);
            echo json_encode(['ok' => false]);
            return;
        }

        $categoryId = (int) $id;
        $imageId = (int) ($_POST['image_id'] ?? 0);
        $category = Category::find($categoryId);
        $image = Image::find($imageId);
        if ($category === null) {
            http_response_code(404);
            echo json_encode(['ok' => false]);
            return;
        }
        if ($image === null || (int) $image['category_id'] !== $categoryId) {
            http_response_code(422);
            echo json_encode(['ok' => false]);
            return;
        }

        Category::update($categoryId, array_merge($category, ['cover_image_id' => $imageId]));
        echo json_encode(['ok' => true]);
    }
}
