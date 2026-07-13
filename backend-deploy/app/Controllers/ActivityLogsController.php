<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class ActivityLogsController
{
    public function index(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = max(1, min(100, (int)($params['per_page'] ?? 50)));
        $offset = ($page - 1) * $perPage;

        $where = "1=1";
        $queryParams = [];

        if (!empty($params['user_id'])) {
            $where .= " AND al.user_id = ?";
            $queryParams[] = $params['user_id'];
        }
        if (!empty($params['action'])) {
            $where .= " AND al.action = ?";
            $queryParams[] = $params['action'];
        }
        if (!empty($params['module'])) {
            $where .= " AND al.module = ?";
            $queryParams[] = $params['module'];
        }
        if (!empty($params['date_from'])) {
            $where .= " AND al.created_at >= ?";
            $queryParams[] = $params['date_from'] . ' 00:00:00';
        }
        if (!empty($params['date_to'])) {
            $where .= " AND al.created_at <= ?";
            $queryParams[] = $params['date_to'] . ' 23:59:59';
        }

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM activity_logs al WHERE $where", $queryParams)['cnt'];

        $logs = $db->fetchAll(
            "SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE $where
             ORDER BY al.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $queryParams
        );

        Response::paginated($logs, (int)$total, $page, $perPage);
    }
}
