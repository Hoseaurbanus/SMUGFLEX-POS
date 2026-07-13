<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class CategoriesController
{
    public function index(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $where = "is_deleted = 0";
        $queryParams = [];

        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $where .= " AND name LIKE ?";
            $queryParams[] = $search;
        }

        $categories = $db->fetchAll(
            "SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_deleted = 0) as product_count
             FROM categories c WHERE $where ORDER BY c.name ASC",
            $queryParams
        );

        Response::success($categories);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['name'])) {
            Response::error('Category name is required', 422);
        }

        $db = Database::getInstance();

        if ($db->fetch("SELECT id FROM categories WHERE name = ? AND is_deleted = 0", [$body['name']])) {
            Response::error('Category name already exists', 409);
        }

        $categoryId = $db->insert('categories', [
            'name' => $body['name'],
            'description' => $body['description'] ?? null,
            'parent_id' => $body['parent_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $category = $db->fetch("SELECT * FROM categories WHERE id = ?", [$categoryId]);
        Response::success($category, 'Category created', 201);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $category = $db->fetch("SELECT id FROM categories WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$category) {
            Response::error('Category not found', 404);
        }

        if (!empty($body['name'])) {
            $exists = $db->fetch("SELECT id FROM categories WHERE name = ? AND id != ? AND is_deleted = 0", [$body['name'], $id]);
            if ($exists) {
                Response::error('Category name already exists', 409);
            }
        }

        $updateData = [];
        if (isset($body['name'])) $updateData['name'] = $body['name'];
        if (isset($body['description'])) $updateData['description'] = $body['description'];
        if (array_key_exists('parent_id', $body)) $updateData['parent_id'] = $body['parent_id'];

        if (empty($updateData)) {
            Response::error('No data to update', 422);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $db->update('categories', $updateData, 'id = ?', [$id]);

        $category = $db->fetch("SELECT * FROM categories WHERE id = ?", [$id]);
        Response::success($category, 'Category updated');
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $category = $db->fetch("SELECT id FROM categories WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$category) {
            Response::error('Category not found', 404);
        }

        $productCount = $db->fetch("SELECT COUNT(*) as cnt FROM products WHERE category_id = ? AND is_deleted = 0", [$id]);
        if ((int)$productCount['cnt'] > 0) {
            Response::error('Cannot delete category with products', 409);
        }

        $db->update('categories', ['is_deleted' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Category deleted');
    }

    public function tree(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $categories = $db->fetchAll(
            "SELECT id, name, parent_id FROM categories WHERE is_deleted = 0 ORDER BY name ASC"
        );

        $tree = [];
        $map = [];
        foreach ($categories as &$cat) {
            $cat['children'] = [];
            $map[$cat['id']] = &$cat;
        }
        unset($cat);

        foreach ($categories as &$cat) {
            if ($cat['parent_id'] && isset($map[$cat['parent_id']])) {
                $map[$cat['parent_id']]['children'][] = &$cat;
            } else {
                $tree[] = &$cat;
            }
        }
        unset($cat);

        Response::success($tree);
    }
}
