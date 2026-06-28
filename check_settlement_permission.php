<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

header('Content-Type: application/json');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['hasPermission' => false]);
    exit;
}

// التحقق من صلاحية تعديل حالة التسوية
$hasPermission = hasPermission('edit_settlement', $pdo);
echo json_encode(['hasPermission' => $hasPermission]);
?>