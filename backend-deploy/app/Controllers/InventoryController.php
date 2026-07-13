<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class InventoryController
{
    public function index(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = max(1, min(100, (int)($params['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = "p.deleted_at IS NULL";
        $queryParams = [];

        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $where .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
            $queryParams = array_merge($queryParams, [$search, $search]);
        }
        if (!empty($params['warehouse_id'])) {
            $where .= " AND ps.warehouse_id = ?";
            $queryParams[] = $params['warehouse_id'];
        }
        if (!empty($params['category_id'])) {
            $where .= " AND p.category_id = ?";
            $queryParams[] = $params['category_id'];
        }
        if (isset($params['low_stock']) && $params['low_stock'] === '1') {
            $where .= " AND ps.quantity <= p.minimum_stock";
        }

        $total = $db->fetch("SELECT COUNT(DISTINCT p.id) as cnt FROM products p LEFT JOIN product_stocks ps ON ps.product_id = p.id WHERE $where", $queryParams)['cnt'];

        $products = $db->fetchAll(
            "SELECT p.*, c.name as category_name, w.name as warehouse_name, ps.warehouse_id,
                    COALESCE(ps.quantity, 0) as stock_quantity
             FROM products p
             LEFT JOIN product_stocks ps ON ps.product_id = p.id
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN warehouses w ON w.id = ps.warehouse_id
             WHERE $where
             ORDER BY p.name ASC
             LIMIT $perPage OFFSET $offset",
            $queryParams
        );

        Response::paginated($products, (int)$total, $page, $perPage);
    }

    public function adjust(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['product_id']) || !isset($body['quantity_adjustment']) || empty($body['warehouse_id'])) {
            Response::error('Product ID, warehouse ID and quantity adjustment are required', 422);
        }

        $db = Database::getInstance();

        $product = $db->fetch("SELECT id FROM products WHERE id = ? AND deleted_at IS NULL", [$body['product_id']]);
        if (!$product) {
            Response::error('Product not found', 404);
        }

        $warehouse = $db->fetch("SELECT id FROM warehouses WHERE id = ?", [$body['warehouse_id']]);
        if (!$warehouse) {
            Response::error('Warehouse not found', 404);
        }

        $adjustment = (int)$body['quantity_adjustment'];

        $existingStock = $db->fetch(
            "SELECT id, quantity FROM product_stocks WHERE product_id = ? AND warehouse_id = ? AND variant_id IS NULL",
            [$body['product_id'], $body['warehouse_id']]
        );

        if ($existingStock) {
            $newQuantity = (int)$existingStock['quantity'] + $adjustment;
            if ($newQuantity < 0) {
                Response::error('Adjustment would result in negative stock', 400);
            }
            $db->update('product_stocks', [
                'quantity' => $newQuantity,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$existingStock['id']]);
        } else {
            if ($adjustment < 0) {
                Response::error('Adjustment would result in negative stock', 400);
            }
            $db->insert('product_stocks', [
                'product_id' => $body['product_id'],
                'variant_id' => null,
                'warehouse_id' => $body['warehouse_id'],
                'quantity' => $adjustment,
                'reserved_quantity' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $newQuantity = $adjustment;
        }

        $db->insert('stock_movements', [
            'product_id' => $body['product_id'],
            'variant_id' => null,
            'warehouse_id' => $body['warehouse_id'],
            'type' => 'adjustment',
            'quantity' => $adjustment,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => $body['reason'] ?? 'Manual adjustment',
            'user_id' => get_user_id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Response::success(['stock_quantity' => $newQuantity], 'Stock adjusted');
    }

    public function transfer(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['product_id']) || empty($body['from_warehouse_id']) || empty($body['to_warehouse_id']) || empty($body['quantity'])) {
            Response::error('Product, warehouses, and quantity are required', 422);
        }

        if ($body['from_warehouse_id'] === $body['to_warehouse_id']) {
            Response::error('Source and destination warehouses must be different', 400);
        }

        $db = Database::getInstance();

        $product = $db->fetch("SELECT id FROM products WHERE id = ? AND deleted_at IS NULL", [$body['product_id']]);
        if (!$product) {
            Response::error('Product not found', 404);
        }

        $quantity = (int)$body['quantity'];

        $fromStock = $db->fetch(
            "SELECT id, quantity FROM product_stocks WHERE product_id = ? AND warehouse_id = ? AND variant_id IS NULL",
            [$body['product_id'], $body['from_warehouse_id']]
        );

        if (!$fromStock || (int)$fromStock['quantity'] < $quantity) {
            Response::error('Insufficient stock in source warehouse', 400);
        }

        $db->update('product_stocks', [
            'quantity' => (int)$fromStock['quantity'] - $quantity,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$fromStock['id']]);

        $toStock = $db->fetch(
            "SELECT id, quantity FROM product_stocks WHERE product_id = ? AND warehouse_id = ? AND variant_id IS NULL",
            [$body['product_id'], $body['to_warehouse_id']]
        );

        if ($toStock) {
            $db->update('product_stocks', [
                'quantity' => (int)$toStock['quantity'] + $quantity,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$toStock['id']]);
        } else {
            $db->insert('product_stocks', [
                'product_id' => $body['product_id'],
                'variant_id' => null,
                'warehouse_id' => $body['to_warehouse_id'],
                'quantity' => $quantity,
                'reserved_quantity' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $db->insert('stock_movements', [
            'product_id' => $body['product_id'],
            'variant_id' => null,
            'warehouse_id' => $body['from_warehouse_id'],
            'type' => 'transfer',
            'quantity' => -$quantity,
            'reference_type' => null,
            'reference_id' => $body['to_warehouse_id'],
            'notes' => "Transfer to warehouse #{$body['to_warehouse_id']}",
            'user_id' => get_user_id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Response::success(null, 'Transfer completed');
    }

    public function lowStock(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $products = $db->fetchAll(
            "SELECT p.id, p.name, p.sku, p.minimum_stock, c.name as category_name,
                    (SELECT COALESCE(SUM(ps.quantity), 0) FROM product_stocks ps WHERE ps.product_id = p.id) as stock_quantity
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.deleted_at IS NULL AND p.status = 'active'
             HAVING stock_quantity <= p.minimum_stock
             ORDER BY (stock_quantity / NULLIF(p.minimum_stock, 0)) ASC"
        );

        Response::success($products);
    }

    public function stockMovements(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = max(1, min(100, (int)($params['per_page'] ?? 50)));
        $offset = ($page - 1) * $perPage;

        $where = "1=1";
        $queryParams = [];

        if (!empty($params['product_id'])) {
            $where .= " AND sm.product_id = ?";
            $queryParams[] = $params['product_id'];
        }
        if (!empty($params['warehouse_id'])) {
            $where .= " AND sm.warehouse_id = ?";
            $queryParams[] = $params['warehouse_id'];
        }
        if (!empty($params['type'])) {
            $where .= " AND sm.type = ?";
            $queryParams[] = $params['type'];
        }
        if (!empty($params['date_from'])) {
            $where .= " AND sm.created_at >= ?";
            $queryParams[] = $params['date_from'] . ' 00:00:00';
        }
        if (!empty($params['date_to'])) {
            $where .= " AND sm.created_at <= ?";
            $queryParams[] = $params['date_to'] . ' 23:59:59';
        }

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM stock_movements sm WHERE $where", $queryParams)['cnt'];

        $movements = $db->fetchAll(
            "SELECT sm.*, p.name as product_name, p.sku, w.name as warehouse_name,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name
             FROM stock_movements sm
             LEFT JOIN products p ON p.id = sm.product_id
             LEFT JOIN warehouses w ON w.id = sm.warehouse_id
             LEFT JOIN users u ON u.id = sm.user_id
             WHERE $where
             ORDER BY sm.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $queryParams
        );

        Response::paginated($movements, (int)$total, $page, $perPage);
    }
}
