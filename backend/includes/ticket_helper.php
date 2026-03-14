<?php
/**
 * ticket_helper.php
 * Handles generation of tickets after successful payment.
 */

class TicketHelper {
    /**
     * Generates tickets for a specific order.
     * @param int $orderId
     * @param mysqli $conn
     * @return int Number of tickets generated
     */
    public static function generateTicketsForOrder($orderId, $conn) {
        $ticketsGenerated = 0;

        // 0. Check if tickets already exist for this order
        $checkQuery = "SELECT COUNT(*) as count FROM tickets WHERE order_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $orderId);
        $checkStmt->execute();
        $count = $checkStmt->get_result()->fetch_assoc()['count'];
        $checkStmt->close();

        if ($count > 0) {
            return 0; // Tickets already generated
        }

        // 1. Fetch items that are tickets (have event_id and ticket_type_id)
        $query = "SELECT event_id, ticket_type_id, quantity FROM order_items WHERE order_id = ? AND event_id IS NOT NULL AND ticket_type_id IS NOT NULL";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($items)) {
            return 0;
        }

        // 2. Prepare insert statement for tickets
        $ticketStmt = $conn->prepare("INSERT INTO tickets (ticket_code, order_id, event_id, ticket_type_id, qr_code, status) VALUES (?, ?, ?, ?, ?, 'UNUSED')");

        foreach ($items as $item) {
            $eventId = $item['event_id'];
            $ticketTypeId = $item['ticket_type_id'];
            $quantity = $item['quantity'];

            for ($i = 0; $i < $quantity; $i++) {
                // Generate Unique Ticket Code
                $ticketCode = self::generateUniqueTicketCode($conn);
                
                // Generate QR Code (In a real app, this could be a URL or a Base64 string from a library)
                // For now, we store the ticket code or a direct link to verify it
                $qrCodeData = $ticketCode; 

                $ticketStmt->bind_param("siiis", $ticketCode, $orderId, $eventId, $ticketTypeId, $qrCodeData);
                if ($ticketStmt->execute()) {
                    $ticketsGenerated++;
                }
            }
        }

        $ticketStmt->close();
        return $ticketsGenerated;
    }

    /**
     * Generates a unique ticket code in the format MHFC-XXXXXX
     * @param mysqli $conn
     * @return string
     */
    private static function generateUniqueTicketCode($conn) {
        $exists = true;
        $code = '';

        while ($exists) {
            // Generate 6-8 random alphanumeric characters
            $randomPart = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            $code = "MHFC-" . $randomPart;

            // Check if it exists in DB
            $check = $conn->prepare("SELECT id FROM tickets WHERE ticket_code = ?");
            $check->bind_param("s", $code);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                $exists = false;
            }
            $check->close();
        }

        return $code;
    }
}
