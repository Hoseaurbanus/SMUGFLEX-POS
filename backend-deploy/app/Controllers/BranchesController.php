<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class BranchesController
{
    public function index(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $branches = $db->fetchAll(
            "SELECT b.*, (SELECT COUNT(*) FROM users WHERE branch_id = b.id AND is_deleted = 0) as user_count
             FROM branches b WHERE b.is_deleted = 0 ORDER BY b.name ASC"
        );

        Response::success($branches);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['name'])) {
            Response::error('Branch name is required', 422);
        }

        $db = Database::getInstance();

        if ($db->fetch("SELECT id FROM branches WHERE name = ? AND is_deleted = 0", [$body['name']])) {
            Response::error('Branch name already exists', 409);
        }

        $branchId = $db->insert('branches', [
            'name' => $body['name'],
            'address' => $body['address'] ?? null,
            'phone' => $body['phone'] ?? null,
            'is_active' => $body['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $branch = $db->fetch("SELECT * FROM branches WHERE id = ?", [$branchId]);
        Response::success($branch, 'Branch created', 201);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $branch = $db->fetch("SELECT id FROM branches WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$branch) {
            Response::error('Branch not found', 404);
        }

        if (!empty($body['name'])) {
            $exists = $db->fetch("SELECT id FROM branches WHERE name = ? AND id != ? AND is_deleted = 0", [$body['name'], $id]);
            if ($exists) {
                Response::error('Branch name already exists', 409);
            }
        }

        $updateData = [];
        if (isset($body['name'])) $updateData['name'] = $body['name'];
        if (isset($body['address'])) $updateData['address'] = $body['address'];
        if (isset($body['phone'])) $updateData['phone'] = $body['phone'];
        if (isset($body['is_active'])) $updateData['is_active'] = $body['is_active'];

        if (empty($updateData)) {
            Response::error('No data to update', 422);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $db->update('branches', $updateData, 'id = ?', [$id]);

        $branch = $db->fetch("SELECT * FROM branches WHERE id = ?", [$id]);
        Response::success($branch, 'Branch updated');
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $branch = $db->fetch("SELECT id FROM branches WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$branch) {
            Response::error('Branch not found', 404);
        }

        $userCount = $db->fetch("SELECT COUNT(*) as cnt FROM users WHERE branch_id = ? AND is_deleted = 0", [$id]);
        if ((int)$userCount['cnt'] > 0) {
            Response::error('Cannot delete branch with users', 409);
        }

        $db->update('branches', ['is_deleted' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Branch deleted');
    }
}
