<?php
/**
 * Payment Gateway Helper Class
 *
 * Wraps M-Pesa STK Push (sandbox) and Card Payment provider integration.
 * All operations are SANDBOX-only; live keys are never used.
 */

require_once __DIR__ . '/../config/payment_config.php';

class PaymentGateway {
    private static $logFile = __DIR__ . '/../logs/payment_transactions.log';

    /**
     * Ensure logs directory exists
     */
    private static function ensureLogDir() {
        $dir = dirname(self::$logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    /**
     * Log every transaction for audit purposes
     */
    public static function logTransaction($data) {
        self::ensureLogDir();
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = json_encode([
            'timestamp' => $timestamp,
            'data' => $data
        ]) . "\n";
        @file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Initiate M-Pesa STK Push (sandbox mode)
     * 
     * @param array $orderData {
     *     @var string $phone         Customer phone number
     *     @var float  $amount        Amount to charge
     *     @var string $reference     Unique transaction reference
     *     @var string $callbackURL   Webhook/Callback URL
     *     @var string $accountNumber Account number / order ID
     * }
     * @return array {status, checkout_request_id, message}
     */
    public static function initiateStkPush($orderData) {
        // Validate required fields
        $required = ['phone', 'amount', 'reference', 'callbackURL', 'accountNumber'];
        foreach ($required as $field) {
            if (empty($orderData[$field])) {
                return ['status' => 'error', 'message' => "Missing required field: {$field}"];
            }
        }

        // Validate phone number format (Kenyan format: 254XXXXXXXXX or 07XXXXXXXXX)
        $phone = preg_replace('/[^0-9]/', '', $orderData['phone']);
        if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            $phone = '254' . substr($phone, 1);
        } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
            // Already in international format
        } else {
            return ['status' => 'error', 'message' => 'Invalid phone number format. Use 07XX XXX XXX or 2547XX XXX XXX'];
        }

        // Generate password
        $timestamp = date('YmdHis');
        $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

        // Build request payload (sandbox)
        $payload = [
            'BusinessShortCode' => MPESA_SHORTCODE,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int)round($orderData['amount']),
            'PartyA' => $phone,
            'IdentifierType' => '4',
            'CallBackURL' => $orderData['callbackURL'] ?: PAYMENT_WEBHOOK_URL,
            'AccountReference' => $orderData['accountNumber'],
            'TransactionDesc' => 'Mombasa Hamlets FC Payment - Order #' . $orderData['accountNumber'],
            'Remarks' => 'Order payment for ' . $orderData['accountNumber']
        ];

        // Log the request
        self::logTransaction([
            'event' => 'stk_push_request',
            'reference' => $orderData['reference'],
            'phone' => $phone,
            'amount' => $orderData['amount'],
            'payload' => $payload
        ]);

        // Get OAuth token (sandbox)
        $authResponse = self::getOauthToken();
        if ($authResponse['status'] !== 'success') {
            return ['status' => 'error', 'message' => 'Authentication failed: ' . ($authResponse['message'] ?? 'Unknown error')];
        }

        $accessToken = $authResponse['access_token'];

        // Send STK Push request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, MPESA_STK_PUSH_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Sandbox
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);

        // Log the response
        self::logTransaction([
            'event' => 'stk_push_response',
            'http_code' => $httpCode,
            'response' => $responseData ?: $response,
            'error' => $error
        ]);

        if ($error) {
            return ['status' => 'error', 'message' => 'Network error: ' . $error];
        }

        if ($httpCode != 200 || !$responseData) {
            return ['status' => 'error', 'message' => 'STK Push failed: ' . ($responseData['errorMessage'] ?? 'HTTP ' . $httpCode)];
        }

        // Handle M-Pesa sandbox response
        $merchantId = $responseData['MerchantRequestID'] ?? '';
        $checkoutRequestId = $responseData['CheckoutRequestID'] ?? '';
        $responseCode = $responseData['ResponseCode'] ?? 'Unknown';
        $customerMessage = $responseData['CustomerMessage'] ?? ($responseData['errorMessage'] ?? 'Unknown error');

