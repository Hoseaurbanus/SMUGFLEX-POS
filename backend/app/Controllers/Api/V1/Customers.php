<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Customers extends BaseController
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

        $builder = $this->db->table('customers');
        $builder->where('deleted_at', null);

        if ($search) {
            $builder->groupStart();
            $builder->like('first_name', $search);
            $builder->orLike('last_name', $search);
            $builder->orLike('email', $search);
            $builder->orLike('phone', $search);
            $builder->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $customers = $builder->get()->getResultArray();

        return paginated_response($customers, $total, $page, $limit);
    }

    public function create()
    {
        $data = $this->getRequestData();

        $rules = [
            'first_name' => 'required|max_length[50]',
            'last_name'  => 'required|max_length[50]',
            'phone'      => 'permit_empty|max_length[20]',
            'email'      => 'permit_empty|valid_email|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $data['wallet_balance'] = $data['wallet_balance'] ?? 0;

        $id = $this->db->table('customers')->insert($data);

        if (!$id) {
            return api_error('Failed to create customer', 500);
        }

        $customer = $this->db->table('customers')->where('id', $id)->get()->getRowArray();

        return api_success($customer, 'Customer created', 201);
    }

    public function show($id = null)
    {
        $customer = $this->db->table('customers')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$customer) {
            return api_error('Customer not found', 404);
        }

        return api_success($customer);
    }

    public function update($id = null)
    {
        $customer = $this->db->table('customers')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$customer) {
            return api_error('Customer not found', 404);
        }

        $data = $this->getRequestData();
        unset($data['wallet_balance']);

        $this->db->table('customers')->where('id', $id)->update($data);

        $updated = $this->db->table('customers')->where('id', $id)->get()->getRowArray();

        return api_success($updated, 'Customer updated');
    }

    public function delete($id = null)
    {
        $customer = $this->db->table('customers')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$customer) {
            return api_error('Customer not found', 404);
        }

        $sales = $this->db->table('sales')->where('customer_id', $id)->countAllResults();

        if ($sales > 0) {
            return api_error('Cannot delete customer with sales history', 422);
        }

        $this->db->table('customers')->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);

        return api_success(null, 'Customer deleted');
    }

    public function wallet($id = null)
    {
        $customer = $this->db->table('customers')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$customer) {
            return api_error('Customer not found', 404);
        }

        return api_success([
            'wallet_balance' => (float) ($customer['wallet_balance'] ?? 0),
        ]);
    }

    public function walletTopup($id = null)
    {
        $customer = $this->db->table('customers')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$customer) {
            return api_error('Customer not found', 404);
        }

        $data = $this->getRequestData();

        $rules = [
            'amount' => 'required|decimal|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $amount = (float) $data['amount'];
        $newBalance = (float) $customer['wallet_balance'] + $amount;

        $this->db->table('customers')->where('id', $id)->update(['wallet_balance' => $newBalance]);

        $this->db->table('wallet_transactions')->insert([
            'customer_id' => $id,
            'type'        => 'topup',
            'amount'      => $amount,
            'balance_after' => $newBalance,
            'description' => $data['description'] ?? 'Wallet topup',
            'reference'   => generate_reference('WLT'),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $updated = $this->db->table('customers')->where('id', $id)->get()->getRowArray();

        return api_success($updated, 'Wallet topped up successfully');
    }

    public function walletDeduct($id = null)
    {
        $customer = $this->db->table('customers')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$customer) {
            return api_error('Customer not found', 404);
        }

        $data = $this->getRequestData();

        $rules = [
            'amount' => 'required|decimal|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $amount = (float) $data['amount'];
        $currentBalance = (float) $customer['wallet_balance'];

        if ($amount > $currentBalance) {
            return api_error('Insufficient wallet balance', 422);
        }

        $newBalance = $currentBalance - $amount;

        $this->db->table('customers')->where('id', $id)->update(['wallet_balance' => $newBalance]);

        $this->db->table('wallet_transactions')->insert([
            'customer_id' => $id,
            'type'        => 'deduct',
            'amount'      => -$amount,
            'balance_after' => $newBalance,
            'description' => $data['description'] ?? 'Wallet deduction',
            'reference'   => generate_reference('WLT'),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $updated = $this->db->table('customers')->where('id', $id)->get()->getRowArray();

        return api_success($updated, 'Amount deducted from wallet');
    }

    public function statement($id = null)
    {
        $customer = $this->db->table('customers')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$customer) {
            return api_error('Customer not found', 404);
        }

        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 20);

        $builder = $this->db->table('wallet_transactions');
        $builder->where('customer_id', $id);
        $total = $builder->countAllResults(false);
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $transactions = $builder->get()->getResultArray();

        return paginated_response($transactions, $total, $page, $limit);
    }

    public function purchaseHistory($id = null)
    {
        $customer = $this->db->table('customers')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$customer) {
            return api_error('Customer not found', 404);
        }

        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 15);

        $builder = $this->db->table('sales');
        $builder->select('sales.*, users.first_name as cashier_name, users.last_name as cashier_last_name');
        $builder->join('users', 'users.id = sales.user_id', 'left');
        $builder->where('sales.customer_id', $id);
        $builder->where('sales.sale_status', 'completed');
        $total = $builder->countAllResults(false);
        $builder->orderBy('sales.created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $sales = $builder->get()->getResultArray();

        return paginated_response($sales, $total, $page, $limit);
    }
}
