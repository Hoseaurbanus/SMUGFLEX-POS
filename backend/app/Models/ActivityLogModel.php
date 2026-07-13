<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table = 'activity_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id', 'action', 'module', 'description',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';

    public function getLogs(array $filters = []): array
    {
        $builder = $this->db->table($this->table);
        $builder->select('activity_logs.*, users.first_name, users.last_name, users.email');
        $builder->join('users', 'users.id = activity_logs.user_id', 'left');

        if (!empty($filters['module'])) {
            $builder->where('activity_logs.module', $filters['module']);
        }

        if (!empty($filters['user_id'])) {
            $builder->where('activity_logs.user_id', $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $builder->where('activity_logs.action', $filters['action']);
        }

        $builder->orderBy('activity_logs.created_at', 'DESC');

        return $builder->get()->getResultArray();
    }
}
