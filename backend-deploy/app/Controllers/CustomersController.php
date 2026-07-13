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

        $where = "c.deleted_at IS NULL";
        $queryParams = [];

        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $where .= " AND (CONCAT(c.first_name, ' ', c.last_name) LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
            $queryParams = array_merge($queryParams, [$search, $search, $search]);
        }

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM customers c WHERE $where", $queryParams)['cnt'];

        $customers = $db->fetchAll(
            "SELECT c.*, cw.balance as wallet_balance
             FROM customers c
             LEFT JOIN customer_wallets cw ON cw.customer_id = c.id
             WHERE $where
             ORDER BY c.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $queryParams
        );

        Response::paginated($customers, (int)$total, $page, $perPage);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['first_name'])) {
            Response::error('Customer first name is required', 422);
        }

        $db = Database::getInstance();

        $customerId = $db->insert('customers', [
            'first_name' => $body['first_name'],
            'last_name' => $body['last_name'] ?? '',
            'email' => $body['email'] ?? null,
            'phone' => $body['phone'] ?? null,
            'address' => $body['address'] ?? null,
            'city' => $body['city'] ?? null,
            'state' => $body['state'] ?? null,
            'country' => $body['country'] ?? null,
            'tax_number' => $body['tax_number'] ?? null,
            'credit_limit' => $body['credit_limit'] ?? 0,
            'outstanding_balance' => $body['outstanding_balance'] ?? 0,
            'reward_points' => $body['reward_points'] ?? 0,
            'notes' => $body['notes'] ?? null,
            'is_active' => $body['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $db->insert('customer_wallets', [
            'customer_id' => $customerId,
            'balance' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $customer = $db->fetch(
            "SELECT c.*, cw.balance as wallet_balance FROM customers c LEFT JOIN customer_wallets cw ON cw.customer_id = c.id WHERE c.id = ?",
            [$customerId]
        );
        Response::success($customer, 'Customer created', 201);
    }

    public function show(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $customer = $db->fetch(
            "SELECT c.*, cw.balance as wallet_balance
             FROM customers c
             LEFT JOIN customer_wallets cw ON cw.customer_id = c.id
             WHERE c.id = ? AND c.deleted_at IS NULL",
            [$id]
        );

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

        $customer = $db->fetch("SELECT id FROM customers WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$customer) {
            Response::error('Customer not found', 404);
        }

        $updateData = [];
        $fields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'country',
                    'tax_number', 'credit_limit', 'outstanding_balance', 'reward_points', 'notes', 'is_active'];
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

        $customer = $db->fetch(
            "SELECT c.*, cw.balance as wallet_balance FROM customers c LEFT JOIN customer_wallets cw ON cw.customer_id = c.id WHERE c.id = ?",
            [$id]
        );
        Response::success($customer, 'Customer updated');
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $customer = $db->fetch("SELECT id FROM customers WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$customer) {
            Response::error('Customer not found', 404);
        }

        $db->update('customers', ['deleted_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Customer deleted');
    }

    public function wallet(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $customer = $db->fetch(
            "SELECT c.id, CONCAT(c.first_name, ' ', c.last_name) as name, cw.balance as wallet_balance
             FROM customers c
             LEFT JOIN customer_wallets cw ON cw.customer_id = c.id
             WHERE c.id = ? AND c.deleted_at IS NULL",
            [$id]
        );
        if (!$customer) {
            Response::error('Customer not found', 404);
        }

        $wallet = $db->fetch("SELECT id FROM customer_wallets WHERE customer_id = ?", [$id]);
        $transactions = [];
        if ($wallet) {
            $transactions = $db->fetchAll(
                "SELECT * FROM customer_wallet_transactions WHERE wallet_id = ? ORDER BY created_at DESC LIMIT 50",
                [$wallet['id']]
            );
        }

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

        $customer = $db->fetch("SELECT id FROM customers WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$customer) {
            Response::error('Customer not found', 404);
        }

        $wallet = $db->fetch("SELECT id, balance FROM customer_wallets WHERE customer_id = ?", [$id]);
        if (!$wallet) {
            $walletId = $db->insert('customer_wallets', [
                'customer_id' => $id,
                'balance' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $wallet = ['id' => $walletId, 'balance' => 0];
        }

        $newBalance = (float)$wallet['balance'] + $amount;
        $db->update('customer_wallets', ['balance' => $newBalance, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$wallet['id']]);

        $db->insert('customer_wallet_transactions', [
            'wallet_id' => $wallet['id'],
            'type' => 'credit',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $body['description'] ?? 'Wallet top-up',
            'reference_type' => null,
            'reference_id' => null,
            'user_id' => get_user_id(),
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

        $customer = $db->fetch("SELECT id FROM customers WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$customer) {
            Response::error('Customer not found', 404);
        }

        $wallet = $db->fetch("SELECT id, balance FROM customer_wallets WHERE customer_id = ?", [$id]);
        if (!$wallet) {
            Response::error('Customer wallet not found', 404);
        }

        if ((float)$wallet['balance'] < $amount) {
            Response::error('Insufficient wallet balance', 400);
        }

        $newBalance = (float)$wallet['balance'] - $amount;
        $db->update('customer_wallets', ['balance' => $newBalance, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$wallet['id']]);

        $db->insert('customer_wallet_transactions', [
            'wallet_id' => $wallet['id'],
            'type' => 'debit',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $body['description'] ?? 'Wallet deduction',
            'reference_type' => null,
            'reference_id' => null,
            'user_id' => get_user_id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Response::success(['wallet_balance' => $newBalance], 'Deduction successful');
    }

    public function statement(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $customer = $db->fetch(
            "SELECT c.id, CONCAT(c.first_name, ' ', c.last_name) as name, cw.balance as wallet_balance
             FROM customers c
             LEFT JOIN customer_wallets cw ON cw.customer_id = c.id
             WHERE c.id = ? AND c.deleted_at IS NULL",
            [$id]
        );
        if (!$customer) {
            Response::error('Customer not found', 404);
        }

        $where = "s.customer_id = ?";
        $queryParams = [$id];

        if (!empty($params['from_date'])) {
            $where .= " AND s.created_at >= ?";
            $queryParams[] = $params['from_date'] . ' 00:00:00';
        }
        if (!empty($params['to_date'])) {
            $where .= " AND s.created_at <= ?";
            $queryParams[] = $params['to_date'] . ' 23:59:59';
        }

        $sales = $db->fetchAll(
            "SELECT id, invoice_number, total, payment_method, sale_status, created_at
             FROM sales s WHERE $where ORDER BY created_at DESC",
            $queryParams
        );

        $wallet = $db->fetch("SELECT id FROM customer_wallets WHERE customer_id = ?", [$id]);
        $walletTransactions = [];
        if ($wallet) {
            $walletTransactions = $db->fetchAll(
                "SELECT * FROM customer_wallet_transactions WHERE wallet_id = ? ORDER BY created_at DESC",
                [$wallet['id']]
            );
        }

        Response::success([
            'customer' => $customer,
            'sales' => $sales,
            'wallet_transactions' => $walletTransactions,
        ]);
    }
}
