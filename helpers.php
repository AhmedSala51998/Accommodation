<?php
// helpers.php - دوال مساعدة للنظام

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل الدخول
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// إعادة التوجيه عند عدم تسجيل الدخول
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// التحقق من الصلاحية
function hasPermission($permission, $pdo) {
    if (!isLoggedIn()) {
        return false;
    }

    try {
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as count
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            JOIN users u ON u.role_id = rp.role_id
            WHERE u.id = ? AND p.permission_name = ?
        ');
        $stmt->execute([$_SESSION['user_id'], $permission]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    } catch (Exception $e) {
        return false;
    }
}

// التحقق من الصلاحية مع إعادة توجيه
function requirePermission($permission, $pdo) {
    if (!hasPermission($permission, $pdo)) {
        http_response_code(403);
        die('ليس لديك صلاحية للوصول إلى هذه الصفحة');
    }
}

// تسجيل النشاط
function logActivity($user_id, $action, $entity_type, $entity_id = null, $old_data = null, $new_data = null, $description = '', $pdo) {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, old_data, new_data, description, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $user_id,
            $action,
            $entity_type,
            $entity_id,
            $old_data ? json_encode($old_data) : null,
            $new_data ? json_encode($new_data) : null,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// الحصول على معلومات المستخدم
function getUserInfo($user_id, $pdo) {
    try {
        $stmt = $pdo->prepare('
            SELECT u.id, u.username, u.email, u.full_name, u.status, r.role_name, r.id as role_id
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ');
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// الحصول على صلاحيات المستخدم
function getUserPermissions($user_id, $pdo) {
    try {
        $stmt = $pdo->prepare('
            SELECT p.permission_name, p.module
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            JOIN users u ON u.role_id = rp.role_id
            WHERE u.id = ?
            ORDER BY p.module, p.permission_name
        ');
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// تسجيل الخروج
function logout() {
    if (isset($_SESSION['user_id'])) {
        session_destroy();
    }
    header('Location: login.php');
    exit;
}

// تنسيق التاريخ والوقت
function formatDateTime($datetime) {
    if (!$datetime) return '-';
    $date = new DateTime($datetime);
    return $date->format('Y-m-d H:i:s');
}

// الحصول على وصف الإجراء بالعربية
function getActionDescription($action, $entity_type) {
    $descriptions = [
        'ADD' => [
            'workers' => 'إضافة عاملة جديدة',
            'offices' => 'إضافة مكتب جديد',
            'users' => 'إضافة مستخدم جديد',
            'roles' => 'إضافة دور جديد'
        ],
        'EDIT' => [
            'workers' => 'تعديل بيانات عاملة',
            'offices' => 'تعديل مكتب',
            'users' => 'تعديل بيانات مستخدم',
            'roles' => 'تعديل دور'
        ],
        'DELETE' => [
            'workers' => 'حذف عاملة',
            'offices' => 'حذف مكتب',
            'users' => 'حذف مستخدم',
            'roles' => 'حذف دور'
        ],
        'ARCHIVE' => [
            'workers' => 'أرشفة عاملة'
        ],
        'LOGIN' => [
            'auth' => 'تسجيل دخول'
        ],
        'LOGOUT' => [
            'auth' => 'تسجيل خروج'
        ],
        'IMPORT' => [
            'workers' => 'استيراد عاملات من Excel',
            'offices' => 'استيراد مكاتب من Excel'
        ],
        'EXPORT' => [
            'workers' => 'تصدير بيانات العاملات'
        ]
    ];

    return $descriptions[$action][$entity_type] ?? $action;
}

// الحصول على أيقونة الإجراء
function getActionIcon($action) {
    $icons = [
        'ADD' => '<i class="fas fa-plus-circle" style="color: #10b981;"></i>',
        'EDIT' => '<i class="fas fa-edit" style="color: #f59e0b;"></i>',
        'DELETE' => '<i class="fas fa-trash" style="color: #ef4444;"></i>',
        'ARCHIVE' => '<i class="fas fa-archive" style="color: #8b5cf6;"></i>',
        'LOGIN' => '<i class="fas fa-sign-in-alt" style="color: #3b82f6;"></i>',
        'LOGOUT' => '<i class="fas fa-sign-out-alt" style="color: #6b7280;"></i>',
        'IMPORT' => '<i class="fas fa-file-import" style="color: #06b6d4;"></i>',
        'EXPORT' => '<i class="fas fa-file-export" style="color: #14b8a6;"></i>'
    ];

    return $icons[$action] ?? '<i class="fas fa-circle"></i>';
}
?>
