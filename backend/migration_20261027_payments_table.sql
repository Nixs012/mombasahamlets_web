-- =====================================================================
-- Migration: Payment Gateway Tables (M-Pesa STK Push + Card Payments)
-- Date: 2026-10-27
-- Environment: SANDBOX ONLY
-- =====================================================================
-- All credentials live in backend/.env (git-ignored, never committed)
-- This migration stores transaction references and statuses only.

-- 1. Add payment_status column to orders table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'orders' 
                   AND COLUMN_NAME = 'payment_status');

SET @sql_add_payment_status = IF(@col_exists > 0,
    'SELECT 1',
    'ALTER TABLE orders ADD COLUMN payment_status ENUM("pending","processing","paid","failed","cancelled","refunded") DEFAULT "pending" AFTER status'
);

PREPARE stmt1 FROM @sql_add_payment_status;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- 2. Add checkout_request_id to orders for M-Pesa callback correlation
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'orders' 
                   AND COLUMN_NAME = 'checkout_request_id');

SET @sql_add_checkout_req = IF(@col_exists > 0,
    'SELECT 1',
    'ALTER TABLE orders ADD COLUMN checkout_request_id VARCHAR(255) NULL AFTER payment_status'
);

PREPARE stmt2 FROM @sql_add_checkout_req;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 3. Add payment_reference column to orders for cross-reference
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'orders' 
                   AND COLUMN_NAME = 'payment_reference');

SET @sql_add_payment_ref = IF(@col_exists > 0,
    'SELECT 1',
    'ALTER TABLE orders ADD COLUMN payment_reference VARCHAR(255) NULL AFTER checkout_request_id'
);

PREPARE stmt3 FROM @sql_add_payment_ref;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- 4. Create payment_logs table for audit trail
CREATE TABLE IF NOT EXISTS `payment_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL COMMENT 'Unique transaction reference',
  `payment_method` varchar(50) DEFAULT NULL COMMENT 'mpesa_stk or card',
  `amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','processing','success','failed','cancelled') DEFAULT 'pending',
  `checkout_request_id` varchar(255) DEFAULT NULL COMMENT 'M-Pesa CheckoutRequestID',
  `transaction_id` varchar(255) DEFAULT NULL COMMENT 'Provider transaction ID',
  `phone_number` varchar(20) DEFAULT NULL,
  `response_code` varchar(10) DEFAULT NULL,
  `response_description` text,
  `raw_response` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_reference` (`reference`),
  KEY `idx_checkout_request_id` (`checkout_request_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Update payments table status ENUM to include 'processing' and 'cancelled'
-- MySQL requires MODIFY COLUMN to change an ENUM's allowed values
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'payments' 
                   AND COLUMN_NAME = 'checkout_request_id');

SET @sql_update_payments = IF(@col_exists > 0,
    'SELECT 1',
    'ALTER TABLE payments 
     MODIFY COLUMN status ENUM("pending","processing","success","failed","cancelled","refunded") DEFAULT "pending",
     ADD COLUMN checkout_request_id VARCHAR(255) NULL AFTER reference,
     ADD COLUMN transaction_id VARCHAR(255) NULL AFTER checkout_request_id,
     ADD COLUMN raw_response TEXT AFTER transaction_id,
     ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
);

PREPARE stmt4 FROM @sql_update_payments;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

-- 6. Add delivery_zone_id column to orders (if not already present — needed for M-Pesa orders)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'orders' 
                   AND COLUMN_NAME = 'delivery_zone_id');

SET @sql_add_zone_id = IF(@col_exists > 0,
    'SELECT 1',
    'ALTER TABLE orders ADD COLUMN delivery_zone_id INT DEFAULT NULL AFTER payment_reference'
);

PREPARE stmt5 FROM @sql_add_zone_id;
EXECUTE stmt5;
DEALLOCATE PREPARE stmt5;

-- 7. Add delivery_zone_name column to orders for human-readable zone info
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'orders' 
                   AND COLUMN_NAME = 'delivery_zone_name');

SET @sql_add_zone_name = IF(@col_exists > 0,
    'SELECT 1',
    'ALTER TABLE orders ADD COLUMN delivery_zone_name VARCHAR(255) NULL AFTER delivery_zone_id'
);

PREPARE stmt6 FROM @sql_add_zone_name;
EXECUTE stmt6;
DEALLOCATE PREPARE stmt6;
