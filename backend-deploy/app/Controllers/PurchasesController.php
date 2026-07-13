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

        $where = "1=1";
        $queryParams = [];

        if (!empty($params['status'])) {
            $where .= " AND p.status = ?";
            $queryParams[] = $params['status'];
        }
        if (!empty($params['supplier_id'])) {
            $where .= " AND p.supplier_id = ?";
            $queryParams[] = $params['supplier_id'];
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
            "SELECT p.*, s.name as supplier_name
             FROM purchases p LEFT JOIN suppliers s ON s.id = p.supplier_id
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

        $db = Database::getInstance();
        $total = 0;

        foreach ($body['items'] as $item) {
            if (empty($item['product_id']) || empty($item['quantity'])) {
                Response::error('Each item must have product_id and quantity', 422);
            }
            $total += ($item['unit_cost'] ?? 0) * $item['quantity'];
        }

        $purchaseId = $db->insert('purchases', [
            'reference_number' => generate_reference('PUR'),
            'supplier_id' => $body['supplier_id'] ?? null,
            'total' => $total,
            'status' => 'pending',
            'notes' => $body['notes'] ?? null,
            'created_by' => get_user_id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($body['items'] as $item) {
            $subtotal = ($item['unit_cost'] ?? 0) * $item['quantity'];
            $db->insert('purchase_items', [
                'purchase_id' => $purchaseId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'] ?? 0,
                'subtotal' => $subtotal,
                'received_quantity' => 0,
            ]);
        }

        $purchase = $db->fetch("SELECT * FROM purchases WHERE id = ?", [$purchaseId]);
        $items = $db->fetchAll("SELECT * FROM purchase_items WHERE purchase_id = ?", [$purchaseId]);
        $purchase['items'] = $items;

        Response::success($purchase, 'Purchase created', 201);
    }

    public function show(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $purchase = $db->fetch(
            "SELECT p.*, s.name as supplier_name
             FROM purchases p LEFT JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.id = ?",
            [$id]
        );

        if (!$purchase) {
            Response::error('Purchase not found', 404);
        }

        $items = $db->fetchAll(
            "SELECT pi.*, pr.name as product_name
             FROM purchase_items pi JOIN products pr ON pr.id = pi.product_id
             WHERE pi.purchase_id = ?",
            [$id]
        );

        $payments = $db->fetchAll(
            "SELECT * FROM purchase_payments WHERE purchase_id = ? ORDER BY created_at DESC",
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

        $purchase = $db->fetch("SELECT id, status FROM purchases WHERE id = ?", [$id]);
        if (!$purchase) {
            Response::error('Purchase not found', 404);
        }

        if ($purchase['status'] === 'received') {
            Response::error('Cannot update received purchase', 409);
        }

        $updateData = [];
        if (isset($body['supplier_id'])) $updateData['supplier_id'] = $body['supplier_id'];
        if (isset($body['notes'])) $updateData['notes'] = $body['notes'];

        if (!empty($body['items'])) {
            $total = 0;
            $db->delete('purchase_items', 'purchase_id = ?', [$id]);
            foreach ($body['items'] as $item) {
                $subtotal = ($item['unit_cost'] ?? 0) * $item['quantity'];
                $total += $subtotal;
                $db->insert('purchase_items', [
                    'purchase_id' => $id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'subtotal' => $subtotal,
                    'received_quantity' => 0,
                ]);
            }
            $updateData['total'] = $total;
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $db->update('purchases', $updateData, 'id = ?', [$id]);
        }

        $purchase = $db->fetch("SELECT * FROM purchases WHERE id = ?", [$id]);
        $items = $db->fetchAll("SELECT * FROM purchase_items WHERE purchase_id = ?", [$id]);
        $purchase['items'] = $items;

        Response::success($purchase, 'Purchase updated');
    }

    public function receive(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $purchase = $db->fetch("SELECT id, status FROM purchases WHERE id = ?", [$id]);
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
                $db->query(
                    "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?",
                    [$qty, $item['product_id']]
                );
                $db->update('purchase_items', ['received_quantity' => $item['quantity']], 'id = ?', [$item['id']]);

                $db->insert('stock_movements', [
                    'product_id' => $item['product_id'],
                    'type' => 'purchase',
                    'quantity' => $qty,
                    'reference' => $purchase['id'],
                    'notes' => "Purchase #{$id} received",
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $db->update('purchases', [
            'status' => 'received',
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

        $purchase = $db->fetch("SELECT id, supplier_id, total FROM purchases WHERE id = ?", [$id]);
        if (!$purchase) {
            Response::error('Purchase not found', 404);
        }

        $db->insert('purchase_payments', [
            'purchase_id' => $id,
            'amount' => $body['amount'],
            'payment_method' => $body['payment_method'] ?? 'cash',
            'reference' => $body['reference'] ?? generate_reference('PURPAY'),
            'notes' => $body['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($purchase['supplier_id']) {
            $db->query(
                "UPDATE suppliers SET balance = balance - ? WHERE id = ?",
                [$body['amount'], $purchase['supplier_id']]
            );
        }

        Response::success(null, 'Payment recorded');
    }
}
