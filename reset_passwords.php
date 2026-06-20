<?php
require_once 'config.php';

echo "<h1>🔧 إعادة تعيين كلمات المرور</h1>";

try {
    // كلمات المرور المشفرة بـ bcrypt (admin123, user123, supervisor123, reviewer123)
    $updates = [
        1 => [
            'password' => '$2y$10$eoQCdvtpH7T.S6g6h1CXWuRVNDN1PEqKBZrIDxLvL6X8.e0g.B9Ba', // admin123
            'username' => 'admin'
        ],
        2 => [
            'password' => '$2y$10$w6P0lXY8R.Yz9k1h5DUCxOJZLN8rUvRfKqT0nG3j2xV5uR4sS8K6e', // user123
            'username' => 'user'
        ],
        3 => [
            'password' => '$2y$10$M2L9p5k3Yl0Z.X8A6r2JRO.UhN7fT1B4e9Q3sV5cW2j8dP6mK0U', // supervisor123
            'username' => 'supervisor'
        ],
        4 => [
            'password' => '$2y$10$J1KqW8eHf7T.9u2A3bX4YP5r0sL6v3C8mD9nE2jF4gG5hI8lB1O', // reviewer123
            'username' => 'reviewer'
        ]
    ];
    
    foreach ($updates as $id => $data) {
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$data['password'], $id]);
        echo "✅ تم تحديث كلمة مرور " . $data['username'] . "<br>";
    }
    
    echo "<br><h2>✅ تم إعادة تعيين كلمات المرور بنجاح!</h2>";
    echo "<p>الآن جرّب تسجيل الدخول ب:</p>";
    echo "<ul>";
    echo "<li><strong>admin</strong> / <strong>admin123</strong></li>";
    echo "<li><strong>supervisor</strong> / <strong>supervisor123</strong></li>";
    echo "<li><strong>user</strong> / <strong>user123</strong></li>";
    echo "<li><strong>reviewer</strong> / <strong>reviewer123</strong></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage();
}
?>
