<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Brands extends BaseController
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

        $builder = $this->db->table('brands');
        $builder->where('deleted_at', null);

        if ($search) {
            $builder->groupStart();
            $builder->like('name', $search);
            $builder->orLike('slug', $search);
            $builder->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $brands = $builder->get()->getResultArray();

        return paginated_response($brands, $total, $page, $limit);
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

        $data['slug'] = slugify($data['name']);

        $id = $this->db->table('brands')->insert($data);

        if (!$id) {
            return api_error('Failed to create brand', 500);
        }

        $brand = $this->db->table('brands')->where('id', $id)->get()->getRowArray();

        return api_success($brand, 'Brand created', 201);
    }

    public function update($id = null)
    {
        $brand = $this->db->table('brands')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$brand) {
            return api_error('Brand not found', 404);
        }

        $data = $this->getRequestData();

        if (!empty($data['name'])) {
            $data['slug'] = slugify($data['name']);
        }

        $this->db->table('brands')->where('id', $id)->update($data);

        $updated = $this->db->table('brands')->where('id', $id)->get()->getRowArray();

        return api_success($updated, 'Brand updated');
    }

    public function delete($id = null)
    {
        $brand = $this->db->table('brands')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$brand) {
            return api_error('Brand not found', 404);
        }

        $products = $this->db->table('products')->where('brand_id', $id)->where('deleted_at', null)->countAllResults();

        if ($products > 0) {
            return api_error('Cannot delete brand with associated products', 422);
        }

        $this->db->table('brands')->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);

        return api_success(null, 'Brand deleted');
    }
}
