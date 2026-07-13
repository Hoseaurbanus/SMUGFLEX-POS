<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Routes extends \CodeIgniter\Router\RouteCollection
{
    public function init(): void
    {
        $routes->setAutoRoute(false);
        $routes->setNamespace('App\Controllers');

        // API v1 group
        $routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1'], static function ($routes) {

            // Public routes (no auth)
            $routes->post('auth/login', 'Auth::login');
            $routes->post('auth/register', 'Auth::register');
            $routes->post('auth/forgot-password', 'Auth::forgotPassword');

            // Protected routes (JWT required)
            $routes->group('', ['filter' => 'jwtauth'], static function ($routes) {

                // Auth
                $routes->post('auth/logout', 'Auth::logout');
                $routes->post('auth/refresh', 'Auth::refresh');
                $routes->get('auth/me', 'Auth::me');
                $routes->post('auth/change-password', 'Auth::changePassword');

                // Dashboard
                $routes->get('dashboard', 'Dashboard::index');
                $routes->get('dashboard/sales-chart', 'Dashboard::salesChart');
                $routes->get('dashboard/profit-chart', 'Dashboard::profitChart');

                // Users
                $routes->get('users', 'Users::index');
                $routes->post('users', 'Users::create');
                $routes->get('users/(:num)', 'Users::show/$1');
                $routes->put('users/(:num)', 'Users::update/$1');
                $routes->delete('users/(:num)', 'Users::delete/$1');
                $routes->put('users/(:num)/status', 'Users::toggleStatus/$1');
                $routes->put('users/(:num)/assign-role', 'Users::assignRole/$1');

                // Roles
                $routes->get('roles', 'Roles::index');
                $routes->post('roles', 'Roles::create');
                $routes->put('roles/(:num)', 'Roles::update/$1');
                $routes->delete('roles/(:num)', 'Roles::delete/$1');
                $routes->put('roles/(:num)/permissions', 'Roles::updatePermissions/$1');

                // Permissions
                $routes->get('permissions', 'Permissions::index');

                // Products
                $routes->get('products', 'Products::index');
                $routes->post('products', 'Products::create');
                $routes->get('products/(:num)', 'Products::show/$1');
                $routes->put('products/(:num)', 'Products::update/$1');
                $routes->delete('products/(:num)', 'Products::delete/$1');
                $routes->get('products/(:num)/stock-history', 'Products::stockHistory/$1');
                $routes->get('products/barcode/(:segment)', 'Products::barcodeLookup/$1');
                $routes->post('products/import', 'Products::import');
                $routes->post('products/(:num)/variants', 'Products::addVariant/$1');
                $routes->put('products/variants/(:num)', 'Products::updateVariant/$1');
                $routes->delete('products/variants/(:num)', 'Products::deleteVariant/$1');

                // Categories
                $routes->get('categories', 'Categories::index');
                $routes->post('categories', 'Categories::create');
                $routes->put('categories/(:num)', 'Categories::update/$1');
                $routes->delete('categories/(:num)', 'Categories::delete/$1');
                $routes->get('categories/tree', 'Categories::tree');

                // Brands
                $routes->get('brands', 'Brands::index');
                $routes->post('brands', 'Brands::create');
                $routes->put('brands/(:num)', 'Brands::update/$1');
                $routes->delete('brands/(:num)', 'Brands::delete/$1');

                // Units
                $routes->get('units', 'Units::index');
                $routes->post('units', 'Units::create');
                $routes->put('units/(:num)', 'Units::update/$1');
                $routes->delete('units/(:num)', 'Units::delete/$1');

                // Customers
                $routes->get('customers', 'Customers::index');
                $routes->post('customers', 'Customers::create');
                $routes->get('customers/(:num)', 'Customers::show/$1');
                $routes->put('customers/(:num)', 'Customers::update/$1');
                $routes->delete('customers/(:num)', 'Customers::delete/$1');
                $routes->get('customers/(:num)/wallet', 'Customers::wallet/$1');
                $routes->post('customers/(:num)/wallet/topup', 'Customers::walletTopup/$1');
                $routes->post('customers/(:num)/wallet/deduct', 'Customers::walletDeduct/$1');
                $routes->get('customers/(:num)/statement', 'Customers::statement/$1');
                $routes->get('customers/(:num)/purchase-history', 'Customers::purchaseHistory/$1');

                // Suppliers
                $routes->get('suppliers', 'Suppliers::index');
                $routes->post('suppliers', 'Suppliers::create');
                $routes->get('suppliers/(:num)', 'Suppliers::show/$1');
                $routes->put('suppliers/(:num)', 'Suppliers::update/$1');
                $routes->delete('suppliers/(:num)', 'Suppliers::delete/$1');
                $routes->get('suppliers/(:num)/statement', 'Suppliers::statement/$1');
                $routes->post('suppliers/(:num)/payments', 'Suppliers::addPayment/$1');

                // Purchases
                $routes->get('purchases', 'Purchases::index');
                $routes->post('purchases', 'Purchases::create');
                $routes->get('purchases/(:num)', 'Purchases::show/$1');
                $routes->put('purchases/(:num)', 'Purchases::update/$1');
                $routes->post('purchases/(:num)/receive', 'Purchases::receive/$1');
                $routes->post('purchases/(:num)/return', 'Purchases::returnPurchase/$1');
                $routes->post('purchases/(:num)/payment', 'Purchases::addPayment/$1');
                $routes->get('purchases/(:num)/payments', 'Purchases::getPayments/$1');

                // Sales
                $routes->post('sales', 'Sales::create');
                $routes->get('sales', 'Sales::index');
                $routes->get('sales/(:num)', 'Sales::show/$1');
                $routes->post('sales/(:num)/void', 'Sales::void/$1');
                $routes->post('sales/hold', 'Sales::hold');
                $routes->get('sales/held', 'Sales::heldSales');
                $routes->post('sales/(:num)/resume', 'Sales::resume/$1');
                $routes->post('sales/(:num)/return', 'Sales::returnSale/$1');
                $routes->get('sales/(:num)/receipt', 'Sales::receipt/$1');

                // Returns
                $routes->get('returns', 'Returns::index');
                $routes->get('returns/(:num)', 'Returns::show/$1');
                $routes->post('returns/(:num)/approve', 'Returns::approve/$1');

                // Expenses
                $routes->get('expenses', 'Expenses::index');
                $routes->post('expenses', 'Expenses::create');
                $routes->put('expenses/(:num)', 'Expenses::update/$1');
                $routes->delete('expenses/(:num)', 'Expenses::delete/$1');
                $routes->get('expense-categories', 'Expenses::categories');
                $routes->post('expense-categories', 'Expenses::createCategory');
                $routes->put('expense-categories/(:num)', 'Expenses::updateCategory/$1');
                $routes->delete('expense-categories/(:num)', 'Expenses::deleteCategory/$1');

                // Inventory
                $routes->get('inventory', 'Inventory::index');
                $routes->post('inventory/adjust', 'Inventory::adjust');
                $routes->post('inventory/transfer', 'Inventory::transfer');
                $routes->get('inventory/low-stock', 'Inventory::lowStock');
                $routes->get('inventory/out-of-stock', 'Inventory::outOfStock');
                $routes->get('inventory/expiry-alerts', 'Inventory::expiryAlerts');

                // Warehouses
                $routes->get('warehouses', 'Warehouses::index');
                $routes->post('warehouses', 'Warehouses::create');
                $routes->put('warehouses/(:num)', 'Warehouses::update/$1');
                $routes->delete('warehouses/(:num)', 'Warehouses::delete/$1');

                // Branches
                $routes->get('branches', 'Branches::index');
                $routes->post('branches', 'Branches::create');
                $routes->put('branches/(:num)', 'Branches::update/$1');
                $routes->delete('branches/(:num)', 'Branches::delete/$1');

                // Reports
                $routes->get('reports/daily', 'Reports::daily');
                $routes->get('reports/weekly', 'Reports::weekly');
                $routes->get('reports/monthly', 'Reports::monthly');
                $routes->get('reports/yearly', 'Reports::yearly');
                $routes->get('reports/sales', 'Reports::sales');
                $routes->get('reports/profit', 'Reports::profit');
                $routes->get('reports/inventory', 'Reports::inventory');
                $routes->get('reports/expenses', 'Reports::expenses');
                $routes->get('reports/tax', 'Reports::tax');
                $routes->get('reports/cashflow', 'Reports::cashflow');
                $routes->get('reports/best-selling', 'Reports::bestSelling');
                $routes->get('reports/least-selling', 'Reports::leastSelling');
                $routes->get('reports/payment-methods', 'Reports::paymentMethods');
                $routes->get('reports/export/(:segment)', 'Reports::export/$1');

                // Settings
                $routes->get('settings/company', 'Settings::company');
                $routes->put('settings/company', 'Settings::updateCompany');
                $routes->get('settings', 'Settings::index');
                $routes->put('settings', 'Settings::update');
                $routes->post('settings/backup', 'Settings::backup');
                $routes->get('settings/backups', 'Settings::backups');
                $routes->post('settings/restore/(:num)', 'Settings::restore/$1');
                $routes->post('settings/upload-logo', 'Settings::uploadLogo');

                // Notifications
                $routes->get('notifications', 'Notifications::index');
                $routes->put('notifications/(:num)/read', 'Notifications::markRead/$1');
                $routes->put('notifications/read-all', 'Notifications::markAllRead');

                // Activity Logs
                $routes->get('activity-logs', 'ActivityLogs::index');
            });
        });

        // Catch-all route
        $routes->set404Override(function () {
            return service('response')->setJSON([
                'success' => false,
                'message' => 'Endpoint not found'
            ])->setStatusCode(404);
        });
    }
}
