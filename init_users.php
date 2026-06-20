<?php
// init_users.php - ملف لإنشاء مستخدمين تجريبيين
require_once 'config.php';

try {
    // إنشاء مستخدم مدير النظام
    $admin_password = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('
        INSERT IGNORE INTO users (username, email, full_name, password, role_id, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute(['admin', 'admin@accommodation.local', 'مدير النظام', $admin_password, 1, 'نشط']);
    
    // إنشاء مستخدم عادي
    $user_password = password_hash('user123', PASSWORD_BCRYPT);
    $stmt->execute(['user', 'user@accommodation.local', 'موظف', $user_password, 3, 'نشط']);
    
    // إنشاء مشرف
    $supervisor_password = password_hash('supervisor123', PASSWORD_BCRYPT);
    $stmt->execute(['supervisor', 'supervisor@accommodation.local', 'المشرف', $supervisor_password, 2, 'نشط']);
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إنشاء المستخدمين بنجاح',
        'users' => [
            [
                'username' => 'admin',
                'password' => 'admin123',
                'role' => 'مدير النظام'
            ],
            [
                'username' => 'user',
                'password' => 'user123',
                'role' => 'موظف'
            ],
            [
                'username' => 'supervisor',
                'password' => 'supervisor123',
                'role' => 'مشرف'
            ]
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
