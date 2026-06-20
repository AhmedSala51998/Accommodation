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
    mobile VARCHAR(50) DEFAULT NULL,
    receiver VARCHAR(50) DEFAULT NULL,
    receiver_other VARCHAR(255) DEFAULT NULL,
    passport_missing ENUM('لا','نعم') NOT NULL DEFAULT 'لا',
    passport_missing_note TEXT DEFAULT NULL,
    case_status VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== جداول نظام الأدوار والصلاحيات =====

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('نشط', 'معطل') DEFAULT 'نشط',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    module VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT,
    old_data JSON,
    new_data JSON,
    description TEXT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== الأدوار الافتراضية =====

INSERT IGNORE INTO roles (id, role_name, description) VALUES
(1, 'مدير النظام', 'لديه جميع الصلاحيات'),
(2, 'مشرف', 'يمكنه إدارة العاملات والمكاتب'),
(3, 'موظف', 'يمكنه عرض وإضافة العاملات فقط'),
(4, 'مراجع', 'يمكنه عرض البيانات فقط');

-- ===== الصلاحيات =====

INSERT IGNORE INTO permissions (permission_name, description, module) VALUES
-- صلاحيات العاملات
('view_workers', 'عرض العاملات', 'workers'),
('add_worker', 'إضافة عاملة جديدة', 'workers'),
('edit_worker', 'تعديل بيانات العاملة', 'workers'),
('delete_worker', 'حذف عاملة', 'workers'),
('archive_worker', 'أرشفة عاملة', 'workers'),
('export_workers', 'تصدير بيانات العاملات', 'workers'),
('import_workers', 'استيراد العاملات من Excel', 'workers'),
('view_worker_logs', 'عرض سجل أنشطة العاملة', 'workers'),

-- صلاحيات المكاتب
('view_offices', 'عرض المكاتب', 'offices'),
('add_office', 'إضافة مكتب', 'offices'),
('edit_office', 'تعديل مكتب', 'offices'),
('delete_office', 'حذف مكتب', 'offices'),
('import_offices', 'استيراد المكاتب', 'offices'),

-- صلاحيات التقارير
('view_reports', 'عرض التقارير', 'reports'),
('view_all_workers_report', 'عرض التقرير الشامل', 'reports'),
('view_admin_report', 'عرض التقرير الإداري', 'reports'),

-- صلاحيات الأرشيف
('view_archive', 'عرض الأرشيف', 'archive'),
('manage_archive', 'إدارة الأرشيف', 'archive'),

-- صلاحيات المستخدمين
('view_users', 'عرض المستخدمين', 'users'),
('add_user', 'إضافة مستخدم', 'users'),
('edit_user', 'تعديل مستخدم', 'users'),
('delete_user', 'حذف مستخدم', 'users'),
('assign_roles', 'تعيين الأدوار', 'users'),

-- صلاحيات الأدوار
('view_roles', 'عرض الأدوار', 'roles'),
('add_role', 'إضافة دور', 'roles'),
('edit_role', 'تعديل دور', 'roles'),
('delete_role', 'حذف دور', 'roles'),
('manage_permissions', 'إدارة الصلاحيات', 'roles'),

-- صلاحيات السجلات
('view_activity_logs', 'عرض سجل الأنشطة', 'logs');

-- ===== إسناد الصلاحيات للأدوار =====

-- مدير النظام: جميع الصلاحيات
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- مشرف: معظم الصلاحيات ما عدا إدارة المستخدمين
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE permission_name NOT IN ('add_user', 'edit_user', 'delete_user', 'assign_roles', 'view_users', 'add_role', 'edit_role', 'delete_role', 'manage_permissions');

-- موظف: صلاحيات محدودة
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE permission_name IN ('view_workers', 'add_worker', 'view_offices', 'export_workers', 'view_reports', 'view_archive');

-- مراجع: صلاحيات عرض فقط
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE permission_name IN ('view_workers', 'view_offices', 'view_reports', 'view_archive', 'view_worker_logs');

-- ===== المستخدمون الافتراضيون =====

INSERT IGNORE INTO users (id, username, email, password, full_name, role_id, status) VALUES
-- كلمة المرور: admin123 (مشفرة بـ bcrypt)
(1, 'admin', 'admin@accommodation.com', '$2y$10$eoQCdvtpH7T.S6g6h1CXWuRVNDN1PEqKBZrIDxLvL6X8.e0g.B9Ba', 'مدير النظام', 1, 'نشط'),

-- كلمة المرور: user123
(2, 'user', 'user@accommodation.com', '$2y$10$w6P0lXY8R.Yz9k1h5DUCxOJZLN8rUvRfKqT0nG3j2xV5uR4sS8K6e', 'موظف النظام', 3, 'نشط'),

-- كلمة المرور: supervisor123
(3, 'supervisor', 'supervisor@accommodation.com', '$2y$10$M2L9p5k3Yl0Z.X8A6r2JRO.UhN7fT1B4e9Q3sV5cW2j8dP6mK0U', 'المشرف', 2, 'نشط'),

-- كلمة المرور: reviewer123
(4, 'reviewer', 'reviewer@accommodation.com', '$2y$10$J1KqW8eHf7T.9u2A3bX4YP5r0sL6v3C8mD9nE2jF4gG5hI8lB1O', 'المراجع', 4, 'نشط');
