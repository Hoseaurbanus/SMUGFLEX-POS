<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class CustomersController
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

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM customers WHERE $where", $queryParams)['cnt'];

        $customers = $db->fetchAll(
            "SELECT * FROM customers WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset",
            $queryParams
        );

        Response::paginated($customers, (int)$total, $page, $perPage);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['name'])) {
            Response::error('Customer name is required', 422);
        }

        $db = Database::getInstance();

        $customerId = $db->insert('customers', [
            'name' => $body['name'],
            'email' => $body['email'] ?? null,
            'phone' => $body['phone'] ?? null,
            'address' => $body['address'] ?? null,
            'wallet_balance' => $body['wallet_balance'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $customer = $db->fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);
        Response::success($customer, 'Customer created', 201);
    }

    public function show(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $customer = $db->fetch("SELECT * FROM customers WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$customer) {
            Response::error('Customer not found', 404);
        }

        Response::success($customer);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $customer = $db->fetch("SELECT id FROM customers WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$customer) {
            Response::error('Customer not found', 404);
        }

        $updateData = [];
        $fields = ['name', 'email', 'phone', 'address'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $body)) {
                $updateData[$field] = $body[$field];
            }
        }

        if (empty($updateData)) {
            Response::error('No data to update', 422);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $db->update('customers', $updateData, 'id = ?', [$id]);

        $customer = $db->fetch("SELECT * FROM customers WHERE id = ?", [$id]);
        Response::success($customer, 'Customer updated');
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $customer = $db->fetch("SELECT id FROM customers WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$customer) {
            Response::error('Customer not found', 404);
        }

        $db->update('customers', ['is_deleted' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Customer deleted');
    }

    public function wallet(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $customer = $db->fetch("SELECT id, name, wallet_balance FROM customers WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$customer) {
            Response::error('Customer not found', 404);
        }

        $transactions = $db->fetchAll(
            "SELECT * FROM wallet_transactions WHERE customer_id = ? ORDER BY created_at DESC LIMIT 50",
            [$id]
        );

        Response::success([
            'balance' => (float)$customer['wallet_balance'],
            'transactions' => $transactions,
        ]);
    }

    public function walletTopup(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['amount']) || (float)$body['amount'] <= 0) {
            Response::error('Valid amount is required', 422);
        }

        $db = Database::getInstance();
        $amount = (float)$body['amount'];

        $customer = $db->fetch("SELECT id, wallet_balance FROM customers WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$customer) {
            Response::error('Customer not found', 404);
        }

        $newBalance = (float)$customer['wallet_balance'] + $amount;
        $db->update('customers', ['wallet_balance' => $newBalance, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);

        $db->insert('wallet_transactions', [
            'customer_id' => $id,
            'type' => 'topup',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $body['description'] ?? 'Wallet top-up',
            'reference' => generate_reference('WLT'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Response::success(['wallet_balance' => $newBalance], 'Top-up successful');
    }

    public function walletDeduct(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['amount']) || (float)$body['amount'] <= 0) {
            Response::error('Valid amount is required', 422);
        }

        $db = Database::getInstance();
        $amount = (float)$body['amount'];

        $customer = $db->fetch("SELECT id, wallet_balance FROM customers WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$customer) {
            Response::error('Customer not found', 404);
        }

        if ((float)$customer['wallet_balance'] < $amount) {
            Response::error('Insufficient wallet balance', 400);
        }

        $newBalance = (float)$customer['wallet_balance'] - $amount;
        $db->update('customers', ['wallet_balance' => $newBalance, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);

        $db->insert('wallet_transactions', [
            'customer_id' => $id,
            'type' => 'deduction',
            'amount' => -$amount,
            'balance_after' => $newBalance,
            'description' => $body['description'] ?? 'Wallet deduction',
            'reference' => generate_reference('WLT'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Response::success(['wallet_balance' => $newBalance], 'Deduction successful');
    }

    public function statement(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $customer = $db->fetch("SELECT id, name, wallet_balance FROM customers WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$customer) {
            Response::error('Customer not found', 404);
        }

        $where = "customer_id = ?";
        $queryParams = [$id];

        if (!empty($params['from_date'])) {
            $where .= " AND created_at >= ?";
            $queryParams[] = $params['from_date'] . ' 00:00:00';
        }
        if (!empty($params['to_date'])) {
            $where .= " AND created_at <= ?";
            $queryParams[] = $params['to_date'] . ' 23:59:59';
        }

        $sales = $db->fetchAll(
            "SELECT id, reference_number, total, payment_method, status, created_at
             FROM sales WHERE $where ORDER BY created_at DESC",
            $queryParams
        );

        $walletTransactions = $db->fetchAll(
            "SELECT * FROM wallet_transactions WHERE customer_id = ? ORDER BY created_at DESC",
            [$id]
        );

        Response::success([
            'customer' => $customer,
            'sales' => $sales,
            'wallet_transactions' => $walletTransactions,
        ]);
    }
}
