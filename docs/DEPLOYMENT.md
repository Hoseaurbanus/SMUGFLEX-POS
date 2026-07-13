# SmugFlex POS - Deployment Guide

## Frontend Deployment

### Vercel
1. Push to GitHub
2. Import project on Vercel
3. Set root directory to `frontend`
4. Build command: `npm run build`
5. Output directory: `dist`
6. Add environment variable: `VITE_API_URL`

### Netlify
1. Connect GitHub repo
2. Base directory: `frontend`
3. Build command: `npm run build`
4. Publish directory: `dist`
5. Add `_redirects` file: `/* /index.html 200`

### Apache/Nginx
```bash
cd frontend && npm run build
cp -r dist/* /var/www/html/
```

## Backend Deployment

### cPanel (Shared Hosting)
1. Upload all backend files via File Manager
2. Move `public/` contents to `public_html/api/`
3. Move `app/`, `vendor/`, `writable/` one level above `public_html`
4. Configure `.env`:
   ```
   CI_ENVIRONMENT = production
   app.baseURL = 'https://yourdomain.com/api/v1'
   database.default.hostname = localhost
   database.default.database = your_db_name
   database.default.username = your_db_user
   database.default.password = your_db_password
   JWT_SECRET = your-strong-random-secret
   ```
5. Import `database/database.sql` via phpMyAdmin
6. Run `composer install --no-dev` via SSH
7. Set folder permissions: `writable/` → 755

### VPS (Ubuntu/Nginx)
```bash
# Install PHP 8.2+
sudo apt install php8.2-fpm php8.2-mysql

# Configure Nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /var/www/smugflex-api/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realroot$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Environment Variables

### Backend (.env)
```
CI_ENVIRONMENT = production
app.baseURL = https://yourdomain.com/api/v1
database.default.hostname = localhost
database.default.database = smugflex_pos
database.default.username = db_user
database.default.password = db_password
JWT_SECRET = your-256-bit-secret-key-here
JWT_EXPIRATION = 86400
JWT_REFRESH_EXPIRATION = 604800
```

### Frontend (.env)
```
VITE_API_URL=https://yourdomain.com/api/v1
VITE_APP_NAME=SmugFlex POS
```

## SSL Configuration
Always use HTTPS in production. Configure SSL via:
- Let's Encrypt (free)
- Cloudflare (free CDN + SSL)
- Your hosting provider's SSL

## Backup
Use the built-in backup feature:
1. Login as Admin
2. Go to Settings → Backup & Restore
3. Click "Create Backup"
4. Download backup file
