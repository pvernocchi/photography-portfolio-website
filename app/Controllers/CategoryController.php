<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\CSRF;
use App\Core\Controller;
use App\Core\ImageProcessor;
use App\Core\Session;
use App\Models\Category;
use App\Models\Image;

class CategoryController extends Controller
{
    public function index(): void
    {
        $this->render('admin/categories/index', [
            'title' => 'Categories',
            'categories' => Category::all(),
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/categories/create', [
            'title' => 'Create Category',
            'error' => Session::flash('error'),
        ]);
    }

    public function store(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token.');
            $this->redirect('/admin/categories/create');
        }

        $payload = $this->payload();
        if ($payload['name_es'] === '' || $payload['name_en'] === '' || $payload['slug'] === '') {
            Session::flash('error', 'Name and slug are required.');
            $this->redirect('/admin/categories/create');
        }
        if (Category::slugExists($payload['slug'])) {
            Session::flash('error', 'Slug already in use.');
            $this->redirect('/admin/categories/create');
        }

        Category::create($payload);
        Session::flash('success', 'Category created.');
        $this->redirect('/admin/categories');
    }

    public function edit(string $id): void
    {
        $category = Category::find((int) $id);
        if ($category === null) {
            Session::flash('error', 'Category not found.');
            $this->redirect('/admin/categories');
        }

        $this->render('admin/categories/edit', [
            'title' => 'Edit Category',
            'category' => $category,
            'images' => Image::byCategory((int) $id),
            'error' => Session::flash('error'),
        ]);
    }

    public function update(string $id): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token.');
            $this->redirect('/admin/categories/' . (int) $id . '/edit');
        }

        $categoryId = (int) $id;
        $payload = $this->payload();
        if ($payload['name_es'] === '' || $payload['name_en'] === '' || $payload['slug'] === '') {
            Session::flash('error', 'Name and slug are required.');
            $this->redirect('/admin/categories/' . $categoryId . '/edit');
        }
        if (Category::slugExists($payload['slug'], $categoryId)) {
            Session::flash('error', 'Slug already in use.');
            $this->redirect('/admin/categories/' . $categoryId . '/edit');
        }

        Category::update($categoryId, $payload + ['cover_image_id' => (int) ($_POST['cover_image_id'] ?? 0)]);
        Session::flash('success', 'Category updated.');
        $this->redirect('/admin/categories');
    }

    public function delete(string $id): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token.');
            $this->redirect('/admin/categories');
        }

        $categoryId = (int) $id;
        foreach (Image::byCategory($categoryId) as $image) {
            ImageProcessor::deleteImages((string) $image['filename'], $categoryId);
        }

        Category::delete($categoryId);
        Session::flash('success', 'Category deleted.');
        $this->redirect('/admin/categories');
    }

    public function reorder(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(422);
            echo json_encode(['ok' => false]);
            return;
        }

        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }

        Category::reorder(array_map('intval', $ids));
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    private function payload(): array
    {
        return [
            'name_es' => trim((string) ($_POST['name_es'] ?? '')),
            'name_en' => trim((string) ($_POST['name_en'] ?? '')),
            'slug' => $this->slugify((string) ($_POST['slug'] ?? $_POST['name_en'] ?? '')),
            'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
        ];
    }

    private function slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?: '';
        return trim($slug, '-') ?: 'category-' . time();
    }
}
