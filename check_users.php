<?php
require_once 'config.php';

try {
    // التحقق من وجود جدول users
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    
    if (empty($tables)) {
        echo "❌ جدول users غير موجود!<br>";
        echo "يجب تشغيل schema.sql أولاً<br>";
        echo "استخدم الأمر: mysql -u root accommodation_db < schema.sql";
        exit;
    }
    
    // عرض المستخدمين الموجودين
    $users = $pdo->query("SELECT id, username, email, full_name, status FROM users")->fetchAll();
    
    echo "<h2>المستخدمون الموجودون:</h2>";
    
    if (empty($users)) {
        echo "❌ لا توجد مستخدمون!<br>";
        echo "يجب تشغيل schema.sql لإضافة المستخدمين الافتراضيين<br>";
        echo "<br><strong>استخدم هذا الأمر:</strong><br>";
        echo "<code>mysql -u root accommodation_db < schema.sql</code>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>اسم المستخدم</th><th>الاسم الكامل</th><th>البريد</th><th>الحالة</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['full_name'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<br><h3>✅ المستخدمون موجودون! جاهز للدخول</h3>";
    }
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}
?>
