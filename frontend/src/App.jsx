import { lazy, Suspense } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './hooks/useAuth';
import LoadingSpinner from './components/common/LoadingSpinner';

const AuthLayout = lazy(() => import('./layouts/AuthLayout'));
const DashboardLayout = lazy(() => import('./layouts/DashboardLayout'));
const Login = lazy(() => import('./pages/auth/Login'));
const Dashboard = lazy(() => import('./pages/dashboard/Dashboard'));
const POS = lazy(() => import('./pages/pos/POS'));
const Products = lazy(() => import('./pages/products/Products'));
const ProductCreate = lazy(() => import('./pages/products/ProductCreate'));
const ProductEdit = lazy(() => import('./pages/products/ProductEdit'));
const Categories = lazy(() => import('./pages/categories/Categories'));
const Brands = lazy(() => import('./pages/brands/Brands'));
const Units = lazy(() => import('./pages/units/Units'));
const Customers = lazy(() => import('./pages/customers/Customers'));
const CustomerCreate = lazy(() => import('./pages/customers/CustomerCreate'));
const CustomerEdit = lazy(() => import('./pages/customers/CustomerEdit'));
const Suppliers = lazy(() => import('./pages/suppliers/Suppliers'));
const SupplierCreate = lazy(() => import('./pages/suppliers/SupplierCreate'));
const Purchases = lazy(() => import('./pages/purchases/Purchases'));
const PurchaseCreate = lazy(() => import('./pages/purchases/PurchaseCreate'));
const Sales = lazy(() => import('./pages/sales/Sales'));
const SaleView = lazy(() => import('./pages/sales/SaleView'));
const Returns = lazy(() => import('./pages/returns/Returns'));
const Expenses = lazy(() => import('./pages/expenses/Expenses'));
const Inventory = lazy(() => import('./pages/inventory/Inventory'));
const Warehouses = lazy(() => import('./pages/warehouses/Warehouses'));
const Branches = lazy(() => import('./pages/branches/Branches'));
const Users = lazy(() => import('./pages/users/Users'));
const UserCreate = lazy(() => import('./pages/users/UserCreate'));
const Roles = lazy(() => import('./pages/roles/Roles'));
const Reports = lazy(() => import('./pages/reports/Reports'));
const Settings = lazy(() => import('./pages/settings/Settings'));
const Notifications = lazy(() => import('./pages/notifications/Notifications'));
const ActivityLog = lazy(() => import('./pages/activity-log/ActivityLog'));
const Profile = lazy(() => import('./pages/profile/Profile'));

function ProtectedRoute({ children }) {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return <LoadingSpinner fullScreen />;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return children;
}

function PublicRoute({ children }) {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return <LoadingSpinner fullScreen />;
  }

  if (isAuthenticated) {
    return <Navigate to="/" replace />;
  }

  return children;
}

export default function App() {
  return (
    <Suspense fallback={<LoadingSpinner fullScreen />}>
      <Routes>
        <Route
          path="/login"
          element={
            <PublicRoute>
              <AuthLayout>
                <Login />
              </AuthLayout>
            </PublicRoute>
          }
        />

        <Route
          path="/"
          element={
            <ProtectedRoute>
              <DashboardLayout />
            </ProtectedRoute>
          }
        >
          <Route index element={<Dashboard />} />
          <Route path="pos" element={<POS />} />

          <Route path="products" element={<Products />} />
          <Route path="products/create" element={<ProductCreate />} />
          <Route path="products/:id/edit" element={<ProductEdit />} />

          <Route path="categories" element={<Categories />} />
          <Route path="brands" element={<Brands />} />
          <Route path="units" element={<Units />} />

          <Route path="customers" element={<Customers />} />
          <Route path="customers/create" element={<CustomerCreate />} />
          <Route path="customers/:id/edit" element={<CustomerEdit />} />

          <Route path="suppliers" element={<Suppliers />} />
          <Route path="suppliers/create" element={<SupplierCreate />} />

          <Route path="purchases" element={<Purchases />} />
          <Route path="purchases/create" element={<PurchaseCreate />} />

          <Route path="sales" element={<Sales />} />
          <Route path="sales/:id" element={<SaleView />} />

          <Route path="returns" element={<Returns />} />
          <Route path="expenses" element={<Expenses />} />
          <Route path="inventory" element={<Inventory />} />
          <Route path="warehouses" element={<Warehouses />} />
          <Route path="branches" element={<Branches />} />

          <Route path="users" element={<Users />} />
          <Route path="users/create" element={<UserCreate />} />
          <Route path="roles" element={<Roles />} />

          <Route path="reports" element={<Reports />} />
          <Route path="settings" element={<Settings />} />
          <Route path="notifications" element={<Notifications />} />
          <Route path="activity-log" element={<ActivityLog />} />
          <Route path="profile" element={<Profile />} />
        </Route>

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </Suspense>
  );
}
