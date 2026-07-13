<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\RoleModel;

class Roles extends BaseController
{
    protected $model;
    protected $db;

    public function __construct()
    {
        $this->model = new RoleModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $roles = $this->model->getAllRoles();

        return api_success($roles);
    }

    public function create()
    {
        $data = $this->getRequestData();

        $rules = [
            'name'        => 'required|max_length[100]|is_unique[roles.name]',
            'description' => 'permit_empty|max_length[500]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $data['slug'] = slugify($data['name']);
        $data['is_system'] = 0;

        $id = $this->model->insert($data);

        if (!$id) {
            return api_error('Failed to create role', 500);
        }

        $role = $this->model->getRoleWithPermissions($id);

        return api_success($role, 'Role created', 201);
    }

    public function update($id = null)
    {
        $role = $this->model->find($id);

        if (!$role) {
            return api_error('Role not found', 404);
        }

        if ($role['is_system']) {
            return api_error('Cannot modify system role', 422);
        }

        $data = $this->getRequestData();

        if (!empty($data['name']) && $data['name'] !== $role['name']) {
            $builder = $this->db->table('roles');
            $builder->where('name', $data['name']);
            $builder->where('id !=', $id);
            if ($builder->countAllResults() > 0) {
                return api_error('Role name already exists', 422);
            }
            $data['slug'] = slugify($data['name']);
        }

        $this->model->update($id, $data);

        $updated = $this->model->getRoleWithPermissions($id);

        return api_success($updated, 'Role updated');
    }

    public function delete($id = null)
    {
        $role = $this->model->find($id);

        if (!$role) {
            return api_error('Role not found', 404);
        }

        if ($role['is_system']) {
            return api_error('Cannot delete system role', 422);
        }

        $users = $this->db->table('users')->where('role_id', $id)->where('deleted_at', null)->countAllResults();

        if ($users > 0) {
            return api_error('Cannot delete role with assigned users', 422);
        }

        $this->db->table('role_permissions')->where('role_id', $id)->delete();
        $this->model->delete($id);

        return api_success(null, 'Role deleted');
    }

    public function updatePermissions($id = null)
    {
        $role = $this->model->find($id);

        if (!$role) {
            return api_error('Role not found', 404);
        }

        if ($role['is_system']) {
            return api_error('Cannot modify permissions of system role', 422);
        }

        $data = $this->getRequestData();

        $rules = [
            'permissions' => 'required',
            'permissions.*' => 'integer',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $this->db->transStart();

        $this->db->table('role_permissions')->where('role_id', $id)->delete();

        foreach ($data['permissions'] as $permissionId) {
            $this->db->table('role_permissions')->insert([
                'role_id'       => $id,
                'permission_id' => $permissionId,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return api_error('Failed to update permissions', 500);
        }

        $updated = $this->model->getRoleWithPermissions($id);

        return api_success($updated, 'Permissions updated');
    }
}
