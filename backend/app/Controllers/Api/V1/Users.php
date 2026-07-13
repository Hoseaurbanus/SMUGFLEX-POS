<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Users extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    public function index()
    {
        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 15);
        $search = $this->request->getVar('search') ?? '';

        $builder = $this->db->table('users');
        $builder->select('users.id, users.first_name, users.last_name, users.email, users.phone, users.is_active, users.last_login_at, users.created_at, roles.name as role_name, branches.name as branch_name');
        $builder->join('roles', 'roles.id = users.role_id', 'left');
        $builder->join('branches', 'branches.id = users.branch_id', 'left');
        $builder->where('users.deleted_at', null);

        if ($search) {
            $builder->groupStart();
            $builder->like('users.first_name', $search);
            $builder->orLike('users.last_name', $search);
            $builder->orLike('users.email', $search);
            $builder->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('users.created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $users = $builder->get()->getResultArray();

        return paginated_response($users, $total, $page, $limit);
    }

    public function create()
    {
        $data = $this->getRequestData();

        $rules = [
            'first_name' => 'required|max_length[50]',
            'last_name'  => 'required|max_length[50]',
            'email'      => 'required|valid_email|is_unique[users.email]',
            'password'   => 'required|min_length[8]',
            'role_id'    => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['is_active'] = $data['is_active'] ?? 1;

        $id = $this->model->insert($data);

        if (!$id) {
            return api_error('Failed to create user', 500);
        }

        $user = $this->model->find($id);
        unset($user['password']);

        return api_success($user, 'User created', 201);
    }

    public function show($id = null)
    {
        $user = $this->model->find($id);

        if (!$user) {
            return api_error('User not found', 404);
        }

        unset($user['password']);

        return api_success($user);
    }

    public function update($id = null)
    {
        $user = $this->model->find($id);

        if (!$user) {
            return api_error('User not found', 404);
        }

        $data = $this->getRequestData();
        unset($data['password']);

        if (!$this->model->update($id, $data)) {
            return api_error('Failed to update user', 500);
        }

        $updated = $this->model->find($id);
        unset($updated['password']);

        return api_success($updated, 'User updated');
    }

    public function delete($id = null)
    {
        $user = $this->model->find($id);

        if (!$user) {
            return api_error('User not found', 404);
        }

        if ($user['role_id'] == 1) {
            return api_error('Cannot delete super admin', 403);
        }

        $this->model->delete($id);

        return api_success(null, 'User deleted');
    }

    public function toggleStatus($id = null)
    {
        $user = $this->model->find($id);

        if (!$user) {
            return api_error('User not found', 404);
        }

        $this->model->update($id, ['is_active' => !$user['is_active']]);

        return api_success(null, 'User status updated');
    }

    public function assignRole($id = null)
    {
        $user = $this->model->find($id);

        if (!$user) {
            return api_error('User not found', 404);
        }

        $data = $this->getRequestData();

        if (empty($data['role_id'])) {
            return api_error('Role ID is required', 422);
        }

        $this->model->update($id, ['role_id' => $data['role_id']]);

        return api_success(null, 'Role assigned');
    }
}
