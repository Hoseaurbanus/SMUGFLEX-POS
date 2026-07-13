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
                    COALESCE(SUM(discount_amount), 0) as discounts, COALESCE(SUM(tax_amount), 0) as taxes
             FROM sales WHERE sale_date = ? AND sale_status = 'completed'",
            [$date]
        );

        $expenses = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM expenses WHERE expense_date = ? AND deleted_at IS NULL",
            [$date]
        );

        $cashSales = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE sale_date = ? AND payment_method = 'cash' AND sale_status = 'completed'",
            [$date]
        );

        $cardSales = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE sale_date = ? AND payment_method = 'card' AND sale_status = 'completed'",
            [$date]
        );

        $walletSales = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE sale_date = ? AND payment_method = 'wallet' AND sale_status = 'completed'",
            [$date]
        );

        $transferSales = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE sale_date = ? AND payment_method = 'transfer' AND sale_status = 'completed'",
            [$date]
        );

        $topProducts = $db->fetchAll(
            "SELECT p.id, p.name, SUM(si.quantity) as sold, SUM(si.subtotal) as revenue
             FROM sale_items si JOIN products p ON p.id = si.product_id
             JOIN sales s ON s.id = si.sale_id AND s.sale_status = 'completed'
             WHERE s.sale_date = ?
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
            'transfer_sales' => (float)$transferSales['total'],
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
            "SELECT sale_date as date,
                    COUNT(*) as count, COALESCE(SUM(total), 0) as total
             FROM sales WHERE sale_date BETWEEN ? AND ? AND sale_status = 'completed'
             GROUP BY sale_date ORDER BY date ASC",
            [$startDate, $endDate]
        );

        $expenses = $db->fetchAll(
            "SELECT expense_date as date, COALESCE(SUM(amount), 0) as total
             FROM expenses WHERE expense_date BETWEEN ? AND ? AND deleted_at IS NULL
             GROUP BY expense_date ORDER BY date ASC",
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
            "SELECT sale_date as date,
                    COUNT(*) as count, COALESCE(SUM(total), 0) as total
             FROM sales WHERE sale_date BETWEEN ? AND ? AND sale_status = 'completed'
             GROUP BY sale_date ORDER BY date ASC",
            [$startDate, $endDate]
        );

        $expenses = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE expense_date BETWEEN ? AND ? AND deleted_at IS NULL",
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
            "SELECT DATE_FORMAT(sale_date, '%m') as month,
                    COUNT(*) as count, COALESCE(SUM(total), 0) as total
             FROM sales WHERE YEAR(sale_date) = ? AND sale_status = 'completed'
             GROUP BY DATE_FORMAT(sale_date, '%m') ORDER BY month ASC",
            [$year]
        );

        $monthlyExpenses = $db->fetchAll(
            "SELECT DATE_FORMAT(expense_date, '%m') as month, COALESCE(SUM(amount), 0) as total
             FROM expenses WHERE YEAR(expense_date) = ? AND deleted_at IS NULL
             GROUP BY DATE_FORMAT(expense_date, '%m') ORDER BY month ASC",
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

        $where = "s.sale_status = 'completed'";
        $queryParams = [];

        if (!empty($params['date_from'])) {
            $where .= " AND s.sale_date >= ?";
            $queryParams[] = $params['date_from'];
        }
        if (!empty($params['date_to'])) {
            $where .= " AND s.sale_date <= ?";
            $queryParams[] = $params['date_to'];
        }

        $summary = $db->fetch(
            "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total,
                    COALESCE(SUM(discount_amount), 0) as discounts, COALESCE(AVG(total), 0) as average
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

        $where = "s.sale_status = 'completed'";
        $queryParams = [];

        if (!empty($params['date_from'])) {
            $where .= " AND s.sale_date >= ?";
            $queryParams[] = $params['date_from'];
        }
        if (!empty($params['date_to'])) {
            $where .= " AND s.sale_date <= ?";
            $queryParams[] = $params['date_to'];
        }

        $salesTotal = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total FROM sales s WHERE $where",
            $queryParams
        );

        $cogs = $db->fetch(
            "SELECT COALESCE(SUM(si.quantity * p.buying_price), 0) as total
             FROM sale_items si
             JOIN products p ON p.id = si.product_id
             JOIN sales s ON s.id = si.sale_id
             WHERE $where",
            $queryParams
        );

        $expensesWhere = "1=1";
        $expenseParams = [];
        if (!empty($params['date_from'])) {
            $expensesWhere .= " AND expense_date >= ?";
            $expenseParams[] = $params['date_from'];
        }
        if (!empty($params['date_to'])) {
            $expensesWhere .= " AND expense_date <= ?";
            $expenseParams[] = $params['date_to'];
        }

        $expenses = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE $expensesWhere AND deleted_at IS NULL",
            $expenseParams
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

        $where = "p.deleted_at IS NULL";
        $queryParams = [];

        if (!empty($params['category_id'])) {
            $where .= " AND p.category_id = ?";
            $queryParams[] = $params['category_id'];
        }

        $summary = $db->fetch(
            "SELECT COUNT(DISTINCT p.id) as total_products,
                    COALESCE(SUM(ps.quantity * p.buying_price), 0) as total_value,
                    COALESCE(SUM(ps.quantity), 0) as total_stock
             FROM products p
             LEFT JOIN product_stocks ps ON ps.product_id = p.id
             WHERE $where",
            $queryParams
        );

        $lowStock = $db->fetch(
            "SELECT COUNT(DISTINCT p.id) as cnt
             FROM products p
             LEFT JOIN product_stocks ps ON ps.product_id = p.id
             WHERE $where AND ps.quantity <= p.minimum_stock",
            $queryParams
        );

        $byCategory = $db->fetchAll(
            "SELECT c.name, COUNT(DISTINCT p.id) as count, COALESCE(SUM(ps.quantity), 0) as stock
             FROM products p
             LEFT JOIN product_stocks ps ON ps.product_id = p.id
             LEFT JOIN categories c ON c.id = p.category_id
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

        $where = "e.deleted_at IS NULL";
        $queryParams = [];

        if (!empty($params['date_from'])) {
            $where .= " AND e.expense_date >= ?";
            $queryParams[] = $params['date_from'];
        }
        if (!empty($params['date_to'])) {
            $where .= " AND e.expense_date <= ?";
            $queryParams[] = $params['date_to'];
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

    public function customers(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $topCustomers = $db->fetchAll(
            "SELECT c.id, CONCAT(c.first_name, ' ', c.last_name) as name, c.email, c.phone,
                    COUNT(s.id) as order_count, COALESCE(SUM(s.total), 0) as total_spent
             FROM customers c
             LEFT JOIN sales s ON s.customer_id = c.id AND s.sale_status = 'completed'
             WHERE c.deleted_at IS NULL
             GROUP BY c.id ORDER BY total_spent DESC LIMIT 20"
        );

        Response::success($topCustomers);
    }

    public function products(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $topProducts = $db->fetchAll(
            "SELECT p.id, p.name, p.sku, SUM(si.quantity) as sold, SUM(si.subtotal) as revenue
             FROM sale_items si
             JOIN products p ON p.id = si.product_id
             JOIN sales s ON s.id = si.sale_id AND s.sale_status = 'completed'
             WHERE p.deleted_at IS NULL";

        $queryParams = [];
        if (!empty($params['date_from'])) {
            $topProducts .= " AND s.sale_date >= ?";
            $queryParams[] = $params['date_from'];
        }
        if (!empty($params['date_to'])) {
            $topProducts .= " AND s.sale_date <= ?";
            $queryParams[] = $params['date_to'];
        }

        $topProducts .= " GROUP BY p.id ORDER BY revenue DESC LIMIT 20";

        $products = $db->fetchAll($topProducts, $queryParams);

        Response::success($products);
    }
}
