<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class UnitsController
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

        $units = $db->fetchAll(
            "SELECT u.*, (SELECT COUNT(*) FROM products WHERE unit_id = u.id AND is_deleted = 0) as product_count
             FROM units u WHERE $where ORDER BY u.name ASC",
            $queryParams
        );

        Response::success($units);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['name'])) {
            Response::error('Unit name is required', 422);
        }

        $db = Database::getInstance();

        if ($db->fetch("SELECT id FROM units WHERE name = ? AND is_deleted = 0", [$body['name']])) {
            Response::error('Unit name already exists', 409);
        }

        $unitId = $db->insert('units', [
            'name' => $body['name'],
            'short_name' => $body['short_name'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $unit = $db->fetch("SELECT * FROM units WHERE id = ?", [$unitId]);
        Response::success($unit, 'Unit created', 201);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $unit = $db->fetch("SELECT id FROM units WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$unit) {
            Response::error('Unit not found', 404);
        }

        if (!empty($body['name'])) {
            $exists = $db->fetch("SELECT id FROM units WHERE name = ? AND id != ? AND is_deleted = 0", [$body['name'], $id]);
            if ($exists) {
                Response::error('Unit name already exists', 409);
            }
        }

        $updateData = [];
        if (isset($body['name'])) $updateData['name'] = $body['name'];
        if (isset($body['short_name'])) $updateData['short_name'] = $body['short_name'];

        if (empty($updateData)) {
            Response::error('No data to update', 422);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $db->update('units', $updateData, 'id = ?', [$id]);

        $unit = $db->fetch("SELECT * FROM units WHERE id = ?", [$id]);
        Response::success($unit, 'Unit updated');
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $unit = $db->fetch("SELECT id FROM units WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$unit) {
            Response::error('Unit not found', 404);
        }

        $productCount = $db->fetch("SELECT COUNT(*) as cnt FROM products WHERE unit_id = ? AND is_deleted = 0", [$id]);
        if ((int)$productCount['cnt'] > 0) {
            Response::error('Cannot delete unit with products', 409);
        }

        $db->update('units', ['is_deleted' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Unit deleted');
    }
}
