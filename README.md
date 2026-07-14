# SmugFlex POS

**Enterprise Point of Sale System**  
Version: 1.0.0 | Company: SmugFlex Ventures

---

## Overview

SmugFlex POS is a complete enterprise-grade Point of Sale system built with:

- **Frontend**: React 19 + Vite + Bootstrap 5.3 + Dark Glassmorphism Theme
- **Backend**: Standalone PHP 8.2 REST API (no Composer required)
- **Database**: MySQL 8.0 (35 tables, triggers, foreign keys, seed data)
- **Auth**: JWT (HS256, standalone implementation)
- **Deployment**: Vercel (frontend) + cPanel shared hosting (backend)

## Quick Start

### Frontend (Development)

```bash
cd frontend
npm install
cp .env.example .env   # Set VITE_API_URL
npm run dev
```

### Backend (Development)

The backend is a standalone PHP REST API — no Composer, no framework. Upload files directly to any PHP 8.2+ hosting.

```bash
cd backend-deploy
# Upload contents to your web server's document root
# Import database/database.sql into MySQL
# Edit config/database.php with your credentials
```

**Default Login:**
- Email: `admin@smugflex.com`
- Password: `password`

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | React 19, Vite 6, React Router 7, Axios |
| UI | Bootstrap 5.3, Bootstrap Icons, Custom dark theme |
| State | React Context (Auth, Theme, Notifications) |
| Backend | Standalone PHP 8.2, custom regex router |
| Auth | JWT HS256 (no external libraries) |
| Database | MySQL 8.0, PDO |
| Hosting | Vercel (frontend), cPanel (backend) |

## Features

### Core POS
- Real-time dashboard with sales, profit, and expense stats
- Full POS terminal with product search, cart, and multiple payment methods
- Barcode support and product lookup
- Held sales (temporary cart save)
- Receipt generation
- Tax rate configurable from company settings

### Product Management
- Full CRUD with category, brand, and unit dropdowns
- SKU and barcode management
- Stock tracking per warehouse
- Stock history and audit trail
- Low stock alerts

### Sales & Returns
- Sale creation with items, discounts, tax, and payments
- Multiple payment methods (cash, card, transfer, wallet)
- Sale returns with item-level tracking
- Invoice number generation
- Sale void capability

### Purchasing
- Purchase order creation with supplier and warehouse selection
- Purchase receive workflow (stock increment on receive)
- Purchase payments tracking
- Reference number generation

### Customer Management
- Customer CRUD with contact info
- Customer wallet system (top-up, deduct, balance check)
- Wallet transaction history
- Customer statements

### Supplier Management
- Supplier CRUD with contact details
- Supplier payment tracking
- Supplier statements

### Inventory
- Stock levels across warehouses
- Stock adjustment with reason tracking
- Stock transfers between warehouses
- Low stock alerts

### Expenses
- Expense CRUD with category dropdown
- Expense categories management
- Payment method tracking

### Multi-Branch / Multi-Warehouse
- Branch management
- Warehouse management
- Stock per warehouse tracking

### Reports
- Daily, weekly, monthly, yearly reports
- Sales reports
- Profit reports
- Inventory reports
- Expense reports

### User Management
- User CRUD with role assignment
- Role-based access control (8 roles, 55+ permissions)
- Branch assignment per user
- User status toggle (active/inactive)

### System
- Activity log (audit trail)
- Notifications with read/unread status
- Company settings (name, address, currency, tax rate)
- Dark/Light theme toggle
- Mobile responsive design (all pages)
- Profile management with password change

## Project Structure

```
SmugFlex-POS/
├── backend-deploy/              # Standalone PHP REST API (no Composer)
│   ├── index.php                # Entry point + CORS headers
│   ├── .htaccess                # URL rewriting
│   ├── config/
│   │   ├── app.php              # JWT secret, app constants
│   │   └── database.php         # PDO credentials
│   ├── core/
│   │   ├── Database.php         # PDO singleton
│   │   ├── Router.php           # Regex-based router
│   │   ├── JWT.php              # HS256 implementation
│   │   ├── AuthMiddleware.php   # Token validation + permission checks
│   │   ├── Request.php          # Request helper
│   │   ├── Response.php         # JSON response helper
│   │   └── Helpers.php          # Utility functions
│   ├── vendor/
│   │   └── autoload_local.php   # Custom PSR-4 autoloader
│   ├── app/Controllers/         # 22 controllers
│   └── routes/
│       └── api.php              # 103+ routes
├── frontend/                    # React SPA
│   ├── src/
│   │   ├── components/
│   │   │   ├── layout/          # Sidebar.jsx, TopBar.jsx
│   │   │   └── common/          # LoadingSpinner, etc.
│   │   ├── pages/               # 25+ page components
│   │   ├── contexts/            # AuthContext, ThemeContext, NotificationContext
│   │   ├── hooks/               # useAuth
│   │   ├── layouts/             # DashboardLayout, AuthLayout
│   │   ├── services/            # api.js (Axios + token refresh)
│   │   ├── styles/              # theme.css, globals.css
│   │   └── utils/               # formatters.js
│   ├── vercel.json              # SPA rewrite rules
│   └── .env                     # VITE_API_URL
├── database/
│   └── database.sql             # Full schema + seed data + triggers
├── docs/
│   └── DEPLOYMENT.md
├── WORKFLOW_MANUAL.md
└── README.md
```

## API Endpoints (103+)

| Module | Endpoints |
|--------|-----------|
| Auth | login, logout, refresh, me, change-password |
| Dashboard | stats, sales-chart, profit-chart |
| Users | CRUD, status toggle, role assignment |
| Roles | CRUD, permissions |
| Permissions | list |
| Products | CRUD, barcode lookup, stock history |
| Categories | CRUD, tree |
| Brands | CRUD |
| Units | CRUD |
| Customers | CRUD, wallet (topup/deduct), statement |
| Suppliers | CRUD, payments |
| Purchases | CRUD, receive, payment |
| Sales | create, list, held, hold, void, resume, return, receipt |
| Returns | list |
| Expenses | CRUD, categories |
| Inventory | list, adjust, transfer, low-stock |
| Warehouses | CRUD |
| Branches | CRUD |
| Reports | daily, weekly, monthly, yearly, sales, profit, inventory, expenses |
| Settings | company info |
| Notifications | list, mark-read, mark-all-read |
| Activity Logs | list |

## Database

35 tables with:
- Foreign key constraints
- Stock update triggers
- Audit trail (activity_logs)
- Seed data: 8 roles, 55+ permissions, 2 users, 8 products, 5 customers, 3 suppliers, 8 expense categories, 17 settings

## Deployment

### Frontend (Vercel)
1. Push to GitHub
2. Import project on Vercel, set root to `frontend/`
3. Build: `npm run build`, Output: `dist/`
4. `vercel.json` handles SPA routing
5. Set env: `VITE_API_URL=https://yourdomain.com/api/v1`

### Backend (cPanel)
1. Upload `backend-deploy/` contents to document root
2. Ensure `index.php` and `.htaccess` are at root
3. Edit `config/database.php` with production credentials
4. Import `database/database.sql` via phpMyAdmin
5. No Composer needed — autoloader is included

## Environment Variables

### Frontend (`.env`)
```
VITE_API_URL=https://yourdomain.com/api/v1
```

### Backend (`config/database.php`)
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
```

### Backend (`config/app.php`)
```php
define('JWT_SECRET', 'your-256-bit-secret');
define('JWT_EXPIRATION', 86400);
```

## License

Proprietary - SmugFlex Ventures
