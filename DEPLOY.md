# Deployment Guide — Mombasa Hamlets FC

This guide covers the steps needed to deploy the Mombasa Hamlets FC website to a production server.

---

## Prerequisites

- PHP >= 8.0 with extensions: `mysqli`, `gd`, `mbstring`, `openssl`
- Apache with `mod_rewrite`, `mod_headers`, `mod_expires` enabled
- MySQL / MariaDB database
- FFmpeg (for image optimization, optional if assets already optimized)
- Git

---

## 1. Environment Setup

### Clone the repository (on the `deploy-prep` branch)

```bash
git clone -b deploy-prep https://github.com/your-org/mombasahamlets_web.git /var/www/mombasahamlets_web
cd /var/www/mombasahamlets_web
```

### Configure environment variables

Copy the example `.env` file and edit it:

```bash
cp backend/.env.example backend/.env
```

Edit `backend/.env`:

```dotenv
# Application Environment
# Set to 'production' to disable error display and enable file logging
APP_ENV=production

# Base URL — set explicitly for production (no trailing slash)
BASE_URL=https://mombasahamletsfc.co.ke

# Database Configuration
DB_HOST=localhost
DB_USER=your_db_user
DB_PASS=your_db_password
DB_NAME=mombasahamlets_db
DB_PORT=3306

# Other settings...
```

### Import the database

```bash
mysql -u your_db_user -p your_db_name < backend/db/mombasahamlets_schema.sql
```

If migrating from a local version, check for migration scripts in `backend/db/migrations/`.

---

## 2. Asset Preparation (Run Locally Before Deployment)

### Optimize Images

Images should be in WebP format with a max width/height of 1920px.

```bash
php backend/scripts/optimize_images.php
```

This script:
- Converts all images to WebP format
- Resizes images larger than 1920px
- Removes duplicate hash-named files

### Minify CSS & JS

```bash
php backend/scripts/minify_assets.php
```

This generates:
- `frontend/dist/css/*.min.css` — minified CSS files
- `frontend/dist/js/*.min.js` — minified JS files
- `frontend/dist/manifest.json` — asset manifest

---

## 3. File Permissions (on server)

```bash
cd /var/www/mombasahamlets_web

# Make storage/logs directory writable
mkdir -p backend/logs
chmod 755 backend/logs
chown www-data:www-data backend/logs

# Ensure session storage is writable
mkdir -p backend/storage
chmod 755 backend/storage
chown www-data:www-data backend/storage
```

---

## 4. Web Server Configuration (Apache)

The `.htaccess` files are already included. Ensure they are loaded by Apache and `AllowOverride All` is set in your virtual host config.

### Virtual Host Example

```apache
<VirtualHost *:80>
    ServerName mombasahamletsfc.co.ke
    ServerAlias www.mombasahamletsfc.co.ke
    DocumentRoot /var/www/mombasahamlets_web

    <Directory /var/www/mombasahamlets_web>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Force HTTPS (add your SSL config here or use certbot)
</VirtualHost>
```

### SSL / HTTPS

Obtain an SSL certificate using Let's Encrypt:

```bash
sudo certbot --apache -d mombasahamletsfc.co.ke -d www.mombasahamletsfc.co.ke
```

---

## 5. Verify Production Settings

### Error Display

Confirm that `APP_ENV` is set to `production` in `backend/.env`. This ensures:
- PHP errors are not displayed to users
- Errors are logged to `backend/logs/php_errors.log`
- Frontend error reporting is also suppressed

### Caching Headers

The `.htaccess` files configure:
- **Static assets** (CSS, JS, images): 1-year cache with versioning
- **HTML/PHP** files: no-cache headers
- **API endpoints**: no-cache headers

### WebP Fallback

The root `.htaccess` uses `mod_rewrite` to serve `.webp` versions of images when the browser supports WebP. Ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite headers expires deflate
sudo systemctl restart apache2
```

---

## 6. Cron Jobs (Optional)

Set up a cron job to clean expired sessions:

```bash
# Add to crontab (crontab -e)
0 2 * * * find /var/www/mombasahamlets_web/backend/storage -name "sess_*" -mmin +1440 -delete
```

---

## 7. Deployment Checklist

| Task | Status |
|------|--------|
| ✅ Branch: `deploy-prep` checked out | |
| ✅ `backend/.env` configured with `APP_ENV=production` | |
| ✅ `BASE_URL` set in `.env` | |
| ✅ Database imported / migrated | |
| ✅ Images optimized (WebP, max 1920px) | |
| ✅ CSS/JS minified (run `backend/scripts/minify_assets.php`) | |
| ✅ `frontend/dist/` exists with minified assets | |
| ✅ `backend/logs/` writable by web server | |
| ✅ `.htaccess` files in place (root, backend/, frontend/) | |
| ✅ Apache `AllowOverride All` enabled | |
| ✅ SSL certificate installed | |
| ✅ All PHP syntax checks pass (`php -l *.php`) | |
| ✅ Favicons present (`frontend/images/favicon.*`) | |
| ✅ `manifest.json` present (PWA support) | |
| ✅ `sitemap.xml` present at site root | |
| ✅ `robots.txt` present at site root | |

---

## 8. Post-Deployment Verification

1. Visit `https://mombasahamletsfc.co.ke` — should load with proper styling
2. Check browser DevTools → Network → assets should load from `frontend/dist/` in production
3. Verify meta tags: view page source, confirm `<meta name="description">`, `<og:>` tags present
4. Test favicons: visit `https://mombasahamletsfc.co.ke/frontend/images/favicon.ico`
5. Test `robots.txt`: visit `https://mombasahamletsfc.co.ke/robots.txt`
6. Test `sitemap.xml`: visit `https://mombasahamletsfc.co.ke/sitemap.xml`
7. Verify WebP: images should load as WebP in Chrome/Firefox
8. Check error logging: trigger an error, verify it appears in `backend/logs/php_errors.log` (not displayed to user)
9. Test API endpoints: ensure CORS/AJAX calls work with `BASE_URL`

