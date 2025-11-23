<?php
include 'config.php';
include 'functions.php';
session_start();

// If already logged in, redirect
if (isset($_SESSION['usermail'])) {
    header("Location: home.php");
    exit();
}

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error = '';
$success = '';

if (empty($token)) {
    $error = 'Invalid reset link';
} else {
    // Verify token
    $stmt = $conn->prepare("SELECT pr.user_id, pr.expires_at, s.Email 
                           FROM password_resets pr 
                           JOIN signup s ON pr.user_id = s.UserID 
                           WHERE pr.token = ? AND pr.expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $error = 'Invalid or expired reset link';
    } else {
        $resetData = $result->fetch_assoc();
        $userId = $resetData['user_id'];
        
        if (isset($_POST['reset_password_submit'])) {
            $password = $_POST['password'];
            $cpassword = $_POST['cpassword'];
            
            if (empty($password) || empty($cpassword)) {
                $error = 'Please fill all fields';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters';
            } elseif ($password !== $cpassword) {
                $error = 'Passwords do not match';
            } else {
                // Hash new password
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                
                // Update password
                $updateStmt = $conn->prepare("UPDATE signup SET Password = ? WHERE UserID = ?");
                $updateStmt->bind_param("si", $hashedPassword, $userId);
                $updateStmt->execute();
                
                // Delete used token
                $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                $deleteStmt->bind_param("s", $token);
                $deleteStmt->execute();
                
                // Reset failed attempts
                resetFailedAttempts($conn, $resetData['Email'], 'signup');
                
                // Log activity
                logActivity($conn, $userId, 'password_reset_completed', 'signup', $userId, 'Password reset successfully');
                
                $success = 'Password reset successfully! You can now login with your new password.';
            }
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
    <title>Reset Password - Hotel TDTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/login.css">
    <style>
        .reset-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2 class="text-center mb-4">Reset Password</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
            <a href="index.php" class="btn btn-secondary w-100">Back to Login</a>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?php echo escapeOutput($success); ?></div>
            <a href="index.php" class="btn btn-primary w-100">Go to Login</a>
        <?php else: ?>
            <p class="text-center text-muted mb-4">Enter your new password</p>
            <form method="POST" action="">
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" name="password" placeholder=" " required minlength="6">
                    <label for="password">New Password (min 6 chars)</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" name="cpassword" placeholder=" " required>
                    <label for="cpassword">Confirm New Password</label>
                </div>
                <button type="submit" name="reset_password_submit" class="btn btn-primary w-100 mb-3">Reset Password</button>
                <a href="index.php" class="btn btn-secondary w-100">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

