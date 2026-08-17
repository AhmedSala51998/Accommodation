<?php
session_start();
require_once 'helpers.php';
require_once 'config.php';

// التحقق من تسجيل الدخول
requireLogin();
requirePermission('view_workers', $pdo);

$current_user = getUserInfo($_SESSION['user_id'], $pdo);

// عد العاملات حسب الفرع
$housingCounts = [
    'ينبع' => 0,
    'جدة' => 0,
    'الرياض' => 0
];

$transferCounts = [
    'ينبع' => 0,
    'جدة' => 0,
    'الرياض' => 0
];

$rentalCounts = [
    'ينبع' => 0,
    'جدة' => 0,
    'الرياض' => 0
];

try {

    // السكن فقط (استبعاد نقل الخدمات والتأجير)
    $stmt = $pdo->query("
        SELECT housing_location, COUNT(*) cnt
        FROM workers
        WHERE is_archived = 0
        AND action_type NOT IN ('نقل خدمات','تأجير')
        GROUP BY housing_location
    ");

    while($row = $stmt->fetch()){
        $housingCounts[$row['housing_location']] = $row['cnt'];
    }

    // نقل الخدمات
    $stmt = $pdo->query("
        SELECT housing_location, COUNT(*) cnt
        FROM workers
        WHERE is_archived = 0
        AND action_type = 'نقل خدمات'
        GROUP BY housing_location
    ");

    while($row = $stmt->fetch()){
        $transferCounts[$row['housing_location']] = $row['cnt'];
    }

    // التأجير
    $stmt = $pdo->query("
        SELECT housing_location, COUNT(*) cnt
        FROM workers
        WHERE is_archived = 0
        AND action_type = 'تأجير'
        GROUP BY housing_location
    ");

    while($row = $stmt->fetch()){
        $rentalCounts[$row['housing_location']] = $row['cnt'];
    }

} catch(Exception $e){}

// السكن
$yanbuCount  = $housingCounts['ينبع'];
$jeddahCount = $housingCounts['جدة'];
$riyadhCount = $housingCounts['الرياض'];

// نقل الخدمات
$yanbuTransfer  = $transferCounts['ينبع'];
$jeddahTransfer = $transferCounts['جدة'];
$riyadhTransfer = $transferCounts['الرياض'];

// التأجير
$yanbuRental  = $rentalCounts['ينبع'];
$jeddahRental = $rentalCounts['جدة'];
$riyadhRental = $rentalCounts['الرياض'];

$totalTransfer =
    $yanbuTransfer +
    $jeddahTransfer +
    $riyadhTransfer;

$totalRental =
    $yanbuRental +
    $jeddahRental +
    $riyadhRental;

$totalCount =
    $yanbuCount +
    $jeddahCount +
    $riyadhCount +
    $totalTransfer +
    $totalRental;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة سكن العاملات</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=1.5">
    <!-- jQuery (Required for search and dynamic features) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SheetJS for Excel Parsing -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        /* Standalone Excel Modal Styles to avoid conflicts */
        .excel-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
        }

        .excel-modal-content {
            background: white;
            margin: 3% auto;
            padding: 25px;
            border-radius: 24px;
            width: 95%;
            max-width: 1100px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .excel-upload-zone {
            border: 3px dashed #cbd5e1;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .excel-upload-zone:hover {
            border-color: #4361ee;
            background: #f0f7ff;
        }

        .excel-preview-table-wrapper {
            margin-top: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: auto;
            max-height: 250px;
            background: white;
        }

        .excel-preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .excel-preview-table th {
            background: #f1f5f9;
            padding: 10px;
            border: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            font-weight: 800;
        }

        .excel-preview-table td { padding: 8px; border: 1px solid #e2e8f0; text-align: center; color: #475569; }

        /* Focus effect for office search */
        .search-box:focus-within {
            border-color: #4361ee !important;
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.15) !important;
            transform: translateY(-1px);
        }

        /* User Info Bar */
        .user-info-bar {
            background: linear-gradient(135deg, #4361ee 0%, #5b7cfa 100%);
            color: white;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            margin-bottom: 10px;
            margin-top:20px !important;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-right:25px;
            margin-left:25px
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

        

        .user-info-bar {
    background: #1e293b;
    color: #fff;
    padding: 12px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.user-info-bar .left,
.user-info-bar .right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-info-bar .right a {
    color: #fff;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 6px;
    transition: .3s;
    white-space: nowrap;
}

.user-info-bar .right a:hover {
    background: rgba(255,255,255,0.15);
}

.mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 22px;
    cursor: pointer;
}

/* Branch Counters */
.branch-counters {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}

.branch-counter-item {
    display: flex;
    align-items: center;
    gap: 5px;
    background: rgba(255,255,255,0.12);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    transition: 0.3s;
    border: 1px solid rgba(255,255,255,0.15);
}

.branch-counter-item:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-1px);
}

.branch-counter-item .counter-icon {
    font-size: 13px;
}

.branch-counter-item .counter-label {
    color: rgba(255,255,255,0.85);
}

.branch-counter-item .counter-value {
    background: rgba(255,255,255,0.25);
    padding: 1px 8px;
    border-radius: 10px;
    font-weight: 800;
    font-size: 13px;
    min-width: 28px;
    text-align: center;
}

.branch-counter-total {
    background: rgba(67, 97, 238, 0.5);
    border: 1px solid rgba(67, 97, 238, 0.7);
}

.branch-counter-total .counter-value {
    background: rgba(67, 97, 238, 0.7);
}

@media (max-width: 768px) {
    .branch-counters {
        width: 100%;
        justify-content: center;
        margin-top: 8px;
    }
    .branch-counter-item {
        font-size: 11px;
        padding: 3px 8px;
    }
}

/* Responsive */

@media (max-width: 768px) {

    .user-info-bar {
        padding: 10px 15px;
    }

    .user-info-bar .left {
        width: 100%;
        justify-content: center;
        text-align: center;
        flex-wrap: wrap;
    }

    .mobile-menu-btn {
        display: block;
    }

    .user-info-bar .right {
        width: 100%;
        display: none;
        flex-direction: column;
        align-items: stretch;
        margin-top: 10px;
        gap: 5px;
    }

    .user-info-bar .right.show {
        display: flex;
    }

    .user-info-bar .right a {
        width: 100%;
        text-align: center;
        padding: 12px;
        background: rgba(255,255,255,0.08);
    }
}



.branch-tree{
    display:flex;
    gap:25px;
    justify-content:center;
    align-items:flex-start;
    flex-wrap:wrap;
}

.branch-node{
    display:flex;
    flex-direction:column;
    align-items:center;
    position:relative;
}

.transfer-node{
    margin-top:18px;
    position:relative;

    display:flex;
    align-items:center;
    gap:5px;
    background:rgba(245,158,11,.15);
    padding:4px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:700;
    border:1px solid rgba(245,158,11,.3);
}

.transfer-node::before{
    content:'';
    position:absolute;
    width:2px;
    height:18px;
    background:#94a3b8;
    top:-18px;
    left:50%;
    transform:translateX(-50%);
}

.tree-connector{
    width:320px;
    height:25px;
    margin:0 auto;
    border-top:2px solid #94a3b8;
    border-left:2px solid #94a3b8;
    border-right:2px solid #94a3b8;
    border-radius:10px 10px 0 0;
}

.transfer-total{
    margin:0 auto;
    width:fit-content;
    background:rgba(245,158,11,.2);
    border:1px solid rgba(245,158,11,.4);
}

.tree-down{
    width:2px;
    height:20px;
    background:#94a3b8;
    margin:0 auto;
}



.knockout-tree{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:40px;
}

.round{
    display:flex;
    flex-direction:column;
}

.round1{
    gap:28px;
}

.round2{
    gap:28px;
}

.match-card{
    position:relative;
}

/* خطوط من الفروع إلى نقل الخدمات */

.round1 .match-card::after{
    content:'';
    position:absolute;
    top:50%;
    left:-40px;
    width:40px;
    height:2px;
    background:#94a3b8;
}

/* خطوط من نقل الخدمات لإجمالي النقل */

.round2 .match-card::after{
    content:'';
    position:absolute;
    top:50%;
    left:-40px;
    width:40px;
    height:2px;
    background:#94a3b8;
}

.round2{
    position:relative;
}

.round2::before{
    content:'';
    position:absolute;
    left:-40px;
    top:20px;
    bottom:20px;
    width:2px;
    background:#94a3b8;
}

.round3{
    position:relative;
}

.round3::before{
    content:'';
    position:absolute;
    left:-40px;
    top:50%;
    width:40px;
    height:2px;
    background:#94a3b8;
}

/* من إجمالي النقل للإجمالي */

.round4{
    position:relative;
}

.round4::before{
    content:'';
    position:absolute;
    left:-40px;
    top:50%;
    width:40px;
    height:2px;
    background:#94a3b8;
}

.transfer-total{
    background:rgba(245,158,11,.15);
    border:1px solid rgba(245,158,11,.35);
}


.transfer-node{
    display:flex;
    align-items:center;
    gap:5px;

    background:rgba(255,255,255,0.12);
    padding:4px 12px;
    border-radius:20px;

    font-size:12px;
    font-weight:700;
    white-space:nowrap;

    transition:.3s;
    border:1px solid rgba(255,255,255,0.15);

    margin-top:18px;
    position:relative;
}

.transfer-node:hover{
    background:rgba(255,255,255,0.2);
    transform:translateY(-1px);
}

.transfer-node .counter-value{
    background:rgba(255,255,255,0.25);
    padding:1px 8px;
    border-radius:10px;
    font-weight:800;
    font-size:13px;
    min-width:28px;
    text-align:center;
}

/* =========================
   Mobile Knockout Tree
========================= */
@media (max-width:768px){

    .knockout-tree{
        flex-direction:column;
        align-items:center;
        gap:15px;
        width:100%;
    }

    .round{
        width:100%;
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:10px;
        position:relative;
    }

    /* إزالة خطوط الديسكتوب */
    .round1 .match-card::after,
    .round2 .match-card::after,
    .round2::before,
    .round3::before,
    .round4::before{
        display:none !important;
    }

    /* حجم الكروت */
    .branch-counter-item,
    .transfer-node{
        min-width:220px;
        justify-content:center;
        margin:0 !important;
    }

    /* خطوط الفروع */
    .round1 .match-card{
        position:relative;
    }

    .round1 .match-card::before{
        content:'';
        position:absolute;
        left:50%;
        transform:translateX(-50%);
        top:100%;
        width:2px;
        height:15px;
        background:#94a3b8;
    }

    /* خطوط نقل الخدمات */
    .round:nth-child(2) .match-card{
        position:relative;
    }

    .round:nth-child(2) .match-card::after{
        content:'';
        position:absolute;
        left:50%;
        transform:translateX(-50%);
        top:100%;
        width:2px;
        height:15px;
        background:#94a3b8;
        display:block !important;
    }

    /* إجمالي نقل الخدمات */
    .transfer-total{
        position:relative;
        margin:10px 0 !important;
    }

    .transfer-total::after{
        content:'';
        position:absolute;
        left:50%;
        transform:translateX(-50%);
        top:100%;
        width:2px;
        height:18px;
        background:#94a3b8;
    }

    /* الإجمالي */
    .branch-counter-total{
        margin-top:18px !important;
    }

}

.btn-warning{
    background:#f59e0b;
    color:#fff;
}

.btn-primary{
    background:#3b82f6;
    color:#fff;
}
    </style>
</head>

<body>
    <!-- شريط معلومات المستخدم -->
<div class="user-info-bar">

    <div class="left">
        <span>
            <i class="fas fa-user-circle"></i>
            <?php echo htmlspecialchars($current_user['full_name']); ?>
        </span>

        <span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;">
            <?php echo htmlspecialchars($current_user['role_name']); ?>
        </span>
    </div>

    <!-- عدادات الفروع -->
    <!-- عدادات الفروع -->
    <div class="knockout-tree">

        <!-- العمود الأول : الفروع -->
        <div class="round round1">

            <div class="branch-counter-item match-card">
                <i class="fas fa-building counter-icon" style="color:#60a5fa"></i>
                <span class="counter-label">ينبع:</span>
                <span class="counter-value"><?= $yanbuCount ?></span>
            </div>

            <div class="branch-counter-item match-card">
                <i class="fas fa-city counter-icon" style="color:#f472b6"></i>
                <span class="counter-label">جدة:</span>
                <span class="counter-value"><?= $jeddahCount ?></span>
            </div>

            <div class="branch-counter-item match-card">
                <i class="fas fa-landmark counter-icon" style="color:#a78bfa"></i>
                <span class="counter-label">الرياض:</span>
                <span class="counter-value"><?= $riyadhCount ?></span>
            </div>

        </div>

        <!-- العمود الثاني : نقل الخدمات -->
        <div class="round round3">

            <div class="transfer-node match-card" style="margin-top:5px !important">
                <i class="fas fa-exchange-alt counter-icon" style="color:#f59e0b"></i>
                <span class="counter-label">نقل الخدمات:</span>
                <span class="counter-value"><?= $yanbuTransfer ?></span>
            </div>

            <div class="transfer-node match-card" style="margin-top:40px !important">
                <i class="fas fa-exchange-alt counter-icon" style="color:#f59e0b"></i>
                <span class="counter-label">نقل الخدمات:</span>
                <span class="counter-value"><?= $jeddahTransfer ?></span>
            </div>

            <div class="transfer-node match-card" style="margin-top:45px !important">
                <i class="fas fa-exchange-alt counter-icon" style="color:#f59e0b"></i>
                <span class="counter-label">نقل الخدمات:</span>
                <span class="counter-value"><?= $riyadhTransfer ?></span>
            </div>

        </div>

        <!-- العمود الثالث : التأجير -->
        <div class="round round3">

            <div class="transfer-node match-card" style="margin-top:5px !important">
                <i class="fas fa-key counter-icon" style="color:#10b981"></i>
                <span class="counter-label">التأجير:</span>
                <span class="counter-value"><?= $yanbuRental ?></span>
            </div>

            <div class="transfer-node match-card" style="margin-top:40px !important">
                <i class="fas fa-key counter-icon" style="color:#10b981"></i>
                <span class="counter-label">التأجير:</span>
                <span class="counter-value"><?= $jeddahRental ?></span>
            </div>

            <div class="transfer-node match-card" style="margin-top:45px !important">
                <i class="fas fa-key counter-icon" style="color:#10b981"></i>
                <span class="counter-label">التأجير:</span>
                <span class="counter-value"><?= $riyadhRental ?></span>
            </div>

        </div>

        <!-- العمود الرابع : الإجماليات -->
        <div class="round round3">

            <div class="branch-counter-item transfer-total">
                <i class="fas fa-random counter-icon" style="color:#fbbf24"></i>
                <span class="counter-label">إجمالي نقل الخدمات:</span>
                <span class="counter-value"><?= $totalTransfer ?></span>
            </div>

            <div class="branch-counter-item transfer-total" style="margin-top:20px;">
                <i class="fas fa-key counter-icon" style="color:#10b981"></i>
                <span class="counter-label">إجمالي التأجير:</span>
                <span class="counter-value"><?= $totalRental ?></span>
            </div>

        </div>

        <!-- العمود الخامس : الإجمالي الكلي -->
        <div class="round round2">

            <div class="branch-counter-item branch-counter-total">
                <i class="fas fa-users counter-icon" style="color:#fff"></i>
                <span class="counter-label">الإجمالي:</span>
                <span class="counter-value"><?= $totalCount ?></span>
            </div>

        </div>

    </div>
    
    <button class="mobile-menu-btn" onclick="toggleUserMenu()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="right" id="userMenu">

        <?php if (hasPermission('view_users', $pdo)): ?>
            <a href="manage_users.php">
                <i class="fas fa-users"></i>
                المستخدمين
            </a>
        <?php endif; ?>

        <?php if (hasPermission('view_roles', $pdo)): ?>
            <a href="manage_roles.php">
                <i class="fas fa-shield-alt"></i>
                الأدوار
            </a>
        <?php endif; ?>

        <?php if (hasPermission('view_activity_logs', $pdo)): ?>
            <a href="activity_logs.php">
                <i class="fas fa-history"></i>
                السجلات
            </a>
        <?php endif; ?>

        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i>
            تسجيل خروج
        </a>

    </div>

</div>

    <div class="dashboard-container">
        <header>
            <div class="header-content" style="flex-direction: column; align-items: stretch; gap: 0;">
                <!-- Row 1: Title and Navigation -->
                <div class="header-row-1">
                    <h1>إدارة سكن العاملات</h1>
                    <div class="header-nav">
                        <?php if (hasPermission('view_offices', $pdo)): ?>
                            <button id="manageOfficesBtn" class="btn btn-secondary"><i class="fas fa-building"></i> إدارة المكاتب</button>
                        <?php endif; ?>
                        <?php if (hasPermission('view_archive', $pdo)): ?>
                            <a href="archive.php" class="btn btn-success"><i class="fas fa-box-open"></i> الأرشيف</a>
                        <?php endif; ?>
                        <?php if (hasPermission('view_all_workers_report', $pdo)): ?>
                            <a href="all_workers.php" class="btn btn-success" style="background: #3f37c9; color: white; border-color: #3f37c9;"><i class="fas fa-users"></i> تقرير شامل للعاملات</a>
                        <?php endif; ?>
                        <?php if (hasPermission('view_admin_report', $pdo)): ?>
                            <a href="admin_report.php" class="btn btn-info" style="background:#0ea5a4; color:white; border-color:#0ea5a4;"><i class="fas fa-file-alt"></i> تقرير إداري</a>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Row 2: Action Buttons -->
                <div class="header-row-2">
                    <?php if (hasPermission('import_workers', $pdo)): ?>
                        <button class="btn btn-success" onclick="excelImporter.openModal()"><i class="fas fa-file-excel"></i> استيراد Excel</button>
                    <?php endif; ?>
                    <?php if (hasPermission('add_worker', $pdo)): ?>
                        <button id="bulkAddBtn" class="btn btn-primary"><i class="fas fa-plus-circle"></i> إضافة </button>
                    <?php endif; ?>
                    <?php if (hasPermission('edit_worker', $pdo)): ?>
                        <button id="bulkEditBtn" class="btn btn-warning" disabled><i class="fas fa-edit"></i> تعديل محدد</button>
                    <?php endif; ?>
                    <?php if (hasPermission('delete_worker', $pdo)): ?>
                        <button id="bulkDeleteBtn" class="btn btn-danger" disabled><i class="fas fa-trash"></i> حذف محدد</button>
                    <?php endif; ?>
                    <?php if (hasPermission('archive_worker', $pdo)): ?>
                        <button id="bulkArchiveBtn" class="btn btn-secondary" disabled><i class="fas fa-archive"></i> أرشفة المحدد</button>
                    <?php endif; ?>
                    <div class="range-selector" style="display: flex; align-items: center; gap: 4px; background: rgba(255,255,255,0.15); padding: 4px 8px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.2);">
                        <span style="font-size: 11px; font-weight: bold; color: white;">من:</span>
                        <input type="number" id="rangeFrom" min="1" style="width: 45px; height: 28px; border-radius: 6px; border: none; text-align: center; font-size: 12px; font-weight: bold;">
                        <span style="font-size: 11px; font-weight: bold; color: white;">إلى:</span>
                        <input type="number" id="rangeTo" min="1" style="width: 45px; height: 28px; border-radius: 6px; border: none; text-align: center; font-size: 12px; font-weight: bold;">
                        <button id="applyRangeSelect" style="display:none;"></button>
                    </div>
                    <?php if (hasPermission('export_workers', $pdo)): ?>
                        <button id="exportExcelBtn" class="btn btn-success"><i class="fas fa-file-excel"></i> تصدير Excel</button>
                        <button id="exportPdfBtn" class="btn btn-success"><i class="fas fa-file-pdf"></i> تصدير PDF</button>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="print-header" style="display: none;">
            <h1 style="text-align: center; margin-bottom: 20px; border-bottom: 3px solid #000; padding-bottom: 10px;">
                تقرير بيانات سكن العاملات</h1>
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-weight: bold;">
                <span>تاريخ التقرير:
                    <?php echo date('Y-m-d'); ?>
                </span>
                <span>الفرع: الإدارة العامة</span>
            </div>
        </div>

        <section class="filters-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="globalSearch" placeholder="بحث شامل في جميع الأعمدة...">
            </div>
            <div class="filter-grid">
                <select id="filterGuarantee">
                    <option value="">كل حالات الضمان</option>
                    <option value="داخل الضمان">داخل الضمان</option>
                    <option value="خارج الضمان">خارج الضمان</option>
                </select>
                <select id="filterNationality">
                    <option value="">كل الجنسيات</option>
                    <option value="اثيوبيا">اثيوبيا</option>
                    <option value="بوروندي">بوروندي</option>
                    <option value="الفلبين">الفلبين</option>
                    <option value="سريلانكا">سريلانكا</option>
                    <option value="اوغندا">اوغندا</option>
                    <option value="كينيا">كينيا</option>
                    <option value="الهند">الهند</option>
                    <option value="بنجلاديش">بنجلاديش</option>
                </select>
                <select id="filterHousing">
                    <option value="">كل المواقع</option>
                    <option value="الرياض">الرياض</option>
                    <option value="جدة">جدة</option>
                    <option value="ينبع">ينبع</option>
                </select>
                <select id="filterOffice">
                    <option value="">كل المكاتب</option>
                </select>
                <select id="filterAction">
                    <option value="">كل الإجراءات</option>
                    <option value="السكن">السكن</option>
                    <option value="نقل خدمات">نقل خدمات</option>
                    <option value="خروج نهائي">خروج نهائي</option>
                    <option value="تأجير">تأجير</option>
                    <option value="هروب">هروب</option>
                    <option value="اخرى">اخرى</option>
                </select>
                <select id="filterSettlement">
                    <option value="">كل حالات التسوية</option>
                    <option value="لم يتم الخصم">لم يتم الخصم</option>
                    <option value="تم الخصم">تم الخصم</option>
                    <option value="تم الخصم جزئياً">تم الخصم جزئياً</option>
                    <option value="لا تخصم">لا تخصم</option>
                </select>
            </div>
        </section>

        <main class="table-responsive">
            <table id="workerTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th style="width: 40px;">#</th>
                        <th>العاملة</th>
                        <th>الجواز</th>
                        <th>الجنسية</th>
                        <th>المكتب</th>
                        <th>العميل</th>
                        <th>الهوية</th>
                        <th>الضمان</th>
                        <th>الموقع</th>
                        <th>دخول المملكة</th>
                        <th>(In KSA)</th>
                        <th>دخول الإيواء</th>
                        <th>(In Housing)</th>
                        <th>الراتب</th>
                        <th>الإجراء</th>
                        <th>التذكرة</th>
                        <th>التسوية</th>
                        <th style="width: 80px;" class="no-print">تفاصيل</th>
                    </tr>
                </thead>
                <tbody id="workerBody"></tbody>
            </table>
        </main>
    </div>

    <!-- Standalone Excel Modal -->
    <div id="excelImportModal" class="excel-modal">
        <div class="excel-modal-content">
            <div
                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">
                <h2 style="margin:0;"><i class="fas fa-file-excel" style="color:#10b981;"></i> استيراد ذكي من Excel</h2>
                <div style="display:flex; gap:10px;">
                    <button class="btn btn-secondary" style="padding:5px 15px; font-size:12px; background:#e2e8f0;"
                        onclick="excelImporter.downloadSample()">
                        <i class="fas fa-download"></i> تحميل ملف نموذجي (Sample)
                    </button>
                    <span style="font-size:30px; cursor:pointer;" onclick="excelImporter.closeModal()">&times;</span>
                </div>
            </div>

            <div
                style="background:#f1f5f9; padding:15px; border-radius:12px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-weight:800; color:#4361ee;">هل تريد استيراد هذه البيانات إلى الأرشيف؟</div>
                <select id="excelImportTarget"
                    style="padding:10px 20px; border-radius:10px; border:1px solid #ddd; outline:none; font-weight:800; cursor:pointer;">
                    <option value="0">لا (إلى الجدول الرئيسي)</option>
                    <option value="1">نعم (إلى الأرشيف مباشرة)</option>
                </select>
            </div>

            <div id="excelDropZone" class="excel-upload-zone"
                onclick="document.getElementById('excelFileInput').click()">
                <i class="fas fa-cloud-upload-alt" style="font-size:60px; color:#4361ee;"></i>
                <p style="font-size:18px; font-weight:bold; margin-top:10px;">اسحب ملف الإكسيل هنا أو اضغط للاختيار</p>
                <input type="file" id="excelFileInput" accept=".xlsx, .xls" style="display:none;"
                    onchange="excelImporter.handleFile(this.files[0])">
            </div>

            <div id="excelPreviewSection" style="display:none; margin-top:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <p style="font-weight:800; color:#4361ee; margin:0;">معاينة البيانات (تم قراءة <span
                            id="excelRowCount">0</span> سجل):</p>
                    <div style="display:flex; gap:10px;">
                        <button id="showAllPreviewBtn" class="btn btn-secondary"
                            style="display:none; padding:5px 15px; font-size:12px;"
                            onclick="excelImporter.showFullPreview()">عرض كل السجلات</button>
                        <button class="btn btn-danger" style="padding:5px 15px; font-size:12px;"
                            onclick="excelImporter.reset()">حذف الملف ورفع غيره</button>
                    </div>
                </div>
                <div class="excel-preview-table-wrapper">
                    <table class="excel-preview-table">
                        <thead>
                            <tr id="excelPreviewHeader"></tr>
                        </thead>
                        <tbody id="excelPreviewBody"></tbody>
                    </table>
                </div>
            </div>

            <div
                style="margin-top:25px; border-top:1px solid #eee; padding-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                <button class="btn btn-secondary" onclick="excelImporter.closeModal()">إلغاء</button>
                <button id="confirmExcelImportBtn" class="btn btn-primary" disabled
                    onclick="excelImporter.startImport()">تأكيد وحفظ البيانات</button>
            </div>
        </div>
    </div>

    <!-- Re-instating all original modals for app.js functionality -->
    <div id="addModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2>إضافة عاملات (نظام الصفين الذكي)</h2><span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="addBody" class="worker-group-list"></div>
                <div class="modal-actions"><button id="addRowBtn" style="margin-top:10px"
                        class="btn btn-secondary">إضافة عاملة جديدة</button></div>
            </div>
            <div class="modal-footer"><button id="saveBulkAdd" class="btn btn-primary">حفظ الكل</button></div>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2>تعديل بيانات العاملات المختارة</h2><span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="editBody" class="worker-group-list"></div>
            </div>
            <div class="modal-footer"><button id="saveBulkEdit" class="btn btn-warning">حفظ التعديلات</button></div>
        </div>
    </div>

    <div id="officesModal" class="modal">
        <div class="modal-content large" style="max-width: 1000px;">
            <div class="modal-header">
                <h2><i class="fas fa-building"></i> إدارة المكاتب</h2><span class="close">&times;</span>
            </div>
            <div class="modal-body" style="padding: 20px 30px;">
                <div style="padding: 0 10px; margin-bottom: 25px;">
                    <div class="header-actions"
                        style="display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; background: #f1f5f9; border-radius: 12px; gap: 15px; border: 1px solid #e2e8f0; flex-wrap: nowrap; width: 100%; margin: 0;">
                        
                        <div class="search-box" style="flex: 1; min-width: 180px; margin: 0; background: white; border: 2px solid #cbd5e1; border-radius: 10px; height: 40px; position: relative;">
                            <i class="fas fa-search" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 14px;"></i>
                            <input type="text" id="officeSearchInput" placeholder="بحث عن مكتب..." style="border: none; outline: none; padding: 0 45px 0 15px; width: 100%; height: 100%; font-size: 13px; font-weight: 600; color: #1e293b; background: transparent; text-align: right;">
                        </div>

                        <button id="showAddOfficesBtn" class="btn btn-primary" style="height: 42px; padding: 0 15px; font-size: 13px; white-space: nowrap;"><i class="fas fa-plus"></i> إضافة</button>
                        <button class="btn btn-success" onclick="excelImporter.openOfficeModal()" style="height: 42px; padding: 0 15px; font-size: 13px; white-space: nowrap;"><i class="fas fa-file-excel"></i> استيراد</button>
                        <button id="bulkEditOfficesBtn" class="btn btn-warning" disabled style="height: 42px; padding: 0 15px; font-size: 13px; white-space: nowrap;"><i class="fas fa-edit"></i> تعديل</button>
                        <button id="bulkDeleteOfficesBtn" class="btn btn-danger" disabled style="height: 42px; padding: 0 15px; font-size: 13px; white-space: nowrap;"><i class="fas fa-trash"></i> حذف</button>
                        
                        <div class="range-selector" style="display: flex; align-items: center; gap: 5px; background: white; padding: 2px 8px; border-radius: 8px; border: 1px solid #cbd5e1; margin-right: 5px;">
                            <span style="font-size: 11px; font-weight: bold; color: #64748b;">من:</span>
                            <input type="number" id="officeRangeFrom" min="1" style="width: 40px; height: 28px; border-radius: 5px; border: 1px solid #cbd5e1; text-align: center; font-size: 11px;">
                            <span style="font-size: 11px; font-weight: bold; color: #64748b;">إلى:</span>
                            <input type="number" id="officeRangeTo" min="1" style="width: 40px; height: 28px; border-radius: 5px; border: 1px solid #cbd5e1; text-align: center; font-size: 11px;">
                        </div>

                        <div style="display: flex; align-items: center; gap: 5px; padding-right: 10px; border-right: 2px solid #e2e8f0; height: 30px;">
                            <input type="checkbox" id="selectAllOffices" style="width: 16px; height: 16px; cursor: pointer;">
                            <label for="selectAllOffices" style="font-weight: bold; cursor: pointer; font-size: 12px; white-space: nowrap;">الكل</label>
                        </div>
                    </div>
                </div>
                <div id="officesBody" class="offices-grid"></div>
            </div>
        </div>
    </div>

    <!-- Standalone Office Import Modal -->
    <div id="officeImportModal" class="excel-modal">
        <div class="excel-modal-content" style="max-width: 600px;">
            <div
                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">
                <h2 style="margin:0;"><i class="fas fa-building" style="color:#10b981;"></i> استيراد مكاتب من Excel</h2>
                <div style="display:flex; gap:10px;">
                    <button class="btn btn-secondary" style="padding:5px 15px; font-size:12px; background:#e2e8f0;"
                        onclick="excelImporter.downloadOfficeSample()">
                        <i class="fas fa-download"></i> نموذج المكاتب
                    </button>
                    <span style="font-size:30px; cursor:pointer;"
                        onclick="excelImporter.closeOfficeModal()">&times;</span>
                </div>
            </div>

            <div id="officeDropZone" class="excel-upload-zone"
                onclick="document.getElementById('officeExcelFileInput').click()">
                <i class="fas fa-cloud-upload-alt" style="font-size:60px; color:#4361ee;"></i>
                <p style="font-size:16px; font-weight:bold; margin-top:10px;">اسحب ملف المكاتب هنا أو اضغط للاختيار</p>
                <input type="file" id="officeExcelFileInput" accept=".xlsx, .xls" style="display:none;"
                    onchange="excelImporter.handleOfficeFile(this.files[0])">
            </div>

            <div id="officePreviewSection" style="display:none; margin-top:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <p style="font-weight:800; color:#4361ee; margin:0;">معاينة المكاتب (<span
                            id="officeRowCount">0</span> مكتب):</p>
                    <button class="btn btn-danger" style="padding:5px 15px; font-size:12px;"
                        onclick="excelImporter.resetOfficeImport()">حذف</button>
                </div>
                <div class="excel-preview-table-wrapper">
                    <table class="excel-preview-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم المكتب</th>
                            </tr>
                        </thead>
                        <tbody id="officePreviewBody"></tbody>
                    </table>
                </div>
            </div>

            <div
                style="margin-top:25px; border-top:1px solid #eee; padding-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                <button class="btn btn-secondary" onclick="excelImporter.closeOfficeModal()">إلغاء</button>
                <button id="confirmOfficeImportBtn" class="btn btn-primary" disabled
                    onclick="excelImporter.startOfficeImport()">تأكيد وحفظ المكاتب</button>
            </div>
        </div>
    </div>

    <div id="addOfficesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="officeModalTitle">إضافة مكاتب جديدة</h2><span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="officeInputsContainer" class="worker-group-list"></div>
                <div class="modal-actions"><button id="addOfficeInputBtn" class="btn btn-secondary">إضافة حقل مكتب
                        آخر</button></div>
            </div>
            <div class="modal-footer"><button id="saveOfficesBtn" class="btn btn-primary">حفظ الكل</button></div>
        </div>
    </div>

    <div id="detailModal" class="modal">
        <div class="modal-content detail-modal-content">
            <div class="modal-header">
                <h2 id="detailTitle">تفاصيل</h2><span class="close"
                    onclick="document.getElementById('detailModal').style.display='none'">&times;</span>
            </div>
            <div class="modal-body">
                <p id="detailContent"></p>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary"
                    onclick="document.getElementById('detailModal').style.display='none'">إغلاق</button></div>
        </div>
    </div>

    <div class="modal fade" id="rentalModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        بيانات التأجير
                    </h5>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="worker_id">

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>اسم المستأجر</label>
                            <input type="text"
                                id="renter_name"
                                class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>جوال المستأجر</label>
                            <input type="text"
                                id="renter_phone"
                                class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>تاريخ خروج العاملة</label>
                            <input type="date"
                                id="departure_date"
                                class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>بداية التأجير</label>
                            <input type="date"
                                id="rent_start_date"
                                class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>نهاية التأجير</label>
                            <input type="date"
                                id="rent_end_date"
                                class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>عودة العاملة</label>
                            <input type="date"
                                id="returned_date"
                                class="form-control">
                        </div>

                        <div class="col-md-12">
                            <label>ملاحظات</label>
                            <textarea id="notes"
                                    class="form-control"></textarea>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button class="btn btn-primary"
                            id="saveRental">

                        حفظ

                    </button>

                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="rentalProfileModal">
    <div class="modal-dialog modal-xl">
            <div class="modal-content">

                <div class="modal-header">
                    <h5>سجل التأجير</h5>
                </div>

                <div class="modal-body" id="rentalProfileBody">

                </div>

            </div>
        </div>
    </div>
    <div id="toastContainer" class="toast-container"></div>
    <script>const PAGE_TYPE = 'main';</script>
    <script src="assets/js/app.js?v=1.9"></script>
    <!-- Standalone Excel Script (Force Refresh) -->
    <script src="assets/js/excel_importer.js?v=1.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
function toggleUserMenu() {
    document.getElementById('userMenu').classList.toggle('show');
}

$(document).on('click', '#saveRental', function() {

    $.ajax({
        url: 'save_rental.php',
        type: 'POST',
        dataType: 'json',
        data: {
            worker_id: $('#worker_id').val(),
            renter_name: $('#renter_name').val(),
            renter_phone: $('#renter_phone').val(),
            departure_date: $('#departure_date').val(),
            rent_start_date: $('#rent_start_date').val(),
            rent_end_date: $('#rent_end_date').val(),
            returned_date: $('#returned_date').val(),
            notes: $('#notes').val()
        },

        success: function(res) {

            if(res.success){

                Swal.fire({
                    icon: 'success',
                    title: 'تم الحفظ',
                    text: 'تم حفظ بيانات التأجير بنجاح'
                });

                $('#rentalModal').modal('hide');

            }else{

                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'فشل الحفظ'
                });

            }
        },

        error: function() {

            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'تعذر الاتصال بالخادم'
            });

        }
    });

});
</script>
</body>

</html>