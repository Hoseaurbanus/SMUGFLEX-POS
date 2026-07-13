<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Branches extends BaseController
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

        $builder = $this->db->table('branches');
        $builder->select('branches.*, (SELECT COUNT(*) FROM users WHERE users.branch_id = branches.id AND users.deleted_at IS NULL) as user_count');

        if ($search) {
            $builder->groupStart();
            $builder->like('name', $search);
            $builder->orLike('address', $search);
            $builder->orLike('phone', $search);
            $builder->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $branches = $builder->get()->getResultArray();

        return paginated_response($branches, $total, $page, $limit);
    }

    public function create()
    {
        $data = $this->getRequestData();

        $rules = [
            'name'  => 'required|max_length[200]',
            'phone' => 'permit_empty|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $data['is_active'] = $data['is_active'] ?? 1;

        $id = $this->db->table('branches')->insert($data);

        if (!$id) {
            return api_error('Failed to create branch', 500);
        }

        $branch = $this->db->table('branches')->where('id', $id)->get()->getRowArray();

        return api_success($branch, 'Branch created', 201);
    }

    public function update($id = null)
    {
        $branch = $this->db->table('branches')->where('id', $id)->get()->getRowArray();

        if (!$branch) {
            return api_error('Branch not found', 404);
        }

        $data = $this->getRequestData();

        $this->db->table('branches')->where('id', $id)->update($data);

        $updated = $this->db->table('branches')->where('id', $id)->get()->getRowArray();

        return api_success($updated, 'Branch updated');
    }

    public function delete($id = null)
    {
        $branch = $this->db->table('branches')->where('id', $id)->get()->getRowArray();

        if (!$branch) {
            return api_error('Branch not found', 404);
        }

        $users = $this->db->table('users')->where('branch_id', $id)->where('deleted_at', null)->countAllResults();

        if ($users > 0) {
            return api_error('Cannot delete branch with assigned users', 422);
        }

        $this->db->table('branches')->where('id', $id)->delete();

        return api_success(null, 'Branch deleted');
    }
}
