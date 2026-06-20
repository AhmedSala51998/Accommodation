<?php
// api.php
session_start();
require_once 'config.php';
require_once 'helpers.php';

header('Content-Type: application/json');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$archived_param = $_GET['archived'] ?? '0';

switch ($action) {
    case 'list':
        try {
            if (!hasPermission('view_workers', $pdo)) {
                throw new Exception('Permission denied');
            }

            if ($archived_param === 'all') {
                $stmt = $pdo->prepare("SELECT *, 
                    DATEDIFF(CURRENT_DATE, entry_date) as days_in_ksa,
                    DATEDIFF(CURRENT_DATE, housing_entry_date) as days_in_housing 
                    FROM workers ORDER BY id DESC");
                $stmt->execute();
            } else {
                $is_archived = $archived_param == '1' ? 1 : 0;
                $stmt = $pdo->prepare("SELECT *, 
                    DATEDIFF(CURRENT_DATE, entry_date) as days_in_ksa,
                    DATEDIFF(CURRENT_DATE, housing_entry_date) as days_in_housing 
                    FROM workers WHERE is_archived = ? ORDER BY id DESC");
                $stmt->execute([$is_archived]);
            }
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'list_all':
        try {
            if (!hasPermission('view_workers', $pdo)) {
                throw new Exception('Permission denied');
            }

            $stmt = $pdo->prepare("SELECT *, 
                DATEDIFF(CURRENT_DATE, entry_date) as days_in_ksa,
                DATEDIFF(CURRENT_DATE, housing_entry_date) as days_in_housing 
                FROM workers ORDER BY id DESC");
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'bulk_add':
        if (!hasPermission('add_worker', $pdo)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            break;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !is_array($data)) {
            echo json_encode(['success' => false, 'message' => 'No data provided']);
            break;
        }

        try {
            $pdo->beginTransaction();
        $sql = "INSERT INTO workers (worker_name, passport, nationality, office, customer, national_id, guarantee_status, housing_location, entry_date, housing_entry_date, salary, status_description, action_type, ticket_info, settlement_status, financial_notes, mobile, receiver, receiver_other, passport_missing, passport_missing_note, case_status, is_archived) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            $count = 0;
            foreach ($data as $row) {
                $stmt->execute([
                    $row['worker_name'] ?? '',
                    $row['passport'] ?? '',
                    $row['nationality'] ?? '',
                    $row['office'] ?? '',
                    $row['customer'] ?? '',
                    $row['national_id'] ?? '',
                    $row['guarantee_status'] ?? 'داخل الضمان',
                    $row['housing_location'] ?? 'الرياض',
                    ($row['entry_date'] && $row['entry_date'] !== '') ? $row['entry_date'] : null,
                    ($row['housing_entry_date'] && $row['housing_entry_date'] !== '') ? $row['housing_entry_date'] : null,
                    $row['salary'] ?: 0,
                    $row['status_description'] ?? '',
                    $row['action_type'] ?? 'السكن',
                    $row['ticket_info'] ?? '',
                    $row['settlement_status'] ?? 'لم يتم الخصم',
                        $row['financial_notes'] ?? '',
                        $row['mobile'] ?? null,
                        $row['receiver'] ?? null,
                        $row['receiver_other'] ?? null,
                        $row['passport_missing'] ?? 'لا',
                        $row['passport_missing_note'] ?? null,
                        $row['case_status'] ?? null,
                        $row['is_archived'] ?? 0
                ]);
                $last_id = $pdo->lastInsertId();
                logActivity($_SESSION['user_id'], 'ADD', 'workers', $last_id, null, $row, "إضافة عاملة جديدة: {$row['worker_name']}", $pdo);
                $count++;
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'count' => $count]);
        } catch (Exception $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_update':
        if (!hasPermission('edit_worker', $pdo)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            break;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !is_array($data)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            break;
        }

        try {
            $pdo->beginTransaction();
        $sql = "UPDATE workers SET 
            worker_name = ?, passport = ?, nationality = ?, office = ?, customer = ?, 
            national_id = ?, guarantee_status = ?, housing_location = ?, entry_date = ?, 
            housing_entry_date = ?, salary = ?, status_description = ?, action_type = ?, 
            ticket_info = ?, settlement_status = ?, financial_notes = ?, mobile = ?, receiver = ?, receiver_other = ?, passport_missing = ?, passport_missing_note = ?, case_status = ? 
            WHERE id = ?";
            $stmt = $pdo->prepare($sql);

            $affectedCount = 0;
            foreach ($data as $row) {
                $stmt->execute([
                    $row['worker_name'] ?? '',
                    $row['passport'] ?? '',
                    $row['nationality'] ?? '',
                    $row['office'] ?? '',
                    $row['customer'] ?? '',
                    $row['national_id'] ?? '',
                    $row['guarantee_status'] ?? 'داخل الضمان',
                    $row['housing_location'] ?? 'الرياض',
                    ($row['entry_date'] && $row['entry_date'] !== '') ? $row['entry_date'] : null,
                    ($row['housing_entry_date'] && $row['housing_entry_date'] !== '') ? $row['housing_entry_date'] : null,
                    $row['salary'] ?: 0,
                    $row['status_description'] ?? '',
                    $row['action_type'] ?? 'السكن',
                    $row['ticket_info'] ?? '',
                        $row['settlement_status'] ?? 'لم يتم الخصم',
                        $row['financial_notes'] ?? '',
                        $row['mobile'] ?? null,
                        $row['receiver'] ?? null,
                        $row['receiver_other'] ?? null,
                        $row['passport_missing'] ?? 'لا',
                        $row['passport_missing_note'] ?? null,
                        $row['case_status'] ?? null,
                        $row['id']
                ]);
                logActivity($_SESSION['user_id'], 'EDIT', 'workers', $row['id'], null, $row, "تعديل بيانات عاملة: {$row['worker_name']}", $pdo);
                $affectedCount += $stmt->rowCount();
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'affected_count' => $affectedCount]);
        } catch (Exception $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_delete':
        if (!hasPermission('delete_worker', $pdo)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            break;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['ids'])) {
            echo json_encode(['success' => false, 'message' => 'No IDs provided']);
            break;
        }

        try {
            $ids = $data['ids'];
            if (empty($ids)) {
                echo json_encode(['success' => true]);
                break;
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            // حصول على بيانات العاملات قبل الحذف
            $stmt = $pdo->prepare("SELECT worker_name FROM workers WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $workers = $stmt->fetchAll();
            
            $stmt = $pdo->prepare("DELETE FROM workers WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            
            // تسجيل النشاط
            foreach ($workers as $worker) {
                logActivity($_SESSION['user_id'], 'DELETE', 'workers', null, $worker, null, "حذف عاملة: {$worker['worker_name']}", $pdo);
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_archive':
        if (!hasPermission('archive_worker', $pdo)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            break;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['ids'])) {
            echo json_encode(['success' => false, 'message' => 'No IDs provided']);
            break;
        }

        try {
            $ids = $data['ids'];
            $status = $_GET['status'] ?? 1; // 1 to archive, 0 to unarchive
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("UPDATE workers SET is_archived = ? WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$status], $ids));
            
            // تسجيل النشاط
            foreach ($ids as $id) {
                logActivity($_SESSION['user_id'], 'ARCHIVE', 'workers', $id, null, ['is_archived' => $status], ($status == 1) ? "أرشفة عاملة" : "استعادة عاملة من الأرشيف", $pdo);
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // Office Management
    case 'list_offices':
        try {
            $stmt = $pdo->query("SELECT * FROM offices ORDER BY id DESC");
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'bulk_add_offices':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !is_array($data)) {
            echo json_encode(['success' => false, 'message' => 'No data provided']);
            break;
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO offices (name) VALUES (?)");
            foreach ($data as $row) {
                if (!empty($row['name'])) {
                    $stmt->execute([$row['name']]);
                }
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_update_offices':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !is_array($data)) {
            echo json_encode(['success' => false, 'message' => 'No data provided']);
            break;
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE offices SET name = ? WHERE id = ?");
            foreach ($data as $row) {
                if (!empty($row['name']) && !empty($row['id'])) {
                    $stmt->execute([$row['name'], $row['id']]);
                }
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_delete_offices':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['ids'])) {
            echo json_encode(['success' => false, 'message' => 'No IDs provided']);
            break;
        }

        try {
            $ids = $data['ids'];
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM offices WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>