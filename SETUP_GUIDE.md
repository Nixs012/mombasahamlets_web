# 💻 Local Setup Guide (Wampserver)

This guide will help you set up the **Mombasa Hamlets** project on your own computer so it looks and runs exactly like the development version.

## 0. Prerequisites (CRITICAL)
Before installing Wampserver, you **MUST** ensure you have all the Microsoft Visual C++ Redistributable packages installed. Without these, Wampserver will fail to start (showing "MSVCR110.dll missing" or similar errors).

1. Download and run the **All-in-One VC Redist** or ensure you have the following versions (both x86 and x64):
   - VC++ 2008, 2010, 2012, 2013, 2015-2022.
2. You can find them on the [Microsoft website](https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist?view=msvc-170) or use a tool like "Check_vcredist" usually provided on the Wampserver download page.

## 1. Install Wampserver
1. Download Wampserver from the [official website](https://www.wampserver.com/en/). Choose the version that matches your Windows (usually 64-bit).
2. Run the installer. **IMPORTANT**: Install it to the default path `C:\wamp64`.
3. If prompted about "Default Browser" or "Default Editor", you can choose Chrome/Edge and Notepad++.
4. Once installed, start Wampserver from your desktop. The "W" icon in your taskbar should turn **Green**.

## 2. Clone the Project
1. Open your terminal (or Git Bash).
2. Navigate to your Wampserver's web directory:
   ```bash
   cd C:/wamp64/www
   ```
3. Clone the project from GitHub:
   ```bash
   git clone https://github.com/Nixs012/mombasahamlets_web.git
   ```
4. Your project folder will now be at `C:\wamp64\www\mombasahamlets_web`.

## 3. Database Setup
1. Click the **Green W** icon in your taskbar and select **phpMyAdmin**.
2. Log in (default username is `root`, leave password empty).
3. Create a new database named `mombasa_hamlets`.
4. Click on the `mombasa_hamlets` database on the left, then click the **Import** tab at the top.
5. Choose the file `mombasa_hamlets_setup.sql` located inside your project folder.
6. Click **Go** at the bottom. Your tables are now ready!

## 4. Configuration (Manual Step)
Since sensitive files are not on GitHub, you must create them manually:

1. Create a file at `backend/db.php` with this content:
   ```php
   <?php
   $host = '127.0.0.1';
   $db_name = 'mombasa_hamlets';
   $username = 'root';
   $password = ''; // Default Wamp password is empty
   $conn = new mysqli($host, $username, $password, $db_name);
   ?>
   ```

2. Create a folder at `backend/config/` (if it doesn't exist).
3. Create `backend/config/app_config.php`:
   ```php
   <?php
   define('APP_ENV', 'development');
   define('JWT_SECRET', 'mombasahamlets_dev_secret_key');
   ?>
   ```

4. Create `backend/config/paystack_config.php` (for testing):
   ```php
   <?php
   define('PAYSTACK_PUBLIC_KEY', 'pk_test_your_test_key_here');
   define('PAYSTACK_SECRET_KEY', 'sk_test_your_test_key_here');
   define('PAYSTACK_CURRENCY', 'KES');
   define('SHIPPING_MOMBASA', 150);
   define('SHIPPING_OUTSIDE', 300);
   define('PAYSTACK_VERIFY_URL', 'http://localhost/mombasahamlets_web/backend/api/verify_paystack.php');
   ?>
   ```

## 5. View the Website
Open your browser and go to: [http://localhost/mombasahamlets_web/](http://localhost/mombasahamlets_web/)

Your site should now be running perfectly! ⚽
