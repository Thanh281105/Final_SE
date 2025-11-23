<?php
include 'config.php';
include 'functions.php';
session_start();

// Check if 2FA session exists
if (!isset($_SESSION['2fa_user_id']) || !isset($_SESSION['2fa_email'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if (isset($_POST['verify_2fa'])) {
    $code = trim($_POST['code']);
    $userId = $_SESSION['2fa_user_id'];
    
    if (empty($code)) {
        $error = 'Please enter the verification code';
    } elseif (strlen($code) != 6 || !is_numeric($code)) {
        $error = 'Code must be 6 digits';
    } else {
        // Verify code
        if (verify2FACode($conn, $userId, $code)) {
            // Code is valid - complete login
            $email = $_SESSION['2fa_email'];
            $role = $_SESSION['2fa_role'] ?? 'customer';
            $isEmp = isset($_SESSION['2fa_is_emp']) && $_SESSION['2fa_is_emp'];
            
            // Set session
            $_SESSION['usermail'] = $email;
            $_SESSION['role'] = $role;
            $_SESSION['user_id'] = $userId;
            $_SESSION['last_activity'] = time();
            
            // Clear 2FA session
            unset($_SESSION['2fa_user_id']);
            unset($_SESSION['2fa_email']);
            unset($_SESSION['2fa_role']);
            unset($_SESSION['2fa_is_emp']);
            
            // Log activity
            logActivity($conn, $userId, 'login_success', $isEmp ? 'emp_login' : 'signup', $userId, '2FA verified successfully');
            
            // Redirect based on role
            if ($role == 'admin' || $role == 'staff' || $isEmp) {
                header("Location: admin/admin.php");
            } else {
                header("Location: home.php");
            }
            exit();
        } else {
            $error = 'Invalid verification code. Please try again.';
            logActivity($conn, $userId, '2fa_failed', 'signup', $userId, 'Invalid 2FA code entered');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify 2FA - Hotel TDTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <link rel="stylesheet" href="./css/login.css">
    <style>
        .verify-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .code-input {
            font-size: 24px;
            text-align: center;
            letter-spacing: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <h2 class="text-center mb-4">Two-Factor Authentication</h2>
        <p class="text-center text-muted mb-4">Enter the 6-digit code sent to your email</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-floating mb-3">
                <input type="text" class="form-control code-input" name="code" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus>
                <label for="code">Verification Code</label>
            </div>
            <button type="submit" name="verify_2fa" class="btn btn-primary w-100 mb-3">Verify</button>
            <a href="index.php" class="btn btn-secondary w-100">Cancel</a>
        </form>
    </div>
    
    <script>
        // Auto-focus and format code input
        document.querySelector('input[name="code"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>

