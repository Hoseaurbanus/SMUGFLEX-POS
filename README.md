# SmugFlex POS

**Enterprise Point of Sale System**  
Version: 1.0.0 | Company: SmugFlex Ventures

---

## Overview

SmugFlex POS is a complete enterprise-grade Point of Sale system built with:

- **Frontend**: React 19 + Vite + Bootstrap 5.3 + Dark Theme
- **Backend**: CodeIgniter 4 REST API + PHP 8.2+
- **Database**: MySQL 8.0+
- **Auth**: JWT (firebase/php-jwt)

## Quick Start

### Backend

```bash
cd backend
composer install
cp .env.example .env   # Configure database credentials
# Import database/database.sql into MySQL
php spark serve --port 8080
```

### Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

**Default Login:**
- Email: `admin@smugflex.com`
- Password: `password`

## Features

- Real-time Dashboard with charts
- Full POS Terminal with barcode support
- Product Management (CRUD, variants, images)
- Customer & Supplier Management
- Purchase Orders & Goods Received
- Sales & Returns Processing
- Inventory Management with stock transfers
- Multi-branch & Multi-warehouse
- Expense Tracking
- Reports (Sales, Profit, Tax, Cash Flow)
- User Management with Role-Based Access Control
- Activity Logs & Notifications
- Company Settings & Backup
- Dark/Light Theme Toggle
- PWA Support

## Project Structure

```
SmugFlex-POS/
├── backend/              # CodeIgniter 4 REST API
│   ├── app/
│   │   ├── Controllers/  # API Controllers
│   │   ├── Models/       # Database Models
│   │   ├── Services/     # Business Logic
│   │   ├── Filters/      # JWT, CORS, Rate Limit
│   │   ├── Helpers/      # JWT, Auth, Response helpers
│   │   └── Config/       # Routes, Database, CORS
│   ├── database/         # SQL files
│   └── public/           # Entry point
├── frontend/             # React SPA
│   ├── src/
│   │   ├── components/   # Reusable components
│   │   ├── pages/        # Page components
│   │   ├── contexts/     # React Context
│   │   ├── hooks/        # Custom hooks
│   │   ├── services/     # API services
│   │   ├── styles/       # CSS themes
│   │   └── utils/        # Helpers
│   └── public/
├── docs/                 # Documentation
└── README.md
```

## API Endpoints

| Module | Endpoints |
|--------|-----------|
| Auth | login, logout, register, refresh, me |
| Users | CRUD, status, role assignment |
| Products | CRUD, search, barcode, variants |
| Categories | CRUD, tree |
| Customers | CRUD, wallet, rewards, statements |
| Suppliers | CRUD, statements, payments |
| Purchases | CRUD, receive, return, payments |
| Sales | create, list, void, hold, resume, return |
| Inventory | list, adjust, transfer, alerts |
| Reports | daily, weekly, monthly, yearly |
| Settings | company, backup, restore |

## Deployment

### Frontend (Vercel/Netlify)
```bash
cd frontend
npm run build
# Deploy dist/ folder
```

### Backend (cPanel)
1. Upload backend files to `public_html/api/`
2. Point document root to `public/`
3. Configure `.env` with production credentials
4. Run `composer install --no-dev`

## License

Proprietary - SmugFlex Ventures
