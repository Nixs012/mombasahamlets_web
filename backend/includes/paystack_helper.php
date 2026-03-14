<?php
/**
 * Paystack Helper Class
 */
require_once __DIR__ . '/../config/paystack_config.php';
require_once __DIR__ . '/../config/app_config.php';

class PaystackHelper {
    private static $secretKey = PAYSTACK_SECRET_KEY;

    /**
     * Verify a transaction with Paystack
     */
    public static function verifyTransaction($reference) {
        $url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . self::$secretKey,
            "Cache-Control: no-cache",
        ));

        // SSL verification: 
        // - Enabled in production for security
        // - Can be disabled locally for development (but not recommended)
        $sslVerify = !(defined('APP_ENV') && APP_ENV === 'development');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : 0);
        
        // Add timeouts for robustness
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return (object)[
                'status' => false,
                'message' => "Curl Error: " . $error
            ];
        }

        return json_decode($response);
    }
}
?>

