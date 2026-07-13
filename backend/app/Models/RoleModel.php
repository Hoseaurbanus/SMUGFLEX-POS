<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['name', 'slug', 'description', 'is_system'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getRoleWithPermissions(int $roleId): ?array
    {
        $builder = $this->db->table('roles');
        $builder->select('roles.*, GROUP_CONCAT(permissions.slug) as permission_slugs');
        $builder->join('role_permissions', 'role_permissions.role_id = roles.id', 'left');
        $builder->join('permissions', 'permissions.id = role_permissions.permission_id', 'left');
        $builder->where('roles.id', $roleId);
        $builder->groupBy('roles.id');

        return $builder->get()->getRowArray();
    }

    public function getAllRoles(): array
    {
        $builder = $this->db->table('roles');
        $builder->select('roles.*, COUNT(role_permissions.permission_id) as permission_count');
        $builder->join('role_permissions', 'role_permissions.role_id = roles.id', 'left');
        $builder->groupBy('roles.id');
        $builder->orderBy('roles.name', 'ASC');

        return $builder->get()->getResultArray();
    }
}
