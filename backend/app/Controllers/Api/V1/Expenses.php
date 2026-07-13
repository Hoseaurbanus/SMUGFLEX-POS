<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Expenses extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 15);
        $search = $this->request->getVar('search') ?? '';
        $categoryId = $this->request->getVar('category_id') ?? '';
        $dateFrom = $this->request->getVar('date_from') ?? '';
        $dateTo = $this->request->getVar('date_to') ?? '';

        $builder = $this->db->table('expenses');
        $builder->select('expenses.*, expense_categories.name as category_name, users.first_name, users.last_name, warehouses.name as warehouse_name');
        $builder->join('expense_categories', 'expense_categories.id = expenses.category_id', 'left');
        $builder->join('users', 'users.id = expenses.user_id', 'left');
        $builder->join('warehouses', 'warehouses.id = expenses.warehouse_id', 'left');

        if ($search) {
            $builder->like('expenses.title', $search);
        }

        if ($categoryId) {
            $builder->where('expenses.category_id', $categoryId);
        }

        if ($dateFrom) {
            $builder->where('expenses.expense_date >=', $dateFrom);
        }

        if ($dateTo) {
            $builder->where('expenses.expense_date <=', $dateTo);
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('expenses.created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $expenses = $builder->get()->getResultArray();

        return paginated_response($expenses, $total, $page, $limit);
    }

    public function create()
    {
        $data = $this->getRequestData();

        $rules = [
            'title'       => 'required|max_length[200]',
            'amount'      => 'required|decimal|greater_than[0]',
            'category_id' => 'required|integer',
            'expense_date' => 'required|valid_date',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $data['reference'] = $data['reference'] ?? generate_reference('EXP');
        $data['user_id'] = get_user_id_from_token();
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = $this->db->table('expenses')->insert($data);

        if (!$id) {
            return api_error('Failed to create expense', 500);
        }

        $expense = $this->db->table('expenses')->where('id', $id)->get()->getRowArray();

        return api_success($expense, 'Expense created', 201);
    }

    public function update($id = null)
    {
        $expense = $this->db->table('expenses')->where('id', $id)->get()->getRowArray();

        if (!$expense) {
            return api_error('Expense not found', 404);
        }

        $data = $this->getRequestData();
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->table('expenses')->where('id', $id)->update($data);

        $updated = $this->db->table('expenses')->where('id', $id)->get()->getRowArray();

        return api_success($updated, 'Expense updated');
    }

    public function delete($id = null)
    {
        $expense = $this->db->table('expenses')->where('id', $id)->get()->getRowArray();

        if (!$expense) {
            return api_error('Expense not found', 404);
        }

        $this->db->table('expenses')->where('id', $id)->delete();

        return api_success(null, 'Expense deleted');
    }

    public function categories()
    {
        $categories = $this->db->table('expense_categories')
            ->select('expense_categories.*, COUNT(expenses.id) as expense_count')
            ->join('expenses', 'expenses.category_id = expense_categories.id', 'left')
            ->groupBy('expense_categories.id')
            ->orderBy('expense_categories.name', 'ASC')
            ->get()
            ->getResultArray();

        return api_success($categories);
    }

    public function createCategory()
    {
        $data = $this->getRequestData();

        $rules = [
            'name' => 'required|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $data['slug'] = slugify($data['name']);

        $id = $this->db->table('expense_categories')->insert($data);

        if (!$id) {
            return api_error('Failed to create expense category', 500);
        }

        $category = $this->db->table('expense_categories')->where('id', $id)->get()->getRowArray();

        return api_success($category, 'Expense category created', 201);
    }

    public function updateCategory($id = null)
    {
        $category = $this->db->table('expense_categories')->where('id', $id)->get()->getRowArray();

        if (!$category) {
            return api_error('Expense category not found', 404);
        }

        $data = $this->getRequestData();

        if (!empty($data['name'])) {
            $data['slug'] = slugify($data['name']);
        }

        $this->db->table('expense_categories')->where('id', $id)->update($data);

        $updated = $this->db->table('expense_categories')->where('id', $id)->get()->getRowArray();

        return api_success($updated, 'Expense category updated');
    }

    public function deleteCategory($id = null)
    {
        $category = $this->db->table('expense_categories')->where('id', $id)->get()->getRowArray();

        if (!$category) {
            return api_error('Expense category not found', 404);
        }

        $expenses = $this->db->table('expenses')->where('category_id', $id)->countAllResults();

        if ($expenses > 0) {
            return api_error('Cannot delete category with associated expenses', 422);
        }

        $this->db->table('expense_categories')->where('id', $id)->delete();

        return api_success(null, 'Expense category deleted');
    }
}
