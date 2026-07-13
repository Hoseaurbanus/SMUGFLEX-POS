<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Suppliers extends BaseController
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

        $builder = $this->db->table('suppliers');
        $builder->where('deleted_at', null);

        if ($search) {
            $builder->groupStart();
            $builder->like('name', $search);
            $builder->orLike('email', $search);
            $builder->orLike('phone', $search);
            $builder->orLike('company', $search);
            $builder->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $suppliers = $builder->get()->getResultArray();

        return paginated_response($suppliers, $total, $page, $limit);
    }

    public function create()
    {
        $data = $this->getRequestData();

        $rules = [
            'name'    => 'required|max_length[200]',
            'phone'   => 'required|max_length[20]',
            'email'   => 'permit_empty|valid_email|max_length[100]',
            'company' => 'permit_empty|max_length[200]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $data['due_amount'] = $data['due_amount'] ?? 0;

        $id = $this->db->table('suppliers')->insert($data);

        if (!$id) {
            return api_error('Failed to create supplier', 500);
        }

        $supplier = $this->db->table('suppliers')->where('id', $id)->get()->getRowArray();

        return api_success($supplier, 'Supplier created', 201);
    }

    public function show($id = null)
    {
        $supplier = $this->db->table('suppliers')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$supplier) {
            return api_error('Supplier not found', 404);
        }

        return api_success($supplier);
    }

    public function update($id = null)
    {
        $supplier = $this->db->table('suppliers')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$supplier) {
            return api_error('Supplier not found', 404);
        }

        $data = $this->getRequestData();
        unset($data['due_amount']);

        $this->db->table('suppliers')->where('id', $id)->update($data);

        $updated = $this->db->table('suppliers')->where('id', $id)->get()->getRowArray();

        return api_success($updated, 'Supplier updated');
    }

    public function delete($id = null)
    {
        $supplier = $this->db->table('suppliers')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$supplier) {
            return api_error('Supplier not found', 404);
        }

        $purchases = $this->db->table('purchases')->where('supplier_id', $id)->countAllResults();

        if ($purchases > 0) {
            return api_error('Cannot delete supplier with purchase history', 422);
        }

        $this->db->table('suppliers')->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);

        return api_success(null, 'Supplier deleted');
    }

    public function statement($id = null)
    {
        $supplier = $this->db->table('suppliers')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$supplier) {
            return api_error('Supplier not found', 404);
        }

        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 20);

        $builder = $this->db->table('supplier_payments');
        $builder->select('supplier_payments.*, users.first_name, users.last_name');
        $builder->join('users', 'users.id = supplier_payments.user_id', 'left');
        $builder->where('supplier_payments.supplier_id', $id);
        $total = $builder->countAllResults(false);
        $builder->orderBy('supplier_payments.created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $payments = $builder->get()->getResultArray();

        return paginated_response($payments, $total, $page, $limit);
    }

    public function addPayment($id = null)
    {
        $supplier = $this->db->table('suppliers')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$supplier) {
            return api_error('Supplier not found', 404);
        }

        $data = $this->getRequestData();

        $rules = [
            'amount'    => 'required|decimal|greater_than[0]',
            'paid_via'  => 'required|in_list[cash,bank_transfer,card,cheque,mobile]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $amount = (float) $data['amount'];
        $currentDue = (float) $supplier['due_amount'];

        if ($amount > $currentDue) {
            return api_error('Payment amount exceeds due amount', 422);
        }

        $newDue = $currentDue - $amount;

        $this->db->table('suppliers')->where('id', $id)->update(['due_amount' => $newDue]);

        $paymentData = [
            'supplier_id'    => $id,
            'amount'         => $amount,
            'paid_via'       => $data['paid_via'],
            'reference'      => $data['reference'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'user_id'        => get_user_id_from_token(),
            'payment_date'   => date('Y-m-d'),
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        $paymentId = $this->db->table('supplier_payments')->insert($paymentData);

        if (!$paymentId) {
            return api_error('Failed to record payment', 500);
        }

        return api_success(null, 'Payment recorded successfully', 201);
    }
}
