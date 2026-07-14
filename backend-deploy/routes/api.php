<?php

// Public routes
$router->post('/api/v1/auth/login', [App\Controllers\AuthController::class, 'login']);

// Protected auth routes
$router->post('/api/v1/auth/logout', function() { (new App\Controllers\AuthController())->logout(); });
$router->post('/api/v1/auth/refresh', function() { (new App\Controllers\AuthController())->refresh(); });
$router->get('/api/v1/auth/me', function() { (new App\Controllers\AuthController())->me(); });
$router->post('/api/v1/auth/change-password', function() { (new App\Controllers\AuthController())->changePassword(); });

// Dashboard
$router->get('/api/v1/dashboard', function() { (new App\Controllers\DashboardController())->index(); });
$router->get('/api/v1/dashboard/sales-chart', function() { (new App\Controllers\DashboardController())->salesChart(); });
$router->get('/api/v1/dashboard/profit-chart', function() { (new App\Controllers\DashboardController())->profitChart(); });

// Users
$router->get('/api/v1/users', function() { (new App\Controllers\UsersController())->index(); });
$router->post('/api/v1/users', function() { (new App\Controllers\UsersController())->create(); });
$router->get('/api/v1/users/{id}', function($id) { (new App\Controllers\UsersController())->show($id); });
$router->put('/api/v1/users/{id}', function($id) { (new App\Controllers\UsersController())->update($id); });
$router->delete('/api/v1/users/{id}', function($id) { (new App\Controllers\UsersController())->delete($id); });
$router->put('/api/v1/users/{id}/status', function($id) { (new App\Controllers\UsersController())->toggleStatus($id); });
$router->put('/api/v1/users/{id}/assign-role', function($id) { (new App\Controllers\UsersController())->assignRole($id); });

// Roles
$router->get('/api/v1/roles', function() { (new App\Controllers\RolesController())->index(); });
$router->post('/api/v1/roles', function() { (new App\Controllers\RolesController())->create(); });
$router->put('/api/v1/roles/{id}', function($id) { (new App\Controllers\RolesController())->update($id); });
$router->delete('/api/v1/roles/{id}', function($id) { (new App\Controllers\RolesController())->delete($id); });
$router->put('/api/v1/roles/{id}/permissions', function($id) { (new App\Controllers\RolesController())->updatePermissions($id); });

// Permissions
$router->get('/api/v1/permissions', function() { (new App\Controllers\PermissionsController())->index(); });

// Products - specific routes BEFORE {id}
$router->get('/api/v1/products', function() { (new App\Controllers\ProductsController())->index(); });
$router->post('/api/v1/products', function() { (new App\Controllers\ProductsController())->create(); });
$router->get('/api/v1/products/barcode/{barcode}', function($barcode) { (new App\Controllers\ProductsController())->barcodeLookup($barcode); });
$router->get('/api/v1/products/{id}', function($id) { (new App\Controllers\ProductsController())->show($id); });
$router->put('/api/v1/products/{id}', function($id) { (new App\Controllers\ProductsController())->update($id); });
$router->delete('/api/v1/products/{id}', function($id) { (new App\Controllers\ProductsController())->delete($id); });
$router->get('/api/v1/products/{id}/stock-history', function($id) { (new App\Controllers\ProductsController())->stockHistory($id); });

// Categories - tree BEFORE {id}
$router->get('/api/v1/categories', function() { (new App\Controllers\CategoriesController())->index(); });
$router->post('/api/v1/categories', function() { (new App\Controllers\CategoriesController())->create(); });
$router->get('/api/v1/categories/tree', function() { (new App\Controllers\CategoriesController())->tree(); });
$router->put('/api/v1/categories/{id}', function($id) { (new App\Controllers\CategoriesController())->update($id); });
$router->delete('/api/v1/categories/{id}', function($id) { (new App\Controllers\CategoriesController())->delete($id); });

