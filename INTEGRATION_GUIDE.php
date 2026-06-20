<?php
/**
 * تحديث الصفحات الموجودة - دليل الدمج
 * 
 * هذا الملف يشرح كيفية إضافة عرض الأنشطة للعاملات في الصفحات الموجودة
 */
?>

<!-- 
    ===================================================
    1. إضافة نافذة سجل الأنشطة إلى صفحة الفهرس (index.php)
    ===================================================
    
    أضف السطر التالي قبل إغلاق tag </body>:
-->

<?php include 'worker_activity_modal.html'; ?>

<!-- 
    ===================================================
    2. إضافة زر عرض الأنشطة في جدول العاملات
    ===================================================
    
    في صفحة index.php، ضمن جدول العاملات، أضف زر جديد في العمود "تفاصيل":
-->

<!--
    <td style="text-align: center;">
        <button onclick="openWorkerActivityModal(<?php echo $worker['id']; ?>, '<?php echo htmlspecialchars($worker['worker_name']); ?>')">
            <i class="fas fa-history"></i> الأنشطة
        </button>
    </td>
-->

<!-- 
    ===================================================
    3. تحديث app.js لدعم النوافذ الجديدة
    ===================================================
    
    في assets/js/app.js، تأكد من وجود معالجات الأحداث التالية:
-->

<?php

// مثال على كيفية استدعاء الأنشطة في JavaScript:

?>

<script>
/*
// عند إضافة عاملة جديدة، سيتم تسجيل النشاط تلقائياً
app.onWorkerAdded = function(worker) {
    showToast('تم إضافة العاملة بنجاح');
    // سيتم تسجيل النشاط في api.php تلقائياً
};

// عند تعديل عاملة
app.onWorkerEdited = function(worker) {
    showToast('تم تحديث البيانات بنجاح');
    // سيتم تسجيل النشاط في api.php تلقائياً
};

// عند حذف عاملة
app.onWorkerDeleted = function(workerId) {
    showToast('تم حذف العاملة');
    // سيتم تسجيل النشاط في api.php تلقائياً
};

// عند أرشفة عاملة
app.onWorkerArchived = function(workerId) {
    showToast('تم أرشفة العاملة');
    // سيتم تسجيل النشاط في api.php تلقائياً
};
*/
</script>

<!-- 
    ===================================================
    4. إضافة صلاحيات إلى الأزرار في index.php
    ===================================================
    
    تحديث الأزرار بالشروط التالية:
-->

<?php

/**
 * مثال على كيفية إضافة الشروط:
 * 
 * <?php if (hasPermission('add_worker', $pdo)): ?>
 *     <button id="bulkAddBtn" class="btn btn-primary">
 *         <i class="fas fa-plus-circle"></i> إضافة
 *     </button>
 * <?php endif; ?>
 */

?>

<!-- 
    ===================================================
    5. قائمة الصفحات المحتاجة للتحديث
    ===================================================
-->

<?php

$pages_to_update = [
    'archive.php' => [
        'requirements' => [
            'إضافة شريط معلومات المستخدم في الأعلى',
            'إضافة نافذة عرض الأنشطة',
            'إضافة زر عرض الأنشطة لكل عاملة مؤرشفة',
            'التحقق من الصلاحيات للأزرار'
        ]
    ],
    'all_workers.php' => [
        'requirements' => [
            'إضافة شريط معلومات المستخدم',
            'نافذة عرض الأنشطة',
            'زر الأنشطة في الجدول',
            'التحقق من صلاحيات التقارير'
        ]
    ],
    'admin_report.php' => [
        'requirements' => [
            'إضافة شريط معلومات المستخدم',
            'التحقق من صلاحية view_admin_report'
        ]
    ]
];

foreach ($pages_to_update as $page => $config) {
    echo "📝 $page\n";
    foreach ($config['requirements'] as $req) {
        echo "   - $req\n";
    }
}

?>

<!-- 
    ===================================================
    6. كود CSS مطلوب في جميع الصفحات
    ===================================================
    
    أضف في <head>:
-->

