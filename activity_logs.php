<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

requireLogin();
requirePermission('view_activity_logs', $pdo);

$entity_type = $_GET['entity_type'] ?? null;
$entity_id = $_GET['entity_id'] ?? null;
$user_id_filter = $_GET['user_id'] ?? null;
$date_from = $_GET['date_from'] ?? null;
$date_to = $_GET['date_to'] ?? null;
$action_filter = $_GET['action'] ?? null;
$worker_search = trim($_GET['worker_search'] ?? '');

// بناء الاستعلام
$query = 'SELECT al.*, u.full_name, u.username FROM activity_logs al JOIN users u ON al.user_id = u.id WHERE 1=1';
$params = [];

if ($entity_type) {
    $query .= ' AND al.entity_type = ?';
    $params[] = $entity_type;
}

if ($entity_id) {
    $query .= ' AND al.entity_id = ?';
    $params[] = $entity_id;
}

if ($user_id_filter) {
    $query .= ' AND al.user_id = ?';
    $params[] = $user_id_filter;
}

if ($action_filter) {
    $query .= ' AND al.action = ?';
    $params[] = $action_filter;
}

if ($date_from) {
    $query .= ' AND DATE(al.created_at) >= ?';
    $params[] = $date_from;
}

if ($date_to) {
    $query .= ' AND DATE(al.created_at) <= ?';
    $params[] = $date_to;
}

