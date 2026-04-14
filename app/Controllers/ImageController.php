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
    public function library(): void
    {
        $this->render('admin/images/library', [
            'title' => 'Image Library',
            'images' => Image::all(),
            'categories' => Category::all(),
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
        ]);
    }

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

    public function showUpload(): void
    {
        $this->render('admin/images/upload', [
            'title' => 'Upload Images',
            'categories' => Category::all(),
            'error' => Session::flash('error'),
        ]);
    }

    public function upload(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token.');
            $this->redirect('/admin/images/upload');
        }

        $files = $_FILES['images'] ?? null;
        if (!is_array($files) || !isset($files['name']) || !is_array($files['name'])) {
            Session::flash('error', 'Please select at least one JPG file.');
            $this->redirect('/admin/images/upload');
        }

        $categoryIds = array_map('intval', (array) ($_POST['categories'] ?? []));
        $uploadedIds = [];

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
                $meta = ImageProcessor::processUpload($tmpFile, $filename);
                $imageId = Image::create([
                    'filename' => $filename,
                    'original_filename' => (string) $originalName,
                    'width' => $meta['width'],
                    'height' => $meta['height'],
                    'file_size' => $meta['file_size'],
                ]);

                if ($imageId > 0 && $categoryIds !== []) {
                    Image::assignCategories($imageId, $categoryIds);
                }

                $uploadedIds[] = $imageId;
            } catch (\Throwable) {
                continue;
            }
        }

        if ($uploadedIds === []) {
            Session::flash('error', 'No images were uploaded.');
            $this->redirect('/admin/images/upload');
        }

        Session::put('last_uploaded_ids', $uploadedIds);
        Session::flash('success', count($uploadedIds) . ' image(s) uploaded.');
        $this->redirect('/admin/images/assign');
    }

    public function showAssign(): void
    {
        $uploadedIds = Session::get('last_uploaded_ids', []);
        $images = [];
        foreach ($uploadedIds as $id) {
            $img = Image::find((int) $id);
            if ($img !== null) {
                $img['assigned_categories'] = Image::categoryIdsForImage((int) $id);
                $images[] = $img;
            }
        }

        $this->render('admin/images/assign', [
            'title' => 'Assign Galleries',
            'images' => $images,
            'categories' => Category::all(),
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
        ]);
    }

    public function saveAssign(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token.');
            $this->redirect('/admin/images/assign');
        }

        $assignments = (array) ($_POST['assignments'] ?? []);
        foreach ($assignments as $imageId => $catIds) {
            $imageId = (int) $imageId;
            if (Image::find($imageId) === null) {
                continue;
            }
            $catIds = array_map('intval', is_array($catIds) ? $catIds : []);
            Image::assignCategories($imageId, $catIds);
        }

        Session::forget('last_uploaded_ids');
        Session::flash('success', 'Gallery assignments saved.');
        $this->redirect('/admin/images');
    }

    public function edit(string $id): void
    {
        $image = Image::find((int) $id);
        if ($image === null) {
            Session::flash('error', 'Image not found.');
            $this->redirect('/admin/images');
        }

        $this->render('admin/images/edit', [
            'title' => 'Edit Image',
            'image' => $image,
            'categories' => Category::all(),
            'assignedCategories' => Image::categoryIdsForImage((int) $id),
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
            $this->redirect('/admin/images');
        }

        Image::updateMeta($imageId, [
            'title_es' => trim((string) ($_POST['title_es'] ?? '')),
            'title_en' => trim((string) ($_POST['title_en'] ?? '')),
            'alt_es' => trim((string) ($_POST['alt_es'] ?? '')),
            'alt_en' => trim((string) ($_POST['alt_en'] ?? '')),
        ]);

        $categoryIds = array_map('intval', (array) ($_POST['categories'] ?? []));
        Image::assignCategories($imageId, $categoryIds);

        Session::flash('success', 'Image updated.');
        $this->redirect('/admin/images');
    }

    public function delete(string $id): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token.');
            $this->redirect('/admin/images');
        }

        $image = Image::find((int) $id);
        if ($image === null) {
            $this->redirect('/admin/images');
        }

        ImageProcessor::deleteImages((string) $image['filename']);
        Image::delete((int) $id);

        Session::flash('success', 'Image deleted.');
        $this->redirect('/admin/images');
    }

    public function bulkAction(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token.');
            $this->redirect('/admin/images');
        }

        $action = (string) ($_POST['action'] ?? '');
        $rawIds = (array) ($_POST['ids'] ?? []);
        $ids = array_values(array_filter(array_map('intval', $rawIds), static fn(int $id) => $id > 0));
        $returnTo = (string) ($_POST['return_to'] ?? '/admin/images');

        // Allow only known safe admin return paths (no traversal, no query strings).
        $allowedPaths = ['/admin/images'];
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        if ($categoryId > 0) {
            $allowedPaths[] = '/admin/categories/' . $categoryId . '/images';
        }
        if (!in_array($returnTo, $allowedPaths, true)) {
            $returnTo = '/admin/images';
        }

        if ($ids === []) {
            Session::flash('error', 'No images selected.');
            $this->redirect($returnTo);
        }

        if ($action === 'delete') {
            $images = Image::findMany($ids);
            foreach ($images as $image) {
                ImageProcessor::deleteImages((string) $image['filename']);
            }
            Image::deleteMany($ids);
            Session::flash('success', count($images) . ' image(s) deleted.');
        } elseif ($action === 'remove_from_category') {
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            if ($categoryId < 1) {
                Session::flash('error', 'Invalid category.');
                $this->redirect($returnTo);
            }
            Image::removeFromCategory($categoryId, $ids);
            Session::flash('success', count($ids) . ' image(s) removed from gallery.');
        } else {
            Session::flash('error', 'Unknown action.');
            $this->redirect($returnTo);
        }

        $this->redirect($returnTo);
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
        if ($image === null) {
            http_response_code(422);
            echo json_encode(['ok' => false]);
            return;
        }

        Category::update($categoryId, array_merge($category, ['cover_image_id' => $imageId]));
        echo json_encode(['ok' => true]);
    }
}
