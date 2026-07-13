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
            "SELECT w.*, b.name as branch_name,
                    (SELECT COUNT(*) FROM product_stocks WHERE warehouse_id = w.id AND quantity > 0) as product_count
             FROM warehouses w
             LEFT JOIN branches b ON b.id = w.branch_id
             ORDER BY w.name ASC"
        );

        Response::success($warehouses);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['name']) || empty($body['code'])) {
            Response::error('Warehouse name and code are required', 422);
        }

        $db = Database::getInstance();

        if ($db->fetch("SELECT id FROM warehouses WHERE code = ?", [$body['code']])) {
            Response::error('Warehouse code already exists', 409);
        }

        $warehouseId = $db->insert('warehouses', [
            'name' => $body['name'],
            'code' => $body['code'],
            'branch_id' => $body['branch_id'] ?? null,
            'address' => $body['address'] ?? null,
            'is_active' => $body['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $warehouse = $db->fetch("SELECT w.*, b.name as branch_name FROM warehouses w LEFT JOIN branches b ON b.id = w.branch_id WHERE w.id = ?", [$warehouseId]);
        Response::success($warehouse, 'Warehouse created', 201);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $warehouse = $db->fetch("SELECT id FROM warehouses WHERE id = ?", [$id]);
        if (!$warehouse) {
            Response::error('Warehouse not found', 404);
        }

        if (!empty($body['code'])) {
            $exists = $db->fetch("SELECT id FROM warehouses WHERE code = ? AND id != ?", [$body['code'], $id]);
            if ($exists) {
                Response::error('Warehouse code already exists', 409);
            }
        }

        $updateData = [];
        if (isset($body['name'])) $updateData['name'] = $body['name'];
        if (isset($body['code'])) $updateData['code'] = $body['code'];
        if (array_key_exists('branch_id', $body)) $updateData['branch_id'] = $body['branch_id'];
        if (isset($body['address'])) $updateData['address'] = $body['address'];
        if (isset($body['is_active'])) $updateData['is_active'] = $body['is_active'];

        if (empty($updateData)) {
            Response::error('No data to update', 422);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $db->update('warehouses', $updateData, 'id = ?', [$id]);

        $warehouse = $db->fetch("SELECT w.*, b.name as branch_name FROM warehouses w LEFT JOIN branches b ON b.id = w.branch_id WHERE w.id = ?", [$id]);
        Response::success($warehouse, 'Warehouse updated');
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $warehouse = $db->fetch("SELECT id FROM warehouses WHERE id = ?", [$id]);
        if (!$warehouse) {
            Response::error('Warehouse not found', 404);
        }

        $stockCount = $db->fetch("SELECT COUNT(*) as cnt FROM product_stocks WHERE warehouse_id = ? AND quantity > 0", [$id]);
        if ((int)$stockCount['cnt'] > 0) {
            Response::error('Cannot delete warehouse with stock', 409);
        }

        $db->delete('product_stocks', 'warehouse_id = ?', [$id]);
        $db->delete('warehouses', 'id = ?', [$id]);
        Response::success(null, 'Warehouse deleted');
    }
}
