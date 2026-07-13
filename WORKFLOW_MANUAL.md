# SmugFlex POS — End-to-End Audit, Workflow Manual & Roadmap

> **Date:** July 13, 2026
> **System Version:** v1.0.0
> **Stack:** React 19 + Vite (Frontend) | Standalone PHP 8.2 REST API (Backend) | MySQL 8.0

---

## TABLE OF CONTENTS

1. [System Architecture](#1-system-architecture)
2. [Audit Summary Dashboard](#2-audit-summary-dashboard)
3. [Critical Issues (Must Fix Before Launch)](#3-critical-issues-must-fix-before-launch)
4. [High Priority Issues](#4-high-priority-issues)
5. [Medium Priority Issues](#5-medium-priority-issues)
6. [Complete Workflow Manual](#6-complete-workflow-manual)
7. [API Reference](#7-api-reference)
8. [Database Schema](#8-database-schema)
9. [Development Roadmap](#9-development-roadmap)
10. [Deployment Checklist](#10-deployment-checklist)

---

## 1. SYSTEM ARCHITECTURE

```
┌──────────────────────────────────────────────────────────────┐
│                        FRONTEND                              │
│  React 19 + Vite → Vercel (smugflex-pos-mrfb.vercel.app)   │
│  25+ pages │ React Query │ React Hot Toast │ Bootstrap 5.3  │
└──────────────────────┬───────────────────────────────────────┘
                       │ REST API (JSON)
                       ▼
┌──────────────────────────────────────────────────────────────┐
│                        BACKEND                               │
│  Standalone PHP 8.2 → cPanel (smug.com.gracelandroyal...)   │
│  Custom Router │ JWT Auth │ PDO MySQL │ 21 Controllers      │
└──────────────────────┬───────────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────────┐
│                      DATABASE                                │
│  MySQL 8.0 │ 35 Tables │ Triggers │ Foreign Keys │ Seed Data │
│  DB: mdpjhtua_POS                                           │
└──────────────────────────────────────────────────────────────┘
```

### Frontend Structure
```
frontend/src/
├── components/layout/   → Sidebar.jsx, TopBar.jsx
├── contexts/            → AuthContext, ThemeContext, NotificationContext
├── hooks/               → useAuth.js, useApi.js
├── layouts/             → DashboardLayout, AuthLayout
├── pages/               → 23 module directories (31 component files)
├── services/            → api.js (Axios + token refresh)
├── styles/              → theme.css, globals.css
└── utils/               → formatters.js
```

### Backend Structure
```
backend-deploy/
├── index.php            → Entry point (CORS headers)
├── .htaccess            → URL rewriting
├── config/              → app.php (JWT secret), database.php
├── core/                → Database, Router, JWT, AuthMiddleware, Request, Response, Helpers
├── app/Controllers/     → 21 controllers
└── routes/              → api.php (103 routes)
```

---

## 2. AUDIT SUMMARY DASHBOARD

### Overall Status: ⚠️ NOT PRODUCTION READY

| Category | Score | Details |
|----------|-------|---------|
| Backend API | 85/100 | 103 routes, all methods exist, auth applied. Issues: register endpoint security, missing permission enforcement, field mismatches |
| Frontend Pages | 60/100 | 31 components, 8 fully functional, 17 functional with issues, 4 read-only/partial, 2 broken links |
| DB Schema | 90/100 | 35 tables, proper FKs, triggers, seed data. 10 tables have no controller |
| API Integration | 40/100 | Pervasive field name mismatches between frontend and backend |
| Security | 50/100 | JWT auth works but: hardcoded secrets, open registration, no rate limiting, no permission enforcement |
| Mobile Responsive | 80/100 | Sidebar, topbar, login, all pages responsive. Minor edge cases |

### Page Status Matrix

| Page | Status | API Calls | Issues |
|------|--------|-----------|--------|
| Login | ✅ Working | POST /auth/login | Hardcoded default credentials |
| Dashboard | ⚠️ Partial | GET /dashboard | 8 field name mismatches, hardcoded percentages |
| POS Terminal | ⚠️ Partial | GET /products, GET /customers, POST /sales | Sale payload field mismatch, hold not persistent |
| Products (list) | ⚠️ Partial | GET /products, DELETE /products/{id} | `total_stock` vs `stock_quantity`, no pagination UI |
| Product Create | ❌ Broken | POST /products | Missing category/brand/unit dropdowns, `buying_price` field mismatch |
| Product Edit | ❌ Broken | GET/PUT /products/{id} | Same issues as Create |
| Categories | ✅ Working | Full CRUD | `products_count` vs `product_count` display issue |
| Brands | ✅ Working | Full CRUD | Same product count display issue |
| Units | ✅ Working | Full CRUD | Minor |
| Customers (list) | ⚠️ Partial | GET /customers, DELETE | `first_name/last_name` vs backend `name` field |
| Customer Create | ⚠️ Partial | POST /customers | Sends first_name/last_name but backend expects `name` |
| Customer Edit | ⚠️ Partial | GET/PUT /customers/{id} | Same field mismatch |
| Suppliers (list) | ⚠️ Partial | GET /suppliers, DELETE | Edit link broken (no SupplierEdit page) |
| Supplier Create | ⚠️ Partial | POST /suppliers | `contact_person` vs backend `company` field |
| Sales (list) | ⚠️ Partial | GET /sales | `invoice_number` field mismatch, hardcoded `$` |
| Sale View | ⚠️ Partial | GET /sales/{id} | Multiple field mismatches, hardcoded `$` |
| Purchases (list) | ⚠️ Partial | GET /purchases | Supplier display broken |
| Purchase Create | ⚠️ Partial | GET/POST | Client-calculated total, hardcoded `$` |
| Returns | ❌ Broken | GET /returns | **No backend endpoint exists** — 404 error |
| Expenses | ⚠️ Partial | GET/POST/DELETE | Category text input vs foreign key mismatch |
| Inventory | 📖 Read-only | GET /inventory | No adjust/transfer UI |
| Warehouses | ✅ Working | Full CRUD | — |
| Branches | ✅ Working | Full CRUD | — |
| Users (list) | ⚠️ Partial | GET /users, DELETE | Edit link broken (no UserEdit page) |
| User Create | ✅ Working | GET/POST | — |
| Roles | ✅ Working | Full CRUD + permissions | — |
| Reports | ⚠️ Partial | GET /reports/* | Date picker only works for daily, field mismatches |
| Settings | ✅ Working | GET/PUT /settings/company | — |
| Notifications | ✅ Working | GET /notifications | No error handling on mutations |
| Activity Log | 📖 Read-only | GET /activity-logs | — |
| Profile | ⚠️ Partial | GET /auth/me, POST /change-password | Cannot edit name/email/phone |

### Legend
- ✅ Working — Fully functional end-to-end
- ⚠️ Partial — Works but has field mismatches, missing features, or UX issues
- ❌ Broken — Will not function correctly
- 📖 Read-only — Can view data but no create/edit/delete

---

## 3. CRITICAL ISSUES (Must Fix Before Launch)

### C1: Open Registration Allows Privilege Escalation
**File:** `AuthController.php:64-93`
`POST /api/v1/auth/register` is publicly accessible with NO authentication. An attacker can register with `role_id` pointing to super_admin. **Fix:** Remove this endpoint or add admin-only auth middleware.

### C2: Hardcoded JWT Secret
**File:** `config/app.php:4`
JWT_SECRET is hardcoded as `SmuGFlExP0s_S3cr3t_K3y_2026_Pr0duct10n_R34dy`. If the code is public, anyone can forge tokens. **Fix:** Move to environment variable or encrypted config.

### C3: Hardcoded Database Credentials
**File:** `config/database.php:3-4`
DB credentials `mdpjhtua_POS` / `159075321@Au` are in source code. **Fix:** Move to environment variable.

### C4: No Permission Enforcement at API Level
**File:** All controllers
`AuthMiddleware::requirePermission()` exists but is **never used**. Any authenticated user can access any endpoint (delete users, manage roles, view reports). **Fix:** Add permission checks to sensitive endpoints.

### C5: Dashboard Field Name Mismatches
**File:** `Dashboard.jsx` vs `DashboardController.php`
Frontend expects `total_customers`, `total_products`, `total_suppliers`, `low_stock_count`, `today_profit`, `today_expenses` — backend returns `customer_count`, `product_count`, `supplier_count`, `low_stock` (array), and omits profit/expenses. Dashboard stats will all show 0 or undefined.

### C6: Customer Model Field Mismatch
**Files:** `Customers.jsx`, `CustomerCreate.jsx`, `CustomerEdit.jsx` vs `CustomersController.php`
Frontend uses `first_name` + `last_name` but backend stores `name` (single field). All customer creation/editing will silently ignore the name fields. Customer list will show undefined names.

### C7: POS Sale Payload Field Mismatch
**Files:** `POS.jsx` vs `SalesController.php`
Frontend sends `unit_price`, `discount_amount`, `paid_amount` — backend expects `price`, `discount`, `amount_paid`. Sale creation will fail or have wrong values.

### C8: Product Stock Field Mismatch
**Files:** `Products.jsx`, `POS.jsx` vs `ProductsController.php`
Frontend reads `product.total_stock` — backend returns `stock_quantity`. All stock displays will show 0.

### C9: Returns Page Has No Backend
**File:** `Returns.jsx`
Frontend calls `GET /returns` but no such route exists in `api.php`. Returns page will show 404 error.

### C10: Missing Product Dropdowns in Create/Edit
**Files:** `ProductCreate.jsx`, `ProductEdit.jsx`
No category, brand, or unit select fields. Products can never be assigned to categories/brands/units from the UI.

---

## 4. HIGH PRIORITY ISSUES

| # | Issue | File | Impact |
|---|-------|------|--------|
| H1 | SupplierEdit page missing | App.jsx, Suppliers.jsx | Edit button is dead link |
| H2 | UserEdit page missing | App.jsx, Users.jsx | Edit button is dead link |
| H3 | POS warehouse_id/branch_id hardcoded to 1 | POS.jsx:117-118 | Wrong warehouse for multi-branch |
| H4 | Wallet payment has no balance check | SalesController.php:142-158 | Wallet can go negative |
| H5 | Purchase detail/receive page missing | App.jsx | Cannot receive purchases from UI |
| H6 | Expense category is text input, not dropdown | Expenses.jsx:147 | Categories never saved correctly |
| H7 | AuthContext uses raw axios for /auth/me | AuthContext.jsx | Bypasses token refresh interceptor |
| H8 | Reports date picker only works for daily tab | Reports.jsx | Weekly/monthly/yearly ignore date |
| H9 | Suppliers field mismatch (contact_person vs company) | SupplierCreate.jsx | Contact info not saved |
| H10 | Sales invoice_number field mismatch | Sales.jsx:98 | Shows undefined |
| H11 | Hold sales are ephemeral (lost on refresh) | POS.jsx | Data loss |
| H12 | No barcode scanning integration | POS.jsx | Missing core POS feature |

---

## 5. MEDIUM PRIORITY ISSUES

| # | Issue | File | Impact |
|---|-------|------|--------|
| M1 | 9 pages use hardcoded `$` instead of formatCurrency() | Multiple | Inconsistent currency display |
| M2 | Dashboard stat percentages are hardcoded strings | Dashboard.jsx | Fake data |
| M3 | Product Export button has no handler | Products.jsx | Dead button |
| M4 | No pagination UI on Products page | Products.jsx | Can't navigate pages |
| M5 | Categories product count field mismatch | Categories.jsx | Always shows 0 |
| M6 | Brands product count field mismatch | Brands.jsx | Always shows 0 |
| M7 | 10 DB tables have no controller | Schema | Features incomplete |
| M8 | Stock trigger may double-count | database.sql | Stock quantity incorrect |
| M9 | No transaction handling in sales creation | SalesController.php | Race condition risk |
| M10 | Refresh token same as access token | AuthController.php | Security concern |
| M11 | Profile cannot edit name/email/phone | Profile.jsx | Incomplete |
| M12 | No chart visualizations anywhere | Dashboard/Reports | Poor UX |
| M13 | "Forgot password" link is dead | Login.jsx | UX dead end |
| M14 | "Remember me" checkbox is non-functional | Login.jsx | UX dead end |
| M15 | useApi.js hook is unused | hooks/useApi.js | Dead code |
| M16 | Expense edit functionality missing | Expenses.jsx | Can't correct mistakes |
| M17 | Reports missing profit/inventory/expense views | Reports.jsx | Backend has them, frontend doesn't |
| M18 | Notifications have no error handling | Notifications.jsx | Silent failures |

---

## 6. COMPLETE WORKFLOW MANUAL

### 6.1 Authentication Workflow

```
User opens app → Redirected to /login
    ↓
Enters email + password → POST /auth/login
    ↓
Backend validates → Returns { token, refresh_token, user }
    ↓
Frontend stores in localStorage → Redirects to /
    ↓
Every API request → Header: Authorization: Bearer <token>
    ↓
On 401 response → POST /auth/refresh with refresh_token
    ↓
If refresh succeeds → New token stored, original request retried
If refresh fails → Clear localStorage → Redirect to /login
    ↓
Logout → POST /auth/logout → Clear localStorage → /login
```

**Known Issues:** Refresh token identical to access token. No server-side token revocation.

### 6.2 Dashboard Workflow

```
User lands on / → Dashboard.jsx mounts
    ↓
GET /dashboard → Returns:
  - today_sales, today_sales_count
  - monthly_sales, monthly_sales_count
  - monthly_expenses, monthly_profit
  - customer_count, product_count, supplier_count
  - low_stock (array), recent_sales, top_products
    ↓
Dashboard displays stat cards, recent sales table, top products
```

**Known Issues:** 8 field name mismatches. Hardcoded change percentages.

### 6.3 Products Workflow

**List Products:**
```
/products → GET /products?search=&page=1&limit=15
    ↓
Displays: name, SKU, category_name, prices, stock, status
    ↓
Search: Debounced text input → filters by name/SKU/barcode
    ↓
Delete: Confirm dialog → DELETE /products/{id} → Refetch list
```

**Create Product:**
```
/products/create → Form with fields
    ↓
POST /products → { name, sku, barcode, buying_price, selling_price, ... }
    ↓
On success → Toast → Redirect to /products
```

**Known Issues:** Missing category/brand/unit dropdowns. `buying_price` field not saved by backend.

### 6.4 Sales Workflow (POS Terminal)

```
/pos → Loads products grid + customer dropdown
    ↓
User searches products → GET /products?search=&limit=50
    ↓
User clicks product → Added to cart (local state)
    ↓
User selects customer → GET /customers (dropdown)
    ↓
User clicks Pay → Payment modal opens
    ↓
Selects payment method (cash/card/transfer/wallet)
    ↓
POST /sales → {
  items: [{ product_id, quantity, unit_price, tax_rate }],
  customer_id, discount_amount, paid_amount, payment_method
}
    ↓
Backend validates stock → Creates sale + items → Decrements stock
    ↓
Returns receipt data → POS clears cart
```

**Known Issues:** Field name mismatches in payload. Hold sales not persistent. warehouse_id/branch_id hardcoded.

### 6.5 Purchases Workflow

**List:**
```
/purchases → GET /purchases → Displays: reference, supplier, total, status, date
```

**Create:**
```
/purchases/create → Fetches suppliers + products dropdowns
    ↓
User adds items (product, qty, unit_cost) → Calculates total
    ↓
POST /purchases → { supplier_id, warehouse_id, items, notes }
    ↓
Backend creates purchase with status "pending"
```

**Receive (Backend only, no UI):**
```
POST /purchases/{id}/receive → Updates received_quantity → Increments stock
```

**Known Issues:** No purchase detail view. No receive button in frontend.

### 6.6 Customers Workflow

**List:**
```
/customers → GET /customers → Displays: name, email, phone, wallet_balance
    ↓
Search: filters by name/email/phone
    ↓
Delete: SweetAlert → DELETE /customers/{id}
```

**Create:**
```
/customers/create → Form: first_name, last_name, email, phone, address, city, state
    ↓
POST /customers → Backend expects `name` (not first/last)
```

**Known Issues:** `first_name`/`last_name` vs `name` mismatch. Wallet operations exist but no UI.

### 6.7 Categories/Brands/Units Workflow

```
/categories → GET /categories → Table with name, description, product_count
    ↓
Click "Add Category" → Modal with name + description
    ↓
POST /categories → Creates → Refetches list
    ↓
Edit: Click row → Modal pre-filled → PUT /categories/{id}
    ↓
Delete: SweetAlert → DELETE /categories/{id} → Refetches
```

**Status:** ✅ Fully functional. Minor product_count display issue.

### 6.8 Reports Workflow

```
/reports → Tab: Daily | Weekly | Monthly | Yearly
    ↓
Date picker → GET /reports/{type}?date={date}
    ↓
Displays: sales totals, expenses, profit, top products, breakdown
```

**Known Issues:** Date picker only works for daily. Field name mismatches. No charts.

### 6.9 Expenses Workflow

```
/expenses → GET /expenses → Table with category, amount, payment_method, date
    ↓
Click "Add Expense" → Modal with category (text!), amount, method, date, description
    ↓
POST /expenses → Category text sent but backend expects expense_category_id
    ↓
Delete: SweetAlert → DELETE /expenses/{id}
```

**Known Issues:** Category is text input (should be dropdown). No edit functionality.

### 6.10 Settings Workflow

```
/settings → GET /settings/company → Form with company details
    ↓
Edit fields → PUT /settings/company → Saves
```

**Status:** ✅ Functional.

---

## 7. API REFERENCE

### Base URL
```
Production: https://smug.com.gracelandroyalacademy.com.ng/api/v1
```

### Authentication
```
Header: Authorization: Bearer <token>
```

### Response Format
```json
{
  "success": true,
  "data": { ... },
  "message": "Success",
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

### Endpoints (103 total)

| Module | Method | Endpoint | Auth | Description |
|--------|--------|----------|------|-------------|
| **Auth** | POST | /auth/login | No | Login |
| | POST | /auth/register | No | Register (SECURITY RISK) |
| | POST | /auth/logout | Yes | Logout |
| | POST | /auth/refresh | Yes | Refresh token |
| | GET | /auth/me | Yes | Current user |
| | POST | /auth/change-password | Yes | Change password |
| **Dashboard** | GET | /dashboard | Yes | Dashboard stats |
| | GET | /dashboard/sales-chart | Yes | Sales chart data |
| | GET | /dashboard/profit-chart | Yes | Profit chart data |
| **Users** | GET | /users | Yes | List users |
| | POST | /users | Yes | Create user |
| | GET | /users/{id} | Yes | Get user |
| | PUT | /users/{id} | Yes | Update user |
| | DELETE | /users/{id} | Yes | Delete user |
| | PUT | /users/{id}/status | Yes | Toggle active |
| | PUT | /users/{id}/assign-role | Yes | Assign role |
| **Roles** | GET | /roles | Yes | List roles |
| | POST | /roles | Yes | Create role |
| | PUT | /roles/{id} | Yes | Update role |
| | DELETE | /roles/{id} | Yes | Delete role |
| | PUT | /roles/{id}/permissions | Yes | Set permissions |
| **Permissions** | GET | /permissions | Yes | List permissions |
| **Products** | GET | /products | Yes | List products |
| | POST | /products | Yes | Create product |
| | GET | /products/barcode/{barcode} | Yes | Barcode lookup |
| | GET | /products/{id} | Yes | Get product |
| | PUT | /products/{id} | Yes | Update product |
| | DELETE | /products/{id} | Yes | Delete product |
| | GET | /products/{id}/stock-history | Yes | Stock history |
| **Categories** | GET | /categories | Yes | List categories |
| | POST | /categories | Yes | Create category |
| | GET | /categories/tree | Yes | Category tree |
| | PUT | /categories/{id} | Yes | Update category |
| | DELETE | /categories/{id} | Yes | Delete category |
| **Brands** | GET | /brands | Yes | List brands |
| | POST | /brands | Yes | Create brand |
| | PUT | /brands/{id} | Yes | Update brand |
| | DELETE | /brands/{id} | Yes | Delete brand |
| **Units** | GET | /units | Yes | List units |
| | POST | /units | Yes | Create unit |
| | PUT | /units/{id} | Yes | Update unit |
| | DELETE | /units/{id} | Yes | Delete unit |
| **Customers** | GET | /customers | Yes | List customers |
| | POST | /customers | Yes | Create customer |
| | GET | /customers/{id} | Yes | Get customer |
| | PUT | /customers/{id} | Yes | Update customer |
| | DELETE | /customers/{id} | Yes | Delete customer |
| | GET | /customers/{id}/wallet | Yes | Wallet info |
| | POST | /customers/{id}/wallet/topup | Yes | Top up wallet |
| | POST | /customers/{id}/wallet/deduct | Yes | Deduct wallet |
| | GET | /customers/{id}/statement | Yes | Wallet statement |
| **Suppliers** | GET | /suppliers | Yes | List suppliers |
| | POST | /suppliers | Yes | Create supplier |
| | GET | /suppliers/{id} | Yes | Get supplier |
| | PUT | /suppliers/{id} | Yes | Update supplier |
| | DELETE | /suppliers/{id} | Yes | Delete supplier |
| | POST | /suppliers/{id}/payments | Yes | Add payment |
| **Purchases** | GET | /purchases | Yes | List purchases |
| | POST | /purchases | Yes | Create purchase |
| | GET | /purchases/{id} | Yes | Get purchase |
| | PUT | /purchases/{id} | Yes | Update purchase |
| | POST | /purchases/{id}/receive | Yes | Receive purchase |
| | POST | /purchases/{id}/payment | Yes | Add payment |
| **Sales** | GET | /sales | Yes | List sales |
| | POST | /sales | Yes | Create sale |
| | GET | /sales/held | Yes | Held sales |
| | POST | /sales/hold | Yes | Hold sale |
| | GET | /sales/{id} | Yes | Get sale |
| | POST | /sales/{id}/void | Yes | Void sale |
| | POST | /sales/{id}/resume | Yes | Resume held sale |
| | POST | /sales/{id}/return | Yes | Return sale |
| | GET | /sales/{id}/receipt | Yes | Get receipt |
| **Expenses** | GET | /expenses | Yes | List expenses |
| | POST | /expenses | Yes | Create expense |
| | PUT | /expenses/{id} | Yes | Update expense |
| | DELETE | /expenses/{id} | Yes | Delete expense |
| | GET | /expense-categories | Yes | List categories |
| **Inventory** | GET | /inventory | Yes | Stock levels |
| | POST | /inventory/adjust | Yes | Adjust stock |
| | POST | /inventory/transfer | Yes | Transfer stock |
| | GET | /inventory/low-stock | Yes | Low stock items |
| **Warehouses** | GET | /warehouses | Yes | List warehouses |
| | POST | /warehouses | Yes | Create warehouse |
| | PUT | /warehouses/{id} | Yes | Update warehouse |
| | DELETE | /warehouses/{id} | Yes | Delete warehouse |
| **Branches** | GET | /branches | Yes | List branches |
| | POST | /branches | Yes | Create branch |
| | PUT | /branches/{id} | Yes | Update branch |
| | DELETE | /branches/{id} | Yes | Delete branch |
| **Reports** | GET | /reports/daily | Yes | Daily report |
| | GET | /reports/weekly | Yes | Weekly report |
| | GET | /reports/monthly | Yes | Monthly report |
| | GET | /reports/yearly | Yes | Yearly report |
| | GET | /reports/sales | Yes | Sales report |
| | GET | /reports/profit | Yes | Profit report |
| | GET | /reports/inventory | Yes | Inventory report |
| | GET | /reports/expenses | Yes | Expense report |
| **Settings** | GET | /settings/company | Yes | Get company info |
| | PUT | /settings/company | Yes | Update company info |
| **Notifications** | GET | /notifications | Yes | List notifications |
| | PUT | /notifications/read-all | Yes | Mark all read |
| | PUT | /notifications/{id}/read | Yes | Mark read |
| **Activity** | GET | /activity-logs | Yes | List activity logs |

---

## 8. DATABASE SCHEMA

### Tables (35 total)

| # | Table | Records | Purpose |
|---|-------|---------|---------|
| 1 | roles | 8 | User roles (Super Admin → Auditor) |
| 2 | permissions | 55+ | Granular permissions per module |
| 3 | role_permissions | — | Role-permission mapping |
| 4 | branches | 2 | Business locations |
| 5 | warehouses | 2 | Stock locations |
| 6 | users | 2 | System users |
| 7 | categories | 8 | Product categories |
| 8 | brands | 8 | Product brands |
| 9 | units | 12 | Measurement units |
| 10 | products | 8 | Products catalog |
| 11 | product_variants | 0 | Product variants |
| 12 | product_stocks | 8 | Stock per warehouse |
| 13 | stock_movements | 0 | Stock audit trail |
| 14 | customers | 5 | Customer database |
| 15 | customer_wallets | 5 | Customer wallets |
| 16 | customer_wallet_transactions | 0 | Wallet transaction history |
| 17 | suppliers | 3 | Supplier database |
| 18 | purchases | 0 | Purchase orders |
| 19 | purchase_items | 0 | Purchase line items |
| 20 | purchase_payments | 0 | Purchase payments |
| 21 | sales | 0 | Sales transactions |
| 22 | sale_items | 0 | Sale line items |
| 23 | sale_payments | 0 | Sale payments |
| 24 | sale_returns | 0 | Returns |
| 25 | return_items | 0 | Return line items |
| 26 | expense_categories | 8 | Expense categories |
| 27 | expenses | 0 | Expense records |
| 28 | coupons | 0 | Discount coupons |
| 29 | gift_cards | 0 | Gift cards |
| 30 | reward_transactions | 0 | Reward points history |
| 31 | shifts | 0 | Cash register shifts |
| 32 | attendance | 0 | Employee attendance |
| 33 | payroll | 0 | Employee payroll |
| 34 | notifications | 0 | System notifications |
| 35 | activity_logs | 0 | Audit trail |
| 36 | company | 1 | Company settings |
| 37 | settings | 17 | Application settings |
| 38 | backups | 0 | Backup records |

### Seed Data Included
- 8 roles with 55+ permissions
- 2 users (Super Admin + Cashier)
- 2 branches, 2 warehouses
- 8 categories, 8 brands, 12 units
- 8 sample products with stock
- 5 customers with funded wallets
- 3 suppliers
- 8 expense categories
- 17 application settings
- Company profile (SmugFlex Ventures, NGN currency)

---

## 9. DEVELOPMENT ROADMAP

### Phase 1: Critical Fixes (Week 1) 🔴

| # | Task | Effort | Impact |
|---|------|--------|--------|
| 1.1 | Fix all frontend-backend field name mismatches (Dashboard, Products, POS, Sales, Customers, Reports) | 2 days | Unblocks all pages |
| 1.2 | Add category/brand/unit dropdowns to ProductCreate and ProductEdit | 1 day | Unblocks product creation |
| 1.3 | Fix Customer model alignment (first_name/last_name vs name) — pick one approach | 0.5 day | Unblocks customer CRUD |
| 1.4 | Fix POS sale payload field names (unit_price→price, discount_amount→discount, paid_amount→amount_paid) | 0.5 day | Unblocks POS sales |
| 1.5 | Remove or protect `/auth/register` endpoint | 0.5 day | Security |
| 1.6 | Move JWT_SECRET and DB credentials to environment variables | 0.5 day | Security |
| 1.7 | Fix SuppliersController `purchase_status` column error | 0.5 day | Runtime error |
| 1.8 | Fix PurchasesController NOT NULL FK violations (supplier_id, branch_id) | 0.5 day | Runtime error |
| 1.9 | Add `/returns` backend endpoint or update frontend to use correct endpoint | 1 day | Unblocks returns |

**Total Phase 1: ~7 days**

### Phase 2: Missing Pages & Features (Week 2-3) 🟡

| # | Task | Effort | Impact |
|---|------|--------|--------|
| 2.1 | Create SupplierEdit.jsx + route | 0.5 day | Fixes broken edit link |
| 2.2 | Create UserEdit.jsx + route | 0.5 day | Fixes broken edit link |
| 2.3 | Create PurchaseDetail page with receive + payment buttons | 2 days | Unblocks purchase workflow |
| 2.4 | Add expense category dropdown (fetch from /expense-categories) | 0.5 day | Fixes expense creation |
| 2.5 | Add expense edit modal | 0.5 day | Completes expense CRUD |
| 2.6 | Add returns create/initiate UI on SaleView page | 1 day | Unblocks returns |
| 2.7 | Add category/brand/unit select to Expenses form | 0.5 day | Better UX |
| 2.8 | Fix currency formatting across all pages (use formatCurrency) | 1 day | Consistency |
| 2.9 | Add error state UI to all pages missing it | 1 day | Better UX |
| 2.10 | Add pagination UI to Products page | 0.5 day | Usability |
| 2.11 | Add pagination UI to all list pages | 1 day | Usability |
| 2.12 | Fix Products Export button or remove it | 0.5 day | Cleanup |
| 2.13 | Fix AuthContext to use api instance instead of raw axios | 0.5 day | Reliability |
| 2.14 | Add profile editing (name, email, phone) | 1 day | Completeness |

**Total Phase 2: ~11 days**

### Phase 3: POS & Sales Enhancements (Week 3-4) 🟡

| # | Task | Effort | Impact |
|---|------|--------|--------|
| 3.1 | Add barcode scanning input to POS | 1 day | Core POS feature |
| 3.2 | Persist hold sales to backend (POST /sales/hold, GET /sales/held) | 1 day | Prevents data loss |
| 3.3 | Add warehouse/branch selector to POS (from user profile) | 0.5 day | Multi-branch support |
| 3.4 | Add stock limit enforcement on POS (warn when quantity > stock) | 1 day | Prevents overselling |
| 3.5 | Make tax rate configurable from settings (not hardcoded 7.5%) | 0.5 day | Accuracy |
| 3.6 | Add receipt printing modal | 1.5 days | Core POS feature |
| 3.7 | Add sale return initiation from SaleView page | 1 day | Completes return workflow |
| 3.8 | Add wallet balance check before sale | 0.5 day | Prevents negative wallet |

**Total Phase 3: ~7 days**

### Phase 4: Inventory & Reports (Week 4-5) 🟢

| # | Task | Effort | Impact |
|---|------|--------|--------|
| 4.1 | Add inventory adjust UI | 1 day | Stock management |
| 4.2 | Add inventory transfer UI (warehouse to warehouse) | 1.5 days | Multi-warehouse |
| 4.3 | Add low stock alerts / notifications | 1 day | Proactive management |
| 4.4 | Add date range selectors for weekly/monthly/yearly reports | 1 day | Report usability |
| 4.5 | Add profit/COGS report view | 1 day | Financial visibility |
| 4.6 | Add inventory report view | 1 day | Stock visibility |
| 4.7 | Add expense report view | 1 day | Financial visibility |
| 4.8 | Add chart visualizations (recharts or chart.js) | 2 days | Dashboard/Reports UX |
| 4.9 | Add export functionality (CSV/PDF) | 1.5 days | Reporting |
| 4.10 | Fix Dashboard hardcoded percentages (compute from data) | 0.5 day | Accuracy |

**Total Phase 4: ~11.5 days**

### Phase 5: Security & Performance (Week 5-6) 🟢

| # | Task | Effort | Impact |
|---|------|--------|--------|
| 5.1 | Add API rate limiting (login, sensitive endpoints) | 1 day | Security |
| 5.2 | Add CSRF protection | 0.5 day | Security |
| 5.3 | Implement proper permission checks in controllers | 2 days | Security |
| 5.4 | Add server-side token revocation (blacklist) | 1 day | Security |
| 5.5 | Generate separate refresh tokens (not same as access) | 1 day | Security |
| 5.6 | Add input sanitization audit | 1 day | Security |
| 5.7 | Add database transaction wrappers for critical operations | 1 day | Data integrity |
| 5.8 | Add API response caching (dashboard, reports) | 1 day | Performance |
| 5.9 | Add lazy loading for images and heavy components | 0.5 day | Performance |
| 5.10 | Add error logging / monitoring | 1 day | Operations |

**Total Phase 5: ~9 days**

### Phase 6: Advanced Features (Week 6-8) 🔵

| # | Task | Effort | Impact |
|---|------|--------|--------|
| 6.1 | Product variants management UI | 2 days | Advanced catalog |
| 6.2 | Coupon management CRUD | 1 day | Promotions |
| 6.3 | Gift card management CRUD | 1 day | Sales channel |
| 6.4 | Reward points management | 1 day | Customer loyalty |
| 6.5 | Cash register shift management | 2 days | POS operations |
| 6.6 | Employee attendance tracking | 2 days | HR |
| 6.7 | Payroll management | 2 days | HR |
| 6.8 | Database backup/restore from UI | 1 day | Operations |
| 6.9 | Email notifications (low stock, daily summary) | 2 days | Automation |
| 6.10 | Multi-language support (i18n) | 3 days | Localization |
| 6.11 | Dark/Light theme toggle improvements | 1 day | UX |
| 6.12 | Print receipt via thermal printer | 2 days | POS operations |

**Total Phase 6: ~19 days**

### Phase 7: Testing & Deployment (Week 8-10) 🔵

| # | Task | Effort | Impact |
|---|------|--------|--------|
| 7.1 | Write unit tests for backend controllers | 3 days | Quality |
| 7.2 | Write integration tests for critical workflows | 3 days | Quality |
| 7.3 | Write frontend component tests | 2 days | Quality |
| 7.4 | Load testing / stress testing | 1 day | Performance |
| 7.5 | Security penetration testing | 1 day | Security |
| 7.6 | Cross-browser testing (Chrome, Firefox, Safari, Edge) | 1 day | Compatibility |
| 7.7 | Mobile device testing (iOS Safari, Android Chrome) | 1 day | Compatibility |
| 7.8 | Production environment setup & hardening | 1 day | Deployment |
| 7.9 | CI/CD pipeline setup | 1 day | DevOps |
| 7.10 | Documentation (API docs, user guide, admin guide) | 2 days | Documentation |

**Total Phase 7: ~16 days**

---

### Roadmap Summary

| Phase | Focus | Duration | Status |
|-------|-------|----------|--------|
| Phase 1 | Critical Fixes | 1 week | 🔴 Must do first |
| Phase 2 | Missing Pages & Features | 2 weeks | 🟡 Core completeness |
| Phase 3 | POS & Sales Enhancements | 1 week | 🟡 Core POS features |
| Phase 4 | Inventory & Reports | 1 week | 🟢 Advanced features |
| Phase 5 | Security & Performance | 1 week | 🟢 Production readiness |
| Phase 6 | Advanced Features | 2 weeks | 🔵 Nice-to-have |
| Phase 7 | Testing & Deployment | 2 weeks | 🔵 Launch preparation |
| **Total** | | **~10 weeks** | |

---

## 10. DEPLOYMENT CHECKLIST

### Pre-Deployment
- [ ] All Phase 1 critical fixes completed
- [ ] Database schema imported to cPanel MySQL
- [ ] Environment variables configured (JWT_SECRET, DB credentials)
- [ ] CORS configured for production domain
- [ ] `.htaccess` working for URL rewriting

### Backend (cPanel)
- [ ] Upload `backend-deploy/` contents to `/home/mdpjhtua/smug.com/`
- [ ] Verify `index.php` is at root (not in subdirectory)
- [ ] Test `GET /api/v1/auth/login` returns 405 (wrong method)
- [ ] Test `POST /api/v1/auth/login` with credentials
- [ ] Test `GET /api/v1/dashboard` with valid token
- [ ] Verify all 103 routes respond correctly

### Frontend (Vercel)
- [ ] Push to `main` branch triggers auto-deploy
- [ ] Verify `vercel.json` is in `frontend/` directory
- [ ] Verify `.env` has correct `VITE_API_URL`
- [ ] Test login flow end-to-end
- [ ] Test all major workflows (Products, Sales, POS, etc.)
- [ ] Verify mobile responsiveness

### Post-Deployment
- [ ] Change default admin password
- [ ] Remove hardcoded credentials from source
- [ ] Set up database backup cron job
- [ ] Monitor error logs for first 48 hours
- [ ] Test with multiple user roles

---

*Document generated by automated end-to-end audit. For questions, refer to the codebase or contact the development team.*
