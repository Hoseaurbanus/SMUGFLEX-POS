<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class UsersController
{
    public function index(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = max(1, min(100, (int)($params['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;
        $search = $params['search'] ?? '';
        $where = "u.is_deleted = 0";

        if ($search) {
            $where .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
            $searchParam = "%$search%";
            $countParams = [$searchParam, $searchParam, $searchParam];
        } else {
            $countParams = [];
        }

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM users u WHERE $where", $countParams)['cnt'];

        $sql = "SELECT u.id, u.name, u.email, u.phone, u.role_id, u.branch_id, u.is_active, u.created_at,
                       r.name as role_name, b.name as branch_name
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN branches b ON b.id = u.branch_id
                WHERE $where
                ORDER BY u.created_at DESC
                LIMIT $perPage OFFSET $offset";

        $users = $db->fetchAll($sql, $countParams);
        Response::paginated($users, (int)$total, $page, $perPage);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['name']) || empty($body['email']) || empty($body['password'])) {
            Response::error('Name, email and password are required', 422);
        }

        $db = Database::getInstance();

        if ($db->fetch("SELECT id FROM users WHERE email = ?", [$body['email']])) {
            Response::error('Email already exists', 409);
        }

        $userId = $db->insert('users', [
            'name' => $body['name'],
            'email' => $body['email'],
            'password' => hash_password($body['password']),
            'phone' => $body['phone'] ?? null,
            'role_id' => $body['role_id'] ?? null,
            'branch_id' => $body['branch_id'] ?? null,
            'is_active' => $body['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $user = $db->fetch("SELECT id, name, email, phone, role_id, branch_id, is_active, created_at FROM users WHERE id = ?", [$userId]);
        Response::success($user, 'User created', 201);
    }

    public function show(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $user = $db->fetch(
            "SELECT u.id, u.name, u.email, u.phone, u.role_id, u.branch_id, u.is_active, u.created_at, u.updated_at,
                    r.name as role_name, b.name as branch_name
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             LEFT JOIN branches b ON b.id = u.branch_id
             WHERE u.id = ? AND u.is_deleted = 0",
            [$id]
        );

        if (!$user) {
            Response::error('User not found', 404);
        }

        Response::success($user);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $user = $db->fetch("SELECT id FROM users WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$user) {
            Response::error('User not found', 404);
        }

        if (!empty($body['email'])) {
            $exists = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$body['email'], $id]);
            if ($exists) {
                Response::error('Email already exists', 409);
            }
        }

        $updateData = [];
        if (isset($body['name'])) $updateData['name'] = $body['name'];
        if (isset($body['email'])) $updateData['email'] = $body['email'];
        if (isset($body['phone'])) $updateData['phone'] = $body['phone'];
        if (isset($body['role_id'])) $updateData['role_id'] = $body['role_id'];
        if (isset($body['branch_id'])) $updateData['branch_id'] = $body['branch_id'];
        if (isset($body['is_active'])) $updateData['is_active'] = $body['is_active'];
        if (!empty($body['password'])) $updateData['password'] = hash_password($body['password']);

        if (empty($updateData)) {
            Response::error('No data to update', 422);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $db->update('users', $updateData, 'id = ?', [$id]);

        $user = $db->fetch("SELECT id, name, email, phone, role_id, branch_id, is_active, created_at FROM users WHERE id = ?", [$id]);
        Response::success($user, 'User updated');
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $user = $db->fetch("SELECT id, role_id FROM users WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$user) {
            Response::error('User not found', 404);
        }

        $role = $db->fetch("SELECT name FROM roles WHERE id = ?", [$user['role_id']]);
        if ($role && $role['name'] === 'super_admin') {
            Response::error('Cannot delete super admin', 403);
        }

        $db->update('users', ['is_deleted' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'User deleted');
    }

    public function toggleStatus(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $user = $db->fetch("SELECT id, is_active FROM users WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$user) {
            Response::error('User not found', 404);
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        $db->update('users', ['is_active' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);

        Response::success(['is_active' => $newStatus], 'Status updated');
    }

    public function assignRole(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['role_id'])) {
            Response::error('Role ID is required', 422);
        }

        $db = Database::getInstance();

        $user = $db->fetch("SELECT id FROM users WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$user) {
            Response::error('User not found', 404);
        }

        $role = $db->fetch("SELECT id FROM roles WHERE id = ?", [$body['role_id']]);
        if (!$role) {
            Response::error('Role not found', 404);
        }

        $db->update('users', ['role_id' => $body['role_id'], 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Role assigned');
    }
}
