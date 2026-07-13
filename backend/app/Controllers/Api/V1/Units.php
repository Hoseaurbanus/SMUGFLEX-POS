<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Units extends BaseController
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

        $builder = $this->db->table('units');
        $builder->where('deleted_at', null);

        if ($search) {
            $builder->groupStart();
            $builder->like('name', $search);
            $builder->orLike('short_name', $search);
            $builder->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $units = $builder->get()->getResultArray();

        return paginated_response($units, $total, $page, $limit);
    }

    public function create()
    {
        $data = $this->getRequestData();

        $rules = [
            'name'       => 'required|max_length[100]',
            'short_name' => 'required|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $id = $this->db->table('units')->insert($data);

        if (!$id) {
            return api_error('Failed to create unit', 500);
        }

        $unit = $this->db->table('units')->where('id', $id)->get()->getRowArray();

        return api_success($unit, 'Unit created', 201);
    }

    public function update($id = null)
    {
        $unit = $this->db->table('units')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$unit) {
            return api_error('Unit not found', 404);
        }

        $data = $this->getRequestData();

        $this->db->table('units')->where('id', $id)->update($data);

        $updated = $this->db->table('units')->where('id', $id)->get()->getRowArray();

        return api_success($updated, 'Unit updated');
    }

    public function delete($id = null)
    {
        $unit = $this->db->table('units')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$unit) {
            return api_error('Unit not found', 404);
        }

        $products = $this->db->table('products')->where('unit_id', $id)->where('deleted_at', null)->countAllResults();

        if ($products > 0) {
            return api_error('Cannot delete unit with associated products', 422);
        }

        $this->db->table('units')->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);

        return api_success(null, 'Unit deleted');
    }
}
