<?php
require_once 'config.php';

echo "<h1>🔍 فحص المستخدمين</h1>";

try {
    // احصل على جميع المستخدمين
    $users = $pdo->query("
        SELECT u.id, u.username, u.email, u.full_name, u.password, u.status, r.role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        ORDER BY u.id
    ")->fetchAll();
    
    if (empty($users)) {
        echo "❌ لا توجد مستخدمين";
        exit;
    }
    
    echo "<table border='1' cellpadding='15' style='width:100%; border-collapse: collapse;'>";
    echo "<tr style='background: #667eea; color: white;'>";
    echo "<th>ID</th>";
    echo "<th>اسم المستخدم</th>";
    echo "<th>الاسم الكامل</th>";
    echo "<th>البريد</th>";
    echo "<th>الحالة</th>";
    echo "<th>الدور</th>";
    echo "<th>كلمة المرور (Hash)</th>";
    echo "<th>اختبار كلمة المرور</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td><strong>" . $user['username'] . "</strong></td>";
        echo "<td>" . $user['full_name'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . ($user['status'] === 'نشط' ? '✅ نشط' : '❌ معطل') . "</td>";
        echo "<td>" . $user['role_name'] . "</td>";
        echo "<td><code style='font-size: 11px;'>" . substr($user['password'], 0, 30) . "...</code></td>";
        
        // اختبر كلمات المرور الشهيرة
        $testPasswords = [
            'admin123' => 'admin123',
            'user123' => 'user123',
            'supervisor123' => 'supervisor123',
            'reviewer123' => 'reviewer123',
            'password' => 'password',
            '123456' => '123456'
        ];
        
        $matchedPassword = false;
        foreach ($testPasswords as $test) {
            if (password_verify($test, $user['password'])) {
                echo "<td>✅ <strong>" . $test . "</strong></td>";
                $matchedPassword = true;
                break;
            }
        }
        
        if (!$matchedPassword) {
            echo "<td>❌ لم توجد مطابقة</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<br><br>";
    echo "<h2>📋 البيانات المتوقعة:</h2>";
    echo "<table border='1' cellpadding='10' style='background: #f0f7ff; border-collapse: collapse;'>";
    echo "<tr>";
    echo "<th>اسم المستخدم</th>";
    echo "<th>كلمة المرور</th>";
    echo "<th>الدور</th>";
    echo "</tr>";
    echo "<tr>";
    echo "<td><strong>admin</strong></td>";
    echo "<td><strong>admin123</strong></td>";
    echo "<td>مدير النظام</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td><strong>supervisor</strong></td>";
    echo "<td><strong>supervisor123</strong></td>";
    echo "<td>مشرف</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td><strong>user</strong></td>";
    echo "<td><strong>user123</strong></td>";
    echo "<td>موظف</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td><strong>reviewer</strong></td>";
    echo "<td><strong>reviewer123</strong></td>";
    echo "<td>مراجع</td>";
    echo "</tr>";
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage();
}
?>
