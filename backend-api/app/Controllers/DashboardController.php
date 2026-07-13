<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class DashboardController
{
    public function index(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        $todaySales = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = ? AND status = 'completed'",
            [$today]
        );

        $monthlySales = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE created_at BETWEEN ? AND ? AND status = 'completed'",
            [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59']
        );

        $monthlyExpenses = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE DATE(created_at) BETWEEN ? AND ?",
            [$monthStart, $monthEnd]
        );

        $todaySalesCount = $db->fetch(
            "SELECT COUNT(*) as cnt FROM sales WHERE DATE(created_at) = ? AND status = 'completed'",
            [$today]
        );

        $monthlySalesCount = $db->fetch(
            "SELECT COUNT(*) as cnt FROM sales WHERE created_at BETWEEN ? AND ? AND status = 'completed'",
            [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59']
        );

        $customerCount = $db->fetch("SELECT COUNT(*) as cnt FROM customers WHERE is_deleted = 0");
        $productCount = $db->fetch("SELECT COUNT(*) as cnt FROM products WHERE is_deleted = 0");
        $supplierCount = $db->fetch("SELECT COUNT(*) as cnt FROM suppliers WHERE is_deleted = 0");

        $lowStock = $db->fetchAll(
            "SELECT id, name, sku, stock_quantity, minimum_stock FROM products WHERE stock_quantity <= minimum_stock AND is_deleted = 0 ORDER BY stock_quantity ASC LIMIT 10"
        );

        $recentSales = $db->fetchAll(
            "SELECT s.id, s.reference_number, s.total, s.payment_method, s.created_at, c.name as customer_name
             FROM sales s LEFT JOIN customers c ON c.id = s.customer_id
             WHERE s.status = 'completed'
             ORDER BY s.created_at DESC LIMIT 10"
        );

        $topProducts = $db->fetchAll(
            "SELECT p.id, p.name, p.sku, SUM(si.quantity) as sold, SUM(si.subtotal) as revenue
             FROM sale_items si JOIN products p ON p.id = si.product_id
             JOIN sales s ON s.id = si.sale_id AND s.status = 'completed'
             WHERE DATE(s.created_at) BETWEEN ? AND ?
             GROUP BY p.id ORDER BY sold DESC LIMIT 10",
            [$monthStart, $monthEnd]
        );

        $profit = (float)$monthlySales['total'] - (float)$monthlyExpenses['total'];

        Response::success([
            'today_sales' => (float)$todaySales['total'],
            'today_sales_count' => (int)$todaySalesCount['cnt'],
            'monthly_sales' => (float)$monthlySales['total'],
            'monthly_sales_count' => (int)$monthlySalesCount['cnt'],
            'monthly_expenses' => (float)$monthlyExpenses['total'],
            'monthly_profit' => $profit,
            'customer_count' => (int)$customerCount['cnt'],
            'product_count' => (int)$productCount['cnt'],
            'supplier_count' => (int)$supplierCount['cnt'],
            'low_stock' => $lowStock,
            'recent_sales' => $recentSales,
            'top_products' => $topProducts,
        ]);
    }

    public function salesChart(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $data = $db->fetchAll(
            "SELECT DATE(created_at) as date, COALESCE(SUM(total), 0) as total, COUNT(*) as count
             FROM sales WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at) ORDER BY date ASC"
        );

        Response::success($data);
    }

    public function profitChart(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $sales = $db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COALESCE(SUM(total), 0) as total
             FROM sales WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month ASC"
        );

        $expenses = $db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COALESCE(SUM(amount), 0) as total
             FROM expenses WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month ASC"
        );

        $expenseMap = [];
        foreach ($expenses as $e) {
            $expenseMap[$e['month']] = (float)$e['total'];
        }

        $result = [];
        foreach ($sales as $s) {
            $month = $s['month'];
            $exp = $expenseMap[$month] ?? 0;
            $result[] = [
                'month' => $month,
                'sales' => (float)$s['total'],
                'expenses' => $exp,
                'profit' => (float)$s['total'] - $exp,
            ];
        }

        Response::success($result);
    }
}
