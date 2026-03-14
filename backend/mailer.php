<?php
require_once __DIR__ . '/../db.php';

/**
 * Sends an email notification to all active subscribers.
 * 
 * @param string $subject The subject of the email
 * @param string $message The body of the email (HTML supported)
 * @param string $type The type of content (news, product, event, match)
 * @return array Result summary ['sent' => int, 'failed' => int]
 */
function sendNotificationToSubscribers($subject, $message, $type = 'general') {
    global $conn;
    
    // Check if connection is alive, if not, try to reconnect (basic check)
    if (!$conn || $conn->connect_errno) {
        // In a real scenario we might re-include db.php or handle error
        // For now assume db.php was included by the caller
        if (!isset($conn)) {
             return ['error' => 'Database connection missing'];
        }
    }

    // Get all subscribers
    $sql = "SELECT email FROM subscribers";
    $result = $conn->query($sql);
    
    $sentCount = 0;
    $failedCount = 0;
    
    if ($result && $result->num_rows > 0) {
        // Headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        
        // From address - Change this to your domain email
        $headers .= 'From: Mombasa Hamlets FC <noreply@mombasahamlets.com>' . "\r\n";
        
        while ($row = $result->fetch_assoc()) {
            $to = $row['email'];
            
            // Basic HTML wrapper
            $htmlMessage = "
            <html>
            <head>
                <title>{$subject}</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                    .header { background-color: #DA291C; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; }
                    .btn { display: inline-block; background: #DA291C; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 10px;}
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Mombasa Hamlets FC Update</h2>
                    </div>
                    <div class='content'>
                        <h3>{$subject}</h3>
                        <p>{$message}</p>
                        <p>Visit our website to see more details.</p>
                    </div>
                    <div class='footer'>
                        <p>You received this email because you subscribed to Mombasa Hamlets FC updates.</p>
                        <p>&copy; " . date('Y') . " Mombasa Hamlets FC</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Use standard PHP mail function
            // Note: On local WAMP, this depends on sendmail/SMTP config in php.ini
            // It might not send actual emails without proper configuration
            if (mail($to, $subject, $htmlMessage, $headers)) {
                $sentCount++;
            } else {
                $failedCount++;
                // Log failure if needed
                error_log("Failed to send email to $to");
            }
        }
    }
    
    return ['sent' => $sentCount, 'failed' => $failedCount];
}
?>
