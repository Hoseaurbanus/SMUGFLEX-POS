<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class ReturnsController
{
    public function index(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = max(1, min(100, (int)($params['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = "1=1";
        $queryParams = [];

        if (!empty($params['status'])) {
            $where .= " AND sr.status = ?";
            $queryParams[] = $params['status'];
        }

        $total = $db->fetch(
            "SELECT COUNT(*) as cnt FROM sale_returns sr WHERE $where",
            $queryParams
        )['cnt'];

        $returns = $db->fetchAll(
            "SELECT sr.*, s.invoice_number as sale_invoice_number,
                    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                    CONCAT(u.first_name, ' ', u.last_name) as processed_by_name
             FROM sale_returns sr
             LEFT JOIN sales s ON s.id = sr.sale_id
             LEFT JOIN customers c ON c.id = sr.customer_id
             LEFT JOIN users u ON u.id = sr.user_id
             WHERE $where
             ORDER BY sr.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $queryParams
        );

        Response::paginated($returns, (int)$total, $page, $perPage);
    }
}