        if ($responseCode == '0' || $responseCode == '00') {
            return [
                'status' => 'success',
                'checkout_request_id' => $checkoutRequestId,
                'merchant_request_id' => $merchantId,
                'message' => $customerMessage ?: 'STK Push sent successfully'
            ];
        }

        return [
            'status' => 'error',
            'checkout_request_id' => $checkoutRequestId,
            'message' => $customerMessage
        ];
    }

    /**
     * Query STK Push payment status (sandbox mode)
     * 
     * @param string $checkoutRequestId  The CheckoutRequestID from STK Push
     * @return array
     */
    public static function queryStkStatus($checkoutRequestId) {
        $authResponse = self::getOauthToken();
        if ($authResponse['status'] !== 'success') {
            return ['status' => 'error', 'message' => 'Authentication failed'];
        }

        $accessToken = $authResponse['access_token'];

        $payload = [
            'BusinessShortCode' => MPESA_SHORTCODE,
            'Password' => base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . date('YmdHis')),
            'Timestamp' => date('YmdHis'),
            'CheckoutRequestID' => $checkoutRequestId
        ];

        self::logTransaction([
            'event' => 'stk_query_request',
            'checkout_request_id' => $checkoutRequestId
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, MPESA_STK_QUERY_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);

        self::logTransaction([
            'event' => 'stk_query_response',
            'checkout_request_id' => $checkoutRequestId,
            'response' => $responseData
        ]);

        if (!isset($responseData['ResponseCode'])) {
            return ['status' => 'error', 'message' => 'Unable to query payment status'];
        }

        $responseCode = $responseData['ResponseCode'];
        $resultDesc = $responseData['ResultDesc'] ?? '';

        if ($responseCode == '0' || $responseCode == '00') {
            // Transaction completed
            $transactionStatus = $responseData['TransactionStatus'] ?? 'Completed';
            if ($transactionStatus === 'Completed' || $responseCode == '0') {
                return [
                    'status' => 'success',
                    'paid' => true,
                    'receipt' => $responseData['ReceiptCode'] ?? $responseData['MpesaReceiptCode'] ?? '',
                    'transaction_amount' => $responseData['TransactionAmount'] ?? 0,
                    'result_desc' => $resultDesc
                ];
            }
            return [
                'status' => 'pending',
                'paid' => false,
                'result_desc' => $resultDesc ?: 'Transaction pending'
            ];
        }

        // Payment not completed or failed
        return [
            'status' => ($responseCode == '1' || $responseCode == '103900') ? 'failed' : 'pending',
            'paid' => ($responseCode == '0') ? false : false,
            'result_code' => $responseCode,
            'result_desc' => $resultDesc ?: 'Payment not successful'
        ];
    }

    /**
     * Verify webhook signature (HMAC)
     * 
     * @param string $payload     Raw POST body
     * @param string $signature   Signature from webhook header
     * @return bool
     */
    public static function verifyWebhookSignature($payload, $signature) {
        if (empty($signature) || empty($payload)) {
            return false;
        }

        // M-Pesa sends the signature in the 'X-Callback-Auth' or similar header
        // For sandbox, we verify the signature using the consumer secret
        $expectedSignature = strtoupper(hash_hmac('sha256', $payload, MPESA_CONSUMER_SECRET));

        // Accept either exact match or the common callback formats
        $providedSignature = strtoupper(trim($signature));

        self::logTransaction([
            'event' => 'webhook_signature_verification',
            'expected' => $expectedSignature,
            'provided' => $providedSignature,
            'match' => ($expectedSignature === $providedSignature)
        ]);

        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * Get OAuth token from M-Pesa sandbox (client_credentials grant)
     * 
     * @return array {status, access_token, message}
     */
    private static function getOauthToken() {
        $authKey = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, MPESA_SANDBOX_URL . '/oauth/v1/generate?grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $authKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode != 200) {
            return ['status' => 'error', 'message' => $error ?: 'HTTP ' . $httpCode];
        }

        $tokenData = json_decode($response, true);

        if (!isset($tokenData['access_token'])) {
            return ['status' => 'error', 'message' => $tokenData['errorMessage'] ?? 'No access token returned'];
        }

        return [
            'status' => 'success',
            'access_token' => $tokenData['access_token'],
            'expires_in' => $tokenData['expires_in'] ?? 3600
        ];
    }

    /**
     * Record payment attempt in the database
     * 
     * @param mysqli $conn
     * @param array $paymentData
     * @return int|bool Payment record ID or false on failure
     */
    public static function recordPayment($conn, $paymentData) {
        $orderId       = intval($paymentData['order_id']);
        $reference     = $paymentData['reference'] ?? '';
        $amount        = floatval($paymentData['amount'] ?? 0);
        $method        = $paymentData['payment_method'] ?? 'mpesa_stk';
        $status        = $paymentData['status'] ?? 'pending';
        $checkoutReqId = $paymentData['checkout_request_id'] ?? null;
        $rawResponse   = isset($paymentData['raw_response']) ? json_encode($paymentData['raw_response']) : null;

        $stmt = $conn->prepare("
            INSERT INTO payments 
            (order_id, reference, amount, payment_method, status, checkout_request_id, raw_response)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isdssss", $orderId, $reference, $amount, $method, $status, $checkoutReqId, $rawResponse);

        if ($stmt->execute()) {
            return $conn->insert_id;
        }
        error_log("Payment record insert failed: " . $stmt->error);
        return false;
    }

    /**
     * Update payment status in the database
     * Updates both orders table (status + payment_status columns) and payments table.
     * 
     * @param mysqli $conn
     * @param int|string $identifier Order ID or reference
     * @param string $status New status: pending, paid, failed, cancelled, processing
     * @param array $extra Extra data (e.g., transaction_id, raw_response, payment_method)
     * @return bool
     */
    public static function updatePaymentStatus($conn, $identifier, $status, $extra = []) {
        $column = is_numeric($identifier) ? 'id' : 'reference';
        $value = is_numeric($identifier) ? $identifier : $identifier;

        // Map 'success' to 'paid' for order status
        $orderStatus = ($status === 'success' || $status === 'completed' || $status === 'completed') ? 'paid' : $status;
        // Map to payment_status column value
        $paymentStatus = ($status === 'success' || $status === 'completed') ? 'paid' : $status;

        // Update orders table - set both status and payment_status columns
        if (is_numeric($identifier)) {
            $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
            $stmt->bind_param("ssi", $orderStatus, $paymentStatus, $identifier);
        } else {
            $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE payment_reference = ?");
            $stmt->bind_param("sss", $orderStatus, $paymentStatus, $identifier);
        }
        $stmt->execute();
        $stmt->close();

        // Update payments table
        $setParts = ["status = ?"];
        $types = "s";
        $params = [$status];

        if (isset($extra['transaction_id'])) {
            $setParts[] = "transaction_id = ?";
            $types .= "s";
            $params[] = $extra['transaction_id'];
        }
        if (isset($extra['raw_response'])) {
            $setParts[] = "raw_response = ?";
            $types .= "s";
            $params[] = json_encode($extra['raw_response']);
        }
        if (isset($extra['amount'])) {
            $setParts[] = "amount = ?";
            $types .= "d";
            $params[] = $extra['amount'];
        }
        if (isset($extra['payment_method'])) {
            $setParts[] = "payment_method = ?";
            $types .= "s";
            $params[] = $extra['payment_method'];
        }
        if (isset($extra['reference'])) {
            $setParts[] = "reference = ?";
            $types .= "s";
            $params[] = $extra['reference'];
        }
        if (isset($extra['checkout_request_id'])) {
            $setParts[] = "checkout_request_id = ?";
            $types .= "s";
            $params[] = $extra['checkout_request_id'];
        }

        $params[] = $identifier; // for order_id/reference
        $setClause = implode(", ", $setParts);
        $sql = "UPDATE payments SET {$setClause}, updated_at = NOW() WHERE order_id = ? OR reference = ?";
        $stmt = $conn->prepare($sql);
        $types .= "is";
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        self::logTransaction([
            'event' => 'payment_status_updated',
            'order_or_ref' => $identifier,
            'new_status' => $status,
            'extra' => $extra
        ]);

        return true;
    }

    /**
     * Handle duplicate webhook notification
     * 
     * Checks if this transaction has already been processed to prevent duplicates.
     * 
     * @param mysqli $conn
     * @param string $reference
     * @return bool True if duplicate, false if not
     */
    public static function isDuplicateTransaction($conn, $reference) {
        $stmt = $conn->prepare("SELECT id, status FROM payments WHERE reference = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("s", $reference);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            // If already completed successfully, it's a duplicate
            if ($existing['status'] === 'success' || $existing['status'] === 'completed') {
                self::logTransaction([
                    'event' => 'duplicate_callback_detected',
                    'reference' => $reference,
                    'existing_status' => $existing['status']
                ]);
                return true;
            }
        }
        return false;
    }

    /**
     * Generate a unique transaction reference
     * 
     * @param string $prefix Optional prefix
     * @return string
     */
    public static function generateReference($prefix = 'MP') {
        return $prefix . '-' . date('Ymd') . '-' . substr(strtoupper(md5(uniqid())), 0, 8) . '-' . mt_rand(1000, 9999);
    }

    /**
     * Validate amount against order total
     * 
     * @param mysqli $conn
     * @param string $orderId
     * @param float $amount
     * @return bool
     */
    public static function validateAmount($conn, $orderId, $amount) {
        $stmt = $conn->prepare("SELECT total_amount FROM orders WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows === 1) {
            $order = $result->fetch_assoc();
            $expectedAmount = floatval($order['total_amount']);
            // Allow 1 KES tolerance for rounding
            return abs($expectedAmount - $amount) <= 1.00;
        }
        return false;
    }

    /**
     * Initiate card payment (sandbox)
     * 
     * @param array $paymentData
     * @return array {status, payment_url, reference}
     */
    public static function initiateCardPayment($paymentData) {
        $required = ['email', 'amount', 'currency', 'callback_url'];
        foreach ($required as $field) {
            if (empty($paymentData[$field])) {
                return ['status' => 'error', 'message' => "Missing required field: {$field}"];
            }
        }

        $reference = self::generateReference('CARD');

        $payload = [
            'amount' => (int)round($paymentData['amount'] * 100), // cents
            'currency' => $paymentData['currency'] ?? PAYMENT_CURRENCY,
            'email' => $paymentData['email'],
            'reference' => $reference,
            'callback_url' => $paymentData['callback_url'],
            'metadata' => [
                'order_id' => $paymentData['order_id'] ?? '',
                'source' => 'mombasa_hamlets_checkout'
            ]
        ];

        self::logTransaction([
            'event' => 'card_payment_request',
            'reference' => $reference,
            'payload' => $payload
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.cardprovider.com/v1/payments'); // Sandbox URL
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . CARD_SECRET_KEY
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);

        self::logTransaction([
            'event' => 'card_payment_response',
            'http_code' => $httpCode,
            'response' => $responseData,
            'error' => $error
        ]);

        if ($error) {
            return ['status' => 'error', 'message' => 'Network error: ' . $error];
        }

        if ($httpCode == 200 && $responseData && isset($responseData['data']['authorization_url'])) {
            return [
                'status' => 'success',
                'reference' => $reference,
                'payment_url' => $responseData['data']['authorization_url'],
                'access_code' => $responseData['data']['access_code'] ?? ''
            ];
        }

        return ['status' => 'error', 'message' => $responseData['message'] ?? 'Payment initiation failed. HTTP ' . $httpCode];
    }
}
?>
