# SmugFlex POS — Deployment Guide

## Prerequisites

- **Frontend:** Node.js 18+, npm
- **Backend:** PHP 8.2+, MySQL 8.0
- **Hosting:** Vercel (frontend), cPanel shared hosting (backend)

---

## Frontend Deployment (Vercel)

### Option 1: Auto-deploy from GitHub

1. Push code to GitHub repository
2. Go to [vercel.com](https://vercel.com) → New Project
3. Import the GitHub repository
4. Configure:
   - **Root Directory:** `frontend`
   - **Build Command:** `npm run build`
   - **Output Directory:** `dist`
5. Add environment variable:
   - `VITE_API_URL` = `https://yourdomain.com/api/v1`
6. Deploy

Subsequent pushes to `main` branch auto-deploy.

### Option 2: Manual deploy

```bash
cd frontend
npm install
npm run build
# Upload dist/ folder to your hosting
```

### SPA Routing

The `frontend/vercel.json` file handles client-side routing:
```json
{
  "rewrites": [
    { "source": "/(.*)", "destination": "/index.html" }
  ]
}
```

This ensures all routes (e.g., `/pos`, `/products/create`) work on refresh.

---

## Backend Deployment (cPanel)

### Step 1: Prepare the Backend

The backend is standalone PHP — no Composer, no framework dependencies. The custom autoloader (`vendor/autoload_local.php`) handles class loading.

### Step 2: Upload Files

Upload the entire `backend-deploy/` directory contents to your cPanel document root:

```
/home/mdpjhtua/smug.com/
├── index.php            ← Entry point
├── .htaccess            ← URL rewriting
├── config/
│   ├── app.php          ← JWT secret, constants
│   └── database.php     ← DB credentials
├── core/
│   ├── Database.php
│   ├── Router.php
│   ├── JWT.php
│   ├── AuthMiddleware.php
│   ├── Request.php
│   ├── Response.php
│   └── Helpers.php
├── vendor/
│   └── autoload_local.php
├── app/
│   └── Controllers/     ← 22 controllers
└── routes/
    └── api.php          ← 103+ routes
```

**Important:** `index.php` and `.htaccess` must be at the document root, not in a subdirectory.

### Step 3: Configure Database

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mdpjhtua_POS');
define('DB_USER', 'mdpjhtua_user');
define('DB_PASS', 'your_password');
```

### Step 4: Configure JWT

Edit `config/app.php`:

```php
define('JWT_SECRET', 'your-256-bit-secret-key');
define('JWT_EXPIRATION', 86400);        // 24 hours
define('JWT_REFRESH_EXPIRATION', 604800); // 7 days
```

### Step 5: Import Database

1. Open **phpMyAdmin** in cPanel
2. Select your database (`mdpjhtua_POS`)
3. Click **Import** tab
4. Choose `database/database.sql` from the repository
5. Click **Go**

This creates all 35+ tables with seed data, triggers, and foreign keys.

### Step 6: Verify

Test these endpoints:

```bash
# Health check (should return 405 - wrong method)
curl https://yourdomain.com/api/v1/auth/login

# Login
curl -X POST https://yourdomain.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@smugflex.com","password":"password"}'

# Dashboard (with token from login)
curl https://yourdomain.com/api/v1/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

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
define('JWT_SECRET', 'your-secret-key');
define('JWT_EXPIRATION', 86400);
define('APP_DEBUG', false);  // Set false in production
```

---

## SSL Configuration

Always use HTTPS in production. Options:
- **Let's Encrypt** — Free SSL via cPanel
- **Cloudflare** — Free CDN + SSL
- **Hosting provider SSL** — Check your cPanel

---

## Backup Strategy

### Database Backup
1. phpMyAdmin → Export → Custom → Quick download as SQL
2. Or use cPanel Backup Wizard

### File Backup
1. Download the `backend-deploy/` directory via cPanel File Manager
2. Download the `frontend/` directory from GitHub

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| 404 on all API routes | Check `.htaccess` is present and `mod_rewrite` is enabled |
| CORS errors | Verify your domain is in the CORS allowlist in `index.php` |
| Database connection failed | Check `config/database.php` credentials |
| JWT errors | Ensure `JWT_SECRET` is set in `config/app.php` |
| Blank page | Check PHP error log in cPanel |
| Login fails | Verify database was imported correctly and seed users exist |

---

*Last updated: July 14, 2026*
