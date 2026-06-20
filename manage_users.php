<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

requireLogin();
requirePermission('view_users', $pdo);

// معالجة الإضافة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    if (!hasPermission('add_user', $pdo)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية']);
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 0);

    $errors = [];
    if (empty($username)) $errors[] = 'اسم المستخدم مطلوب';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
    if (empty($full_name)) $errors[] = 'الاسم الكامل مطلوب';
    if (empty($password) || strlen($password) < 6) $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    if ($role_id <= 0) $errors[] = 'اختر دور للمستخدم';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    try {
        // تحقق من عدم وجود المستخدم مسبقا
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'المستخدم أو البريد موجود بالفعل']);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('
            INSERT INTO users (username, email, full_name, password, role_id)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$username, $email, $full_name, $hashed_password, $role_id]);
        $user_id = $pdo->lastInsertId();

        logActivity($_SESSION['user_id'], 'ADD', 'users', $user_id, null, 
            ['username' => $username, 'email' => $email, 'full_name' => $full_name], 
            "إضافة مستخدم جديد: $full_name", $pdo);

        echo json_encode(['success' => true, 'message' => 'تم إضافة المستخدم بنجاح', 'id' => $user_id]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ في الإضافة: ' . $e->getMessage()]);
    }
    exit;
}

// معالجة التعديل
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'edit') {
    if (!hasPermission('edit_user', $pdo)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية']);
        exit;
    }

    $user_id = (int)($_POST['user_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 0);
    $status = $_POST['status'] ?? 'نشط';

    $errors = [];
    if ($user_id <= 0) $errors[] = 'بيانات غير صحيحة';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
    if (empty($full_name)) $errors[] = 'الاسم الكامل مطلوب';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    try {
        // احصل على البيانات القديمة
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $old_user = $stmt->fetch();

        if (!$old_user) {
            echo json_encode(['success' => false, 'message' => 'المستخدم غير موجود']);
            exit;
        }

        $stmt = $pdo->prepare('
            UPDATE users SET email = ?, full_name = ?, role_id = ?, status = ?
            WHERE id = ?
        ');
        $stmt->execute([$email, $full_name, $role_id, $status, $user_id]);

        $new_user = [
            'email' => $email,
            'full_name' => $full_name,
            'role_id' => $role_id,
            'status' => $status
        ];

        logActivity($_SESSION['user_id'], 'EDIT', 'users', $user_id, $old_user, $new_user, "تعديل بيانات المستخدم: $full_name", $pdo);

        echo json_encode(['success' => true, 'message' => 'تم تحديث المستخدم بنجاح']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ في التحديث: ' . $e->getMessage()]);
    }
    exit;
}

// معالجة الحذف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete') {
    if (!hasPermission('delete_user', $pdo)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية']);
        exit;
    }

    $user_id = (int)($_POST['user_id'] ?? 0);

    try {
        // احصل على بيانات المستخدم
        $stmt = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'المستخدم غير موجود']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$user_id]);

        logActivity($_SESSION['user_id'], 'DELETE', 'users', $user_id, $user, null, "حذف المستخدم: {$user['full_name']}", $pdo);

        echo json_encode(['success' => true, 'message' => 'تم حذف المستخدم بنجاح']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ في الحذف: ' . $e->getMessage()]);
    }
    exit;
}

// جلب المستخدمين
$stmt = $pdo->prepare('
    SELECT u.id, u.username, u.email, u.full_name, u.status, r.role_name, u.created_at
    FROM users u
    JOIN roles r ON u.role_id = r.id
    ORDER BY u.created_at DESC
');
$stmt->execute();
$users = $stmt->fetchAll();

// جلب الأدوار
$stmt = $pdo->prepare('SELECT id, role_name FROM roles ORDER BY role_name');
$stmt->execute();
$roles = $stmt->fetchAll();

$current_user = getUserInfo($_SESSION['user_id'], $pdo);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=1.5">
    <style>
        .users-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
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

        .users-header h1 {
            font-size: 24px;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .users-table th {
            background: #f1f5f9;
            padding: 15px;
            text-align: right;
            font-weight: 700;
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
        }

        .users-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .users-table tbody tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-edit {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-edit:hover {
            background: #fcd34d;
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-delete:hover {
            background: #fca5a5;
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
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 95%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
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
            color: #1e293b;
            font-size: 18px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
            flex-wrap: wrap;
        }

        .btn-primary,
        .btn-secondary {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            min-width: 100px;
        }

        .btn-primary {
            background: #4361ee;
            color: white;
        }

        .btn-primary:hover {
            background: #3a4fd9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .btn-primary {
            background: #4361ee;
            color: white;
        }

        .btn-primary:hover {
            background: #3a4fd9;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .btn-add {
            background: #10b981;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add:hover {
            background: #059669;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: none;
        }

        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: none;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.3s ease;
            z-index: 10000;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
            }

            to {
                transform: translateX(0);
            }
        }

        .toast-success {
            border-right: 4px solid #10b981;
            color: #065f46;
        }

        .toast-error {
            border-right: 4px solid #ef4444;
            color: #991b1b;
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
    </style>
</head>

<body style="background: #f8fafc;">
    <div class="users-container">
        <div class="users-header">
            <h1><i class="fas fa-users"></i> إدارة المستخدمين</h1>
            <div class="header-buttons">
                <a href="index.php" class="btn-back">
                    <i class="fas fa-home"></i> الرئيسية
                </a>
                <?php if (hasPermission('add_user', $pdo)): ?>
                    <button class="btn-add" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> إضافة مستخدم جديد
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($users)): ?>
            <div class="empty-message">
                <i class="fas fa-inbox"></i>
                <p>لا توجد مستخدمين حتى الآن</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم المستخدم</th>
                            <th>الاسم الكامل</th>
                            <th>البريد الإلكتروني</th>
                            <th>الدور</th>
                            <th>الحالة</th>
                            <th style="width: 100px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $index => $user): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><strong><?php echo htmlspecialchars($user['role_name']); ?></strong></td>
                                <td>
                                    <span class="status-badge <?php echo ($user['status'] === 'نشط') ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $user['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if (hasPermission('edit_user', $pdo)): ?>
                                            <button class="action-btn btn-edit" onclick="openEditModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['full_name']); ?>', <?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')">
                                                <i class="fas fa-edit"></i> تعديل
                                            </button>
                                        <?php endif; ?>
                                        <?php if (hasPermission('delete_user', $pdo) && $user['id'] !== $_SESSION['user_id']): ?>
                                            <button class="action-btn btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- إضافة مستخدم جديد -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> إضافة مستخدم جديد</h2>
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
            </div>
            <div class="error-message" id="addError"></div>
            <form id="addForm" onsubmit="handleAddUser(event)">
                <div class="form-group">
                    <label for="add_username">اسم المستخدم:</label>
                    <input type="text" id="add_username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="add_email">البريد الإلكتروني:</label>
                    <input type="email" id="add_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="add_full_name">الاسم الكامل:</label>
                    <input type="text" id="add_full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="add_password">كلمة المرور:</label>
                    <input type="password" id="add_password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="add_role">الدور:</label>
                    <select id="add_role" name="role_id" required>
                        <option value="">-- اختر دور --</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeAddModal()">إلغاء</button>
                    <button type="submit" class="btn-primary">حفظ المستخدم</button>
                </div>
            </form>
        </div>
    </div>

    <!-- تعديل مستخدم -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> تعديل المستخدم</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="error-message" id="editError"></div>
            <form id="editForm" onsubmit="handleEditUser(event)">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="form-group">
                    <label for="edit_email">البريد الإلكتروني:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_full_name">الاسم الكامل:</label>
                    <input type="text" id="edit_full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_role">الدور:</label>
                    <select id="edit_role" name="role_id" required>
                        <option value="">-- اختر دور --</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_status">الحالة:</label>
                    <select id="edit_status" name="status" required>
                        <option value="نشط">نشط</option>
                        <option value="معطل">معطل</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">إلغاء</button>
                    <button type="submit" class="btn-primary">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
            document.getElementById('addForm').reset();
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function openEditModal(userId, email, fullName, roleId, status) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_full_name').value = fullName;
            document.getElementById('edit_role').value = roleId;
            document.getElementById('edit_status').value = status;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function handleAddUser(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('addForm'));
            formData.append('action', 'add');

            fetch('manage_users.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        document.getElementById('addError').style.display = 'block';
                        document.getElementById('addError').textContent = data.message;
                    }
                });
        }

        function handleEditUser(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('editForm'));
            formData.append('action', 'edit');

            fetch('manage_users.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        document.getElementById('editError').style.display = 'block';
                        document.getElementById('editError').textContent = data.message;
                    }
                });
        }

        function deleteUser(userId) {
            if (!confirm('هل تريد حذف هذا المستخدم؟')) return;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('user_id', userId);

            fetch('manage_users.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(data.message, 'error');
                    }
                });
        }

        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        window.onclick = function (event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            if (event.target === addModal) addModal.style.display = 'none';
            if (event.target === editModal) editModal.style.display = 'none';
        }
    </script>
</body>

</html>
