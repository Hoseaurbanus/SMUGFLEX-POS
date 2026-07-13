<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\ActivityLogModel;

class ActivityLogs extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new ActivityLogModel();
    }

    public function index()
    {
        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 15);

        $filters = [
            'module'  => $this->request->getVar('module') ?? '',
            'user_id' => $this->request->getVar('user_id') ?? '',
            'action'  => $this->request->getVar('action') ?? '',
        ];

        $builder = $this->db->table('activity_logs');
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

        $total = $builder->countAllResults(false);
        $builder->orderBy('activity_logs.created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $logs = $builder->get()->getResultArray();

        return paginated_response($logs, $total, $page, $limit);
    }
}
