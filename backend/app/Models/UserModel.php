<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'first_name', 'last_name', 'email', 'password', 'phone',
        'avatar', 'role_id', 'branch_id', 'warehouse_id', 'is_active',
        'last_login_at', 'last_login_ip', 'remember_token',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'email'    => 'required|valid_email|max_length[100]',
        'password' => 'required|min_length[6]',
        'role_id'  => 'required|integer',
    ];

    protected $validationMessages = [
        'email' => [
            'required'  => 'Email is required',
            'is_unique' => 'Email already exists',
        ],
    ];

    protected $beforeInsert = ['hashPassword'];

    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_BCRYPT);
        }

        return $data;
    }

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    public function getActiveUsers(): array
    {
        return $this->where('is_active', 1)->findAll();
    }

    public function getUsersWithRoles(int $branchId = 0): array
    {
        $builder = $this->db->table($this->table);
        $builder->select('users.*, roles.name as role_name, branches.name as branch_name');
        $builder->join('roles', 'roles.id = users.role_id', 'left');
        $builder->join('branches', 'branches.id = users.branch_id', 'left');
        $builder->where('users.deleted_at', null);

        if ($branchId > 0) {
            $builder->where('users.branch_id', $branchId);
        }

        return $builder->get()->getResultArray();
    }

    public function search(string $query, int $limit = 10): array
    {
        return $this->like('first_name', $query)
                     ->orLike('last_name', $query)
                     ->orLike('email', $query)
                     ->limit($limit)
                     ->findAll();
    }
}
