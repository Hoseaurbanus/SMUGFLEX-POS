<?php

namespace App\Services;

use App\Models\ActivityLogModel;

class ActivityLogService
{
    protected $model;

    public function __construct()
    {
        $this->model = new ActivityLogModel();
    }

    public function log(string $action, string $module, ?string $description = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        $userId = get_user_id_from_token();
        $request = service('request');

        $data = [
            'user_id'      => $userId,
            'action'       => $action,
            'module'       => $module,
            'description'  => $description,
            'old_values'   => $oldValues ? json_encode($oldValues) : null,
            'new_values'   => $newValues ? json_encode($newValues) : null,
            'ip_address'   => $request->getIPAddress(),
            'user_agent'   => $request->getUserAgent()->getAgentString(),
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        $this->model->insert($data);
    }

    public function logCreate(string $module, array $newValues): void
    {
        $this->log('create', $module, "Created new {$module} record", null, $newValues);
    }

    public function logUpdate(string $module, array $oldValues, array $newValues): void
    {
        $this->log('update', $module, "Updated {$module} record", $oldValues, $newValues);
    }

    public function logDelete(string $module, array $oldValues): void
    {
        $this->log('delete', $module, "Deleted {$module} record", $oldValues);
    }
}
