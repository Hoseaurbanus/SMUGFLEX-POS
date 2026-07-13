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

        $where = "e.deleted_at IS NULL";
        $queryParams = [];

        if (!empty($params['category_id'])) {
            $where .= " AND e.expense_category_id = ?";
            $queryParams[] = $params['category_id'];
        }
        if (!empty($params['branch_id'])) {
            $where .= " AND e.branch_id = ?";
            $queryParams[] = $params['branch_id'];
        }
        if (!empty($params['date_from'])) {
            $where .= " AND e.expense_date >= ?";
            $queryParams[] = $params['date_from'];
        }
        if (!empty($params['date_to'])) {
            $where .= " AND e.expense_date <= ?";
            $queryParams[] = $params['date_to'];
        }

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM expenses e WHERE $where", $queryParams)['cnt'];

        $expenses = $db->fetchAll(
            "SELECT e.*, ec.name as category_name, CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    b.name as branch_name
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id = e.expense_category_id
             LEFT JOIN users u ON u.id = e.user_id
             LEFT JOIN branches b ON b.id = e.branch_id
             WHERE $where
             ORDER BY e.expense_date DESC
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

        if (empty($body['branch_id'])) {
            Response::error('Branch ID is required', 422);
        }

        $db = Database::getInstance();

        $expenseId = $db->insert('expenses', [
            'reference_number' => generate_reference('EXP'),
            'expense_category_id' => $body['expense_category_id'] ?? null,
            'branch_id' => $body['branch_id'],
            'amount' => $body['amount'],
            'payment_method' => $body['payment_method'] ?? 'cash',
            'expense_date' => $body['expense_date'] ?? date('Y-m-d'),
            'description' => $body['description'],
            'receipt_file' => $body['receipt_file'] ?? null,
            'user_id' => get_user_id(),
            'is_recurring' => $body['is_recurring'] ?? 0,
            'recurring_frequency' => $body['recurring_frequency'] ?? null,
            'status' => $body['status'] ?? 'approved',
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

        $expense = $db->fetch("SELECT id FROM expenses WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$expense) {
            Response::error('Expense not found', 404);
        }

        $updateData = [];
        $fields = ['expense_category_id', 'branch_id', 'amount', 'payment_method', 'expense_date', 'description',
                    'receipt_file', 'is_recurring', 'recurring_frequency', 'status'];
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

        $expense = $db->fetch("SELECT id FROM expenses WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$expense) {
            Response::error('Expense not found', 404);
        }

        $db->update('expenses', ['deleted_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Expense deleted');
    }

    public function categories(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $categories = $db->fetchAll(
            "SELECT ec.*, (SELECT COUNT(*) FROM expenses WHERE expense_category_id = ec.id AND deleted_at IS NULL) as expense_count
             FROM expense_categories ec ORDER BY ec.name ASC"
        );

        Response::success($categories);
    }

    public function createCategory(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['name'])) {
            Response::error('Category name is required', 422);
        }

        $db = Database::getInstance();

        $categoryId = $db->insert('expense_categories', [
            'name' => $body['name'],
            'description' => $body['description'] ?? null,
            'is_active' => $body['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $category = $db->fetch("SELECT * FROM expense_categories WHERE id = ?", [$categoryId]);
        Response::success($category, 'Category created', 201);
    }

    public function updateCategory(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $category = $db->fetch("SELECT id FROM expense_categories WHERE id = ?", [$id]);
        if (!$category) {
            Response::error('Category not found', 404);
        }

        $updateData = [];
        if (isset($body['name'])) $updateData['name'] = $body['name'];
        if (isset($body['description'])) $updateData['description'] = $body['description'];
        if (isset($body['is_active'])) $updateData['is_active'] = $body['is_active'];

        if (empty($updateData)) {
            Response::error('No data to update', 422);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $db->update('expense_categories', $updateData, 'id = ?', [$id]);

        $category = $db->fetch("SELECT * FROM expense_categories WHERE id = ?", [$id]);
        Response::success($category, 'Category updated');
    }

    public function deleteCategory(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $category = $db->fetch("SELECT id FROM expense_categories WHERE id = ?", [$id]);
        if (!$category) {
            Response::error('Category not found', 404);
        }

        $expenseCount = $db->fetch("SELECT COUNT(*) as cnt FROM expenses WHERE expense_category_id = ? AND deleted_at IS NULL", [$id]);
        if ((int)$expenseCount['cnt'] > 0) {
            Response::error('Cannot delete category with expenses', 409);
        }

        $db->delete('expense_categories', 'id = ?', [$id]);
        Response::success(null, 'Category deleted');
    }
}
