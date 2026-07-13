<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use JWT;
use AuthMiddleware;

class AuthController
{
    public function login(): void
    {
        $body = Request::getBody();
        if (!$body || empty($body['email']) || empty($body['password'])) {
            Response::error('Email and password are required', 422);
        }

        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.email = ? AND u.deleted_at IS NULL",
            [$body['email']]
        );

        if (!$user || !verify_password($body['password'], $user['password'])) {
            Response::error('Invalid credentials', 401);
        }

        if (!$user['is_active']) {
            Response::error('Account is inactive', 403);
        }

        $permissions = $db->fetchAll(
            "SELECT p.name FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?",
            [$user['role_id']]
        );

        $token = JWT::encode([
            'user_id' => (int)$user['id'],
            'email' => $user['email'],
            'role_name' => $user['role_name'] ?? 'user',
            'branch_id' => $user['branch_id'] ? (int)$user['branch_id'] : null,
            'warehouse_id' => $user['warehouse_id'] ? (int)$user['warehouse_id'] : null,
        ]);

        unset($user['password']);
        $user['permissions'] = array_column($permissions, 'name');

        $db->update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ], 'id = ?', [$user['id']]);

        Response::success([
            'token' => $token,
            'refresh_token' => $token,
            'user' => $user,
        ], 'Login successful');
    }

    public function register(): void
    {
        $body = Request::getBody();
        if (!$body || empty($body['first_name']) || empty($body['email']) || empty($body['password'])) {
            Response::error('First name, email and password are required', 422);
        }

        $db = Database::getInstance();
        $exists = $db->fetch("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL", [$body['email']]);
        if ($exists) {
            Response::error('Email already registered', 409);
        }

        $userId = $db->insert('users', [
            'first_name' => $body['first_name'],
            'last_name' => $body['last_name'] ?? '',
            'email' => $body['email'],
            'password' => hash_password($body['password']),
            'role_id' => $body['role_id'] ?? null,
            'branch_id' => $body['branch_id'] ?? null,
            'warehouse_id' => $body['warehouse_id'] ?? null,
            'phone' => $body['phone'] ?? null,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $user = $db->fetch("SELECT id, CONCAT(first_name, ' ', last_name) as name, email, phone, role_id, branch_id, warehouse_id, is_active, created_at FROM users WHERE id = ?", [$userId]);
        Response::success($user, 'User registered', 201);
    }

    public function logout(): void
    {
        AuthMiddleware::authenticate();
        Response::success(null, 'Logged out successfully');
    }

    public function refresh(): void
    {
        $payload = AuthMiddleware::authenticate();

        $db = Database::getInstance();
        $user = $db->fetch("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND u.deleted_at IS NULL", [$payload['user_id']]);

        if (!$user || !$user['is_active']) {
            Response::error('User not found or inactive', 401);
        }

        $permissions = $db->fetchAll(
            "SELECT p.name FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?",
            [$user['role_id']]
        );

        $token = JWT::encode([
            'user_id' => (int)$user['id'],
            'email' => $user['email'],
            'role_name' => $user['role_name'] ?? 'user',
            'branch_id' => $user['branch_id'] ? (int)$user['branch_id'] : null,
            'warehouse_id' => $user['warehouse_id'] ? (int)$user['warehouse_id'] : null,
        ]);

        unset($user['password']);
        $user['permissions'] = array_column($permissions, 'name');

        Response::success([
            'token' => $token,
            'refresh_token' => $token,
            'user' => $user,
        ], 'Token refreshed');
    }

    public function me(): void
    {
        $payload = AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $user = $db->fetch(
            "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as name, u.first_name, u.last_name, u.email, u.phone, u.avatar, u.role_id, u.branch_id, u.warehouse_id, u.is_active, u.created_at,
                    r.name as role_name, b.name as branch_name, w.name as warehouse_name
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             LEFT JOIN branches b ON b.id = u.branch_id
             LEFT JOIN warehouses w ON w.id = u.warehouse_id
             WHERE u.id = ? AND u.deleted_at IS NULL",
            [$payload['user_id']]
        );

        if (!$user) {
            Response::error('User not found', 404);
        }

        $permissions = $db->fetchAll(
            "SELECT p.name FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?",
            [$user['role_id']]
        );

        $user['permissions'] = array_column($permissions, 'name');

        Response::success($user);
    }

    public function changePassword(): void
    {
        $payload = AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['current_password']) || empty($body['new_password'])) {
            Response::error('Current and new password are required', 422);
        }

        $db = Database::getInstance();
        $user = $db->fetch("SELECT password FROM users WHERE id = ? AND deleted_at IS NULL", [$payload['user_id']]);

        if (!$user || !verify_password($body['current_password'], $user['password'])) {
            Response::error('Current password is incorrect', 401);
        }

        $db->update('users', [
            'password' => hash_password($body['new_password']),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$payload['user_id']]);

        Response::success(null, 'Password changed successfully');
    }
}
