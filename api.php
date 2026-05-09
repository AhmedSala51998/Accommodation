<?php
// api.php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        try {
            $stmt = $pdo->query("SELECT *, 
                DATEDIFF(CURRENT_DATE, entry_date) as days_in_ksa,
                DATEDIFF(CURRENT_DATE, housing_entry_date) as days_in_housing 
                FROM workers ORDER BY id DESC");
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'bulk_add':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !is_array($data)) {
            echo json_encode(['success' => false, 'message' => 'No data provided']);
            break;
        }

        try {
            $pdo->beginTransaction();
            $sql = "INSERT INTO workers (worker_name, passport, nationality, office, customer, national_id, guarantee_status, housing_location, entry_date, housing_entry_date, salary, status_description, action_type, ticket_info, settlement_status, financial_notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

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
                    $row['entry_date'] ?: null,
                    $row['housing_entry_date'] ?: null,
                    $row['salary'] ?: 0,
                    $row['status_description'] ?? '',
                    $row['action_type'] ?? 'السكن',
                    $row['ticket_info'] ?? '',
                    $row['settlement_status'] ?? 'لم يتم الخصم',
                    $row['financial_notes'] ?? ''
                ]);
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_update':
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
                    ticket_info = ?, settlement_status = ?, financial_notes = ? 
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
                    $row['id']
                ]);
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
            $stmt = $pdo->prepare("DELETE FROM workers WHERE id IN ($placeholders)");
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