<style>
    /* شريط معلومات المستخدم */
    .user-info-bar {
        background: linear-gradient(135deg, #4361ee 0%, #5b7cfa 100%);
        color: white;
        padding: 10px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        margin-bottom: 10px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .user-info-bar a {
        color: white;
        text-decoration: none;
        margin-left: 10px;
        padding: 5px 12px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 5px;
        transition: 0.3s;
        cursor: pointer;
        border: none;
        font-weight: 600;
    }

    .user-info-bar a:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .user-info-bar .left {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-info-bar .right {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* نافذة الأنشطة */
    .activity-timeline {
        position: relative;
        padding: 20px 0;
    }

    .activity-item {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        position: relative;
    }

    .activity-item::before {
        content: '';
        position: absolute;
        left: 20px;
        top: 50px;
        width: 2px;
        height: calc(100% + 20px);
        background: #e2e8f0;
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        position: relative;
        z-index: 1;
        flex-shrink: 0;
    }

    .activity-icon.add { background: #10b981; }
    .activity-icon.edit { background: #f59e0b; }
    .activity-icon.delete { background: #ef4444; }
    .activity-icon.archive { background: #8b5cf6; }
</style>

<!-- 
    ===================================================
    7. كود PHP مطلوب في جميع الصفحات
    ===================================================
    
    أضف في بداية الملف:
-->

<?php

/*
session_start();
require_once 'config.php';
require_once 'helpers.php';

// التحقق من تسجيل الدخول والصلاحيات
requireLogin();
requirePermission('required_permission', $pdo);

// الحصول على معلومات المستخدم الحالي
$current_user = getUserInfo($_SESSION['user_id'], $pdo);
*/

?>

<!-- 
    ===================================================
    8. HTML لشريط معلومات المستخدم
    ===================================================
-->

<?php

/*
<div class="user-info-bar">
    <div class="left">
        <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($current_user['full_name']); ?></span>
        <span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;">
            <?php echo htmlspecialchars($current_user['role_name']); ?>
        </span>
    </div>
    <div class="right">
        <?php if (hasPermission('view_users', $pdo)): ?>
            <a href="manage_users.php"><i class="fas fa-users"></i> المستخدمين</a>
        <?php endif; ?>
        <?php if (hasPermission('view_roles', $pdo)): ?>
            <a href="manage_roles.php"><i class="fas fa-shield-alt"></i> الأدوار</a>
        <?php endif; ?>
        <?php if (hasPermission('view_activity_logs', $pdo)): ?>
            <a href="activity_logs.php"><i class="fas fa-history"></i> السجلات</a>
        <?php endif; ?>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل خروج</a>
    </div>
</div>
*/

?>

<!-- 
    ===================================================
    9. اختبار النظام
    ===================================================
    
    1. تسجيل الدخول بحساب admin
    2. الذهاب إلى صفحة المستخدمين
    3. الذهاب إلى صفحة الأدوار
    4. الذهاب إلى صفحة السجلات
    5. إضافة عاملة جديدة وتحقق من السجل
    6. تعديل عاملة وتحقق من السجل
    7. حذف عاملة وتحقق من السجل
    8. أرشفة عاملة وتحقق من السجل
    9. اختبر صلاحيات مستخدم عادي
    10. تحقق من عرض الأنشطة على كل عاملة
-->

<!-- 
    ===================================================
    10. نصائح التصحيح
    ===================================================
    
    إذا واجهت مشاكل:
    
    ❌ خطأ "ليس لديك صلاحية":
       ✅ تحقق من أن المستخدم يملك الدور الصحيح
       ✅ تحقق من الصلاحيات المسندة للدور
    
    ❌ لا تظهر الأنشطة:
       ✅ تأكد من تضمين get_worker_activity.php
       ✅ تأكد من أن النافذة مفتوحة بشكل صحيح
    
    ❌ خطأ في السجل:
       ✅ تحقق من اتصال قاعدة البيانات
       ✅ تحقق من جداول activity_logs
    
    ❌ صفحة بيضاء:
       ✅ افحص error logs في PHP
       ✅ تأكد من وجود جميع الملفات المطلوبة
-->

<!-- 
    ===================================================
    ملخص التحديثات المطلوبة
    ===================================================
-->

<?php

$updates_summary = [
    'archive.php' => 'إضافة شريط المستخدم + نافذة الأنشطة',
    'all_workers.php' => 'إضافة شريط المستخدم + نافذة الأنشطة',
    'admin_report.php' => 'إضافة شريط المستخدم',
    'assets/js/app.js' => 'لا يحتاج تحديث (الأنشطة تُسجل تلقائياً)',
    'assets/css/style.css' => 'قد تحتاج لتحديث الألوان',
];

echo "📋 ملخص التحديثات:\n";
foreach ($updates_summary as $file => $update) {
    echo "   $file: $update\n";
}

?>
