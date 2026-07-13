<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Warehouses extends BaseController
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

        $builder = $this->db->table('warehouses');

        if ($search) {
            $builder->groupStart();
            $builder->like('name', $search);
            $builder->orLike('location', $search);
            $builder->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $warehouses = $builder->get()->getResultArray();

        return paginated_response($warehouses, $total, $page, $limit);
    }

    public function create()
    {
        $data = $this->getRequestData();

        $rules = [
            'name' => 'required|max_length[200]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $data['is_active'] = $data['is_active'] ?? 1;

        $id = $this->db->table('warehouses')->insert($data);

        if (!$id) {
            return api_error('Failed to create warehouse', 500);
        }

        $warehouse = $this->db->table('warehouses')->where('id', $id)->get()->getRowArray();

        return api_success($warehouse, 'Warehouse created', 201);
    }

    public function update($id = null)
    {
        $warehouse = $this->db->table('warehouses')->where('id', $id)->get()->getRowArray();

        if (!$warehouse) {
            return api_error('Warehouse not found', 404);
        }

        $data = $this->getRequestData();

        $this->db->table('warehouses')->where('id', $id)->update($data);

        $updated = $this->db->table('warehouses')->where('id', $id)->get()->getRowArray();

        return api_success($updated, 'Warehouse updated');
    }

    public function delete($id = null)
    {
        $warehouse = $this->db->table('warehouses')->where('id', $id)->get()->getRowArray();

        if (!$warehouse) {
            return api_error('Warehouse not found', 404);
        }

        $stocks = $this->db->table('product_stocks')->where('warehouse_id', $id)->where('quantity >', 0)->countAllResults();

        if ($stocks > 0) {
            return api_error('Cannot delete warehouse with stock', 422);
        }

        $this->db->table('warehouses')->where('id', $id)->delete();

        return api_success(null, 'Warehouse deleted');
    }
}
