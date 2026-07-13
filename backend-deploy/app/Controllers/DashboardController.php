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
            "SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE DATE(sale_date) = ? AND sale_status = 'completed'",
            [$today]
        );

        $monthlySales = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE sale_date BETWEEN ? AND ? AND sale_status = 'completed'",
            [$monthStart, $monthEnd]
        );

        $monthlyExpenses = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE expense_date BETWEEN ? AND ?",
            [$monthStart, $monthEnd]
        );

        $todaySalesCount = $db->fetch(
            "SELECT COUNT(*) as cnt FROM sales WHERE DATE(sale_date) = ? AND sale_status = 'completed'",
            [$today]
        );

        $monthlySalesCount = $db->fetch(
            "SELECT COUNT(*) as cnt FROM sales WHERE sale_date BETWEEN ? AND ? AND sale_status = 'completed'",
            [$monthStart, $monthEnd]
        );

        $customerCount = $db->fetch("SELECT COUNT(*) as cnt FROM customers WHERE deleted_at IS NULL");
        $productCount = $db->fetch("SELECT COUNT(*) as cnt FROM products WHERE deleted_at IS NULL");
        $supplierCount = $db->fetch("SELECT COUNT(*) as cnt FROM suppliers WHERE deleted_at IS NULL");

        $lowStock = $db->fetchAll(
            "SELECT p.id, p.name, p.sku, (SELECT COALESCE(SUM(ps.quantity), 0) FROM product_stocks ps WHERE ps.product_id = p.id) as stock_quantity, p.minimum_stock
             FROM products p
             WHERE p.deleted_at IS NULL AND p.status = 'active'
             HAVING stock_quantity <= p.minimum_stock
             ORDER BY stock_quantity ASC LIMIT 10"
        );

        $recentSales = $db->fetchAll(
            "SELECT s.id, s.invoice_number, s.total, s.payment_method, s.sale_date,
                    CONCAT(c.first_name, ' ', c.last_name) as customer_name
             FROM sales s LEFT JOIN customers c ON c.id = s.customer_id
             WHERE s.sale_status = 'completed'
             ORDER BY s.created_at DESC LIMIT 10"
        );

        $topProducts = $db->fetchAll(
            "SELECT p.id, p.name, p.sku, SUM(si.quantity) as sold, SUM(si.subtotal) as revenue
             FROM sale_items si JOIN products p ON p.id = si.product_id
             JOIN sales s ON s.id = si.sale_id AND s.sale_status = 'completed'
             WHERE DATE(s.sale_date) BETWEEN ? AND ?
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
            "SELECT DATE(sale_date) as date, COALESCE(SUM(total), 0) as total, COUNT(*) as count
             FROM sales WHERE sale_status = 'completed' AND sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(sale_date) ORDER BY date ASC"
        );

        Response::success($data);
    }

    public function profitChart(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $sales = $db->fetchAll(
            "SELECT DATE_FORMAT(sale_date, '%Y-%m') as month, COALESCE(SUM(total), 0) as total
             FROM sales WHERE sale_status = 'completed' AND sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(sale_date, '%Y-%m') ORDER BY month ASC"
        );

        $expenses = $db->fetchAll(
            "SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, COALESCE(SUM(amount), 0) as total
             FROM expenses WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(expense_date, '%Y-%m') ORDER BY month ASC"
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
