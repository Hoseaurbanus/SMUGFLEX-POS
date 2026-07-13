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
            "SELECT b.*, (SELECT COUNT(*) FROM users WHERE branch_id = b.id AND deleted_at IS NULL) as user_count
             FROM branches b ORDER BY b.name ASC"
        );

        Response::success($branches);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['name']) || empty($body['code'])) {
            Response::error('Branch name and code are required', 422);
        }

        $db = Database::getInstance();

        if ($db->fetch("SELECT id FROM branches WHERE code = ?", [$body['code']])) {
            Response::error('Branch code already exists', 409);
        }

        $branchId = $db->insert('branches', [
            'name' => $body['name'],
            'code' => $body['code'],
            'address' => $body['address'] ?? null,
            'city' => $body['city'] ?? null,
            'state' => $body['state'] ?? null,
            'country' => $body['country'] ?? null,
            'phone' => $body['phone'] ?? null,
            'email' => $body['email'] ?? null,
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

        $branch = $db->fetch("SELECT id FROM branches WHERE id = ?", [$id]);
        if (!$branch) {
            Response::error('Branch not found', 404);
        }

        if (!empty($body['code'])) {
            $exists = $db->fetch("SELECT id FROM branches WHERE code = ? AND id != ?", [$body['code'], $id]);
            if ($exists) {
                Response::error('Branch code already exists', 409);
            }
        }

        $updateData = [];
        if (isset($body['name'])) $updateData['name'] = $body['name'];
        if (isset($body['code'])) $updateData['code'] = $body['code'];
        if (isset($body['address'])) $updateData['address'] = $body['address'];
        if (isset($body['city'])) $updateData['city'] = $body['city'];
        if (isset($body['state'])) $updateData['state'] = $body['state'];
        if (isset($body['country'])) $updateData['country'] = $body['country'];
        if (isset($body['phone'])) $updateData['phone'] = $body['phone'];
        if (isset($body['email'])) $updateData['email'] = $body['email'];
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

        $branch = $db->fetch("SELECT id FROM branches WHERE id = ?", [$id]);
        if (!$branch) {
            Response::error('Branch not found', 404);
        }

        $userCount = $db->fetch("SELECT COUNT(*) as cnt FROM users WHERE branch_id = ? AND deleted_at IS NULL", [$id]);
        if ((int)$userCount['cnt'] > 0) {
            Response::error('Cannot delete branch with users', 409);
        }

        $db->delete('branches', 'id = ?', [$id]);
        Response::success(null, 'Branch deleted');
    }
}
