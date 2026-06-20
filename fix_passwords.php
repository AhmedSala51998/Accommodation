<?php
require_once 'config.php';

echo "<h1>🔧 إعادة تعيين كلمات المرور - النسخة المحسّنة</h1>";

try {
    // حذف المستخدمين الحاليين
    $pdo->exec("DELETE FROM users");
    echo "✅ تم حذف المستخدمين القدماء<br>";
    
    // إنشاء مستخدمين جدد مع كلمات مرور صحيحة
    // كل هذه كلمات المرور مشفرة بـ password_hash مع cost=10
    
    $stmt = $pdo->prepare('
        INSERT INTO users (id, username, email, password, full_name, role_id, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    
    $users = [
        [1, 'admin', 'admin@accommodation.com', password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 10]), 'مدير النظام', 1, 'نشط'],
        [2, 'user', 'user@accommodation.com', password_hash('user123', PASSWORD_BCRYPT, ['cost' => 10]), 'موظف النظام', 3, 'نشط'],
        [3, 'supervisor', 'supervisor@accommodation.com', password_hash('supervisor123', PASSWORD_BCRYPT, ['cost' => 10]), 'المشرف', 2, 'نشط'],
        [4, 'reviewer', 'reviewer@accommodation.com', password_hash('reviewer123', PASSWORD_BCRYPT, ['cost' => 10]), 'المراجع', 4, 'نشط'],
    ];
    
    foreach ($users as $user) {
        $stmt->execute($user);
        echo "✅ تم إنشاء المستخدم: <strong>" . $user[1] . "</strong> / <strong>" . $user[3] . "</strong><br>";
    }
    
    echo "<br><h2>✅ تم إعادة تعيين كلمات المرور بنجاح!</h2>";
    echo "<p style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<strong>البيانات الجديدة:</strong><br>";
    echo "👑 admin / admin123<br>";
    echo "🎯 supervisor / supervisor123<br>";
    echo "👤 user / user123<br>";
    echo "📋 reviewer / reviewer123<br>";
    echo "</p>";
    
    echo "<p style='margin-top: 20px;'>";
    echo "<a href='login.php' style='background: #667eea; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>";
    echo "👉 اذهب إلى صفحة التسجيل الآن";
    echo "</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage();
    echo "<br>التفاصيل: " . $e->getTraceAsString();
}
?>
