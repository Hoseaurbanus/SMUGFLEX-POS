<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class BrandsController
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

        $brands = $db->fetchAll(
            "SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id = b.id AND is_deleted = 0) as product_count
             FROM brands b WHERE $where ORDER BY b.name ASC",
            $queryParams
        );

        Response::success($brands);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['name'])) {
            Response::error('Brand name is required', 422);
        }

        $db = Database::getInstance();

        if ($db->fetch("SELECT id FROM brands WHERE name = ? AND is_deleted = 0", [$body['name']])) {
            Response::error('Brand name already exists', 409);
        }

        $brandId = $db->insert('brands', [
            'name' => $body['name'],
            'description' => $body['description'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $brand = $db->fetch("SELECT * FROM brands WHERE id = ?", [$brandId]);
        Response::success($brand, 'Brand created', 201);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $brand = $db->fetch("SELECT id FROM brands WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$brand) {
            Response::error('Brand not found', 404);
        }

        if (!empty($body['name'])) {
            $exists = $db->fetch("SELECT id FROM brands WHERE name = ? AND id != ? AND is_deleted = 0", [$body['name'], $id]);
            if ($exists) {
                Response::error('Brand name already exists', 409);
            }
        }

        $updateData = [];
        if (isset($body['name'])) $updateData['name'] = $body['name'];
        if (isset($body['description'])) $updateData['description'] = $body['description'];

        if (empty($updateData)) {
            Response::error('No data to update', 422);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $db->update('brands', $updateData, 'id = ?', [$id]);

        $brand = $db->fetch("SELECT * FROM brands WHERE id = ?", [$id]);
        Response::success($brand, 'Brand updated');
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $brand = $db->fetch("SELECT id FROM brands WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$brand) {
            Response::error('Brand not found', 404);
        }

        $productCount = $db->fetch("SELECT COUNT(*) as cnt FROM products WHERE brand_id = ? AND is_deleted = 0", [$id]);
        if ((int)$productCount['cnt'] > 0) {
            Response::error('Cannot delete brand with products', 409);
        }

        $db->update('brands', ['is_deleted' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Brand deleted');
    }
}
