<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

requireLogin();
requirePermission('view_roles', $pdo);

// معالجة طلب جلب الصلاحيات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'get_permissions') {
    $role_id = (int)($_POST['role_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare('
            SELECT permission_id
            FROM role_permissions
            WHERE role_id = ?
        ');
        $stmt->execute([$role_id]);
        $permissions = $stmt->fetchAll();
        
        echo json_encode($permissions);
    } catch (Exception $e) {
        echo json_encode([]);
    }
    exit;
}

// معالجة الإضافة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add_role') {
    if (!hasPermission('add_role', $pdo)) {
        echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية']);
        exit;
    }

    $role_name = trim($_POST['role_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($role_name)) {
        echo json_encode(['success' => false, 'message' => 'اسم الدور مطلوب']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO roles (role_name, description) VALUES (?, ?)');
        $stmt->execute([$role_name, $description]);
        $role_id = $pdo->lastInsertId();

        logActivity($_SESSION['user_id'], 'ADD', 'roles', $role_id, null, 
            ['role_name' => $role_name, 'description' => $description], 
            "إضافة دور جديد: $role_name", $pdo);

        echo json_encode(['success' => true, 'message' => 'تم إضافة الدور بنجاح', 'id' => $role_id]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
    }
    exit;
}

// معالجة تحديث الدور
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'edit_role') {
    if (!hasPermission('edit_role', $pdo)) {
        echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية']);
        exit;
    }

    $role_id = (int)($_POST['role_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    try {
        $stmt = $pdo->prepare('SELECT * FROM roles WHERE id = ?');
        $stmt->execute([$role_id]);
        $old_role = $stmt->fetch();

        if (!$old_role) {
            echo json_encode(['success' => false, 'message' => 'الدور غير موجود']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE roles SET description = ? WHERE id = ?');
        $stmt->execute([$description, $role_id]);

        logActivity($_SESSION['user_id'], 'EDIT', 'roles', $role_id, $old_role, 
            ['role_name' => $old_role['role_name'], 'description' => $description], 
            "تعديل الدور: {$old_role['role_name']}", $pdo);

        echo json_encode(['success' => true, 'message' => 'تم تحديث الدور بنجاح']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
    }
    exit;
}

// معالجة حذف الدور
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete_role') {
    if (!hasPermission('delete_role', $pdo)) {
        echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية']);
        exit;
    }

    $role_id = (int)($_POST['role_id'] ?? 0);

    // تحقق من أن الدور لا يستخدم
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE role_id = ?');
    $stmt->execute([$role_id]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'لا يمكن حذف دور مستخدم من قبل موظفين']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT role_name FROM roles WHERE id = ?');
        $stmt->execute([$role_id]);
        $role = $stmt->fetch();

        $stmt = $pdo->prepare('DELETE FROM roles WHERE id = ?');
        $stmt->execute([$role_id]);

        logActivity($_SESSION['user_id'], 'DELETE', 'roles', $role_id, $role, null, "حذف الدور: {$role['role_name']}", $pdo);

        echo json_encode(['success' => true, 'message' => 'تم حذف الدور بنجاح']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
    }
    exit;
}

// معالجة تحديث الصلاحيات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update_permissions') {
    if (!hasPermission('manage_permissions', $pdo)) {
        echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية']);
        exit;
    }

    $role_id = (int)($_POST['role_id'] ?? 0);
    $permissions = $_POST['permissions'] ?? [];

    try {
        // احذف الصلاحيات الحالية
        $stmt = $pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?');
        $stmt->execute([$role_id]);

        // أضف الصلاحيات الجديدة
        foreach ($permissions as $permission_id) {
            $stmt = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
            $stmt->execute([$role_id, $permission_id]);
        }

        logActivity($_SESSION['user_id'], 'EDIT', 'roles', $role_id, null, null, "تحديث صلاحيات الدور", $pdo);

        echo json_encode(['success' => true, 'message' => 'تم تحديث الصلاحيات بنجاح']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
    }
    exit;
}

// جلب البيانات
$stmt = $pdo->prepare('SELECT id, role_name, description, created_at FROM roles ORDER BY role_name');
$stmt->execute();
$roles = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT id, permission_name, module, description FROM permissions ORDER BY module, permission_name');
$stmt->execute();
$all_permissions = $stmt->fetchAll();

// تجميع الصلاحيات حسب الوحدة
$permissions_by_module = [];
foreach ($all_permissions as $perm) {
    $permissions_by_module[$perm['module']][] = $perm;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأدوار والصلاحيات</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=1.5">
    <style>
        .roles-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin: 20px;
            min-height: calc(100vh - 100px);
        }

        .roles-list,
        .permissions-panel {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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

        .panel-header h2 {
            margin: 0;
            font-size: 20px;
            color: #1e293b;
        }

        .role-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            border-right: 4px solid #e2e8f0;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .role-item:hover {
            background: #f1f5f9;
            border-right-color: #4361ee;
        }

        .role-item.active {
            background: #ede9fe;
            border-right-color: #7c3aed;
        }

        .role-item-name {
            font-weight: 600;
            color: #1e293b;
        }

        .role-item-count {
            font-size: 12px;
            background: #e2e8f0;
            padding: 4px 8px;
            border-radius: 12px;
            color: #64748b;
        }

        .role-actions {
            display: flex;
            gap: 5px;
        }

        .role-actions button {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: 0.3s;
        }

        .btn-edit-role {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-edit-role:hover {
            background: #fcd34d;
        }

        .btn-delete-role {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-delete-role:hover {
            background: #fca5a5;
        }

        .permissions-module {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .permissions-module:last-child {
            border-bottom: none;
        }

        .module-title {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .module-icon {
            width: 20px;
            height: 20px;
            background: #4361ee;
            color: white;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }

        .permission-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 6px;
            transition: 0.2s;
        }

        .permission-item:hover {
            background: #f8fafc;
        }

        .permission-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 12px;
            margin-top: 2px;
            cursor: pointer;
        }

        .permission-label {
            flex: 1;
        }

        .permission-name {
            font-weight: 600;
            color: #1e293b;
            display: block;
            font-size: 14px;
        }

        .permission-desc {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
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
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
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

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1e293b;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }

        .btn-primary,
        .btn-secondary {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary {
            background: #4361ee;
            color: white;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-add {
            background: #10b981;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .save-permissions-btn {
            background: #4361ee;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }

        .save-permissions-btn:hover {
            background: #3a4fd9;
        }

        .no-role-selected {
            text-align: center;
            color: #64748b;
            padding: 40px 20px;
        }

        .no-role-selected i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 15px;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            z-index: 10000;
        }

        .toast-success {
            border-right: 4px solid #10b981;
        }

        .toast-error {
            border-right: 4px solid #ef4444;
        }

        @media (max-width: 1200px) {
            .roles-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body style="background: #f8fafc;">
    <div class="roles-container">
        <!-- قائمة الأدوار -->
        <div class="roles-list">
            <div class="panel-header">
                <h2><i class="fas fa-shield-alt"></i> الأدوار</h2>
                <div class="header-buttons">
                    <a href="index.php" class="btn-back">
                        <i class="fas fa-home"></i> الرئيسية
                    </a>
                    <?php if (hasPermission('add_role', $pdo)): ?>
                        <button class="btn-add" onclick="openAddRoleModal()">
                            <i class="fas fa-plus"></i> إضافة دور
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div id="rolesList">
                <?php foreach ($roles as $role): ?>
                    <div class="role-item" onclick="selectRole(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['role_name']); ?>')">
                        <div>
                            <div class="role-item-name"><?php echo htmlspecialchars($role['role_name']); ?></div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                                <?php echo htmlspecialchars($role['description'] ?? '-'); ?>
                            </div>
                        </div>
                        <div class="role-actions" onclick="event.stopPropagation();">
                            <?php if (hasPermission('edit_role', $pdo)): ?>
                                <button class="btn-edit-role" onclick="openEditRoleModal(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['role_name']); ?>', '<?php echo htmlspecialchars($role['description'] ?? ''); ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                            <?php endif; ?>
                            <?php if (hasPermission('delete_role', $pdo)): ?>
                                <button class="btn-delete-role" onclick="deleteRole(<?php echo $role['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- لوحة الصلاحيات -->
        <div class="permissions-panel">
            <div class="panel-header">
                <h2><i class="fas fa-lock"></i> الصلاحيات</h2>
                <span id="selectedRoleSpan" style="color: #4361ee; font-weight: 600;">اختر دور</span>
            </div>

            <div id="permissionsContent">
                <div class="no-role-selected">
                    <i class="fas fa-hand-pointer"></i>
                    <p>اختر دور من القائمة لإدارة صلاحياته</p>
                </div>
            </div>
        </div>
    </div>

    <!-- إضافة دور -->
    <div id="addRoleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> إضافة دور جديد</h2>
                <button style="background: none; border: none; font-size: 24px; cursor: pointer;" onclick="closeAddRoleModal()">&times;</button>
            </div>
            <form id="addRoleForm" onsubmit="handleAddRole(event)">
                <div class="form-group">
                    <label for="role_name">اسم الدور:</label>
                    <input type="text" id="role_name" name="role_name" required>
                </div>
                <div class="form-group">
                    <label for="role_description">الوصف:</label>
                    <textarea id="role_description" name="description"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeAddRoleModal()">إلغاء</button>
                    <button type="submit" class="btn-primary">إضافة الدور</button>
                </div>
            </form>
        </div>
    </div>

    <!-- تعديل دور -->
    <div id="editRoleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> تعديل الدور</h2>
                <button style="background: none; border: none; font-size: 24px; cursor: pointer;" onclick="closeEditRoleModal()">&times;</button>
            </div>
            <form id="editRoleForm" onsubmit="handleEditRole(event)">
                <input type="hidden" id="edit_role_id" name="role_id">
                <div class="form-group">
                    <label for="edit_role_name">اسم الدور:</label>
                    <input type="text" id="edit_role_name" name="role_name" disabled style="background: #f1f5f9; cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label for="edit_role_description">الوصف:</label>
                    <textarea id="edit_role_description" name="description"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeEditRoleModal()">إلغاء</button>
                    <button type="submit" class="btn-primary">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedRoleId = null;

        function selectRole(roleId, roleName) {
            selectedRoleId = roleId;
            document.querySelectorAll('.role-item').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById('selectedRoleSpan').textContent = roleName;
            loadPermissions(roleId);
        }

        function loadPermissions(roleId) {
            fetch('manage_roles.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_permissions&role_id=' + roleId
            })
                .then(response => response.json())
                .then(data => {
                    displayPermissions(data);
                });
        }

        function displayPermissions(data) {
            const modules = <?php echo json_encode(array_keys($permissions_by_module)); ?>;
            const allPerms = <?php echo json_encode($all_permissions); ?>;
            const rolePermissions = data;

            if (!modules || modules.length === 0 || !allPerms || allPerms.length === 0) {
                document.getElementById('permissionsContent').innerHTML = '<p style="color: #ef4444;">خطأ في تحميل الصلاحيات</p>';
                return;
            }

            let html = '';
            modules.forEach(module => {
                const modulePerms = allPerms.filter(p => p.module === module);
                if (modulePerms.length === 0) return;
                
                html += `<div class="permissions-module">
                    <div class="module-title">
                        <div class="module-icon">${module.charAt(0).toUpperCase()}</div>
                        ${module}
                    </div>`;

                modulePerms.forEach(perm => {
                    const checked = rolePermissions.some(rp => rp.permission_id === perm.id) ? 'checked' : '';
                    html += `<div class="permission-item">
                        <input type="checkbox" value="${perm.id}" ${checked} onchange="togglePermission(this)">
                        <div class="permission-label">
                            <span class="permission-name">${perm.permission_name}</span>
                            <span class="permission-desc">${perm.description || ''}</span>
                        </div>
                    </div>`;
                });

                html += '</div>';
            });

            html += '<button class="save-permissions-btn" onclick="savePermissions()">💾 حفظ الصلاحيات</button>';
            document.getElementById('permissionsContent').innerHTML = html;
        }

        function togglePermission(checkbox) {
            // يتم الحفظ عند النقر على الزر
        }

        function savePermissions() {
            const checkboxes = document.querySelectorAll('#permissionsContent input[type="checkbox"]:checked');
            const permissions = Array.from(checkboxes).map(cb => cb.value);

            const formData = new FormData();
            formData.append('action', 'update_permissions');
            formData.append('role_id', selectedRoleId);
            permissions.forEach(perm => formData.append('permissions[]', perm));

            fetch('manage_roles.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        setTimeout(() => location.reload(), 1500);
                    }
                });
        }

        function openAddRoleModal() {
            document.getElementById('addRoleModal').style.display = 'block';
        }

        function closeAddRoleModal() {
            document.getElementById('addRoleModal').style.display = 'none';
        }

        function openEditRoleModal(roleId, roleName, description) {
            document.getElementById('edit_role_id').value = roleId;
            document.getElementById('edit_role_name').value = roleName;
            document.getElementById('edit_role_description').value = description;
            document.getElementById('editRoleModal').style.display = 'block';
        }

        function closeEditRoleModal() {
            document.getElementById('editRoleModal').style.display = 'none';
        }

        function handleAddRole(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('addRoleForm'));
            formData.append('action', 'add_role');

            fetch('manage_roles.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        setTimeout(() => location.reload(), 1500);
                    }
                });
        }

        function handleEditRole(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('editRoleForm'));
            formData.append('action', 'edit_role');

            fetch('manage_roles.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        setTimeout(() => location.reload(), 1500);
                    }
                });
        }

        function deleteRole(roleId) {
            if (!confirm('هل تريد حذف هذا الدور؟')) return;

            const formData = new FormData();
            formData.append('action', 'delete_role');
            formData.append('role_id', roleId);

            fetch('manage_roles.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        setTimeout(() => location.reload(), 1500);
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
            const addModal = document.getElementById('addRoleModal');
            const editModal = document.getElementById('editRoleModal');
            if (event.target === addModal) addModal.style.display = 'none';
            if (event.target === editModal) editModal.style.display = 'none';
        }
    </script>
</body>

</html>