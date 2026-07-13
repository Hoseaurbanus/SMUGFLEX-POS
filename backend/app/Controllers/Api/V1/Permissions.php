<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Permissions extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $permissions = $this->db->table('permissions')
            ->orderBy('module', 'ASC')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        $grouped = [];
        foreach ($permissions as $permission) {
            $module = $permission['module'] ?? 'general';
            $grouped[$module][] = $permission;
        }

        return api_success([
            'permissions' => $permissions,
            'grouped'     => $grouped,
        ]);
    }
}
