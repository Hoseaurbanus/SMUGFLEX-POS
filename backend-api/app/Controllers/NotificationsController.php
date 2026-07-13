<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class NotificationsController
{
    public function index(): void
    {
        $payload = AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = max(1, min(100, (int)($params['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = "user_id = ?";
        $queryParams = [$payload['user_id']];

        if (isset($params['unread_only']) && $params['unread_only'] === '1') {
            $where .= " AND is_read = 0";
        }

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM notifications WHERE $where", $queryParams)['cnt'];

        $notifications = $db->fetchAll(
            "SELECT * FROM notifications WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset",
            $queryParams
        );

        Response::paginated($notifications, (int)$total, $page, $perPage);
    }

    public function markRead(string $id): void
    {
        $payload = AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $notification = $db->fetch("SELECT id FROM notifications WHERE id = ? AND user_id = ?", [$id, $payload['user_id']]);
        if (!$notification) {
            Response::error('Notification not found', 404);
        }

        $db->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Marked as read');
    }

    public function markAllRead(): void
    {
        $payload = AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $db->query(
            "UPDATE notifications SET is_read = 1, read_at = ? WHERE user_id = ? AND is_read = 0",
            [date('Y-m-d H:i:s'), $payload['user_id']]
        );

        Response::success(null, 'All notifications marked as read');
    }
}
