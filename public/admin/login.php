<?php
/**
 * Admin Login Page
 */
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn() && isSessionValid()) {
    header('Location: /admin/index.php');
    exit();
}

$error_message = '';
$redirect_url = $_GET['redirect'] ?? '/admin/index.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        $result = authenticateAdmin($username, $password);

        if ($result['success']) {
            // Redirect to intended page or dashboard
            header('Location: ' . $redirect_url);
            exit();
        } else {
            $error_message = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Urgent Care</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-hospital login-icon"></i>
                <h1>Admin Login</h1>
                <p>Urgent Care Form System</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        required
                        autofocus
                        autocomplete="username"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>

            <div class="login-footer">
                <p><i class="fas fa-shield-alt"></i> Secure Admin Access</p>
                <p class="login-help">
                    Default credentials - Username: <strong>admin</strong> | Password: <strong>ChangeMe123!</strong>
                    <br><small style="color: #dc3545;">Please change default password immediately after first login</small>
                </p>
            </div>
        </div>
    </div>

    <style>
        .login-page {
            background: linear-gradient(135deg, #0066cc 0%, #004d99 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        .login-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #0066cc 0%, #004d99 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .login-header h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
        }

        .login-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .login-form {
            padding: 30px;
        }

        .alert-error {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 12px;
            border-radius: 4px;
            margin: -10px 30px 20px 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-footer {
            background: #f5f5f5;
            padding: 20px 30px;
            text-align: center;
            font-size: 13px;
            color: #666;
        }

        .login-help {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #0066cc;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }
    </style>
</body>
</html>
