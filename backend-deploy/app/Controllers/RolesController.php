<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class RolesController
{
    public function index(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $roles = $db->fetchAll(
            "SELECT r.*, (SELECT COUNT(*) FROM users WHERE role_id = r.id AND deleted_at IS NULL) as user_count
             FROM roles r ORDER BY r.name ASC"
        );

        Response::success($roles);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['name'])) {
            Response::error('Role name is required', 422);
        }

        $db = Database::getInstance();

        if ($db->fetch("SELECT id FROM roles WHERE name = ?", [$body['name']])) {
            Response::error('Role name already exists', 409);
        }

        $roleId = $db->insert('roles', [
            'name' => $body['name'],
            'slug' => $body['slug'] ?? strtolower(str_replace(' ', '_', $body['name'])),
            'description' => $body['description'] ?? null,
            'is_system' => $body['is_system'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $role = $db->fetch("SELECT * FROM roles WHERE id = ?", [$roleId]);
        Response::success($role, 'Role created', 201);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $role = $db->fetch("SELECT id, is_system FROM roles WHERE id = ?", [$id]);
        if (!$role) {
            Response::error('Role not found', 404);
        }

        if ($role['is_system']) {
            Response::error('Cannot modify system role', 403);
        }

        if (!empty($body['name'])) {
            $exists = $db->fetch("SELECT id FROM roles WHERE name = ? AND id != ?", [$body['name'], $id]);
            if ($exists) {
                Response::error('Role name already exists', 409);
            }
        }

        $updateData = [];
        if (isset($body['name'])) $updateData['name'] = $body['name'];
        if (isset($body['slug'])) $updateData['slug'] = $body['slug'];
        if (isset($body['description'])) $updateData['description'] = $body['description'];

        if (empty($updateData)) {
            Response::error('No data to update', 422);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $db->update('roles', $updateData, 'id = ?', [$id]);

        $role = $db->fetch("SELECT * FROM roles WHERE id = ?", [$id]);
        Response::success($role, 'Role updated');
    }

    public function show(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $role = $db->fetch(
            "SELECT r.*, (SELECT COUNT(*) FROM users WHERE role_id = r.id AND deleted_at IS NULL) as user_count
             FROM roles r WHERE r.id = ?",
            [$id]
        );

        if (!$role) {
            Response::error('Role not found', 404);
        }

        $permissions = $db->fetchAll(
            "SELECT p.* FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?",
            [$id]
        );

        $role['permissions'] = $permissions;

        Response::success($role);
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $role = $db->fetch("SELECT id, name, is_system FROM roles WHERE id = ?", [$id]);
        if (!$role) {
            Response::error('Role not found', 404);
        }

        if ($role['is_system']) {
            Response::error('Cannot delete system role', 403);
        }

        $userCount = $db->fetch("SELECT COUNT(*) as cnt FROM users WHERE role_id = ? AND deleted_at IS NULL", [$id]);
        if ((int)$userCount['cnt'] > 0) {
            Response::error('Cannot delete role with assigned users', 409);
        }

        $db->delete('role_permissions', 'role_id = ?', [$id]);
        $db->delete('roles', 'id = ?', [$id]);
        Response::success(null, 'Role deleted');
    }

    public function updatePermissions(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || !isset($body['permission_ids'])) {
            Response::error('Permission IDs are required', 422);
        }

        $db = Database::getInstance();

        $role = $db->fetch("SELECT id FROM roles WHERE id = ?", [$id]);
        if (!$role) {
            Response::error('Role not found', 404);
        }

        $db->delete('role_permissions', 'role_id = ?', [$id]);

        if (!empty($body['permission_ids'])) {
            foreach ($body['permission_ids'] as $permId) {
                $db->insert('role_permissions', [
                    'role_id' => $id,
                    'permission_id' => $permId,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        Response::success(null, 'Permissions updated');
    }
}
