<?php
/**
 * delivery_zone_helper.php
 * Helper functions for retrieving and managing delivery zones.
 */

/**
 * Fetches all active delivery zones from the database.
 * Falls back to default zones if the table doesn't exist.
 *
 * @param mysqli $conn Database connection
 * @return array Array of delivery zone objects
 */
function get_active_delivery_zones($conn) {
    $zones = [];
    
    // Check if the delivery_zones table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'delivery_zones'");
    if ($tableCheck && $tableCheck->num_rows === 0) {
        // Table doesn't exist, return default zones
        return get_default_delivery_zones();
    }
    
    $stmt = $conn->prepare("SELECT id, name, fee, description FROM delivery_zones WHERE active = 1 ORDER BY fee ASC, name ASC");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $zones[] = $row;
        }
        $stmt->close();
    }
    
    // If no active zones found, fall back to defaults
    if (empty($zones)) {
        return get_default_delivery_zones();
    }
    
    return $zones;
}

/**
 * Fetches a single delivery zone by ID.
 * Falls back to null if not found.
 *
 * @param mysqli $conn Database connection
 * @param int $id Zone ID
 * @return array|null Zone data or null if not found
 */
function get_delivery_zone_by_id($conn, $id) {
    $stmt = $conn->prepare("SELECT id, name, fee, description FROM delivery_zones WHERE id = ? AND active = 1");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        $stmt->close();
    }
    return null;
}

/**
 * Returns the fee for a given delivery zone ID.
 * Falls back to 0 if zone not found.
 *
 * @param mysqli $conn Database connection
 * @param int $zoneId Zone ID
 * @return float Fee amount (default: 0.00)
 */
function get_delivery_zone_fee($conn, $zoneId) {
    $zone = get_delivery_zone_by_id($conn, $zoneId);
    if ($zone) {
        return floatval($zone['fee']);
    }
    
    // Fallback: check for legacy constants
    $legacyFees = [
        'mombasa' => 150.00,
        'outside' => 300.00,
        'pickup' => 0.00
    ];
    
    return 0.00;
}

/**
 * Provides the default delivery zones for backward compatibility.
 * These match the legacy SHIPPING_MOMBASA (150) and SHIPPING_OUTSIDE (300) constants.
 *
 * @return array
 */
function get_default_delivery_zones() {
    return [
        [
            'id' => 0,
            'name' => 'Mombasa Island',
            'fee' => 150.00,
            'description' => 'Delivery within Mombasa Island area'
        ],
        [
            'id' => 0,
            'name' => 'Outside Mombasa',
            'fee' => 300.00,
            'description' => 'Delivery to areas outside Mombasa Island'
        ],
        [
            'id' => 0,
            'name' => 'Shop Pickup',
            'fee' => 0.00,
            'description' => 'Pick up order from shop location'
        ]
    ];
}
