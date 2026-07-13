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

        if (empty($body['branch_id']) || empty($body['warehouse_id'])) {
            Response::error('Branch ID and Warehouse ID are required', 422);
        }

        $db = Database::getInstance();
        $subtotal = 0;
        $discountAmount = (float)($body['discount_amount'] ?? 0);
        $taxAmount = (float)($body['tax_amount'] ?? 0);
        $shippingCost = (float)($body['shipping_cost'] ?? 0);

        foreach ($body['items'] as $item) {
            if (empty($item['product_id']) || empty($item['quantity'])) {
                Response::error('Each item must have product_id and quantity', 422);
            }
            $product = $db->fetch(
                "SELECT id, selling_price FROM products WHERE id = ? AND deleted_at IS NULL",
                [$item['product_id']]
            );
            if (!$product) {
                Response::error("Product {$item['product_id']} not found", 404);
            }

            $stock = $db->fetch(
                "SELECT COALESCE(SUM(quantity), 0) as qty FROM product_stocks WHERE product_id = ? AND warehouse_id = ?",
                [$item['product_id'], $body['warehouse_id']]
            );
            if ((float)$stock['qty'] < (float)$item['quantity']) {
                Response::error("Insufficient stock for product {$item['product_id']}", 400);
            }

            $itemPrice = $item['unit_price'] ?? $product['selling_price'];
            $itemSubtotal = $itemPrice * $item['quantity'];
            $itemDiscount = $item['discount'] ?? 0;
            $itemTaxRate = $item['tax_rate'] ?? 0;
            $itemTaxAmount = ($itemSubtotal - $itemDiscount) * $itemTaxRate / 100;
            $subtotal += $itemSubtotal - $itemDiscount + $itemTaxAmount;
        }

        $total = $subtotal - $discountAmount + $taxAmount + $shippingCost;
        $paidAmount = (float)($body['paid_amount'] ?? $total);
        $dueAmount = $total - $paidAmount;
        $paymentStatus = 'paid';
        if ($dueAmount > 0 && $paidAmount > 0) {
            $paymentStatus = 'partial';
        } elseif ($dueAmount >= $total) {
            $paymentStatus = 'unpaid';
        }

        $saleId = $db->insert('sales', [
            'invoice_number' => generate_reference('INV'),
            'customer_id' => $body['customer_id'] ?? null,
            'user_id' => get_user_id(),
            'branch_id' => $body['branch_id'],
            'warehouse_id' => $body['warehouse_id'],
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'shipping_cost' => $shippingCost,
            'total' => $total,
            'paid_amount' => $paidAmount,
            'due_amount' => max(0, $dueAmount),
            'payment_status' => $paymentStatus,
            'sale_status' => 'completed',
            'payment_method' => $body['payment_method'] ?? 'cash',
            'coupon_code' => $body['coupon_code'] ?? null,
            'discount_type' => $body['discount_type'] ?? null,
            'notes' => $body['notes'] ?? null,
            'sale_date' => date('Y-m-d'),
            'sale_time' => date('H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($body['items'] as $item) {
            $product = $db->fetch("SELECT id, selling_price FROM products WHERE id = ?", [$item['product_id']]);
            $itemPrice = $item['unit_price'] ?? $product['selling_price'];
            $itemSubtotal = $itemPrice * $item['quantity'];
            $itemDiscount = $item['discount'] ?? 0;
            $itemTaxRate = $item['tax_rate'] ?? 0;
            $itemTaxAmount = ($itemSubtotal - $itemDiscount) * $itemTaxRate / 100;

            $db->insert('sale_items', [
                'sale_id' => $saleId,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $itemPrice,
                'discount' => $itemDiscount,
                'tax_rate' => $itemTaxRate,
                'tax_amount' => $itemTaxAmount,
                'subtotal' => $itemSubtotal - $itemDiscount + $itemTaxAmount,
                'total' => $itemSubtotal - $itemDiscount + $itemTaxAmount,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $existingStock = $db->fetch(
                "SELECT id, quantity FROM product_stocks WHERE product_id = ? AND warehouse_id = ? AND variant_id IS NULL",
                [$item['product_id'], $body['warehouse_id']]
            );

            if ($existingStock) {
                $db->update('product_stocks', [
                    'quantity' => $existingStock['quantity'] - $item['quantity'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$existingStock['id']]);
            }

            $db->insert('stock_movements', [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'warehouse_id' => $body['warehouse_id'],
                'type' => 'sale',
                'quantity' => -$item['quantity'],
                'reference_type' => 'App\Models\Sale',
                'reference_id' => $saleId,
                'notes' => "Sale #$saleId",
                'user_id' => get_user_id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if (!empty($body['customer_id']) && ($body['payment_method'] ?? '') === 'wallet') {
            $wallet = $db->fetch("SELECT id, balance FROM customer_wallets WHERE customer_id = ?", [$body['customer_id']]);
            if ($wallet) {
                $newBalance = (float)$wallet['balance'] - $total;
                $db->update('customer_wallets', ['balance' => $newBalance, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$wallet['id']]);
                $db->insert('customer_wallet_transactions', [
                    'wallet_id' => $wallet['id'],
                    'type' => 'debit',
                    'amount' => $total,
                    'balance_after' => $newBalance,
                    'description' => "Payment for sale #$saleId",
                    'reference_type' => 'App\Models\Sale',
                    'reference_id' => $saleId,
                    'user_id' => get_user_id(),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $sale = $db->fetch("SELECT * FROM sales WHERE id = ?", [$saleId]);
        $items = $db->fetchAll("SELECT si.*, p.name as product_name, p.sku FROM sale_items si JOIN products p ON p.id = si.product_id WHERE si.sale_id = ?", [$saleId]);
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

        if (!empty($params['sale_status'])) {
            $where .= " AND s.sale_status = ?";
            $queryParams[] = $params['sale_status'];
        }
        if (!empty($params['payment_status'])) {
            $where .= " AND s.payment_status = ?";
            $queryParams[] = $params['payment_status'];
        }
        if (!empty($params['date_from'])) {
            $where .= " AND s.sale_date >= ?";
            $queryParams[] = $params['date_from'];
        }
        if (!empty($params['date_to'])) {
            $where .= " AND s.sale_date <= ?";
            $queryParams[] = $params['date_to'];
        }
        if (!empty($params['branch_id'])) {
            $where .= " AND s.branch_id = ?";
            $queryParams[] = $params['branch_id'];
        }
        if (!empty($params['warehouse_id'])) {
            $where .= " AND s.warehouse_id = ?";
            $queryParams[] = $params['warehouse_id'];
        }
        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $where .= " AND (s.invoice_number LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?)";
            $queryParams = array_merge($queryParams, [$search, $search]);
        }

        $total = $db->fetch(
            "SELECT COUNT(*) as cnt FROM sales s LEFT JOIN customers c ON c.id = s.customer_id WHERE $where",
            $queryParams
        )['cnt'];

        $sales = $db->fetchAll(
            "SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                    CONCAT(u.first_name, ' ', u.last_name) as cashier_name
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
            "SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.phone as customer_phone,
                    CONCAT(u.first_name, ' ', u.last_name) as cashier_name
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

        $payments = $db->fetchAll(
            "SELECT sp.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
             FROM sale_payments sp
             LEFT JOIN users u ON u.id = sp.user_id
             WHERE sp.sale_id = ? ORDER BY sp.created_at DESC",
            [$id]
        );

        $sale['items'] = $items;
        $sale['payments'] = $payments;

        Response::success($sale);
    }

    public function void(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $sale = $db->fetch("SELECT id, sale_status, customer_id, warehouse_id, total, payment_method FROM sales WHERE id = ?", [$id]);
        if (!$sale) {
            Response::error('Sale not found', 404);
        }

        if ($sale['sale_status'] === 'voided') {
            Response::error('Sale already voided', 409);
        }

        $items = $db->fetchAll("SELECT * FROM sale_items WHERE sale_id = ?", [$id]);
        foreach ($items as $item) {
            $existingStock = $db->fetch(
                "SELECT id, quantity FROM product_stocks WHERE product_id = ? AND warehouse_id = ? AND variant_id IS NULL",
                [$item['product_id'], $sale['warehouse_id']]
            );

            if ($existingStock) {
                $db->update('product_stocks', [
                    'quantity' => $existingStock['quantity'] + $item['quantity'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$existingStock['id']]);
            }

            $db->insert('stock_movements', [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'warehouse_id' => $sale['warehouse_id'],
                'type' => 'sale',
                'quantity' => $item['quantity'],
                'reference_type' => 'App\Models\Sale',
                'reference_id' => $id,
                'notes' => "Sale #$id voided",
                'user_id' => get_user_id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($sale['customer_id'] && $sale['payment_method'] === 'wallet') {
            $wallet = $db->fetch("SELECT id, balance FROM customer_wallets WHERE customer_id = ?", [$sale['customer_id']]);
            if ($wallet) {
                $newBalance = (float)$wallet['balance'] + (float)$sale['total'];
                $db->update('customer_wallets', ['balance' => $newBalance, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$wallet['id']]);
                $db->insert('customer_wallet_transactions', [
                    'wallet_id' => $wallet['id'],
                    'type' => 'credit',
                    'amount' => $sale['total'],
                    'balance_after' => $newBalance,
                    'description' => "Sale #$id voided - refund",
                    'reference_type' => 'App\Models\Sale',
                    'reference_id' => $id,
                    'user_id' => get_user_id(),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $db->update('sales', ['sale_status' => 'voided', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Sale voided');
    }

    public function hold(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['items']) || !is_array($body['items'])) {
            Response::error('Items are required', 422);
        }

        if (empty($body['branch_id']) || empty($body['warehouse_id'])) {
            Response::error('Branch ID and Warehouse ID are required', 422);
        }

        $db = Database::getInstance();
        $subtotal = 0;

        foreach ($body['items'] as $item) {
            $price = $item['unit_price'] ?? 0;
            $subtotal += $price * $item['quantity'];
        }

        $saleId = $db->insert('sales', [
            'invoice_number' => generate_reference('HLD'),
            'customer_id' => $body['customer_id'] ?? null,
            'user_id' => get_user_id(),
            'branch_id' => $body['branch_id'],
            'warehouse_id' => $body['warehouse_id'],
            'subtotal' => $subtotal,
            'discount_amount' => $body['discount_amount'] ?? 0,
            'tax_amount' => $body['tax_amount'] ?? 0,
            'total' => $subtotal,
            'payment_method' => null,
            'sale_status' => 'held',
            'notes' => $body['notes'] ?? null,
            'sale_date' => date('Y-m-d'),
            'sale_time' => date('H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($body['items'] as $item) {
            $price = $item['unit_price'] ?? 0;
            $db->insert('sale_items', [
                'sale_id' => $saleId,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $price,
                'subtotal' => $price * $item['quantity'],
                'total' => $price * $item['quantity'],
                'created_at' => date('Y-m-d H:i:s'),
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
            "SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                    CONCAT(u.first_name, ' ', u.last_name) as cashier_name
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE s.sale_status = 'held'
             ORDER BY s.created_at DESC"
        );

        Response::success($sales);
    }

    public function resume(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $sale = $db->fetch("SELECT id, sale_status FROM sales WHERE id = ?", [$id]);
        if (!$sale) {
            Response::error('Sale not found', 404);
        }

        if ($sale['sale_status'] !== 'held') {
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

        $sale = $db->fetch("SELECT id, sale_status, customer_id, total, warehouse_id FROM sales WHERE id = ?", [$id]);
        if (!$sale) {
            Response::error('Sale not found', 404);
        }

        if ($sale['sale_status'] !== 'completed') {
            Response::error('Only completed sales can be returned', 409);
        }

        $items = $body['items'] ?? $db->fetchAll("SELECT * FROM sale_items WHERE sale_id = ?", [$id]);

        $returnTotal = 0;
        foreach ($items as $item) {
            $product = $db->fetch("SELECT id, selling_price FROM products WHERE id = ?", [$item['product_id']]);
            if (!$product) {
                Response::error("Product {$item['product_id']} not found", 404);
            }
            $price = $item['unit_price'] ?? $product['selling_price'];
            $returnTotal += $price * $item['quantity'];
        }

        $returnId = $db->insert('sale_returns', [
            'return_number' => generate_reference('RET'),
            'sale_id' => $id,
            'customer_id' => $sale['customer_id'],
            'user_id' => get_user_id(),
            'warehouse_id' => $sale['warehouse_id'],
            'refund_method' => $body['refund_method'] ?? 'cash',
            'refund_amount' => $returnTotal,
            'reason' => $body['reason'] ?? null,
            'status' => 'completed',
            'return_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($items as $item) {
            $existingStock = $db->fetch(
                "SELECT id, quantity FROM product_stocks WHERE product_id = ? AND warehouse_id = ? AND variant_id IS NULL",
                [$item['product_id'], $sale['warehouse_id']]
            );

            if ($existingStock) {
                $db->update('product_stocks', [
                    'quantity' => $existingStock['quantity'] + $item['quantity'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$existingStock['id']]);
            }

            $db->insert('return_items', [
                'return_id' => $returnId,
                'sale_item_id' => $item['id'] ?? null,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'] ?? 0,
                'subtotal' => ($item['unit_price'] ?? 0) * $item['quantity'],
                'reason' => $item['reason'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $db->insert('stock_movements', [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'warehouse_id' => $sale['warehouse_id'],
                'type' => 'return',
                'quantity' => $item['quantity'],
                'reference_type' => 'App\Models\SaleReturn',
                'reference_id' => $returnId,
                'notes' => "Sale #$id returned",
                'user_id' => get_user_id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($sale['customer_id'] && ($body['refund_method'] ?? '') === 'wallet') {
            $wallet = $db->fetch("SELECT id, balance FROM customer_wallets WHERE customer_id = ?", [$sale['customer_id']]);
            if ($wallet) {
                $newBalance = (float)$wallet['balance'] + $returnTotal;
                $db->update('customer_wallets', ['balance' => $newBalance, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$wallet['id']]);
                $db->insert('customer_wallet_transactions', [
                    'wallet_id' => $wallet['id'],
                    'type' => 'credit',
                    'amount' => $returnTotal,
                    'balance_after' => $newBalance,
                    'description' => "Sale #$id return refund",
                    'reference_type' => 'App\Models\SaleReturn',
                    'reference_id' => $returnId,
                    'user_id' => get_user_id(),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $db->update('sales', ['sale_status' => 'returned', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(['return_id' => $returnId], 'Sale returned');
    }

    public function receipt(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $sale = $db->fetch(
            "SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.phone as customer_phone,
                    CONCAT(u.first_name, ' ', u.last_name) as cashier_name
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

        $company = $db->fetch("SELECT * FROM company LIMIT 1");

        $sale['items'] = $items;
        $sale['company'] = $company;

        Response::success($sale);
    }
}
