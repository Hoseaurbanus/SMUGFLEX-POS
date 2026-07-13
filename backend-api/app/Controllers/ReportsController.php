<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class ReportsController
{
    public function daily(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();
        $date = $params['date'] ?? date('Y-m-d');

        $sales = $db->fetch(
            "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total,
                    COALESCE(SUM(discount), 0) as discounts, COALESCE(SUM(tax), 0) as taxes
             FROM sales WHERE DATE(created_at) = ? AND status = 'completed'",
            [$date]
        );

        $expenses = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM expenses WHERE DATE(created_at) = ?",
            [$date]
        );

        $cashSales = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = ? AND payment_method = 'cash' AND status = 'completed'",
            [$date]
        );

        $cardSales = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = ? AND payment_method = 'card' AND status = 'completed'",
            [$date]
        );

        $walletSales = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = ? AND payment_method = 'wallet' AND status = 'completed'",
            [$date]
        );

        $topProducts = $db->fetchAll(
            "SELECT p.id, p.name, SUM(si.quantity) as sold, SUM(si.subtotal) as revenue
             FROM sale_items si JOIN products p ON p.id = si.product_id
             JOIN sales s ON s.id = si.sale_id AND s.status = 'completed'
             WHERE DATE(s.created_at) = ?
             GROUP BY p.id ORDER BY sold DESC LIMIT 10",
            [$date]
        );

        Response::success([
            'date' => $date,
            'total_sales' => (float)$sales['total'],
            'sales_count' => (int)$sales['count'],
            'discounts' => (float)$sales['discounts'],
            'taxes' => (float)$sales['taxes'],
            'total_expenses' => (float)$expenses['total'],
            'profit' => (float)$sales['total'] - (float)$expenses['total'],
            'cash_sales' => (float)$cashSales['total'],
            'card_sales' => (float)$cardSales['total'],
            'wallet_sales' => (float)$walletSales['total'],
            'top_products' => $topProducts,
        ]);
    }

    public function weekly(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $startDate = $params['start_date'] ?? date('Y-m-d', strtotime('-6 days'));
        $endDate = $params['end_date'] ?? date('Y-m-d');

        $dailyData = $db->fetchAll(
            "SELECT DATE(created_at) as date,
                    COUNT(*) as count, COALESCE(SUM(total), 0) as total
             FROM sales WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'completed'
             GROUP BY DATE(created_at) ORDER BY date ASC",
            [$startDate, $endDate]
        );

        $expenses = $db->fetchAll(
            "SELECT DATE(created_at) as date, COALESCE(SUM(amount), 0) as total
             FROM expenses WHERE DATE(created_at) BETWEEN ? AND ?
             GROUP BY DATE(created_at) ORDER BY date ASC",
            [$startDate, $endDate]
        );

        $expenseMap = [];
        foreach ($expenses as $e) {
            $expenseMap[$e['date']] = (float)$e['total'];
        }

        $result = [];
        foreach ($dailyData as $d) {
            $exp = $expenseMap[$d['date']] ?? 0;
            $result[] = [
                'date' => $d['date'],
                'sales_count' => (int)$d['count'],
                'sales_total' => (float)$d['total'],
                'expenses' => $exp,
                'profit' => (float)$d['total'] - $exp,
            ];
        }

        Response::success([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'data' => $result,
        ]);
    }

    public function monthly(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $month = $params['month'] ?? date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $dailyData = $db->fetchAll(
            "SELECT DATE(created_at) as date,
                    COUNT(*) as count, COALESCE(SUM(total), 0) as total
             FROM sales WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'completed'
             GROUP BY DATE(created_at) ORDER BY date ASC",
            [$startDate, $endDate]
        );

        $expenses = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE DATE(created_at) BETWEEN ? AND ?",
            [$startDate, $endDate]
        );

        $totalSales = array_sum(array_column($dailyData, 'total'));

        Response::success([
            'month' => $month,
            'total_sales' => (float)$totalSales,
            'total_expenses' => (float)$expenses['total'],
            'profit' => (float)$totalSales - (float)$expenses['total'],
            'daily_data' => $dailyData,
        ]);
    }

    public function yearly(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $year = $params['year'] ?? date('Y');

        $monthlySales = $db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%m') as month,
                    COUNT(*) as count, COALESCE(SUM(total), 0) as total
             FROM sales WHERE YEAR(created_at) = ? AND status = 'completed'
             GROUP BY DATE_FORMAT(created_at, '%m') ORDER BY month ASC",
            [$year]
        );

        $monthlyExpenses = $db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%m') as month, COALESCE(SUM(amount), 0) as total
             FROM expenses WHERE YEAR(created_at) = ?
             GROUP BY DATE_FORMAT(created_at, '%m') ORDER BY month ASC",
            [$year]
        );

        $expenseMap = [];
        foreach ($monthlyExpenses as $e) {
            $expenseMap[$e['month']] = (float)$e['total'];
        }

        $result = [];
        $totalSales = 0;
        $totalExpenses = 0;
        foreach ($monthlySales as $s) {
            $exp = $expenseMap[$s['month']] ?? 0;
            $totalSales += (float)$s['total'];
            $totalExpenses += $exp;
            $result[] = [
                'month' => $s['month'],
                'sales_count' => (int)$s['count'],
                'sales_total' => (float)$s['total'],
                'expenses' => $exp,
                'profit' => (float)$s['total'] - $exp,
            ];
        }

        Response::success([
            'year' => $year,
            'total_sales' => $totalSales,
            'total_expenses' => $totalExpenses,
            'profit' => $totalSales - $totalExpenses,
            'monthly_data' => $result,
        ]);
    }

    public function sales(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $where = "s.status = 'completed'";
        $queryParams = [];

        if (!empty($params['date_from'])) {
            $where .= " AND s.created_at >= ?";
            $queryParams[] = $params['date_from'] . ' 00:00:00';
        }
        if (!empty($params['date_to'])) {
            $where .= " AND s.created_at <= ?";
            $queryParams[] = $params['date_to'] . ' 23:59:59';
        }

        $summary = $db->fetch(
            "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total,
                    COALESCE(SUM(discount), 0) as discounts, COALESCE(AVG(total), 0) as average
             FROM sales s WHERE $where",
            $queryParams
        );

        $byPayment = $db->fetchAll(
            "SELECT payment_method, COUNT(*) as count, COALESCE(SUM(total), 0) as total
             FROM sales s WHERE $where GROUP BY payment_method",
            $queryParams
        );

        Response::success([
            'summary' => $summary,
            'by_payment_method' => $byPayment,
        ]);
    }

    public function profit(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $where = "s.status = 'completed'";
        $queryParams = [];

        if (!empty($params['date_from'])) {
            $where .= " AND s.created_at >= ?";
            $queryParams[] = $params['date_from'] . ' 00:00:00';
        }
        if (!empty($params['date_to'])) {
            $where .= " AND s.created_at <= ?";
            $queryParams[] = $params['date_to'] . ' 23:59:59';
        }

        $salesTotal = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total FROM sales s WHERE $where",
            $queryParams
        );

        $cogs = $db->fetch(
            "SELECT COALESCE(SUM(si.quantity * p.cost_price), 0) as total
             FROM sale_items si
             JOIN products p ON p.id = si.product_id
             JOIN sales s ON s.id = si.sale_id
             WHERE $where",
            $queryParams
        );

        $expenses = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE 1=1"
        );

        $sales = (float)$salesTotal['total'];
        $cost = (float)$cogs['total'];
        $exp = (float)$expenses['total'];
        $grossProfit = $sales - $cost;
        $netProfit = $grossProfit - $exp;

        Response::success([
            'total_sales' => $sales,
            'cost_of_goods' => $cost,
            'gross_profit' => $grossProfit,
            'expenses' => $exp,
            'net_profit' => $netProfit,
            'gross_margin' => $sales > 0 ? round(($grossProfit / $sales) * 100, 2) : 0,
            'net_margin' => $sales > 0 ? round(($netProfit / $sales) * 100, 2) : 0,
        ]);
    }

    public function inventory(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $where = "p.is_deleted = 0";
        $queryParams = [];

        if (!empty($params['category_id'])) {
            $where .= " AND p.category_id = ?";
            $queryParams[] = $params['category_id'];
        }

        $summary = $db->fetch(
            "SELECT COUNT(*) as total_products,
                    COALESCE(SUM(p.stock_quantity * p.cost_price), 0) as total_value,
                    COALESCE(SUM(p.stock_quantity), 0) as total_stock
             FROM products p WHERE $where",
            $queryParams
        );

        $lowStock = $db->fetch(
            "SELECT COUNT(*) as cnt FROM products p WHERE $where AND p.stock_quantity <= p.minimum_stock",
            $queryParams
        );

        $byCategory = $db->fetchAll(
            "SELECT c.name, COUNT(p.id) as count, COALESCE(SUM(p.stock_quantity), 0) as stock
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE $where GROUP BY c.id, c.name ORDER BY stock DESC",
            $queryParams
        );

        Response::success([
            'summary' => $summary,
            'low_stock_count' => (int)$lowStock['cnt'],
            'by_category' => $byCategory,
        ]);
    }

    public function expenses(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $where = "1=1";
        $queryParams = [];

        if (!empty($params['date_from'])) {
            $where .= " AND e.created_at >= ?";
            $queryParams[] = $params['date_from'] . ' 00:00:00';
        }
        if (!empty($params['date_to'])) {
            $where .= " AND e.created_at <= ?";
            $queryParams[] = $params['date_to'] . ' 23:59:59';
        }

        $total = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses e WHERE $where",
            $queryParams
        );

        $byCategory = $db->fetchAll(
            "SELECT ec.name, COALESCE(SUM(e.amount), 0) as total
             FROM expenses e LEFT JOIN expense_categories ec ON ec.id = e.expense_category_id
             WHERE $where GROUP BY ec.id, ec.name ORDER BY total DESC",
            $queryParams
        );

        Response::success([
            'total_expenses' => (float)$total['total'],
            'by_category' => $byCategory,
        ]);
    }
}
