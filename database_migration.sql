-- ============================================
-- DATABASE MIGRATION SCRIPT
-- Hotel Management System - Database Updates
-- ============================================
-- Run this script AFTER running bluebirdhotel.sql
-- This script extends existing tables and creates new tables

USE bluebirdhotel;

-- ============================================
-- A1. MỞ RỘNG BẢNG signup
-- ============================================
ALTER TABLE `signup` 
  ADD COLUMN `Phone` VARCHAR(20) NULL AFTER `Password`,
  ADD COLUMN `Address` TEXT NULL AFTER `Phone`,
  ADD COLUMN `role` ENUM('customer', 'admin', 'staff', 'supplier') DEFAULT 'customer' AFTER `Address`,
  ADD COLUMN `failed_attempts` INT DEFAULT 0 AFTER `role`,
  ADD COLUMN `locked_until` DATETIME NULL AFTER `failed_attempts`,
  ADD COLUMN `is_active` TINYINT(1) DEFAULT 1 AFTER `locked_until`,
  ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `is_active`,
  ADD COLUMN `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Đổi Password từ VARCHAR(50) sang VARCHAR(255) để lưu bcrypt hash
ALTER TABLE `signup` 
  MODIFY COLUMN `Password` VARCHAR(255) NOT NULL;

-- Update existing admin user to have admin role
UPDATE `signup` SET `role` = 'admin' WHERE `Email` = 'tusharpankhaniya2202@gmail.com' LIMIT 1;

-- ============================================
-- A2. MỞ RỘNG BẢNG room
-- ============================================
ALTER TABLE `room` 
  ADD COLUMN `price` DECIMAL(10,2) NULL AFTER `bedding`,
  ADD COLUMN `max_guests` INT DEFAULT 2 AFTER `price`,
  ADD COLUMN `status` ENUM('Available', 'Occupied', 'Needs Cleaning', 'Cleaning') DEFAULT 'Available' AFTER `max_guests`,
  ADD COLUMN `description` TEXT NULL AFTER `status`,
  ADD COLUMN `amenities` TEXT NULL AFTER `description`;

-- Set default prices based on room type
UPDATE `room` SET `price` = 3000, `max_guests` = 4 WHERE `type` = 'Superior Room';
UPDATE `room` SET `price` = 2000, `max_guests` = 3 WHERE `type` = 'Deluxe Room';
UPDATE `room` SET `price` = 1500, `max_guests` = 2 WHERE `type` = 'Guest House';
UPDATE `room` SET `price` = 1000, `max_guests` = 1 WHERE `type` = 'Single Room';

-- Set all rooms to Available initially
UPDATE `room` SET `status` = 'Available' WHERE `status` IS NULL;

-- ============================================
-- A3. MỞ RỘNG BẢNG roombook
-- ============================================
ALTER TABLE `roombook` 
  ADD COLUMN `status` ENUM('Pending', 'Confirmed', 'Checked-in', 'Checked-out', 'Cancelled') DEFAULT 'Pending' AFTER `stat`,
  ADD COLUMN `room_id` INT(30) NULL AFTER `status`,
  ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `room_id`,
  ADD COLUMN `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
  ADD COLUMN `checked_in_at` DATETIME NULL AFTER `updated_at`,
  ADD COLUMN `checked_out_at` DATETIME NULL AFTER `checked_in_at`;

-- Migrate existing stat to new status column
UPDATE `roombook` SET `status` = 'Confirmed' WHERE `stat` = 'Confirm';
UPDATE `roombook` SET `status` = 'Pending' WHERE `stat` = 'NotConfirm';

-- Add foreign key for room_id
ALTER TABLE `roombook` 
  ADD CONSTRAINT `fk_roombook_room` FOREIGN KEY (`room_id`) REFERENCES `room`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- ============================================
-- A4. MỞ RỘNG BẢNG payment
-- ============================================
ALTER TABLE `payment` 
  ADD COLUMN `method` ENUM('card', 'ewallet', 'cash') DEFAULT 'cash' AFTER `finaltotal`,
  ADD COLUMN `status` ENUM('Success', 'Failed', 'Pending', 'Refunded') DEFAULT 'Pending' AFTER `method`,
  ADD COLUMN `transaction_id` VARCHAR(100) NULL AFTER `status`,
  ADD COLUMN `type` ENUM('Booking', 'Deposit', 'Final') DEFAULT 'Booking' AFTER `transaction_id`,
  ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `type`;

