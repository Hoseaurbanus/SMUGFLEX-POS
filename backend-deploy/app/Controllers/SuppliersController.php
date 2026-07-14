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

        $where = "s.deleted_at IS NULL";
        $queryParams = [];

        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $where .= " AND (s.name LIKE ? OR s.contact_person LIKE ? OR s.email LIKE ? OR s.phone LIKE ?)";
            $queryParams = array_merge($queryParams, [$search, $search, $search, $search]);
        }

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM suppliers s WHERE $where", $queryParams)['cnt'];

        $suppliers = $db->fetchAll(
            "SELECT s.* FROM suppliers s
             WHERE $where
             ORDER BY s.created_at DESC
             LIMIT $perPage OFFSET $offset",
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
            'contact_person' => $body['contact_person'] ?? null,
            'email' => $body['email'] ?? null,
            'phone' => $body['phone'] ?? null,
            'address' => $body['address'] ?? null,
            'city' => $body['city'] ?? null,
            'state' => $body['state'] ?? null,
            'country' => $body['country'] ?? null,
            'tax_number' => $body['tax_number'] ?? null,
            'bank_name' => $body['bank_name'] ?? null,
            'bank_account_number' => $body['bank_account_number'] ?? null,
            'bank_account_name' => $body['bank_account_name'] ?? null,
            'outstanding_balance' => $body['outstanding_balance'] ?? 0,
            'notes' => $body['notes'] ?? null,
            'is_active' => $body['is_active'] ?? 1,
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

        $supplier = $db->fetch("SELECT * FROM suppliers WHERE id = ? AND deleted_at IS NULL", [$id]);
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

        $supplier = $db->fetch("SELECT id FROM suppliers WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$supplier) {
            Response::error('Supplier not found', 404);
        }

        $updateData = [];
        $fields = ['name', 'contact_person', 'email', 'phone', 'address', 'city', 'state', 'country',
                    'tax_number', 'bank_name', 'bank_account_number', 'bank_account_name',
                    'outstanding_balance', 'notes', 'is_active'];
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

        $supplier = $db->fetch("SELECT id FROM suppliers WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$supplier) {
            Response::error('Supplier not found', 404);
        }

        $db->update('suppliers', ['deleted_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Supplier deleted');
    }

    public function statement(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $supplier = $db->fetch("SELECT * FROM suppliers WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$supplier) {
            Response::error('Supplier not found', 404);
        }

        $purchases = $db->fetchAll(
            "SELECT id, reference_number, total, status, payment_status, created_at FROM purchases WHERE supplier_id = ? ORDER BY created_at DESC",
            [$id]
        );

        $payments = $db->fetchAll(
            "SELECT pp.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
             FROM purchase_payments pp
             LEFT JOIN users u ON u.id = pp.user_id
             WHERE pp.purchase_id IN (SELECT id FROM purchases WHERE supplier_id = ?)
             ORDER BY pp.created_at DESC",
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

        $supplier = $db->fetch("SELECT id, outstanding_balance FROM suppliers WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$supplier) {
            Response::error('Supplier not found', 404);
        }

        $newBalance = (float)$supplier['outstanding_balance'] - $amount;
        $db->update('suppliers', ['outstanding_balance' => $newBalance, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);

        if (!empty($body['purchase_id'])) {
            $db->insert('purchase_payments', [
                'purchase_id' => $body['purchase_id'],
                'amount' => $amount,
                'payment_method' => $body['payment_method'] ?? 'cash',
                'reference' => $body['reference'] ?? null,
                'notes' => $body['notes'] ?? null,
                'payment_date' => date('Y-m-d H:i:s'),
                'user_id' => get_user_id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        Response::success(['outstanding_balance' => $newBalance], 'Payment recorded');
    }
}
