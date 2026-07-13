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
            "SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.email = ? AND u.is_deleted = 0",
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
        ]);

        unset($user['password']);
        $user['permissions'] = array_column($permissions, 'name');

        Response::success([
            'token' => $token,
            'user' => $user,
        ], 'Login successful');
    }

    public function register(): void
    {
        $body = Request::getBody();
        if (!$body || empty($body['name']) || empty($body['email']) || empty($body['password'])) {
            Response::error('Name, email and password are required', 422);
        }

        $db = Database::getInstance();
        $exists = $db->fetch("SELECT id FROM users WHERE email = ?", [$body['email']]);
        if ($exists) {
            Response::error('Email already registered', 409);
        }

        $userId = $db->insert('users', [
            'name' => $body['name'],
            'email' => $body['email'],
            'password' => hash_password($body['password']),
            'role_id' => $body['role_id'] ?? null,
            'branch_id' => $body['branch_id'] ?? null,
            'phone' => $body['phone'] ?? null,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $user = $db->fetch("SELECT id, name, email, phone, role_id, branch_id, is_active, created_at FROM users WHERE id = ?", [$userId]);
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
        $user = $db->fetch("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = ?", [$payload['user_id']]);

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
        ]);

        unset($user['password']);
        $user['permissions'] = array_column($permissions, 'name');

        Response::success([
            'token' => $token,
            'user' => $user,
        ], 'Token refreshed');
    }

    public function me(): void
    {
        $payload = AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $user = $db->fetch(
            "SELECT u.id, u.name, u.email, u.phone, u.role_id, u.branch_id, u.is_active, u.created_at,
                    r.name as role_name, b.name as branch_name
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             LEFT JOIN branches b ON b.id = u.branch_id
             WHERE u.id = ?",
            [$payload['user_id']]
        );

        if (!$user) {
            Response::error('User not found', 404);
        }

        $permissions = $db->fetchAll(
            "SELECT p.name, p.description FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?",
            [$user['role_id']]
        );

        $user['permissions'] = $permissions;

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
        $user = $db->fetch("SELECT password FROM users WHERE id = ?", [$payload['user_id']]);

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
