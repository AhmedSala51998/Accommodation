<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

if (!hasPermission('view_worker_logs', $pdo)) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$worker_id = $_GET['worker_id'] ?? null;

if (!$worker_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare('
        SELECT al.id, al.action, al.description, al.created_at, u.full_name
        FROM activity_logs al
        JOIN users u ON al.user_id = u.id
        WHERE al.entity_type = "workers" AND al.entity_id = ?
        ORDER BY al.created_at DESC
        LIMIT 100
    ');
    $stmt->execute([$worker_id]);
    $activities = $stmt->fetchAll();
    
    $result = [];
    foreach ($activities as $activity) {
        $result[] = [
            'id' => $activity['id'],
            'action' => $activity['action'],
            'description' => $activity['description'],
            'full_name' => $activity['full_name'],
            'created_at' => formatDateTime($activity['created_at'])
        ];
    }
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