if ($worker_search) {
    $query .= ' AND (al.description LIKE ? OR JSON_EXTRACT(al.old_data, "$.passport") LIKE ? OR JSON_EXTRACT(al.new_data, "$.passport") LIKE ? OR JSON_EXTRACT(al.old_data, "$.full_name") LIKE ? OR JSON_EXTRACT(al.new_data, "$.full_name") LIKE ?)';
    $searchParam = '%' . $worker_search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$query .= ' ORDER BY al.created_at DESC LIMIT 500';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// جلب المستخدمين للفلتر
$stmt = $pdo->prepare('SELECT id, full_name FROM users ORDER BY full_name');
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل الأنشطة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=1.5">
    <style>
        body {
            background: #f8fafc;
        }

        .logs-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }

        .header-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: #4361ee;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-back:hover {
            background: #3a4fd9;
        }

        .page-header h1 {
            margin: 0;
            font-size: 24px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters-section {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e293b;
            font-size: 13px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-size: 13px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .btn-search,
        .btn-reset {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            font-family: 'Cairo', sans-serif;
        }

        .btn-search {
            background: #4361ee;
            color: white;
        }

        .btn-search:hover {
            background: #3a4fd9;
        }

        .btn-reset {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-reset:hover {
            background: #cbd5e1;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logs-table th {
            background: #f1f5f9;
            padding: 15px;
            text-align: right;
            font-weight: 700;
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
            font-size: 13px;
        }

        .logs-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .logs-table tbody tr:hover {
            background: #f8fafc;
        }

        .action-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .action-add {
            background: #d1fae5;
            color: #065f46;
        }

        .action-edit {
            background: #fef3c7;
            color: #92400e;
        }

        .action-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-archive {
            background: #ede9fe;
            color: #4c1d95;
        }

        .action-login {
            background: #dbeafe;
            color: #1e40af;
        }

        .action-logout {
            background: #e5e7eb;
            color: #374151;
        }

        .action-import {
            background: #cffafe;
            color: #164e63;
        }

        .action-export {
            background: #ccfbf1;
            color: #134e4a;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #4361ee;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: #1e293b;
        }

        .user-username {
            font-size: 12px;
            color: #64748b;
        }

        .entity-info {
            font-size: 12px;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 6px;
            color: #1e293b;
        }

        .details-btn {
            padding: 6px 12px;
            background: #dbeafe;
            color: #1e40af;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: 0.3s;
        }

        .details-btn:hover {
            background: #bfdbfe;
        }

        .empty-message {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }

        .empty-message i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #cbd5e1;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 95%;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }

        .modal-header h2 {
            margin: 0;
        }

        .data-comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }

        .data-section {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-right: 3px solid #e2e8f0;
        }

        .data-section.new {
            border-right-color: #10b981;
        }

        .data-section h3 {
            margin: 0 0 12px 0;
            font-size: 13px;
            color: #1e293b;
            font-weight: 700;
        }

        .data-item {
            margin-bottom: 8px;
            font-size: 12px;
        }

        .data-label {
            font-weight: 600;
            color: #64748b;
        }

        .data-value {
            color: #1e293b;
            word-break: break-all;
            margin-top: 2px;
            background: white;
            padding: 4px 6px;
            border-radius: 4px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            transition: 0.3s;
        }

        .pagination button:hover {
            background: #f1f5f9;
        }

        .pagination button.active {
            background: #4361ee;
            color: white;
            border-color: #4361ee;
        }
    </style>
</head>

<body>
    <div class="logs-container">
        <div class="page-header">
            <h1><i class="fas fa-history"></i> سجل الأنشطة</h1>
            <div class="header-buttons">
                <a href="index.php" class="btn-back">
                    <i class="fas fa-home"></i> الرئيسية
                </a>
                <span style="color: #64748b; font-size: 14px;">إجمالي: <?php echo count($logs); ?> نشاط</span>
            </div>
        </div>

        <form method="GET" action="" style="margin-bottom: 25px;">
            <div class="filters-section">
                <div class="filter-group">
                    <label for="worker_search">بحث بعاملة:</label>
                    <input type="text" id="worker_search" name="worker_search" placeholder="رقم الجواز أو اسم العاملة..." value="<?php echo htmlspecialchars($worker_search ?? ''); ?>">
                </div>

                <div class="filter-group">
                    <label for="entity_type">نوع الكيان:</label>
                    <select id="entity_type" name="entity_type">
                        <option value="">الكل</option>
                        <option value="workers" <?php echo ($entity_type === 'workers') ? 'selected' : ''; ?>>العاملات</option>
                        <option value="offices" <?php echo ($entity_type === 'offices') ? 'selected' : ''; ?>>المكاتب</option>
                        <option value="users" <?php echo ($entity_type === 'users') ? 'selected' : ''; ?>>المستخدمين</option>
                        <option value="roles" <?php echo ($entity_type === 'roles') ? 'selected' : ''; ?>>الأدوار</option>
                        <option value="archive" <?php echo ($entity_type === 'archive') ? 'selected' : ''; ?>>الأرشيف</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="action">الإجراء:</label>
                    <select id="action" name="action">
                        <option value="">الكل</option>
                        <option value="ADD" <?php echo ($action_filter === 'ADD') ? 'selected' : ''; ?>>إضافة</option>
                        <option value="EDIT" <?php echo ($action_filter === 'EDIT') ? 'selected' : ''; ?>>تعديل</option>
                        <option value="DELETE" <?php echo ($action_filter === 'DELETE') ? 'selected' : ''; ?>>حذف</option>
                        <option value="ARCHIVE" <?php echo ($action_filter === 'ARCHIVE') ? 'selected' : ''; ?>>أرشفة</option>
                        <option value="LOGIN" <?php echo ($action_filter === 'LOGIN') ? 'selected' : ''; ?>>دخول</option>
                        <option value="IMPORT" <?php echo ($action_filter === 'IMPORT') ? 'selected' : ''; ?>>استيراد</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="user_id">المستخدم:</label>
                    <select id="user_id" name="user_id">
                        <option value="">الكل</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo ($user_id_filter == $user['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="date_from">من التاريخ:</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from ?? ''); ?>">
                </div>

                <div class="filter-group">
                    <label for="date_to">إلى التاريخ:</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to ?? ''); ?>">
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i> بحث</button>
                    <a href="activity_logs.php" class="btn-reset"><i class="fas fa-sync"></i> إعادة تعيين</a>
                </div>
            </div>
        </form>

        <?php if (empty($logs)): ?>
            <div class="empty-message">
                <i class="fas fa-inbox"></i>
                <p>لا توجد أنشطة مسجلة</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>الإجراء</th>
                            <th>الوصف</th>
                            <th>المستخدم</th>
                            <th>النوع</th>
                            <th>التاريخ والوقت</th>
                            <th style="width: 80px;">الخيارات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <span class="action-badge action-<?php echo strtolower($log['action']); ?>">
                                        <?php echo getActionIcon($log['action']); ?>
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo getActionDescription($log['action'], $log['entity_type']); ?></strong>
                                    <?php if ($log['description']): ?>
                                        <br><small style="color: #64748b;"><?php echo htmlspecialchars($log['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar"><?php echo strtoupper(substr($log['full_name'], 0, 1)); ?></div>
                                        <div class="user-details">
                                            <div class="user-name"><?php echo htmlspecialchars($log['full_name']); ?></div>
                                            <div class="user-username">@<?php echo htmlspecialchars($log['username']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="entity-info">
                                        <?php echo htmlspecialchars($log['entity_type']); ?>
                                        <?php if ($log['entity_id']): ?>
                                            <br>#<?php echo $log['entity_id']; ?>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td style="font-size: 12px; color: #64748b;">
                                    <?php echo formatDateTime($log['created_at']); ?>
                                </td>
                                <td>
                                    <?php if ($log['old_data'] || $log['new_data']): ?>
                                        <button class="details-btn" onclick="showDetails(<?php echo htmlspecialchars(json_encode($log)); ?>)">
                                            <i class="fas fa-eye"></i> التفاصيل
                                        </button>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1; font-size: 12px;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- نافذة التفاصيل -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> تفاصيل النشاط</h2>
                <button class="close-btn" onclick="document.getElementById('detailsModal').style.display='none'">&times;</button>
            </div>
            <div id="detailsContent"></div>
        </div>
    </div>

    <script>
        function showDetails(log) {
            let html = `
                <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0;">
                    <div style="margin-bottom: 10px;">
                        <span style="color: #64748b; font-size: 12px;">الوصف:</span>
                        <div style="color: #1e293b; font-weight: 600;">${log.description || '-'}</div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="color: #64748b; font-size: 12px;">العنوان IP:</span>
                        <div style="color: #1e293b; font-family: monospace; font-size: 12px;">${log.ip_address || '-'}</div>
                    </div>
                </div>
            `;

            const oldData = log.old_data ? JSON.parse(log.old_data) : null;
            const newData = log.new_data ? JSON.parse(log.new_data) : null;

            if (oldData || newData) {
                html += '<div class="data-comparison">';
                
                if (oldData) {
                    html += '<div class="data-section">';
                    html += '<h3><i class="fas fa-arrow-left" style="color: #ef4444;"></i> البيانات القديمة</h3>';
                    for (let key in oldData) {
                        html += `<div class="data-item">
                            <div class="data-label">${key}</div>
                            <div class="data-value">${oldData[key] || '-'}</div>
                        </div>`;
                    }
                    html += '</div>';
                }

                if (newData) {
                    html += '<div class="data-section new">';
                    html += '<h3><i class="fas fa-arrow-right" style="color: #10b981;"></i> البيانات الجديدة</h3>';
                    for (let key in newData) {
                        html += `<div class="data-item">
                            <div class="data-label">${key}</div>
                            <div class="data-value">${newData[key] || '-'}</div>
                        </div>`;
                    }
                    html += '</div>';
                }

                html += '</div>';
            }

            document.getElementById('detailsContent').innerHTML = html;
            document.getElementById('detailsModal').style.display = 'block';
        }

        window.onclick = function (event) {
            const modal = document.getElementById('detailsModal');
            if (event.target === modal) modal.style.display = 'none';
        }
    </script>
</body>

</html>
