<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class PurchasesController
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

        if (!empty($params['status'])) {
            $where .= " AND p.status = ?";
            $queryParams[] = $params['status'];
        }
        if (!empty($params['payment_status'])) {
            $where .= " AND p.payment_status = ?";
            $queryParams[] = $params['payment_status'];
        }
        if (!empty($params['supplier_id'])) {
            $where .= " AND p.supplier_id = ?";
            $queryParams[] = $params['supplier_id'];
        }
        if (!empty($params['warehouse_id'])) {
            $where .= " AND p.warehouse_id = ?";
            $queryParams[] = $params['warehouse_id'];
        }
        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $where .= " AND (p.reference_number LIKE ? OR s.name LIKE ?)";
            $queryParams = array_merge($queryParams, [$search, $search]);
        }

        $total = $db->fetch(
            "SELECT COUNT(*) as cnt FROM purchases p LEFT JOIN suppliers s ON s.id = p.supplier_id WHERE $where",
            $queryParams
        )['cnt'];

        $purchases = $db->fetchAll(
            "SELECT p.*, s.name as supplier_name, w.name as warehouse_name,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by_name
             FROM purchases p
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             LEFT JOIN warehouses w ON w.id = p.warehouse_id
             LEFT JOIN users u ON u.id = p.user_id
             WHERE $where
             ORDER BY p.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $queryParams
        );

        Response::paginated($purchases, (int)$total, $page, $perPage);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['items']) || !is_array($body['items'])) {
            Response::error('Items are required', 422);
        }

        if (empty($body['supplier_id'])) {
            Response::error('Supplier ID is required', 422);
        }

        if (empty($body['warehouse_id'])) {
            Response::error('Warehouse ID is required', 422);
        }

        if (empty($body['branch_id'])) {
            Response::error('Branch ID is required', 422);
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
            $itemTotal = ($item['unit_cost'] ?? 0) * $item['quantity'];
            $subtotal += $itemTotal;
        }

        $total = $subtotal - $discountAmount + $taxAmount + $shippingCost;
        $paidAmount = (float)($body['paid_amount'] ?? 0);
        $dueAmount = $total - $paidAmount;
        $paymentStatus = 'unpaid';
        if ($paidAmount >= $total) {
            $paymentStatus = 'paid';
        } elseif ($paidAmount > 0) {
            $paymentStatus = 'partial';
        }

        $purchaseId = $db->insert('purchases', [
            'reference_number' => generate_reference('PUR'),
            'supplier_id' => $body['supplier_id'],
            'warehouse_id' => $body['warehouse_id'],
            'user_id' => get_user_id(),
            'branch_id' => $body['branch_id'],
            'status' => 'pending',
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'shipping_cost' => $shippingCost,
            'total' => $total,
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'payment_status' => $paymentStatus,
            'order_date' => $body['order_date'] ?? date('Y-m-d'),
            'expected_date' => $body['expected_date'] ?? null,
            'notes' => $body['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($body['items'] as $item) {
            $itemTotal = ($item['unit_cost'] ?? 0) * $item['quantity'];
            $itemDiscount = $item['discount'] ?? 0;
            $itemTaxRate = $item['tax_rate'] ?? 0;
            $itemTaxAmount = ($itemTotal - $itemDiscount) * $itemTaxRate / 100;

            $db->insert('purchase_items', [
                'purchase_id' => $purchaseId,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'received_quantity' => 0,
                'unit_cost' => $item['unit_cost'] ?? 0,
                'discount' => $itemDiscount,
                'tax_rate' => $itemTaxRate,
                'tax_amount' => $itemTaxAmount,
                'total' => $itemTotal - $itemDiscount + $itemTaxAmount,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($paidAmount > 0) {
            $db->insert('purchase_payments', [
                'purchase_id' => $purchaseId,
                'amount' => $paidAmount,
                'payment_method' => $body['payment_method'] ?? 'cash',
                'reference' => $body['payment_reference'] ?? null,
                'notes' => $body['payment_notes'] ?? null,
                'payment_date' => date('Y-m-d H:i:s'),
                'user_id' => get_user_id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $purchase = $db->fetch("SELECT * FROM purchases WHERE id = ?", [$purchaseId]);
        $items = $db->fetchAll("SELECT pi.*, pr.name as product_name FROM purchase_items pi JOIN products pr ON pr.id = pi.product_id WHERE pi.purchase_id = ?", [$purchaseId]);
        $purchase['items'] = $items;

        Response::success($purchase, 'Purchase created', 201);
    }

    public function show(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $purchase = $db->fetch(
            "SELECT p.*, s.name as supplier_name, w.name as warehouse_name,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by_name
             FROM purchases p
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             LEFT JOIN warehouses w ON w.id = p.warehouse_id
             LEFT JOIN users u ON u.id = p.user_id
             WHERE p.id = ? AND p.deleted_at IS NULL",
            [$id]
        );

        if (!$purchase) {
            Response::error('Purchase not found', 404);
        }

        $items = $db->fetchAll(
            "SELECT pi.*, pr.name as product_name, pr.sku
             FROM purchase_items pi JOIN products pr ON pr.id = pi.product_id
             WHERE pi.purchase_id = ?",
            [$id]
        );

        $payments = $db->fetchAll(
            "SELECT pp.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
             FROM purchase_payments pp
             LEFT JOIN users u ON u.id = pp.user_id
             WHERE pp.purchase_id = ? ORDER BY pp.created_at DESC",
            [$id]
        );

        $purchase['items'] = $items;
        $purchase['payments'] = $payments;

        Response::success($purchase);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $purchase = $db->fetch("SELECT id, status FROM purchases WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$purchase) {
            Response::error('Purchase not found', 404);
        }

        if ($purchase['status'] === 'received' || $purchase['status'] === 'cancelled') {
            Response::error('Cannot update received or cancelled purchase', 409);
        }

        $updateData = [];
        if (isset($body['supplier_id'])) $updateData['supplier_id'] = $body['supplier_id'];
        if (isset($body['warehouse_id'])) $updateData['warehouse_id'] = $body['warehouse_id'];
        if (isset($body['branch_id'])) $updateData['branch_id'] = $body['branch_id'];
        if (isset($body['notes'])) $updateData['notes'] = $body['notes'];
        if (isset($body['order_date'])) $updateData['order_date'] = $body['order_date'];
        if (isset($body['expected_date'])) $updateData['expected_date'] = $body['expected_date'];

        if (!empty($body['items'])) {
            $subtotal = 0;
            $db->delete('purchase_items', 'purchase_id = ?', [$id]);
            foreach ($body['items'] as $item) {
                $itemTotal = ($item['unit_cost'] ?? 0) * $item['quantity'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTaxRate = $item['tax_rate'] ?? 0;
                $itemTaxAmount = ($itemTotal - $itemDiscount) * $itemTaxRate / 100;
                $total = $itemTotal - $itemDiscount + $itemTaxAmount;
                $subtotal += $itemTotal;

                $db->insert('purchase_items', [
                    'purchase_id' => $id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'discount' => $itemDiscount,
                    'tax_rate' => $itemTaxRate,
                    'tax_amount' => $itemTaxAmount,
                    'total' => $total,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $discountAmount = (float)($body['discount_amount'] ?? 0);
            $taxAmount = (float)($body['tax_amount'] ?? 0);
            $shippingCost = (float)($body['shipping_cost'] ?? 0);
            $total = $subtotal - $discountAmount + $taxAmount + $shippingCost;
            $paidAmount = (float)($body['paid_amount'] ?? 0);

            $updateData['subtotal'] = $subtotal;
            $updateData['discount_amount'] = $discountAmount;
            $updateData['tax_amount'] = $taxAmount;
            $updateData['shipping_cost'] = $shippingCost;
            $updateData['total'] = $total;
            $updateData['paid_amount'] = $paidAmount;
            $updateData['due_amount'] = $total - $paidAmount;
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $db->update('purchases', $updateData, 'id = ?', [$id]);
        }

        $purchase = $db->fetch("SELECT * FROM purchases WHERE id = ?", [$id]);
        $items = $db->fetchAll("SELECT pi.*, pr.name as product_name FROM purchase_items pi JOIN products pr ON pr.id = pi.product_id WHERE pi.purchase_id = ?", [$id]);
        $purchase['items'] = $items;

        Response::success($purchase, 'Purchase updated');
    }

    public function receive(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $purchase = $db->fetch("SELECT id, status, warehouse_id FROM purchases WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$purchase) {
            Response::error('Purchase not found', 404);
        }

        if ($purchase['status'] === 'received') {
            Response::error('Purchase already received', 409);
        }

        $items = $db->fetchAll("SELECT * FROM purchase_items WHERE purchase_id = ?", [$id]);

        foreach ($items as $item) {
            $qty = $item['quantity'] - ($item['received_quantity'] ?? 0);
            if ($qty > 0) {
                $db->update('purchase_items', ['received_quantity' => $item['quantity']], 'id = ?', [$item['id']]);

                $existingStock = $db->fetch(
                    "SELECT id, quantity FROM product_stocks WHERE product_id = ? AND warehouse_id = ? AND variant_id IS NULL",
                    [$item['product_id'], $purchase['warehouse_id']]
                );

                if ($existingStock) {
                    $db->update('product_stocks', [
                        'quantity' => $existingStock['quantity'] + $qty,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ], 'id = ?', [$existingStock['id']]);
                } else {
                    $db->insert('product_stocks', [
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'warehouse_id' => $purchase['warehouse_id'],
                        'quantity' => $qty,
                        'reserved_quantity' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                $db->insert('stock_movements', [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'warehouse_id' => $purchase['warehouse_id'],
                    'type' => 'purchase',
                    'quantity' => $qty,
                    'reference_type' => 'App\\Models\\Purchase',
                    'reference_id' => $purchase['id'],
                    'notes' => "Purchase #{$id} received",
                    'user_id' => get_user_id(),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $receivedAll = true;
        foreach ($items as $item) {
            if ($item['received_quantity'] < $item['quantity']) {
                $receivedAll = false;
                break;
            }
        }

        $db->update('purchases', [
            'status' => $receivedAll ? 'received' : 'partial',
            'received_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Response::success(null, 'Purchase received');
    }

    public function addPayment(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['amount']) || (float)$body['amount'] <= 0) {
            Response::error('Valid amount is required', 422);
        }

        $db = Database::getInstance();

        $purchase = $db->fetch("SELECT id, supplier_id, total, paid_amount FROM purchases WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$purchase) {
            Response::error('Purchase not found', 404);
        }

        $amount = (float)$body['amount'];
        $newPaidAmount = (float)$purchase['paid_amount'] + $amount;
        $dueAmount = (float)$purchase['total'] - $newPaidAmount;
        $paymentStatus = 'partial';
        if ($dueAmount <= 0) {
            $paymentStatus = 'paid';
        }

        $db->insert('purchase_payments', [
            'purchase_id' => $id,
            'amount' => $amount,
            'payment_method' => $body['payment_method'] ?? 'cash',
            'reference' => $body['reference'] ?? null,
            'notes' => $body['notes'] ?? null,
            'payment_date' => date('Y-m-d H:i:s'),
            'user_id' => get_user_id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $db->update('purchases', [
            'paid_amount' => $newPaidAmount,
            'due_amount' => max(0, $dueAmount),
            'payment_status' => $paymentStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        if ($purchase['supplier_id']) {
            $supplier = $db->fetch("SELECT outstanding_balance FROM suppliers WHERE id = ?", [$purchase['supplier_id']]);
            if ($supplier) {
                $newBalance = (float)$supplier['outstanding_balance'] - $amount;
                $db->update('suppliers', ['outstanding_balance' => $newBalance, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$purchase['supplier_id']]);
            }
        }

        Response::success(null, 'Payment recorded');
    }

    public function cancel(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $purchase = $db->fetch("SELECT id, status FROM purchases WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$purchase) {
            Response::error('Purchase not found', 404);
        }

        if ($purchase['status'] === 'received') {
            Response::error('Cannot cancel received purchase', 409);
        }

        $db->update('purchases', [
            'status' => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Response::success(null, 'Purchase cancelled');
    }
}
