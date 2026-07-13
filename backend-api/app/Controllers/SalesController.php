<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class SalesController
{
    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['items']) || !is_array($body['items'])) {
            Response::error('Items are required', 422);
        }

        $db = Database::getInstance();
        $total = 0;
        $discountAmount = (float)($body['discount'] ?? 0);
        $taxAmount = (float)($body['tax'] ?? 0);

        foreach ($body['items'] as $item) {
            if (empty($item['product_id']) || empty($item['quantity'])) {
                Response::error('Each item must have product_id and quantity', 422);
            }
            $product = $db->fetch("SELECT id, stock_quantity, selling_price FROM products WHERE id = ? AND is_deleted = 0", [$item['product_id']]);
            if (!$product) {
                Response::error("Product {$item['product_id']} not found", 404);
            }
            if ($product['stock_quantity'] < $item['quantity']) {
                Response::error("Insufficient stock for product {$item['product_id']}", 400);
            }
            $subtotal = ($item['price'] ?? $product['selling_price']) * $item['quantity'];
            $total += $subtotal;
        }

        $total = $total - $discountAmount + $taxAmount;

        $saleId = $db->insert('sales', [
            'reference_number' => generate_reference('SAL'),
            'customer_id' => $body['customer_id'] ?? null,
            'user_id' => get_user_id(),
            'subtotal' => $total + $discountAmount - $taxAmount,
            'discount' => $discountAmount,
            'tax' => $taxAmount,
            'total' => $total,
            'payment_method' => $body['payment_method'] ?? 'cash',
            'amount_paid' => $body['amount_paid'] ?? $total,
            'status' => 'completed',
            'notes' => $body['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($body['items'] as $item) {
            $product = $db->fetch("SELECT id, selling_price FROM products WHERE id = ?", [$item['product_id']]);
            $price = $item['price'] ?? $product['selling_price'];
            $subtotal = $price * $item['quantity'];

            $db->insert('sale_items', [
                'sale_id' => $saleId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $price,
                'subtotal' => $subtotal,
            ]);

            $db->query(
                "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?",
                [$item['quantity'], $item['product_id']]
            );

            $db->insert('stock_movements', [
                'product_id' => $item['product_id'],
                'type' => 'sale',
                'quantity' => -$item['quantity'],
                'reference' => $saleId,
                'notes' => "Sale #{$saleId}",
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if (!empty($body['customer_id']) && $body['payment_method'] === 'wallet') {
            $customer = $db->fetch("SELECT wallet_balance FROM customers WHERE id = ?", [$body['customer_id']]);
            if ($customer) {
                $newBalance = (float)$customer['wallet_balance'] - $total;
                $db->update('customers', ['wallet_balance' => $newBalance, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$body['customer_id']]);
                $db->insert('wallet_transactions', [
                    'customer_id' => $body['customer_id'],
                    'type' => 'deduction',
                    'amount' => -$total,
                    'balance_after' => $newBalance,
                    'description' => "Payment for sale #$saleId",
                    'reference' => generate_reference('WLT'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $sale = $db->fetch("SELECT * FROM sales WHERE id = ?", [$saleId]);
        $items = $db->fetchAll("SELECT si.*, p.name as product_name FROM sale_items si JOIN products p ON p.id = si.product_id WHERE si.sale_id = ?", [$saleId]);
        $sale['items'] = $items;

        Response::success($sale, 'Sale completed', 201);
    }

    public function index(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = max(1, min(100, (int)($params['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = "1=1";
        $queryParams = [];

        if (!empty($params['status'])) {
            $where .= " AND s.status = ?";
            $queryParams[] = $params['status'];
        }
        if (!empty($params['date_from'])) {
            $where .= " AND s.created_at >= ?";
            $queryParams[] = $params['date_from'] . ' 00:00:00';
        }
        if (!empty($params['date_to'])) {
            $where .= " AND s.created_at <= ?";
            $queryParams[] = $params['date_to'] . ' 23:59:59';
        }
        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $where .= " AND (s.reference_number LIKE ? OR c.name LIKE ?)";
            $queryParams = array_merge($queryParams, [$search, $search]);
        }

        $total = $db->fetch(
            "SELECT COUNT(*) as cnt FROM sales s LEFT JOIN customers c ON c.id = s.customer_id WHERE $where",
            $queryParams
        )['cnt'];

        $sales = $db->fetchAll(
            "SELECT s.*, c.name as customer_name, u.name as cashier_name
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE $where
             ORDER BY s.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $queryParams
        );

        Response::paginated($sales, (int)$total, $page, $perPage);
    }

    public function show(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $sale = $db->fetch(
            "SELECT s.*, c.name as customer_name, u.name as cashier_name
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE s.id = ?",
            [$id]
        );

        if (!$sale) {
            Response::error('Sale not found', 404);
        }

        $items = $db->fetchAll(
            "SELECT si.*, p.name as product_name, p.sku
             FROM sale_items si JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = ?",
            [$id]
        );

        $sale['items'] = $items;

        Response::success($sale);
    }

    public function void(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $sale = $db->fetch("SELECT id, status, customer_id FROM sales WHERE id = ?", [$id]);
        if (!$sale) {
            Response::error('Sale not found', 404);
        }

        if ($sale['status'] === 'voided') {
            Response::error('Sale already voided', 409);
        }

        $items = $db->fetchAll("SELECT * FROM sale_items WHERE sale_id = ?", [$id]);
        foreach ($items as $item) {
            $db->query(
                "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?",
                [$item['quantity'], $item['product_id']]
            );
            $db->insert('stock_movements', [
                'product_id' => $item['product_id'],
                'type' => 'void',
                'quantity' => $item['quantity'],
                'reference' => $id,
                'notes' => "Sale #$id voided",
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($sale['customer_id']) {
            $saleData = $db->fetch("SELECT total, payment_method FROM sales WHERE id = ?", [$id]);
            if ($saleData['payment_method'] === 'wallet') {
                $customer = $db->fetch("SELECT wallet_balance FROM customers WHERE id = ?", [$sale['customer_id']]);
                if ($customer) {
                    $newBalance = (float)$customer['wallet_balance'] + (float)$saleData['total'];
                    $db->update('customers', ['wallet_balance' => $newBalance], 'id = ?', [$sale['customer_id']]);
                    $db->insert('wallet_transactions', [
                        'customer_id' => $sale['customer_id'],
                        'type' => 'topup',
                        'amount' => $saleData['total'],
                        'balance_after' => $newBalance,
                        'description' => "Sale #$id voided - refund",
                        'reference' => generate_reference('WLT'),
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        $db->update('sales', ['status' => 'voided', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Sale voided');
    }

    public function hold(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['items']) || !is_array($body['items'])) {
            Response::error('Items are required', 422);
        }

        $db = Database::getInstance();
        $total = 0;

        foreach ($body['items'] as $item) {
            $price = $item['price'] ?? 0;
            $total += $price * $item['quantity'];
        }

        $saleId = $db->insert('sales', [
            'reference_number' => generate_reference('HLD'),
            'customer_id' => $body['customer_id'] ?? null,
            'user_id' => get_user_id(),
            'subtotal' => $total,
            'discount' => $body['discount'] ?? 0,
            'tax' => $body['tax'] ?? 0,
            'total' => $total,
            'payment_method' => null,
            'status' => 'held',
            'notes' => $body['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($body['items'] as $item) {
            $price = $item['price'] ?? 0;
            $db->insert('sale_items', [
                'sale_id' => $saleId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $price,
                'subtotal' => $price * $item['quantity'],
            ]);
        }

        $sale = $db->fetch("SELECT * FROM sales WHERE id = ?", [$saleId]);
        $items = $db->fetchAll("SELECT si.*, p.name as product_name FROM sale_items si JOIN products p ON p.id = si.product_id WHERE si.sale_id = ?", [$saleId]);
        $sale['items'] = $items;

        Response::success($sale, 'Sale held', 201);
    }

    public function heldSales(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $sales = $db->fetchAll(
            "SELECT s.*, c.name as customer_name, u.name as cashier_name
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE s.status = 'held'
             ORDER BY s.created_at DESC"
        );

        Response::success($sales);
    }

    public function resume(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $sale = $db->fetch("SELECT id, status FROM sales WHERE id = ?", [$id]);
        if (!$sale) {
            Response::error('Sale not found', 404);
        }

        if ($sale['status'] !== 'held') {
            Response::error('Sale is not held', 409);
        }

        $items = $db->fetchAll(
            "SELECT si.*, p.name as product_name, p.selling_price
             FROM sale_items si JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = ?",
            [$id]
        );

        $saleData = $db->fetch("SELECT * FROM sales WHERE id = ?", [$id]);
        $saleData['items'] = $items;

        Response::success($saleData);
    }

    public function returnSale(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $sale = $db->fetch("SELECT id, status, customer_id, total FROM sales WHERE id = ?", [$id]);
        if (!$sale) {
            Response::error('Sale not found', 404);
        }

        if ($sale['status'] !== 'completed') {
            Response::error('Only completed sales can be returned', 409);
        }

        $items = $body['items'] ?? $db->fetchAll("SELECT * FROM sale_items WHERE sale_id = ?", [$id]);

        $returnTotal = 0;
        foreach ($items as $item) {
            $product = $db->fetch("SELECT id, selling_price FROM products WHERE id = ?", [$item['product_id']]);
            if (!$product) {
                Response::error("Product {$item['product_id']} not found", 404);
            }
            $price = $item['price'] ?? $product['selling_price'];
            $returnTotal += $price * $item['quantity'];
        }

        foreach ($items as $item) {
            $db->query(
                "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?",
                [$item['quantity'], $item['product_id']]
            );
            $db->insert('stock_movements', [
                'product_id' => $item['product_id'],
                'type' => 'return',
                'quantity' => $item['quantity'],
                'reference' => $id,
                'notes' => "Sale #$id returned",
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($sale['customer_id']) {
            $customer = $db->fetch("SELECT wallet_balance FROM customers WHERE id = ?", [$sale['customer_id']]);
            if ($customer) {
                $newBalance = (float)$customer['wallet_balance'] + $returnTotal;
                $db->update('customers', ['wallet_balance' => $newBalance], 'id = ?', [$sale['customer_id']]);
                $db->insert('wallet_transactions', [
                    'customer_id' => $sale['customer_id'],
                    'type' => 'topup',
                    'amount' => $returnTotal,
                    'balance_after' => $newBalance,
                    'description' => "Sale #$id return refund",
                    'reference' => generate_reference('WLT'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $db->update('sales', ['status' => 'returned', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Sale returned');
    }

    public function receipt(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $sale = $db->fetch(
            "SELECT s.*, c.name as customer_name, c.phone as customer_phone, u.name as cashier_name
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE s.id = ?",
            [$id]
        );

        if (!$sale) {
            Response::error('Sale not found', 404);
        }

        $items = $db->fetchAll(
            "SELECT si.*, p.name as product_name, p.sku
             FROM sale_items si JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = ?",
            [$id]
        );

        $company = $db->fetch("SELECT * FROM company_settings LIMIT 1");

        $sale['items'] = $items;
        $sale['company'] = $company;

        Response::success($sale);
    }
}
