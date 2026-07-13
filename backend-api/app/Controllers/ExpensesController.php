<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class ExpensesController
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

        if (!empty($params['category_id'])) {
            $where .= " AND e.expense_category_id = ?";
            $queryParams[] = $params['category_id'];
        }
        if (!empty($params['date_from'])) {
            $where .= " AND e.created_at >= ?";
            $queryParams[] = $params['date_from'] . ' 00:00:00';
        }
        if (!empty($params['date_to'])) {
            $where .= " AND e.created_at <= ?";
            $queryParams[] = $params['date_to'] . ' 23:59:59';
        }

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM expenses e WHERE $where", $queryParams)['cnt'];

        $expenses = $db->fetchAll(
            "SELECT e.*, ec.name as category_name, u.name as created_by_name
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id = e.expense_category_id
             LEFT JOIN users u ON u.id = e.created_by
             WHERE $where
             ORDER BY e.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $queryParams
        );

        Response::paginated($expenses, (int)$total, $page, $perPage);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['amount']) || empty($body['description'])) {
            Response::error('Amount and description are required', 422);
        }

        $db = Database::getInstance();

        $expenseId = $db->insert('expenses', [
            'expense_category_id' => $body['expense_category_id'] ?? null,
            'amount' => $body['amount'],
            'description' => $body['description'],
            'date' => $body['date'] ?? date('Y-m-d'),
            'reference' => $body['reference'] ?? generate_reference('EXP'),
            'payment_method' => $body['payment_method'] ?? 'cash',
            'created_by' => get_user_id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $expense = $db->fetch("SELECT * FROM expenses WHERE id = ?", [$expenseId]);
        Response::success($expense, 'Expense created', 201);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $expense = $db->fetch("SELECT id FROM expenses WHERE id = ?", [$id]);
        if (!$expense) {
            Response::error('Expense not found', 404);
        }

        $updateData = [];
        $fields = ['expense_category_id', 'amount', 'description', 'date', 'reference', 'payment_method'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $body)) {
                $updateData[$field] = $body[$field];
            }
        }

        if (empty($updateData)) {
            Response::error('No data to update', 422);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $db->update('expenses', $updateData, 'id = ?', [$id]);

        $expense = $db->fetch("SELECT * FROM expenses WHERE id = ?", [$id]);
        Response::success($expense, 'Expense updated');
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $expense = $db->fetch("SELECT id FROM expenses WHERE id = ?", [$id]);
        if (!$expense) {
            Response::error('Expense not found', 404);
        }

        $db->delete('expenses', 'id = ?', [$id]);
        Response::success(null, 'Expense deleted');
    }

    public function categories(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $categories = $db->fetchAll(
            "SELECT ec.*, (SELECT COUNT(*) FROM expenses WHERE expense_category_id = ec.id) as expense_count
             FROM expense_categories ec ORDER BY ec.name ASC"
        );

        Response::success($categories);
    }
}