---

## 9. Rollback Procedure

If deployment fails:

```bash
git checkout main  # or previous working branch
php backend/scripts/minify_assets.php  # regenerate if needed
# Restore previous .env if overwritten
```

---

## File Structure Overview

```
mombasahamlets_web/
├── .htaccess                     # Root: GZIP, security headers, WebP rewrite
├── robots.txt                    # Search engine instructions
├── sitemap.xml                   # Auto-generated sitemap
├── frontend/
│   ├── .htaccess                 # Frontend caching + WebP
│   ├── includes/
│   │   ├── meta_head.php         # Shared <head> with OG/meta tags
│   │   └── footer.php            # Shared footer
│   ├── config/
│   │   ├── asset_helper.php      # asset_url(), asset_css(), asset_js()
│   │   └── error_settings.php    # Error reporting config
│   ├── dist/                     # Minified assets (generated)
│   │   ├── css/
│   │   │   ├── style.min.css
│   │   │   ├── dashboard.min.css
│   │   │   └── ...
│   │   ├── js/
│   │   │   ├── main.min.js
│   │   │   └── ...
│   │   └── manifest.json
│   ├── images/                   # Optimized WebP images
│   │   ├── favicon.svg
│   │   ├── favicon.ico
│   │   ├── favicon.png
│   │   ├── apple-touch-icon.png
│   │   └── ...
│   ├── *.php                     # All frontend pages (use meta_head.php)
├── backend/
│   ├── .htaccess                 # API no-cache, .env protection
│   ├── .env                      # Environment (gitignored)
│   ├── config/
│   │   └── base_url.php          # get_base_url(), get_api_url()
│   ├── db.php                    # Database + env-based error reporting
│   ├── logs/                     # PHP error logs (writable)
│   ├── scripts/
│   │   ├── optimize_images.php   # Image optimization
│   │   └── minify_assets.php     # CSS/JS minification
│   └── api/                      # REST API endpoints
│       ├── matches.php
│     ├── products.php
│     └── ...
├── backend/scripts/              # Build/deploy scripts
└── css/
    └── design-system.css         # Root CSS (color variables, design tokens)
```

---

## Production Environment Variables

| Variable | Production Value | Description |
|----------|-----------------|-------------|
| `APP_ENV` | `production` | Disables error display, logs to file |
| `BASE_URL` | `https://mombasahamletsfc.co.ke` | Site root URL (auto-detected if not set) |
| `DB_HOST` | (your host) | Database server |
| `DB_USER` | (your user) | Database username |
| `DB_PASS` | (your pass) | Database password |
| `DB_NAME` | (your db) | Database name |
| `DB_PORT` | `3306` | Database port |

---

## Troubleshooting

### CSS not loading after deploy
- Ensure `frontend/dist/css/style.min.css` exists (run `php backend/scripts/minify_assets.php`)
- Check that `APP_ENV` is set correctly in `.env`
- Verify Apache `mod_rewrite` is enabled

### Images not showing as WebP
- Check that `mod_rewrite` is enabled
- Verify `.htaccess` rewrite rules in root and frontend directories

### API calls failing in JS
- Ensure `window.BASE_URL` is set (PHP injects it via `base_url.php`)
- Verify `BASE_URL` in `.env` matches your domain

### Errors not logging
- Check that `backend/logs/` directory exists and is writable
- Verify `APP_ENV=production` in `.env`
- Check `php_errorlog` path in `.htaccess`

---

## Quick Deploy Commands (One-Liner)

After uploading files to the server:

```bash
# Run from project root
php backend/scripts/minify_assets.php && php backend/scripts/optimize_images.php && php generate_sitemap.php && echo "Deploy prep complete!"
```

Then verify:

```bash
php -l frontend/*.php && echo "All PHP files syntax OK"
```
