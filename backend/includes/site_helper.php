<?php
/**
 * site_helper.php
 * Helper functions for site-wide operations.
 */

if (!function_exists('update_site_timestamp')) {
    function update_site_timestamp($conn) {
        $timestamp = time();
        $key = 'last_content_update';
        
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("sss", $key, $timestamp, $timestamp);
        $stmt->execute();
        $stmt->close();
        
        return $timestamp;
    }
}
?>