// Brands
$router->get('/api/v1/brands', function() { (new App\Controllers\BrandsController())->index(); });
$router->post('/api/v1/brands', function() { (new App\Controllers\BrandsController())->create(); });
$router->put('/api/v1/brands/{id}', function($id) { (new App\Controllers\BrandsController())->update($id); });
$router->delete('/api/v1/brands/{id}', function($id) { (new App\Controllers\BrandsController())->delete($id); });

// Units
$router->get('/api/v1/units', function() { (new App\Controllers\UnitsController())->index(); });
$router->post('/api/v1/units', function() { (new App\Controllers\UnitsController())->create(); });
$router->put('/api/v1/units/{id}', function($id) { (new App\Controllers\UnitsController())->update($id); });
$router->delete('/api/v1/units/{id}', function($id) { (new App\Controllers\UnitsController())->delete($id); });

// Customers
$router->get('/api/v1/customers', function() { (new App\Controllers\CustomersController())->index(); });
$router->post('/api/v1/customers', function() { (new App\Controllers\CustomersController())->create(); });
$router->get('/api/v1/customers/{id}', function($id) { (new App\Controllers\CustomersController())->show($id); });
$router->put('/api/v1/customers/{id}', function($id) { (new App\Controllers\CustomersController())->update($id); });
$router->delete('/api/v1/customers/{id}', function($id) { (new App\Controllers\CustomersController())->delete($id); });
$router->get('/api/v1/customers/{id}/wallet', function($id) { (new App\Controllers\CustomersController())->wallet($id); });
$router->post('/api/v1/customers/{id}/wallet/topup', function($id) { (new App\Controllers\CustomersController())->walletTopup($id); });
$router->post('/api/v1/customers/{id}/wallet/deduct', function($id) { (new App\Controllers\CustomersController())->walletDeduct($id); });
$router->get('/api/v1/customers/{id}/statement', function($id) { (new App\Controllers\CustomersController())->statement($id); });

// Suppliers
$router->get('/api/v1/suppliers', function() { (new App\Controllers\SuppliersController())->index(); });
$router->post('/api/v1/suppliers', function() { (new App\Controllers\SuppliersController())->create(); });
$router->get('/api/v1/suppliers/{id}', function($id) { (new App\Controllers\SuppliersController())->show($id); });
$router->put('/api/v1/suppliers/{id}', function($id) { (new App\Controllers\SuppliersController())->update($id); });
$router->delete('/api/v1/suppliers/{id}', function($id) { (new App\Controllers\SuppliersController())->delete($id); });
$router->post('/api/v1/suppliers/{id}/payments', function($id) { (new App\Controllers\SuppliersController())->addPayment($id); });

// Purchases
$router->get('/api/v1/purchases', function() { (new App\Controllers\PurchasesController())->index(); });
$router->post('/api/v1/purchases', function() { (new App\Controllers\PurchasesController())->create(); });
$router->get('/api/v1/purchases/{id}', function($id) { (new App\Controllers\PurchasesController())->show($id); });
$router->put('/api/v1/purchases/{id}', function($id) { (new App\Controllers\PurchasesController())->update($id); });
$router->post('/api/v1/purchases/{id}/receive', function($id) { (new App\Controllers\PurchasesController())->receive($id); });
$router->post('/api/v1/purchases/{id}/payment', function($id) { (new App\Controllers\PurchasesController())->addPayment($id); });

// Sales - specific routes BEFORE {id}
$router->post('/api/v1/sales', function() { (new App\Controllers\SalesController())->create(); });
$router->get('/api/v1/sales', function() { (new App\Controllers\SalesController())->index(); });
$router->get('/api/v1/sales/held', function() { (new App\Controllers\SalesController())->heldSales(); });
$router->post('/api/v1/sales/hold', function() { (new App\Controllers\SalesController())->hold(); });
$router->get('/api/v1/sales/{id}', function($id) { (new App\Controllers\SalesController())->show($id); });
$router->post('/api/v1/sales/{id}/void', function($id) { (new App\Controllers\SalesController())->void($id); });
$router->post('/api/v1/sales/{id}/resume', function($id) { (new App\Controllers\SalesController())->resume($id); });
$router->post('/api/v1/sales/{id}/return', function($id) { (new App\Controllers\SalesController())->returnSale($id); });
$router->get('/api/v1/sales/{id}/receipt', function($id) { (new App\Controllers\SalesController())->receipt($id); });

