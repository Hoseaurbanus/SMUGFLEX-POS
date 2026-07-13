<?php

use App\Models\UserModel;
use App\Models\RoleModel;

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        $userId = get_user_id_from_token();

        if (!$userId) {
            return null;
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || !$user['is_active']) {
            return null;
        }

        return $user;
    }
}

if (!function_exists('has_permission')) {
    function has_permission(string $permissionSlug): bool
    {
        $user = current_user();

        if (!$user) {
            return false;
        }

        $roleModel = new RoleModel();
        $role = $roleModel->find($user['role_id']);

        if (!$role) {
            return false;
        }

        $db = \Config\Database::connect();
        $builder = $db->table('role_permissions');
        $builder->join('permissions', 'permissions.id = role_permissions.permission_id');
        $builder->where('role_permissions.role_id', $user['role_id']);
        $builder->where('permissions.slug', $permissionSlug);

        return $builder->countAllResults() > 0;
    }
}

if (!function_exists('is_super_admin')) {
    function is_super_admin(): bool
    {
        $user = current_user();

        return $user && $user['role_id'] == 1;
    }
}
