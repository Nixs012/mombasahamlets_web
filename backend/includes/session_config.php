<?php
/**
 * backend/includes/session_config.php
 * 
 * Centralized session configuration with security hardening.
 * This file ensures that all sessions are started with secure cookie parameters.
 * 
 * Call this at the beginning of any file that needs session support.
 * Example: require_once __DIR__ . '/session_config.php';
 */

if (session_status() === PHP_SESSION_NONE) {
    // Detect if HTTPS is active
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
               || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    // Set secure cookie parameters before starting the session
    session_set_cookie_params([
        'lifetime' => 0,           // Expires when browser closes
        'path'     => '/',         // Available across entire domain
        'domain'   => '',          // Current domain (empty = current host)
        'secure'   => $isHttps,    // Only send over HTTPS in production
        'httponly' => true,        // Prevents JavaScript from reading the cookie
        'samesite' => 'Strict',    // Prevents CSRF by restricting cross-site cookie access
    ]);
    
    // Start the session
    session_start();
}

?>
