<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Purchases extends BaseController
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
        $status = $this->request->getVar('status') ?? '';
        $dateFrom = $this->request->getVar('date_from') ?? '';
        $dateTo = $this->request->getVar('date_to') ?? '';

        $builder = $this->db->table('purchases');
        $builder->select('purchases.*, suppliers.name as supplier_name, users.first_name, users.last_name, warehouses.name as warehouse_name');
        $builder->join('suppliers', 'suppliers.id = purchases.supplier_id', 'left');
        $builder->join('users', 'users.id = purchases.user_id', 'left');
        $builder->join('warehouses', 'warehouses.id = purchases.warehouse_id', 'left');

        if ($search) {
            $builder->like('purchases.reference_number', $search);
        }

        if ($status) {
            $builder->where('purchases.status', $status);
        }

        if ($dateFrom) {
            $builder->where('purchases.purchase_date >=', $dateFrom);
        }

        if ($dateTo) {
            $builder->where('purchases.purchase_date <=', $dateTo);
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('purchases.created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $purchases = $builder->get()->getResultArray();

        return paginated_response($purchases, $total, $page, $limit);
    }

    public function create()
    {
        $data = $this->getRequestData();

        $rules = [
            'supplier_id'           => 'required|integer',
            'warehouse_id'          => 'required|integer',
            'items'                 => 'required',
            'items.*.product_id'    => 'required|integer',
            'items.*.quantity'      => 'required|integer|greater_than[0]',
            'items.*.unit_cost'     => 'required|decimal|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $subtotal += $item['quantity'] * $item['unit_cost'];
        }

        $taxAmount = $data['tax_amount'] ?? 0;
        $discountAmount = $data['discount_amount'] ?? 0;
        $total = $subtotal + $taxAmount - $discountAmount;
        $dueAmount = $total - ($data['paid_amount'] ?? 0);

        $purchaseData = [
            'reference_number'  => generate_reference('PO'),
            'supplier_id'       => $data['supplier_id'],
            'warehouse_id'      => $data['warehouse_id'],
            'user_id'           => get_user_id_from_token(),
            'subtotal'          => $subtotal,
            'tax_amount'        => $taxAmount,
            'discount_amount'   => $discountAmount,
            'total'             => $total,
            'paid_amount'       => $data['paid_amount'] ?? 0,
            'due_amount'        => $dueAmount,
            'status'            => 'pending',
            'payment_status'    => $dueAmount > 0 ? 'partial' : 'paid',
            'notes'             => $data['notes'] ?? null,
            'purchase_date'     => date('Y-m-d'),
            'created_at'        => date('Y-m-d H:i:s'),
        ];

        $purchaseId = $this->db->table('purchases')->insert($purchaseData);

        if (!$purchaseId) {
            return api_error('Failed to create purchase', 500);
        }

        foreach ($data['items'] as $item) {
            $this->db->table('purchase_items')->insert([
                'purchase_id' => $purchaseId,
                'product_id'  => $item['product_id'],
                'quantity'    => $item['quantity'],
                'unit_cost'   => $item['unit_cost'],
                'total'       => $item['quantity'] * $item['unit_cost'],
                'received_qty' => 0,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        $purchase = $this->show($purchaseId)->getBody();

        return api_success(json_decode($purchase, true)['data'], 'Purchase created', 201);
    }

    public function show($id = null)
    {
        $builder = $this->db->table('purchases');
        $builder->select('purchases.*, suppliers.name as supplier_name, suppliers.phone as supplier_phone, users.first_name, users.last_name, warehouses.name as warehouse_name');
        $builder->join('suppliers', 'suppliers.id = purchases.supplier_id', 'left');
        $builder->join('users', 'users.id = purchases.user_id', 'left');
        $builder->join('warehouses', 'warehouses.id = purchases.warehouse_id', 'left');
        $builder->where('purchases.id', $id);
        $purchase = $builder->get()->getRowArray();

        if (!$purchase) {
            return api_error('Purchase not found', 404);
        }

        $items = $this->db->table('purchase_items')
            ->select('purchase_items.*, products.name as product_name, products.sku as product_sku')
            ->join('products', 'products.id = purchase_items.product_id', 'left')
            ->where('purchase_items.purchase_id', $id)
            ->get()
            ->getResultArray();

        $purchase['items'] = $items;

        $payments = $this->db->table('purchase_payments')
            ->select('purchase_payments.*, users.first_name, users.last_name')
            ->join('users', 'users.id = purchase_payments.user_id', 'left')
            ->where('purchase_payments.purchase_id', $id)
            ->orderBy('purchase_payments.created_at', 'DESC')
            ->get()
            ->getResultArray();

        $purchase['payments'] = $payments;

        return api_success($purchase);
    }

    public function update($id = null)
    {
        $purchase = $this->db->table('purchases')->where('id', $id)->get()->getRowArray();

        if (!$purchase) {
            return api_error('Purchase not found', 404);
        }

        if ($purchase['status'] === 'received') {
            return api_error('Cannot edit a received purchase', 422);
        }

        $data = $this->getRequestData();

        if (!empty($data['items'])) {
            $this->db->table('purchase_items')->where('purchase_id', $id)->delete();

            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_cost'];
                $this->db->table('purchase_items')->insert([
                    'purchase_id' => $id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_cost'   => $item['unit_cost'],
                    'total'       => $item['quantity'] * $item['unit_cost'],
                    'received_qty' => 0,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
            }

            $data['subtotal'] = $subtotal;
            $data['total'] = $subtotal + ($data['tax_amount'] ?? 0) - ($data['discount_amount'] ?? 0);
        }

        unset($data['items']);
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->table('purchases')->where('id', $id)->update($data);

        $updated = $this->show($id)->getBody();

        return api_success(json_decode($updated, true)['data'], 'Purchase updated');
    }

    public function receive($id = null)
    {
        $purchase = $this->db->table('purchases')->where('id', $id)->get()->getRowArray();

        if (!$purchase) {
            return api_error('Purchase not found', 404);
        }

        if ($purchase['status'] === 'received') {
            return api_error('Purchase already received', 422);
        }

        $items = $this->db->table('purchase_items')->where('purchase_id', $id)->get()->getResultArray();

        $this->db->transStart();

        foreach ($items as $item) {
            $quantityToReceive = $item['quantity'] - $item['received_qty'];

            if ($quantityToReceive > 0) {
                $this->db->table('purchase_items')->where('id', $item['id'])->update([
                    'received_qty' => $item['quantity'],
                ]);

                $existingStock = $this->db->table('product_stocks')
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $purchase['warehouse_id'])
                    ->get()
                    ->getRowArray();

                if ($existingStock) {
                    $this->db->table('product_stocks')->where('id', $existingStock['id'])->update([
                        'quantity' => $existingStock['quantity'] + $quantityToReceive,
                    ]);
                } else {
                    $this->db->table('product_stocks')->insert([
                        'product_id'    => $item['product_id'],
                        'warehouse_id'  => $purchase['warehouse_id'],
                        'quantity'      => $quantityToReceive,
                        'created_at'    => date('Y-m-d H:i:s'),
                    ]);
                }

                $this->db->table('stock_movements')->insert([
                    'product_id'    => $item['product_id'],
                    'warehouse_id'  => $purchase['warehouse_id'],
                    'type'          => 'purchase',
                    'quantity'      => $quantityToReceive,
                    'reference'     => $purchase['reference_number'],
                    'user_id'       => get_user_id_from_token(),
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->table('purchases')->where('id', $id)->update([
            'status'     => 'received',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return api_error('Failed to receive purchase', 500);
        }

        return api_success(null, 'Purchase received successfully');
    }

    public function returnPurchase($id = null)
    {
        $purchase = $this->db->table('purchases')->where('id', $id)->get()->getRowArray();

        if (!$purchase) {
            return api_error('Purchase not found', 404);
        }

        if ($purchase['status'] !== 'received') {
            return api_error('Only received purchases can be returned', 422);
        }

        $data = $this->getRequestData();

        $rules = [
            'items'                 => 'required',
            'items.*.product_id'    => 'required|integer',
            'items.*.quantity'      => 'required|integer|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $this->db->transStart();

        foreach ($data['items'] as $item) {
            $existingStock = $this->db->table('product_stocks')
                ->where('product_id', $item['product_id'])
                ->where('warehouse_id', $purchase['warehouse_id'])
                ->get()
                ->getRowArray();

            if ($existingStock && $existingStock['quantity'] >= $item['quantity']) {
                $this->db->table('product_stocks')->where('id', $existingStock['id'])->update([
                    'quantity' => $existingStock['quantity'] - $item['quantity'],
                ]);

                $this->db->table('stock_movements')->insert([
                    'product_id'    => $item['product_id'],
                    'warehouse_id'  => $purchase['warehouse_id'],
                    'type'          => 'purchase_return',
                    'quantity'      => -$item['quantity'],
                    'reference'     => $purchase['reference_number'],
                    'user_id'       => get_user_id_from_token(),
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->table('purchases')->where('id', $id)->update([
            'status'     => 'returned',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return api_error('Failed to return purchase', 500);
        }

        return api_success(null, 'Purchase returned successfully');
    }

    public function addPayment($id = null)
    {
        $purchase = $this->db->table('purchases')->where('id', $id)->get()->getRowArray();

        if (!$purchase) {
            return api_error('Purchase not found', 404);
        }

        $data = $this->getRequestData();

        $rules = [
            'amount'   => 'required|decimal|greater_than[0]',
            'paid_via' => 'required|in_list[cash,bank_transfer,card,cheque,mobile]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $amount = (float) $data['amount'];
        $currentDue = (float) $purchase['due_amount'];

        if ($amount > $currentDue) {
            return api_error('Payment amount exceeds due amount', 422);
        }

        $newDue = $currentDue - $amount;
        $newPaid = (float) $purchase['paid_amount'] + $amount;

        $this->db->table('purchases')->where('id', $id)->update([
            'paid_amount'   => $newPaid,
            'due_amount'    => $newDue,
            'payment_status' => $newDue > 0 ? 'partial' : 'paid',
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->db->table('purchase_payments')->insert([
            'purchase_id'   => $id,
            'amount'        => $amount,
            'paid_via'      => $data['paid_via'],
            'reference'     => $data['reference'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'user_id'       => get_user_id_from_token(),
            'payment_date'  => date('Y-m-d'),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return api_success(null, 'Payment recorded successfully', 201);
    }

    public function getPayments($id = null)
    {
        $purchase = $this->db->table('purchases')->where('id', $id)->get()->getRowArray();

        if (!$purchase) {
            return api_error('Purchase not found', 404);
        }

        $payments = $this->db->table('purchase_payments')
            ->select('purchase_payments.*, users.first_name, users.last_name')
            ->join('users', 'users.id = purchase_payments.user_id', 'left')
            ->where('purchase_payments.purchase_id', $id)
            ->orderBy('purchase_payments.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return api_success($payments);
    }
}
