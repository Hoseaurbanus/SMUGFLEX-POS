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

        $where = "p.is_deleted = 0";
        $queryParams = [];

        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $where .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
            $queryParams = array_merge($queryParams, [$search, $search]);
        }
        if (!empty($params['warehouse_id'])) {
            $where .= " AND p.warehouse_id = ?";
            $queryParams[] = $params['warehouse_id'];
        }
        if (!empty($params['low_stock'])) {
            $where .= " AND p.stock_quantity <= p.minimum_stock";
        }

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM products p WHERE $where", $queryParams)['cnt'];

        $products = $db->fetchAll(
            "SELECT p.*, c.name as category_name, w.name as warehouse_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN warehouses w ON w.id = p.warehouse_id
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

        if (!$body || empty($body['product_id']) || !isset($body['quantity_adjustment'])) {
            Response::error('Product ID and quantity adjustment are required', 422);
        }

        $db = Database::getInstance();

        $product = $db->fetch("SELECT id, stock_quantity FROM products WHERE id = ? AND is_deleted = 0", [$body['product_id']]);
        if (!$product) {
            Response::error('Product not found', 404);
        }

        $adjustment = (int)$body['quantity_adjustment'];
        $newQuantity = (int)$product['stock_quantity'] + $adjustment;

        if ($newQuantity < 0) {
            Response::error('Adjustment would result in negative stock', 400);
        }

        $db->update('products', ['stock_quantity' => $newQuantity, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$body['product_id']]);

        $db->insert('stock_movements', [
            'product_id' => $body['product_id'],
            'type' => 'adjustment',
            'quantity' => $adjustment,
            'reference' => null,
            'notes' => $body['reason'] ?? 'Manual adjustment',
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

        $product = $db->fetch("SELECT id, stock_quantity FROM products WHERE id = ? AND is_deleted = 0", [$body['product_id']]);
        if (!$product) {
            Response::error('Product not found', 404);
        }

        $quantity = (int)$body['quantity'];

        $db->update('products', [
            'stock_quantity' => (int)$product['stock_quantity'] - $quantity,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$body['product_id']]);

        $db->insert('stock_movements', [
            'product_id' => $body['product_id'],
            'type' => 'transfer_out',
            'quantity' => -$quantity,
            'reference' => $body['to_warehouse_id'],
            'notes' => "Transfer to warehouse #{$body['to_warehouse_id']}",
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Response::success(null, 'Transfer completed');
    }

    public function lowStock(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $products = $db->fetchAll(
            "SELECT p.*, c.name as category_name, w.name as warehouse_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN warehouses w ON w.id = p.warehouse_id
             WHERE p.is_deleted = 0 AND p.stock_quantity <= p.minimum_stock
             ORDER BY (p.stock_quantity / NULLIF(p.minimum_stock, 0)) ASC"
        );

        Response::success($products);
    }
}