// Expenses
$router->get('/api/v1/expenses', function() { (new App\Controllers\ExpensesController())->index(); });

// Returns
$router->get('/api/v1/returns', function() { (new App\Controllers\ReturnsController())->index(); });
$router->post('/api/v1/expenses', function() { (new App\Controllers\ExpensesController())->create(); });
$router->put('/api/v1/expenses/{id}', function($id) { (new App\Controllers\ExpensesController())->update($id); });
$router->delete('/api/v1/expenses/{id}', function($id) { (new App\Controllers\ExpensesController())->delete($id); });
$router->get('/api/v1/expense-categories', function() { (new App\Controllers\ExpensesController())->categories(); });

// Inventory - specific routes BEFORE {id}
$router->get('/api/v1/inventory', function() { (new App\Controllers\InventoryController())->index(); });
$router->post('/api/v1/inventory/adjust', function() { (new App\Controllers\InventoryController())->adjust(); });
$router->post('/api/v1/inventory/transfer', function() { (new App\Controllers\InventoryController())->transfer(); });
$router->get('/api/v1/inventory/low-stock', function() { (new App\Controllers\InventoryController())->lowStock(); });

// Warehouses
$router->get('/api/v1/warehouses', function() { (new App\Controllers\WarehousesController())->index(); });
$router->post('/api/v1/warehouses', function() { (new App\Controllers\WarehousesController())->create(); });
$router->put('/api/v1/warehouses/{id}', function($id) { (new App\Controllers\WarehousesController())->update($id); });
$router->delete('/api/v1/warehouses/{id}', function($id) { (new App\Controllers\WarehousesController())->delete($id); });

// Branches
$router->get('/api/v1/branches', function() { (new App\Controllers\BranchesController())->index(); });
$router->post('/api/v1/branches', function() { (new App\Controllers\BranchesController())->create(); });
$router->put('/api/v1/branches/{id}', function($id) { (new App\Controllers\BranchesController())->update($id); });
$router->delete('/api/v1/branches/{id}', function($id) { (new App\Controllers\BranchesController())->delete($id); });

// Reports
$router->get('/api/v1/reports/daily', function() { (new App\Controllers\ReportsController())->daily(); });
$router->get('/api/v1/reports/weekly', function() { (new App\Controllers\ReportsController())->weekly(); });
$router->get('/api/v1/reports/monthly', function() { (new App\Controllers\ReportsController())->monthly(); });
$router->get('/api/v1/reports/yearly', function() { (new App\Controllers\ReportsController())->yearly(); });
$router->get('/api/v1/reports/sales', function() { (new App\Controllers\ReportsController())->sales(); });
$router->get('/api/v1/reports/profit', function() { (new App\Controllers\ReportsController())->profit(); });
$router->get('/api/v1/reports/inventory', function() { (new App\Controllers\ReportsController())->inventory(); });
$router->get('/api/v1/reports/expenses', function() { (new App\Controllers\ReportsController())->expenses(); });

// Settings
$router->get('/api/v1/settings/company', function() { (new App\Controllers\SettingsController())->company(); });
$router->put('/api/v1/settings/company', function() { (new App\Controllers\SettingsController())->updateCompany(); });

// Notifications - read-all BEFORE {id}
$router->get('/api/v1/notifications', function() { (new App\Controllers\NotificationsController())->index(); });
$router->put('/api/v1/notifications/read-all', function() { (new App\Controllers\NotificationsController())->markAllRead(); });
$router->put('/api/v1/notifications/{id}/read', function($id) { (new App\Controllers\NotificationsController())->markRead($id); });

// Activity Logs
$router->get('/api/v1/activity-logs', function() { (new App\Controllers\ActivityLogsController())->index(); });
