<?php
/**
 * دليل دمج نظام الأنشطة (Activity Logs) في الصفحات الأخرى
 * 
 * هذا الملف يحتوي على أمثلة عملية لكيفية تسجيل الأنشطة في أنحاء النظام
 */

// ملاحظة مهمة: كل صفحة تحتاج للأسطر التالية في البداية:
/*
session_start();
require_once 'config.php';
require_once 'helpers.php';

requireLogin();
requirePermission('permission_needed', $pdo);
*/

// ========================
// مثال 1: تسجيل نشاط الإضافة
// ========================

function example_add_worker($worker_data, $pdo) {
    // أضف البيانات إلى قاعدة البيانات
    $stmt = $pdo->prepare('INSERT INTO workers (worker_name, passport) VALUES (?, ?)');
    $stmt->execute([$worker_data['worker_name'], $worker_data['passport']]);
    $worker_id = $pdo->lastInsertId();
    
    // سجل النشاط
    logActivity(
        $_SESSION['user_id'],           // معرف المستخدم الحالي
        'ADD',                          // نوع الإجراء
        'workers',                      // نوع الكيان
        $worker_id,                     // معرف الكيان (اختياري)
        null,                           // البيانات القديمة (null للإضافة)
        $worker_data,                   // البيانات الجديدة
        "إضافة عاملة جديدة: {$worker_data['worker_name']}", // الوصف
        $pdo                            // اتصال قاعدة البيانات
    );
}

// ========================
// مثال 2: تسجيل نشاط التعديل
// ========================

function example_edit_worker($worker_id, $old_data, $new_data, $pdo) {
    // حدّث البيانات
    $stmt = $pdo->prepare('UPDATE workers SET worker_name = ?, passport = ? WHERE id = ?');
    $stmt->execute([$new_data['worker_name'], $new_data['passport'], $worker_id]);
    
    // سجل النشاط
    logActivity(
        $_SESSION['user_id'],
        'EDIT',
        'workers',
        $worker_id,
        $old_data,                      // البيانات القديمة
        $new_data,                      // البيانات الجديدة
        "تعديل بيانات عاملة: {$new_data['worker_name']}",
        $pdo
    );
}

// ========================
// مثال 3: تسجيل نشاط الحذف
// ========================

function example_delete_worker($worker_id, $worker_name, $pdo) {
    // احصل على البيانات قبل الحذف
    $stmt = $pdo->prepare('SELECT * FROM workers WHERE id = ?');
    $stmt->execute([$worker_id]);
    $old_data = $stmt->fetch();
    
    // احذف البيانات
    $stmt = $pdo->prepare('DELETE FROM workers WHERE id = ?');
    $stmt->execute([$worker_id]);
    
    // سجل النشاط
    logActivity(
        $_SESSION['user_id'],
        'DELETE',
        'workers',
        $worker_id,
        $old_data,                      // البيانات القديمة
        null,                           // لا توجد بيانات جديدة
        "حذف عاملة: $worker_name",
        $pdo
    );
}

// ========================
// مثال 4: تسجيل نشاط الأرشفة
// ========================

function example_archive_worker($worker_id, $status, $pdo) {
    // احصل على البيانات القديمة
    $stmt = $pdo->prepare('SELECT is_archived FROM workers WHERE id = ?');
    $stmt->execute([$worker_id]);
    $old_data = $stmt->fetch();
    
    // حدّث حالة الأرشفة
    $stmt = $pdo->prepare('UPDATE workers SET is_archived = ? WHERE id = ?');
    $stmt->execute([$status, $worker_id]);
    
    // سجل النشاط
    $action = ($status == 1) ? 'أرشفة' : 'استعادة من الأرشيف';
    logActivity(
        $_SESSION['user_id'],
        'ARCHIVE',
        'workers',
        $worker_id,
        $old_data,
        ['is_archived' => $status],
        $action,
        $pdo
    );
}

// ========================
// مثال 5: تسجيل نشاط الاستيراد
// ========================

