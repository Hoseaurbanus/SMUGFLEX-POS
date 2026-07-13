<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\SaleModel;
use App\Models\ProductModel;
use App\Models\CustomerModel;
use App\Models\SupplierModel;
use App\Models\ExpenseModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        // Today's sales
        $todaySales = $db->table('sales')
            ->selectSum('total')
            ->where('sale_date', $today)
            ->where('sale_status', 'completed')
            ->get()->getRowArray();

        // Monthly sales
        $monthlySales = $db->table('sales')
            ->selectSum('total')
            ->where('sale_date >=', $monthStart)
            ->where('sale_date <=', $monthEnd)
            ->where('sale_status', 'completed')
            ->get()->getRowArray();

        // Today's expenses
        $todayExpenses = $db->table('expenses')
            ->selectSum('amount')
            ->where('expense_date', $today)
            ->get()->getRowArray();

        // Monthly expenses
        $monthlyExpenses = $db->table('expenses')
            ->selectSum('amount')
            ->where('expense_date >=', $monthStart)
            ->where('expense_date <=', $monthEnd)
            ->get()->getRowArray();

        // Today's profit
        $todaySalesAmount = (float) ($todaySales['total'] ?? 0);
        $todayExpensesAmount = (float) ($todayExpenses['amount'] ?? 0);
        $todayProfit = $todaySalesAmount - $todayExpensesAmount;

        // Monthly profit
        $monthlySalesAmount = (float) ($monthlySales['total'] ?? 0);
        $monthlyExpensesAmount = (float) ($monthlyExpenses['amount'] ?? 0);
        $monthlyProfit = $monthlySalesAmount - $monthlyExpensesAmount;

        // Counts
        $totalProducts = $db->table('products')->where('deleted_at', null)->countAllResults();
        $totalCustomers = $db->table('customers')->where('deleted_at', null)->countAllResults();
        $totalSuppliers = $db->table('suppliers')->where('deleted_at', null)->countAllResults();

        // Low stock
        $lowStockQuery = $db->table('product_stocks')
            ->select('product_stocks.*, products.name, products.minimum_stock')
            ->join('products', 'products.id = product_stocks.product_id')
            ->where('product_stocks.quantity <=', $db->expr('products.minimum_stock'))
            ->where('products.deleted_at', null)
            ->countAllResults();

        // Recent sales
        $recentSales = $db->table('sales')
            ->select('sales.*, customers.first_name as customer_first_name, customers.last_name as customer_last_name')
            ->join('customers', 'customers.id = sales.customer_id', 'left')
            ->orderBy('sales.created_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        // Top products
        $topProducts = $db->table('sale_items')
            ->select('products.name, SUM(sale_items.quantity) as quantity_sold, SUM(sale_items.total) as revenue')
            ->join('products', 'products.id = sale_items.product_id')
            ->join('sales', 'sales.id = sale_items.sale_id')
            ->where('sales.sale_status', 'completed')
            ->where('sales.sale_date >=', $monthStart)
            ->groupBy('products.id')
            ->orderBy('quantity_sold', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        return api_success([
            'today_sales'       => $todaySalesAmount,
            'monthly_sales'     => $monthlySalesAmount,
            'today_profit'      => $todayProfit,
            'monthly_profit'    => $monthlyProfit,
            'today_expenses'    => $todayExpensesAmount,
            'monthly_expenses'  => $monthlyExpensesAmount,
            'total_products'    => $totalProducts,
            'total_customers'   => $totalCustomers,
            'total_suppliers'   => $totalSuppliers,
            'low_stock_count'   => $lowStockQuery,
            'recent_sales'      => $recentSales,
            'top_products'      => $topProducts,
        ]);
    }

    public function salesChart()
    {
        $db = \Config\Database::connect();
        $days = 7;
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $sales = $db->table('sales')
                ->selectSum('total')
                ->where('sale_date', $date)
                ->where('sale_status', 'completed')
                ->get()->getRowArray();

            $data[] = [
                'date'  => $date,
                'label' => date('D', strtotime($date)),
                'sales' => (float) ($sales['total'] ?? 0),
            ];
        }

        return api_success($data);
    }

    public function profitChart()
    {
        $db = \Config\Database::connect();
        $months = 6;
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = date('Y-m-01', strtotime("-{$i} months"));
            $monthEnd = date('Y-m-t', strtotime("-{$i} months"));

            $sales = $db->table('sales')
                ->selectSum('total')
                ->where('sale_date >=', $monthStart)
                ->where('sale_date <=', $monthEnd)
                ->where('sale_status', 'completed')
                ->get()->getRowArray();

            $expenses = $db->table('expenses')
                ->selectSum('amount')
                ->where('expense_date >=', $monthStart)
                ->where('expense_date <=', $monthEnd)
                ->get()->getRowArray();

            $salesAmount = (float) ($sales['total'] ?? 0);
            $expensesAmount = (float) ($expenses['amount'] ?? 0);

            $data[] = [
                'month'   => date('M Y', strtotime($monthStart)),
                'sales'   => $salesAmount,
                'expenses' => $expensesAmount,
                'profit'  => $salesAmount - $expensesAmount,
            ];
        }

        return api_success($data);
    }
}
