<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class WarehousesController
{
    public function index(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $warehouses = $db->fetchAll(
            "SELECT w.*, (SELECT COUNT(*) FROM products WHERE warehouse_id = w.id AND is_deleted = 0) as product_count
             FROM warehouses w WHERE w.is_deleted = 0 ORDER BY w.name ASC"
        );

        Response::success($warehouses);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['name'])) {
            Response::error('Warehouse name is required', 422);
        }

        $db = Database::getInstance();

        if ($db->fetch("SELECT id FROM warehouses WHERE name = ? AND is_deleted = 0", [$body['name']])) {
            Response::error('Warehouse name already exists', 409);
        }

        $warehouseId = $db->insert('warehouses', [
            'name' => $body['name'],
            'address' => $body['address'] ?? null,
            'phone' => $body['phone'] ?? null,
            'is_active' => $body['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $warehouse = $db->fetch("SELECT * FROM warehouses WHERE id = ?", [$warehouseId]);
        Response::success($warehouse, 'Warehouse created', 201);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $warehouse = $db->fetch("SELECT id FROM warehouses WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$warehouse) {
            Response::error('Warehouse not found', 404);
        }

        if (!empty($body['name'])) {
            $exists = $db->fetch("SELECT id FROM warehouses WHERE name = ? AND id != ? AND is_deleted = 0", [$body['name'], $id]);
            if ($exists) {
                Response::error('Warehouse name already exists', 409);
            }
        }

        $updateData = [];
        if (isset($body['name'])) $updateData['name'] = $body['name'];
        if (isset($body['address'])) $updateData['address'] = $body['address'];
        if (isset($body['phone'])) $updateData['phone'] = $body['phone'];
        if (isset($body['is_active'])) $updateData['is_active'] = $body['is_active'];

        if (empty($updateData)) {
            Response::error('No data to update', 422);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $db->update('warehouses', $updateData, 'id = ?', [$id]);

        $warehouse = $db->fetch("SELECT * FROM warehouses WHERE id = ?", [$id]);
        Response::success($warehouse, 'Warehouse updated');
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $warehouse = $db->fetch("SELECT id FROM warehouses WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$warehouse) {
            Response::error('Warehouse not found', 404);
        }

        $productCount = $db->fetch("SELECT COUNT(*) as cnt FROM products WHERE warehouse_id = ? AND is_deleted = 0", [$id]);
        if ((int)$productCount['cnt'] > 0) {
            Response::error('Cannot delete warehouse with products', 409);
        }

        $db->update('warehouses', ['is_deleted' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Warehouse deleted');
    }
}
