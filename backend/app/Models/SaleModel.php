<?php

namespace App\Models;

use CodeIgniter\Model;

class SaleModel extends Model
{
    protected $table = 'sales';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'invoice_number', 'customer_id', 'user_id', 'branch_id', 'warehouse_id',
        'subtotal', 'discount_amount', 'tax_amount', 'shipping_cost',
        'total', 'paid_amount', 'due_amount',
        'payment_status', 'sale_status', 'payment_method',
        'coupon_code', 'discount_type', 'notes',
        'sale_date', 'sale_time', 'hold_reference',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['generateInvoice'];

    protected function generateInvoice(array $data): array
    {
        if (empty($data['data']['invoice_number'])) {
            $data['data']['invoice_number'] = generate_reference('INV');
        }

        return $data;
    }

    public function getSalesList(array $filters = []): array
    {
        $builder = $this->db->table('sales');
        $builder->select('sales.*, customers.first_name as customer_first_name, customers.last_name as customer_last_name, users.first_name as user_first_name, users.last_name as user_last_name, branches.name as branch_name');
        $builder->join('customers', 'customers.id = sales.customer_id', 'left');
        $builder->join('users', 'users.id = sales.user_id', 'left');
        $builder->join('branches', 'branches.id = sales.branch_id', 'left');

        if (!empty($filters['date_from'])) {
            $builder->where('sales.sale_date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('sales.sale_date <=', $filters['date_to']);
        }

        if (!empty($filters['status'])) {
            $builder->where('sales.sale_status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $builder->where('sales.payment_status', $filters['payment_status']);
        }

        if (!empty($filters['user_id'])) {
            $builder->where('sales.user_id', $filters['user_id']);
        }

        $builder->orderBy('sales.created_at', 'DESC');

        return $builder->get()->getResultArray();
    }

    public function getSaleWithItems(int $id): ?array
    {
        $sale = $this->find($id);

        if (!$sale) {
            return null;
        }

        $itemsBuilder = $this->db->table('sale_items');
        $itemsBuilder->select('sale_items.*, products.name as product_name, products.sku as product_sku');
        $itemsBuilder->join('products', 'products.id = sale_items.product_id', 'left');
        $itemsBuilder->where('sale_items.sale_id', $id);

        $sale['items'] = $itemsBuilder->get()->getResultArray();

        $paymentsBuilder = $this->db->table('sale_payments');
        $paymentsBuilder->where('sale_id', $id);
        $sale['payments'] = $paymentsBuilder->get()->getResultArray();

        return $sale;
    }
}
