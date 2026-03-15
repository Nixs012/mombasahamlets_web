# 🏟 Mombasa Hamlets - Deployment Guide

This guide is for successfully deploying and running the Mombasa Hamlets website on a live server.

## 1. Database Setup
1. Create a new MySQL database on your server (e.g., `mombasa_hamlets_live`).
2. Open **phpMyAdmin** or your preferred database tool.
3. Import the `mombasa_hamlets_setup.sql` file (found in the repository root).

## 2. File Upload & Configuration
1. Upload all repository files to your web root (usually `public_html`).
2. **CRITICAL**: The following files are ignored by Git for security and must be created/uploaded manually from the local development environment:
   - `backend/config/app_config.php`
   - `backend/config/paystack_config.php`
   - `backend/db.php`

### Configuration Content Example:
**backend/db.php**:
```php
<?php
$host = 'localhost';
$db_name = 'your_database_name';
$username = 'your_username';
$password = 'your_password';
$conn = new mysqli($host, $username, $password, $db_name);
?>
```

**backend/config/app_config.php**:
```php
<?php
define('APP_ENV', 'production'); // Set to production
define('JWT_SECRET', 'YOUR_STRONG_RANDOM_SECRET');
?>
```

## 3. Handling Large Media (1.5 GB)
The repository contains the code and essential files. However, the `frontend/images` folder (approx. 1.5 GB) should be uploaded separately to the server for the fastest results:
1. ZIP the `frontend/images` folder on the local machine.
2. Upload and extract it to the `frontend/` directory on the live server.

## 4. Paystack Integration
1. Go to your Paystack Dashboard.
2. Obtain your **Live Secret Key** and **Live Public Key**.
3. Update `backend/config/paystack_config.php` with these live keys.

## 5. Final Verification
1. Ensure your server has **HTTPS** enabled (SSL/TLS).
2. Visit `index.php` and test the following:
   - User Registration / Login.
   - News and Events display.
   - Checkout flow (using test tokens first, then switching to live).

---
*Mombasa Hamlets Website - Production Ready*