function example_import_workers($count, $pdo) {
    // بعد استيراد البيانات بنجاح
    logActivity(
        $_SESSION['user_id'],
        'IMPORT',
        'workers',
        null,
        null,
        ['count' => $count],
        "استيراد $count عاملات من ملف Excel",
        $pdo
    );
}

// ========================
// مثال 6: تسجيل نشاط التصدير
// ========================

function example_export_workers($count, $pdo) {
    // بعد تصدير البيانات بنجاح
    logActivity(
        $_SESSION['user_id'],
        'EXPORT',
        'workers',
        null,
        null,
        ['count' => $count],
        "تصدير $count عاملات إلى ملف Excel",
        $pdo
    );
}

// ========================
// مثال 7: عرض سجل الأنشطة لكيان معين
// ========================

function get_entity_activity($entity_type, $entity_id, $pdo) {
    $stmt = $pdo->prepare('
        SELECT al.*, u.full_name, u.username
        FROM activity_logs al
        JOIN users u ON al.user_id = u.id
        WHERE al.entity_type = ? AND al.entity_id = ?
        ORDER BY al.created_at DESC
    ');
    $stmt->execute([$entity_type, $entity_id]);
    return $stmt->fetchAll();
}

// ========================
// مثال 8: الفلترة المتقدمة
// ========================

function get_activities_filtered($filters, $pdo) {
    $query = 'SELECT al.*, u.full_name FROM activity_logs al JOIN users u ON al.user_id = u.id WHERE 1=1';
    $params = [];
    
    if (!empty($filters['action'])) {
        $query .= ' AND al.action = ?';
        $params[] = $filters['action'];
    }
    
    if (!empty($filters['entity_type'])) {
        $query .= ' AND al.entity_type = ?';
        $params[] = $filters['entity_type'];
    }
    
    if (!empty($filters['user_id'])) {
        $query .= ' AND al.user_id = ?';
        $params[] = $filters['user_id'];
    }
    
    if (!empty($filters['date_from'])) {
        $query .= ' AND DATE(al.created_at) >= ?';
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $query .= ' AND DATE(al.created_at) <= ?';
        $params[] = $filters['date_to'];
    }
    
    $query .= ' ORDER BY al.created_at DESC LIMIT 500';
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ========================
// مثال 9: إضافة زر عرض السجل في جدول
// ========================

?>

<!-- HTML مثال: زر عرض السجل في جدول العاملات -->
<div class="action-buttons">
    <button onclick="openWorkerActivityModal(<?php echo $worker['id']; ?>, '<?php echo htmlspecialchars($worker['worker_name']); ?>')">
        <i class="fas fa-history"></i> عرض السجل
    </button>
</div>

<?php
// ========================
// نصائح مهمة
// ========================
/*
1. تأكد من استدعاء session_start() في بداية الملف
2. تأكد من تضمين helpers.php و config.php
3. التحقق من الصلاحيات قبل تسجيل الأنشطة الحساسة
4. استخدم أسماء وصفية للأنشطة بالعربية
5. قم بتخزين البيانات القديمة والجديدة في JSON
6. يمكنك استخدام getActionDescription() لترجمة الإجراءات
7. يمكنك استخدام formatDateTime() لتنسيق التواريخ
*/

// ========================
// الأنواع المختلفة للإجراءات
// ========================

$action_types = [
    'ADD'     => 'إضافة',
    'EDIT'    => 'تعديل',
    'DELETE'  => 'حذف',
    'ARCHIVE' => 'أرشفة',
    'LOGIN'   => 'دخول',
    'LOGOUT'  => 'خروج',
    'IMPORT'  => 'استيراد',
    'EXPORT'  => 'تصدير'
];

// ========================
// أنواع الكيانات
// ========================

$entity_types = [
    'workers'  => 'العاملات',
    'offices'  => 'المكاتب',
    'users'    => 'المستخدمين',
    'roles'    => 'الأدوار',
    'archive'  => 'الأرشيف',
    'auth'     => 'المصادقة'
];
?>
