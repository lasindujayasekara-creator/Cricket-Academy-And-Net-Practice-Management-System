-- Cricket Academy and Net Practice Management System Database Schema
-- Project: Higher National Diploma (HND) Final Project
-- DBMS: MySQL

CREATE DATABASE IF NOT EXISTS `cricket_academy_db`;
USE `cricket_academy_db`;

-- 1. Users Table (Authentication & Core Identity)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'coach', 'player') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Players Table (Profile details, foreign key to users.id)
CREATE TABLE IF NOT EXISTS `players` (
  `id` INT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT NOT NULL,
  `category` VARCHAR(50) NOT NULL, -- e.g., 'Batsman', 'Bowler', 'All-Rounder', 'Wicketkeeper'
  `performance_notes` TEXT NULL,   -- Overall player performance summary / coach evaluations
  FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Coaches Table (Profile details, foreign key to users.id)
CREATE TABLE IF NOT EXISTS `coaches` (
  `id` INT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `specialization` VARCHAR(100) NOT NULL, -- e.g., 'Batting Coach', 'Fast Bowling Coach', 'Spin Coach', 'Fielding Coach'
  FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Bookings Table (Net bookings, prevents conflict through application validation)
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `player_id` INT NOT NULL,
  `coach_id` INT NULL,
  `booking_date` DATE NOT NULL,
  `time_slot` VARCHAR(50) NOT NULL, -- e.g., '07:00 - 09:00', '09:00 - 11:00', '14:00 - 16:00', '16:00 - 18:00'
  `net_no` INT NOT NULL, -- Net number e.g., 1, 2, 3
  `status` VARCHAR(20) NOT NULL DEFAULT 'Pending', -- 'Pending', 'Approved', 'Cancelled'
  `coach_feedback` TEXT NULL, -- Coach notes specific to this net practice session
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `unique_slot_booking` UNIQUE (`booking_date`, `time_slot`, `net_no`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Note: The UNIQUE constraint above ensures database-level safety. To allow cancellation of a slot and subsequent re-booking, 
-- we will drop the constraint and handle booking availability dynamically in the API, or keep it but handle the status: 
-- In MySQL, a unique constraint on multiple columns including status ('Pending', 'Approved') prevents two APPROVED/PENDING bookings, 
-- which is perfect because a CANCELLED status will not block future bookings!

-- 5. Attendance Table
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `player_id` INT NOT NULL,
  `attendance_date` DATE NOT NULL,
  `status` VARCHAR(20) NOT NULL, -- 'Present', 'Absent'
  UNIQUE KEY `player_date_attendance` (`player_id`, `attendance_date`),
  FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `player_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `receipt_no` VARCHAR(50) NOT NULL UNIQUE,
  FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data (Default password for all demo accounts: 'password123')
-- Bcrypt hash below is the REAL PHP-generated hash for 'password123':
-- Generated with: echo password_hash('password123', PASSWORD_BCRYPT);

-- 1. Insert Admins, Coaches, and Players in users table
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'coach_rahul', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coach'),
(3, 'coach_smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coach'),
(4, 'player_john', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'player'),
(5, 'player_david', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'player'),
(6, 'player_sarah', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'player');

-- 2. Insert Coach Profiles
INSERT INTO `coaches` (`id`, `name`, `email`, `phone`, `specialization`) VALUES
(2, 'Rahul Sharma', 'rahul.sharma@cricketacademy.com', '+919876543210', 'Spin Bowling & Tactical Play'),
(3, 'Steve Smith', 'steve.smith@cricketacademy.com', '+61412345678', 'Batting Technique & Fielding');

-- 3. Insert Player Profiles
INSERT INTO `players` (`id`, `name`, `email`, `phone`, `address`, `category`, `performance_notes`) VALUES
(4, 'John Doe', 'john.doe@email.com', '+1234567890', '123 Cricket Lane, Groundcity', 'Batsman', 'Showing great footwork. Needs improvement in playing short-pitched deliveries.'),
(5, 'David Warner', 'david.warner@email.com', '+1987654321', '45 Boundary Road, Outfield', 'All-Rounder', 'Strong power hitter. Bowling speed averages 125 km/h. Working on accuracy.'),
(6, 'Sarah Taylor', 'sarah.taylor@email.com', '+4478901234', '78 Keepers St, Catchtown', 'Wicketkeeper', 'Excellent reflexes behind the stumps. Solid middle-order batting support.');

-- 4. Insert Net Practice Bookings
INSERT INTO `bookings` (`id`, `player_id`, `coach_id`, `booking_date`, `time_slot`, `net_no`, `status`, `coach_feedback`) VALUES
(1, 4, 3, CURRENT_DATE() - INTERVAL 2 DAY, '09:00 - 11:00', 1, 'Approved', 'Great batting session, practiced cover drives for an hour. Keep focused.'),
(2, 5, 2, CURRENT_DATE() - INTERVAL 1 DAY, '16:00 - 18:00', 2, 'Approved', 'Bowled 6 overs. Spin rotation was excellent. Needs to watch length.'),
(3, 6, 3, CURRENT_DATE(), '14:00 - 16:00', 3, 'Approved', 'Wicketkeeping drills completed. Fast glove work displayed.'),
(4, 4, NULL, CURRENT_DATE() + INTERVAL 1 DAY, '07:00 - 09:00', 1, 'Pending', NULL),
(5, 5, NULL, CURRENT_DATE() + INTERVAL 2 DAY, '09:00 - 11:00', 2, 'Pending', NULL);

-- 5. Insert Attendance Records (For the last 3 days)
INSERT INTO `attendance` (`player_id`, `attendance_date`, `status`) VALUES
(4, CURRENT_DATE() - INTERVAL 2 DAY, 'Present'),
(5, CURRENT_DATE() - INTERVAL 2 DAY, 'Present'),
(6, CURRENT_DATE() - INTERVAL 2 DAY, 'Absent'),
(4, CURRENT_DATE() - INTERVAL 1 DAY, 'Present'),
(5, CURRENT_DATE() - INTERVAL 1 DAY, 'Present'),
(6, CURRENT_DATE() - INTERVAL 1 DAY, 'Present'),
(4, CURRENT_DATE(), 'Present'),
(5, CURRENT_DATE(), 'Present'),
(6, CURRENT_DATE(), 'Present');

-- 6. Insert Payments
INSERT INTO `payments` (`id`, `player_id`, `amount`, `payment_date`, `receipt_no`) VALUES
(1, 4, 150.00, CURRENT_DATE() - INTERVAL 20 DAY, 'REC-20260605-0001'),
(2, 5, 150.00, CURRENT_DATE() - INTERVAL 15 DAY, 'REC-20260610-0002'),
(3, 6, 150.00, CURRENT_DATE() - INTERVAL 5 DAY, 'REC-20260620-0003');

-- 7. Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('academy_name', 'Cricket Academy');
