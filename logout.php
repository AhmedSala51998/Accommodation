<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

if (isset($_SESSION['user_id'])) {
    // تسجيل نشاط تسجيل الخروج
    $stmt = $pdo->prepare('
        INSERT INTO activity_logs (user_id, action, entity_type, description, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $_SESSION['user_id'],
        'LOGOUT',
        'auth',
        'تسجيل خروج',
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
}

session_destroy();
header('Location: login.php');
exit;
?>
