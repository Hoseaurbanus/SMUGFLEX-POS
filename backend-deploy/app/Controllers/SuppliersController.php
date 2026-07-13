<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class SuppliersController
{
    public function index(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = max(1, min(100, (int)($params['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = "is_deleted = 0";
        $queryParams = [];

        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $queryParams = array_merge($queryParams, [$search, $search, $search]);
        }

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM suppliers WHERE $where", $queryParams)['cnt'];

        $suppliers = $db->fetchAll(
            "SELECT * FROM suppliers WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset",
            $queryParams
        );

        Response::paginated($suppliers, (int)$total, $page, $perPage);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['name'])) {
            Response::error('Supplier name is required', 422);
        }

        $db = Database::getInstance();

        $supplierId = $db->insert('suppliers', [
            'name' => $body['name'],
            'email' => $body['email'] ?? null,
            'phone' => $body['phone'] ?? null,
            'address' => $body['address'] ?? null,
            'company' => $body['company'] ?? null,
            'balance' => $body['balance'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $supplier = $db->fetch("SELECT * FROM suppliers WHERE id = ?", [$supplierId]);
        Response::success($supplier, 'Supplier created', 201);
    }

    public function show(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $supplier = $db->fetch("SELECT * FROM suppliers WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$supplier) {
            Response::error('Supplier not found', 404);
        }

        Response::success($supplier);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $supplier = $db->fetch("SELECT id FROM suppliers WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$supplier) {
            Response::error('Supplier not found', 404);
        }

        $updateData = [];
        $fields = ['name', 'email', 'phone', 'address', 'company'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $body)) {
                $updateData[$field] = $body[$field];
            }
        }

        if (empty($updateData)) {
            Response::error('No data to update', 422);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $db->update('suppliers', $updateData, 'id = ?', [$id]);

        $supplier = $db->fetch("SELECT * FROM suppliers WHERE id = ?", [$id]);
        Response::success($supplier, 'Supplier updated');
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $supplier = $db->fetch("SELECT id FROM suppliers WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$supplier) {
            Response::error('Supplier not found', 404);
        }

        $db->update('suppliers', ['is_deleted' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Supplier deleted');
    }

    public function statement(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $supplier = $db->fetch("SELECT * FROM suppliers WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$supplier) {
            Response::error('Supplier not found', 404);
        }

        $purchases = $db->fetchAll(
            "SELECT id, reference_number, total, status, created_at FROM purchases WHERE supplier_id = ? ORDER BY created_at DESC",
            [$id]
        );

        $payments = $db->fetchAll(
            "SELECT * FROM supplier_payments WHERE supplier_id = ? ORDER BY created_at DESC",
            [$id]
        );

        Response::success([
            'supplier' => $supplier,
            'purchases' => $purchases,
            'payments' => $payments,
        ]);
    }

    public function addPayment(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['amount']) || (float)$body['amount'] <= 0) {
            Response::error('Valid amount is required', 422);
        }

        $db = Database::getInstance();
        $amount = (float)$body['amount'];

        $supplier = $db->fetch("SELECT id, balance FROM suppliers WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$supplier) {
            Response::error('Supplier not found', 404);
        }

        $newBalance = (float)$supplier['balance'] - $amount;
        $db->update('suppliers', ['balance' => $newBalance, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);

        $db->insert('supplier_payments', [
            'supplier_id' => $id,
            'amount' => $amount,
            'payment_method' => $body['payment_method'] ?? 'cash',
            'reference' => $body['reference'] ?? generate_reference('SUP'),
            'notes' => $body['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Response::success(['balance' => $newBalance], 'Payment recorded');
    }
}
