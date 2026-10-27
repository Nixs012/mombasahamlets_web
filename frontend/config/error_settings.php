<?php
/**
 * frontend/config/error_settings.php
 *
 * Shared error reporting configuration for the frontend.
 *
 * In production: errors are logged to a file and never displayed.
 * In development: errors are displayed in the browser.
 *
 * Set the APP_ENV environment variable to control behavior:
 *   - 'production': hide errors, log to file
 *   - anything else: display errors (development mode)
 *
 * Usage:
 *   require_once __DIR__ . '/../config/error_settings.php';
 *
 * @package Mombasa Hamlets FC
 */

$appEnv = getenv('APP_ENV') ?: 'development';

if ($appEnv === 'production') {
    // Production: hide errors from users, log to file
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    // Log file path (writable by the web server)
    $logFile = __DIR__ . '/../../backend/logs/php_errors.log';
    if (is_dir(dirname($logFile))) {
        ini_set('error_log', $logFile);
    }
} else {
    // Development: show all errors
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Store the environment for use in templates
$GLOBALS['APP_ENV'] = $appEnv;
