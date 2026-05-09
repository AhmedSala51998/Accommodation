CREATE DATABASE IF NOT EXISTS accommodation_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE accommodation_db;

CREATE TABLE IF NOT EXISTS workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_name VARCHAR(255) NOT NULL,
    passport VARCHAR(100),
    nationality VARCHAR(100),
    office VARCHAR(255),
    customer VARCHAR(255),
    national_id VARCHAR(100),
    guarantee_status ENUM('داخل الضمان', 'خارج الضمان') DEFAULT 'داخل الضمان',
    housing_location ENUM('ايواء ينبع', 'ايواء جدة', 'ايواء الرياض') DEFAULT 'ايواء الرياض',
    entry_date DATE,
    housing_entry_date DATE,
    salary DECIMAL(10, 2),
    status_description TEXT,
    action_type ENUM('السكن', 'نقل خدمات', 'خروج نهائي', 'اخرى') DEFAULT 'السكن',
    ticket_info VARCHAR(255),
    settlement_status ENUM('لم يتم الخصم', 'تم الخصم', 'تم الخصم جزئياً') DEFAULT 'لم يتم الخصم',
    financial_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
