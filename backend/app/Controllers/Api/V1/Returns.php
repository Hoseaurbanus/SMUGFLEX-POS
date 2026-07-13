<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Returns extends BaseController
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
        $status = $this->request->getVar('status') ?? '';

        $builder = $this->db->table('sale_returns');
        $builder->select('sale_returns.*, sales.invoice_number, customers.first_name, customers.last_name, users.first_name as processed_by_name');
        $builder->join('sales', 'sales.id = sale_returns.sale_id', 'left');
        $builder->join('customers', 'customers.id = sales.customer_id', 'left');
        $builder->join('users', 'users.id = sale_returns.user_id', 'left');

        if ($status) {
            $builder->where('sale_returns.status', $status);
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('sale_returns.created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $returns = $builder->get()->getResultArray();

        return paginated_response($returns, $total, $page, $limit);
    }

    public function show($id = null)
    {
        $builder = $this->db->table('sale_returns');
        $builder->select('sale_returns.*, sales.invoice_number, sales.total as sale_total, customers.first_name, customers.last_name, customers.phone as customer_phone, users.first_name as processed_by_name');
        $builder->join('sales', 'sales.id = sale_returns.sale_id', 'left');
        $builder->join('customers', 'customers.id = sales.customer_id', 'left');
        $builder->join('users', 'users.id = sale_returns.user_id', 'left');
        $builder->where('sale_returns.id', $id);
        $return = $builder->get()->getRowArray();

        if (!$return) {
            return api_error('Return not found', 404);
        }

        $returnItems = $this->db->table('sale_return_items')
            ->select('sale_return_items.*, products.name as product_name, products.sku as product_sku')
            ->join('products', 'products.id = sale_return_items.product_id', 'left')
            ->where('sale_return_items.return_id', $id)
            ->get()
            ->getResultArray();

        $return['items'] = $returnItems;

        return api_success($return);
    }

    public function approve($id = null)
    {
        $return = $this->db->table('sale_returns')->where('id', $id)->get()->getRowArray();

        if (!$return) {
            return api_error('Return not found', 404);
        }

        if ($return['status'] === 'approved') {
            return api_error('Return already approved', 422);
        }

        $this->db->transStart();

        $this->db->table('sale_returns')->where('id', $id)->update([
            'status'      => 'approved',
            'approved_by' => get_user_id_from_token(),
            'approved_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $sale = $this->db->table('sales')->where('id', $return['sale_id'])->get()->getRowArray();

        if ($sale && $sale['customer_id']) {
            $customer = $this->db->table('customers')->where('id', $sale['customer_id'])->get()->getRowArray();

            if ($customer) {
                $newBalance = (float) $customer['wallet_balance'] + (float) $return['total'];
                $this->db->table('customers')->where('id', $customer['id'])->update([
                    'wallet_balance' => $newBalance,
                ]);
                $this->db->table('wallet_transactions')->insert([
                    'customer_id'    => $customer['id'],
                    'type'           => 'refund',
                    'amount'         => $return['total'],
                    'balance_after'  => $newBalance,
                    'description'    => 'Refund for return #' . $return['return_number'],
                    'reference'      => $return['return_number'],
                    'created_at'     => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return api_error('Failed to approve return', 500);
        }

        return api_success(null, 'Return approved successfully');
    }
}
