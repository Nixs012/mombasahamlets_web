<?php
/**
 * Simple JWT implementation for PHP
 * No external dependencies required.
 */

// Load configuration — this file is excluded from git and contains environment-specific secrets
require_once __DIR__ . '/../config/app_config.php';

/**
 * Generate a JWT
 */
function generate_jwt($payload) {
    // Header
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

    // Payload
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

    // Signature
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Verify a JWT
 * Returns the payload if valid, or null if invalid
 */
function verify_jwt($jwt) {
    if (empty($jwt)) return null;
    
    $tokenParts = explode('.', $jwt);
    
    if (count($tokenParts) != 3) {
        return null;
    }
    
    $header = base64_decode($tokenParts[0]);
    $payload = base64_decode($tokenParts[1]);
    $signature_provided = $tokenParts[2];

    // Verify expiration if present
    $payloadObj = json_decode($payload);
    if (isset($payloadObj->exp) && $payloadObj->exp < time()) {
        return null;
    }

    // Check the signature
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    if ($base64UrlSignature === $signature_provided) {
        return json_decode($payload, true);
    }
    
    return null;
}

/**
 * Get the Bearer token from headers
 */
function get_bearer_token() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in some setups
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}
?>
