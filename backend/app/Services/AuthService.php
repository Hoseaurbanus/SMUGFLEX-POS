<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\RoleModel;

class AuthService
{
    protected $userModel;
    protected $roleModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Account is deactivated. Contact administrator.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        $this->userModel->update($user['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => service('request')->getIPAddress(),
        ]);

        $token = generate_jwt([
            'user_id' => $user['id'],
            'email'   => $user['email'],
            'role_id' => $user['role_id'],
        ]);

        $refreshToken = generate_refresh_jwt([
            'user_id' => $user['id'],
            'email'   => $user['email'],
        ]);

        unset($user['password']);
        unset($user['remember_token']);

        $role = $this->roleModel->find($user['role_id']);
        $user['role'] = $role;
        $user['permissions'] = $this->getUserPermissions($user['id']);

        return [
            'success' => true,
            'data' => [
                'user'         => $user,
                'token'        => $token,
                'refresh_token' => $refreshToken,
                'expires_in'   => (int) getenv('JWT_EXPIRATION'),
            ],
        ];
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = $this->userModel->find($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }

        $result = $this->userModel->update($userId, [
            'password'   => password_hash($newPassword, PASSWORD_BCRYPT),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $result
            ? ['success' => true, 'message' => 'Password updated successfully']
            : ['success' => false, 'message' => 'Failed to update password'];
    }

    public function getUserPermissions(int $userId): array
    {
        $user = $this->userModel->find($userId);

        if (!$user) {
            return [];
        }

        if ($user['role_id'] == 1) {
            $db = \Config\Database::connect();
            $builder = $db->table('permissions');
            $result = $builder->get()->getResultArray();
            return array_column($result, 'slug');
        }

        $db = \Config\Database::connect();
        $builder = $db->table('role_permissions');
        $builder->select('permissions.slug');
        $builder->join('permissions', 'permissions.id = role_permissions.permission_id');
        $builder->where('role_permissions.role_id', $user['role_id']);
        $result = $builder->get()->getResultArray();

        return array_column($result, 'slug');
    }
}
