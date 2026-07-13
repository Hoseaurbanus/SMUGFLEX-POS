<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\SaleModel;

class Sales extends BaseController
{
    protected $model;
    protected $db;

    public function __construct()
    {
        $this->model = new SaleModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 15);

        $filters = [
            'date_from'      => $this->request->getVar('date_from') ?? '',
            'date_to'        => $this->request->getVar('date_to') ?? '',
            'status'         => $this->request->getVar('status') ?? '',
            'payment_status' => $this->request->getVar('payment_status') ?? '',
            'user_id'        => $this->request->getVar('user_id') ?? '',
        ];

        $sales = $this->model->getSalesList($filters);
        $total = count($sales);
        $offset = ($page - 1) * $limit;
        $sales = array_slice($sales, $offset, $limit);

        return paginated_response($sales, $total, $page, $limit);
    }

    public function create()
    {
        $data = $this->getRequestData();

        $rules = [
            'items'                        => 'required',
            'items.*.product_id'           => 'required|integer',
            'items.*.quantity'             => 'required|integer|greater_than[0]',
            'items.*.unit_price'           => 'required|decimal|greater_than[0]',
            'payment_method'               => 'required|in_list[cash,bank_transfer,card,mobile,wallet,credit]',
            'paid_amount'                  => 'required|decimal',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $subtotal = 0;
        $taxAmount = 0;
        $discountAmount = 0;

        foreach ($data['items'] as &$item) {
            $itemTotal = $item['quantity'] * $item['unit_price'];
            $itemDiscount = $item['discount'] ?? 0;
            $itemTax = $item['tax'] ?? 0;
            $item['total'] = $itemTotal - $itemDiscount + $itemTax;
            $subtotal += $itemTotal;
            $discountAmount += $itemDiscount;
            $taxAmount += $itemTax;
        }
        unset($item);

        $total = $subtotal - $discountAmount + $taxAmount;
        $paidAmount = (float) $data['paid_amount'];
        $dueAmount = $total - $paidAmount;
        $paymentStatus = 'paid';
        if ($dueAmount > 0) {
            $paymentStatus = 'partial';
        }
        if ($data['payment_method'] === 'credit') {
            $paymentStatus = 'due';
        }

        $saleData = [
            'customer_id'     => $data['customer_id'] ?? null,
            'user_id'         => get_user_id_from_token(),
            'branch_id'       => $data['branch_id'] ?? null,
            'warehouse_id'    => $data['warehouse_id'] ?? null,
            'subtotal'        => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount'      => $taxAmount,
            'shipping_cost'   => $data['shipping_cost'] ?? 0,
            'total'           => $total,
            'paid_amount'     => $paidAmount,
            'due_amount'      => $dueAmount,
            'payment_method'  => $data['payment_method'],
            'payment_status'  => $paymentStatus,
            'sale_status'     => 'completed',
            'discount_type'   => $data['discount_type'] ?? null,
            'coupon_code'     => $data['coupon_code'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'sale_date'       => date('Y-m-d'),
            'sale_time'       => date('H:i:s'),
        ];

        $this->db->transStart();

        $saleId = $this->model->insert($saleData);

        if (!$saleId) {
            $this->db->transRollback();
            return api_error('Failed to create sale', 500);
        }

        foreach ($data['items'] as $item) {
            $this->db->table('sale_items')->insert([
                'sale_id'     => $saleId,
                'product_id'  => $item['product_id'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'discount'    => $item['discount'] ?? 0,
                'tax'         => $item['tax'] ?? 0,
                'total'       => $item['total'],
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            $warehouseId = $data['warehouse_id'] ?? null;
            if ($warehouseId) {
                $stock = $this->db->table('product_stocks')
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $warehouseId)
                    ->get()
                    ->getRowArray();

                if ($stock) {
                    $this->db->table('product_stocks')->where('id', $stock['id'])->update([
                        'quantity' => max(0, $stock['quantity'] - $item['quantity']),
                    ]);
                }

                $this->db->table('stock_movements')->insert([
                    'product_id'   => $item['product_id'],
                    'warehouse_id' => $warehouseId,
                    'type'         => 'sale',
                    'quantity'     => -$item['quantity'],
                    'reference'    => $this->model->find($saleId)['invoice_number'],
                    'user_id'      => get_user_id_from_token(),
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->table('sale_payments')->insert([
            'sale_id'       => $saleId,
            'amount'        => $paidAmount,
            'paid_via'      => $data['payment_method'],
            'reference'     => $data['payment_reference'] ?? null,
            'user_id'       => get_user_id_from_token(),
            'payment_date'  => date('Y-m-d'),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        if (!empty($data['customer_id']) && $data['payment_method'] === 'wallet') {
            $customer = $this->db->table('customers')->where('id', $data['customer_id'])->get()->getRowArray();
            if ($customer) {
                $newBalance = (float) $customer['wallet_balance'] - $total;
                $this->db->table('customers')->where('id', $data['customer_id'])->update([
                    'wallet_balance' => $newBalance,
                ]);
                $this->db->table('wallet_transactions')->insert([
                    'customer_id'    => $data['customer_id'],
                    'type'           => 'sale_payment',
                    'amount'         => -$total,
                    'balance_after'  => $newBalance,
                    'description'    => 'Payment for sale',
                    'reference'      => $this->model->find($saleId)['invoice_number'],
                    'created_at'     => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return api_error('Failed to create sale', 500);
        }

        $sale = $this->model->getSaleWithItems($saleId);

        return api_success($sale, 'Sale created', 201);
    }

    public function show($id = null)
    {
        $sale = $this->model->getSaleWithItems($id);

        if (!$sale) {
            return api_error('Sale not found', 404);
        }

        return api_success($sale);
    }

    public function void($id = null)
    {
        $sale = $this->model->find($id);

        if (!$sale) {
            return api_error('Sale not found', 404);
        }

        if ($sale['sale_status'] === 'voided') {
            return api_error('Sale is already voided', 422);
        }

        $data = $this->getRequestData();

        $this->db->transStart();

        $items = $this->db->table('sale_items')->where('sale_id', $id)->get()->getResultArray();

        foreach ($items as $item) {
            $warehouseId = $sale['warehouse_id'];
            if ($warehouseId) {
                $stock = $this->db->table('product_stocks')
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $warehouseId)
                    ->get()
                    ->getRowArray();

                if ($stock) {
                    $this->db->table('product_stocks')->where('id', $stock['id'])->update([
                        'quantity' => $stock['quantity'] + $item['quantity'],
                    ]);
                }

                $this->db->table('stock_movements')->insert([
                    'product_id'   => $item['product_id'],
                    'warehouse_id' => $warehouseId,
                    'type'         => 'sale_void',
                    'quantity'     => $item['quantity'],
                    'reference'    => $sale['invoice_number'],
                    'user_id'      => get_user_id_from_token(),
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->model->update($id, [
            'sale_status' => 'voided',
            'notes'       => ($sale['notes'] ? $sale['notes'] . ' | ' : '') . 'VOIDED: ' . ($data['reason'] ?? 'No reason provided'),
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return api_error('Failed to void sale', 500);
        }

        return api_success(null, 'Sale voided successfully');
    }

    public function hold()
    {
        $data = $this->getRequestData();

        $rules = [
            'items'           => 'required',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity'   => 'required|integer|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $subtotal = 0;
        foreach ($data['items'] as &$item) {
            $item['total'] = $item['quantity'] * ($item['unit_price'] ?? 0);
            $subtotal += $item['total'];
        }
        unset($item);

        $total = $subtotal;

        $saleData = [
            'customer_id'    => $data['customer_id'] ?? null,
            'user_id'        => get_user_id_from_token(),
            'branch_id'      => $data['branch_id'] ?? null,
            'warehouse_id'   => $data['warehouse_id'] ?? null,
            'subtotal'       => $subtotal,
            'total'          => $total,
            'paid_amount'    => 0,
            'due_amount'     => 0,
            'payment_method' => null,
            'payment_status' => 'pending',
            'sale_status'    => 'held',
            'hold_reference' => generate_reference('HOLD'),
            'notes'          => $data['notes'] ?? null,
            'sale_date'      => date('Y-m-d'),
            'sale_time'      => date('H:i:s'),
        ];

        $this->db->transStart();

        $saleId = $this->model->insert($saleData);

        if (!$saleId) {
            $this->db->transRollback();
            return api_error('Failed to hold sale', 500);
        }

        foreach ($data['items'] as $item) {
            $this->db->table('sale_items')->insert([
                'sale_id'     => $saleId,
                'product_id'  => $item['product_id'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'] ?? 0,
                'discount'    => $item['discount'] ?? 0,
                'tax'         => $item['tax'] ?? 0,
                'total'       => $item['total'],
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return api_error('Failed to hold sale', 500);
        }

        $sale = $this->model->getSaleWithItems($saleId);

        return api_success($sale, 'Sale held successfully', 201);
    }

    public function heldSales()
    {
        $heldSales = $this->db->table('sales')
            ->select('sales.*, customers.first_name as customer_first_name, customers.last_name as customer_last_name, users.first_name as cashier_name')
            ->join('customers', 'customers.id = sales.customer_id', 'left')
            ->join('users', 'users.id = sales.user_id', 'left')
            ->where('sales.sale_status', 'held')
            ->orderBy('sales.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return api_success($heldSales);
    }

    public function resume($id = null)
    {
        $sale = $this->model->find($id);

        if (!$sale) {
            return api_error('Sale not found', 404);
        }

        if ($sale['sale_status'] !== 'held') {
            return api_error('Sale is not on hold', 422);
        }

        $data = $this->getRequestData();

        $paymentMethod = $data['payment_method'] ?? 'cash';
        $paidAmount = (float) ($data['paid_amount'] ?? $sale['total']);
        $dueAmount = $sale['total'] - $paidAmount;
        $paymentStatus = 'paid';
        if ($dueAmount > 0) {
            $paymentStatus = 'partial';
        }
        if ($paymentMethod === 'credit') {
            $paymentStatus = 'due';
        }

        $this->db->transStart();

        $this->model->update($id, [
            'sale_status'    => 'completed',
            'payment_method' => $paymentMethod,
            'paid_amount'    => $paidAmount,
            'due_amount'     => $dueAmount,
            'payment_status' => $paymentStatus,
        ]);

        $this->db->table('sale_payments')->insert([
            'sale_id'      => $id,
            'amount'       => $paidAmount,
            'paid_via'     => $paymentMethod,
            'user_id'      => get_user_id_from_token(),
            'payment_date' => date('Y-m-d'),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $items = $this->db->table('sale_items')->where('sale_id', $id)->get()->getResultArray();

        foreach ($items as $item) {
            $warehouseId = $sale['warehouse_id'];
            if ($warehouseId) {
                $stock = $this->db->table('product_stocks')
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $warehouseId)
                    ->get()
                    ->getRowArray();

                if ($stock) {
                    $this->db->table('product_stocks')->where('id', $stock['id'])->update([
                        'quantity' => max(0, $stock['quantity'] - $item['quantity']),
                    ]);
                }

                $this->db->table('stock_movements')->insert([
                    'product_id'   => $item['product_id'],
                    'warehouse_id' => $warehouseId,
                    'type'         => 'sale',
                    'quantity'     => -$item['quantity'],
                    'reference'    => $sale['invoice_number'],
                    'user_id'      => get_user_id_from_token(),
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return api_error('Failed to resume sale', 500);
        }

        $updatedSale = $this->model->getSaleWithItems($id);

        return api_success($updatedSale, 'Sale resumed and completed');
    }

    public function returnSale($id = null)
    {
        $sale = $this->model->find($id);

        if (!$sale) {
            return api_error('Sale not found', 404);
        }

        if ($sale['sale_status'] !== 'completed') {
            return api_error('Only completed sales can be returned', 422);
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

        $returnTotal = 0;
        foreach ($data['items'] as $item) {
            $returnTotal += $item['quantity'] * ($item['unit_price'] ?? 0);

            $warehouseId = $sale['warehouse_id'];
            if ($warehouseId) {
                $stock = $this->db->table('product_stocks')
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $warehouseId)
                    ->get()
                    ->getRowArray();

                if ($stock) {
                    $this->db->table('product_stocks')->where('id', $stock['id'])->update([
                        'quantity' => $stock['quantity'] + $item['quantity'],
                    ]);
                }

                $this->db->table('stock_movements')->insert([
                    'product_id'   => $item['product_id'],
                    'warehouse_id' => $warehouseId,
                    'type'         => 'sale_return',
                    'quantity'     => $item['quantity'],
                    'reference'    => $sale['invoice_number'],
                    'user_id'      => get_user_id_from_token(),
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->table('sale_returns')->insert([
            'sale_id'       => $id,
            'return_number' => generate_reference('SR'),
            'total'         => $returnTotal,
            'reason'        => $data['reason'] ?? null,
            'user_id'       => get_user_id_from_token(),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->model->update($id, [
            'sale_status' => 'returned',
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return api_error('Failed to process return', 500);
        }

        return api_success(null, 'Sale returned successfully');
    }

    public function receipt($id = null)
    {
        $sale = $this->model->getSaleWithItems($id);

        if (!$sale) {
            return api_error('Sale not found', 404);
        }

        $company = $this->db->table('settings')->where('key', 'company')->get()->getRowArray();

        return api_success([
            'sale'    => $sale,
            'company' => $company ? json_decode($company['value'], true) : null,
        ]);
    }
}
