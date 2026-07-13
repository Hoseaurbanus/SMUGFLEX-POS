<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Inventory extends BaseController
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
        $warehouseId = $this->request->getVar('warehouse_id') ?? '';

        $builder = $this->db->table('product_stocks');
        $builder->select('product_stocks.*, products.name as product_name, products.sku as product_sku, products.barcode as product_barcode, warehouses.name as warehouse_name');
        $builder->join('products', 'products.id = product_stocks.product_id', 'left');
        $builder->join('warehouses', 'warehouses.id = product_stocks.warehouse_id', 'left');
        $builder->where('products.deleted_at', null);

        if ($search) {
            $builder->like('products.name', $search);
        }

        if ($warehouseId) {
            $builder->where('product_stocks.warehouse_id', $warehouseId);
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('product_stocks.updated_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $stocks = $builder->get()->getResultArray();

        return paginated_response($stocks, $total, $page, $limit);
    }

    public function adjust()
    {
        $data = $this->getRequestData();

        $rules = [
            'product_id'    => 'required|integer',
            'warehouse_id'  => 'required|integer',
            'quantity'      => 'required|integer',
            'type'          => 'required|in_list[addition,subtraction]',
            'reason'        => 'required|max_length[200]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $existingStock = $this->db->table('product_stocks')
            ->where('product_id', $data['product_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->get()
            ->getRowArray();

        if ($data['type'] === 'subtraction' && $existingStock) {
            if ($existingStock['quantity'] < abs($data['quantity'])) {
                return api_error('Insufficient stock for adjustment', 422);
            }
        }

        $this->db->transStart();

        if ($existingStock) {
            $newQty = $data['type'] === 'addition'
                ? $existingStock['quantity'] + abs($data['quantity'])
                : $existingStock['quantity'] - abs($data['quantity']);

            $this->db->table('product_stocks')->where('id', $existingStock['id'])->update([
                'quantity'   => max(0, $newQty),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            if ($data['type'] === 'addition') {
                $this->db->table('product_stocks')->insert([
                    'product_id'   => $data['product_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'quantity'     => abs($data['quantity']),
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->table('stock_movements')->insert([
            'product_id'   => $data['product_id'],
            'warehouse_id' => $data['warehouse_id'],
            'type'         => 'adjustment',
            'quantity'     => $data['type'] === 'addition' ? abs($data['quantity']) : -abs($data['quantity']),
            'reference'    => 'ADJ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
            'description'  => $data['reason'],
            'user_id'      => get_user_id_from_token(),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return api_error('Failed to adjust inventory', 500);
        }

        return api_success(null, 'Inventory adjusted successfully');
    }

    public function transfer()
    {
        $data = $this->getRequestData();

        $rules = [
            'product_id'        => 'required|integer',
            'from_warehouse_id' => 'required|integer',
            'to_warehouse_id'   => 'required|integer',
            'quantity'          => 'required|integer|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        if ($data['from_warehouse_id'] == $data['to_warehouse_id']) {
            return api_error('Cannot transfer to the same warehouse', 422);
        }

        $sourceStock = $this->db->table('product_stocks')
            ->where('product_id', $data['product_id'])
            ->where('warehouse_id', $data['from_warehouse_id'])
            ->get()
            ->getRowArray();

        if (!$sourceStock || $sourceStock['quantity'] < $data['quantity']) {
            return api_error('Insufficient stock in source warehouse', 422);
        }

        $this->db->transStart();

        $this->db->table('product_stocks')->where('id', $sourceStock['id'])->update([
            'quantity'   => $sourceStock['quantity'] - $data['quantity'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $destStock = $this->db->table('product_stocks')
            ->where('product_id', $data['product_id'])
            ->where('warehouse_id', $data['to_warehouse_id'])
            ->get()
            ->getRowArray();

        if ($destStock) {
            $this->db->table('product_stocks')->where('id', $destStock['id'])->update([
                'quantity'   => $destStock['quantity'] + $data['quantity'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->db->table('product_stocks')->insert([
                'product_id'   => $data['product_id'],
                'warehouse_id' => $data['to_warehouse_id'],
                'quantity'     => $data['quantity'],
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        $transferRef = generate_reference('TRF');

        $this->db->table('stock_movements')->insert([
            'product_id'   => $data['product_id'],
            'warehouse_id' => $data['from_warehouse_id'],
            'type'         => 'transfer_out',
            'quantity'     => -$data['quantity'],
            'reference'    => $transferRef,
            'description'  => $data['notes'] ?? 'Transfer out',
            'user_id'      => get_user_id_from_token(),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->db->table('stock_movements')->insert([
            'product_id'   => $data['product_id'],
            'warehouse_id' => $data['to_warehouse_id'],
            'type'         => 'transfer_in',
            'quantity'     => $data['quantity'],
            'reference'    => $transferRef,
            'description'  => $data['notes'] ?? 'Transfer in',
            'user_id'      => get_user_id_from_token(),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return api_error('Failed to transfer inventory', 500);
        }

        return api_success(null, 'Stock transferred successfully');
    }

    public function lowStock()
    {
        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 15);

        $builder = $this->db->table('product_stocks');
        $builder->select('product_stocks.*, products.name as product_name, products.sku as product_sku, products.minimum_stock, warehouses.name as warehouse_name');
        $builder->join('products', 'products.id = product_stocks.product_id', 'left');
        $builder->join('warehouses', 'warehouses.id = product_stocks.warehouse_id', 'left');
        $builder->where('product_stocks.quantity <=', $this->db->expr('products.minimum_stock'));
        $builder->where('products.deleted_at', null);
        $builder->where('products.minimum_stock >', 0);

        $total = $builder->countAllResults(false);
        $builder->orderBy('product_stocks.quantity', 'ASC');
        $builder->limit($limit, ($page - 1) * $limit);
        $stocks = $builder->get()->getResultArray();

        return paginated_response($stocks, $total, $page, $limit);
    }

    public function outOfStock()
    {
        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 15);

        $builder = $this->db->table('product_stocks');
        $builder->select('product_stocks.*, products.name as product_name, products.sku as product_sku, warehouses.name as warehouse_name');
        $builder->join('products', 'products.id = product_stocks.product_id', 'left');
        $builder->join('warehouses', 'warehouses.id = product_stocks.warehouse_id', 'left');
        $builder->where('product_stocks.quantity', 0);
        $builder->where('products.deleted_at', null);

        $total = $builder->countAllResults(false);
        $builder->orderBy('products.name', 'ASC');
        $builder->limit($limit, ($page - 1) * $limit);
        $stocks = $builder->get()->getResultArray();

        return paginated_response($stocks, $total, $page, $limit);
    }

    public function expiryAlerts()
    {
        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 15);
        $days = (int) ($this->request->getVar('days') ?? 30);

        $expiryDate = date('Y-m-d', strtotime("+{$days} days"));

        $builder = $this->db->table('product_stocks');
        $builder->select('product_stocks.*, products.name as product_name, products.sku as product_sku, warehouses.name as warehouse_name');
        $builder->join('products', 'products.id = product_stocks.product_id', 'left');
        $builder->join('warehouses', 'warehouses.id = product_stocks.warehouse_id', 'left');
        $builder->where('products.has_expiry', 1);
        $builder->where('product_stocks.expiry_date <=', $expiryDate);
        $builder->where('product_stocks.expiry_date >=', date('Y-m-d'));
        $builder->where('product_stocks.quantity >', 0);
        $builder->where('products.deleted_at', null);

        $total = $builder->countAllResults(false);
        $builder->orderBy('product_stocks.expiry_date', 'ASC');
        $builder->limit($limit, ($page - 1) * $limit);
        $stocks = $builder->get()->getResultArray();

        return paginated_response($stocks, $total, $page, $limit);
    }
}
