<?php
session_start();
require_once 'config.php';

// إذا كان المستخدم مسجل دخول بالفعل، أعد توجيهه للصفحة الرئيسية
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'يرجى ملء جميع الحقول';
    } else {
        try {
            $stmt = $pdo->prepare('
                SELECT u.id, u.username, u.email, u.full_name, u.password, u.status, r.role_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.username = ? AND u.status = "نشط"
            ');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // تصحيح الخطأ
            if (!$user) {
                $error = '❌ المستخدم غير موجود أو معطل';
            } else {
                // اختبار كلمة المرور
                $isPasswordValid = password_verify($password, $user['password']);
                
                if ($isPasswordValid) {
                    // تسجيل الدخول الناجح
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role_name'];

                    // تسجيل نشاط تسجيل الدخول
                    $stmt = $pdo->prepare('
                        INSERT INTO activity_logs (user_id, action, entity_type, description, ip_address)
                        VALUES (?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([
                        $user['id'],
                        'LOGIN',
                        'auth',
                        'تسجيل دخول',
                        $_SERVER['REMOTE_ADDR'] ?? ''
                    ]);

                    header('Location: index.php');
                    exit;
                } else {
                    $error = '❌ كلمة المرور غير صحيحة';
                }
            }
        } catch (Exception $e) {
            $error = 'حدث خطأ في النظام';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول - نظام إدارة سكن العاملات</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 800;
        }

        .login-header p {
            font-size: 13px;
            opacity: 0.9;
        }

        .login-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .login-form {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            padding-left: 45px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            transition: 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-group input::placeholder {
            color: #a0aec0;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #667eea;
            font-size: 18px;
            transition: 0.3s;
            background: none;
            border: none;
            padding: 5px;
            z-index: 10;
        }

        .password-toggle:hover {
            color: #764ba2;
            transform: translateY(-50%) scale(1.2);
        }

        .password-toggle:focus {
            outline: none;
        }

        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-right: 4px solid #c53030;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message i {
            font-size: 16px;
        }

        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-family: 'Cairo', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button i {
            font-size: 16px;
        }

        .demo-info {
            background: #f0f7ff;
            border: 1px dashed #4361ee;
            border-radius: 10px;
            padding: 15px;
            margin-top: 25px;
            font-size: 12px;
            color: #1e40af;
        }

        .demo-info strong {
            display: block;
            margin-bottom: 8px;
            color: #1e40af;
        }

        .demo-info p {
            margin-bottom: 5px;
        }

        .demo-info p:last-child {
            margin-bottom: 0;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <h1>نظام إدارة السكن</h1>
            <p>تسجيل دخول آمن للموظفين</p>
        </div>

        <div class="login-form">
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user" style="margin-left: 5px;"></i>اسم المستخدم
                    </label>
                    <input type="text" id="username" name="username" placeholder="أدخل اسم المستخدم" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock" style="margin-left: 5px;"></i>كلمة المرور
                    </label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="أدخل كلمة المرور" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-button">
                    <i class="fas fa-sign-in-alt"></i>
                    تسجيل الدخول
                </button>
            </form>

            <!--<div class="demo-info">
                <strong><i class="fas fa-info-circle"></i> بيانات تجريبية للدخول:</strong>
                <p><strong>المستخدم:</strong> admin</p>
                <p><strong>كلمة المرور:</strong> admin123</p>
                <p style="margin-top: 10px; border-top: 1px dashed #4361ee; padding-top: 10px;"><strong>المستخدم:</strong> user | <strong>كلمة المرور:</strong> user123</p>
            </div>-->
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        // لتسهيل الدخول - يمكنك كتابة البيانات التالية:
        // admin / admin123 (مدير النظام)
        // supervisor / supervisor123 (مشرف)
        // user / user123 (موظف)
        // reviewer / reviewer123 (مراجع)
    </script>
</body>

</html>