-- Update existing payments to Success status
UPDATE `payment` SET `status` = 'Success' WHERE `status` IS NULL OR `status` = '';

-- Add foreign key constraint for payment.id -> roombook.id
ALTER TABLE `payment` 
  ADD CONSTRAINT `fk_payment_roombook` FOREIGN KEY (`id`) REFERENCES `roombook`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ============================================
-- A5. TẠO BẢNG MỚI: room_feedback
-- ============================================
CREATE TABLE IF NOT EXISTS `room_feedback` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `room_id` INT(30) NOT NULL,
  `user_id` INT(100) NOT NULL,
  `rating` INT(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `comment` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_feedback_room` (`room_id`),
  KEY `fk_feedback_user` (`user_id`),
  CONSTRAINT `fk_feedback_room` FOREIGN KEY (`room_id`) REFERENCES `room`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_user` FOREIGN KEY (`user_id`) REFERENCES `signup`(`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- A6. TẠO BẢNG MỚI: support_tickets
-- ============================================
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(100) NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `channel` VARCHAR(50) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` VARCHAR(50) DEFAULT 'open',
  `admin_response` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `fk_support_user` (`user_id`),
  KEY `idx_support_status` (`status`),
  CONSTRAINT `fk_support_user` FOREIGN KEY (`user_id`) REFERENCES `signup`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- A7. TẠO BẢNG MỚI: password_resets
-- ============================================
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(100) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_reset_user` (`user_id`),
  KEY `idx_reset_token` (`token`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `signup`(`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- A8. TẠO BẢNG MỚI: two_factor_codes
-- ============================================
CREATE TABLE IF NOT EXISTS `two_factor_codes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(100) NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_2fa_user` (`user_id`),
  KEY `idx_2fa_code` (`code`, `used`),
  CONSTRAINT `fk_2fa_user` FOREIGN KEY (`user_id`) REFERENCES `signup`(`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- A9. TẠO BẢNG MỚI: activity_logs (cho logging)
-- ============================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(100) NULL,
  `action` VARCHAR(100) NOT NULL,
  `table_name` VARCHAR(50) NULL,
  `record_id` INT(11) NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_user` (`user_id`),
  KEY `idx_log_action` (`action`),
  KEY `idx_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- A10. THÊM INDEXES CHO PERFORMANCE
-- ============================================
-- Indexes for roombook
CREATE INDEX `idx_roombook_dates` ON `roombook`(`cin`, `cout`, `RoomType`, `room_id`);
CREATE INDEX `idx_roombook_status` ON `roombook`(`status`);

-- Indexes for payment
CREATE INDEX `idx_payment_created` ON `payment`(`created_at`);
CREATE INDEX `idx_payment_status` ON `payment`(`status`);

-- Indexes for room
CREATE INDEX `idx_room_status` ON `room`(`status`);
CREATE INDEX `idx_room_type` ON `room`(`type`);

-- ============================================
-- A11. UPDATE emp_login để có role và password hash
-- ============================================
ALTER TABLE `emp_login` 
  ADD COLUMN `role` ENUM('admin', 'staff') DEFAULT 'staff' AFTER `Emp_Password`,
  ADD COLUMN `failed_attempts` INT DEFAULT 0 AFTER `role`,
  ADD COLUMN `locked_until` DATETIME NULL AFTER `failed_attempts`,
  ADD COLUMN `is_active` TINYINT(1) DEFAULT 1 AFTER `locked_until`;

-- Update existing admin
UPDATE `emp_login` SET `role` = 'admin' WHERE `Emp_Email` = 'Admin@gmail.com' LIMIT 1;

-- ============================================
-- MIGRATION COMPLETE
-- ============================================
-- Note: Existing passwords in signup table are plain text
-- They need to be hashed using password_hash() when users login next time
-- or run a separate script to hash existing passwords

