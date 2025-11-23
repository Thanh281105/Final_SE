<?php
include 'config.php';
include 'functions.php';
session_start();

// If already logged in, redirect
if (isset($_SESSION['usermail'])) {
    header("Location: home.php");
    exit();
}

$message = '';
$error = '';

if (isset($_POST['forgot_password_submit'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT UserID, Email FROM signup WHERE Email = ? AND is_active = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $userId = $user['UserID'];
            
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60)); // 1 hour
            
            // Delete old tokens
            $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $deleteStmt->bind_param("i", $userId);
            $deleteStmt->execute();
            
            // Insert new token
            $insertStmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $insertStmt->bind_param("iss", $userId, $token, $expiresAt);
            $insertStmt->execute();
            
            // Log activity
            logActivity($conn, $userId, 'password_reset_requested', 'signup', $userId, 'Password reset token generated');
            
            // For demo: show link (in production, send email)
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
            $message = "Password reset link has been generated. For demo purposes, here is your link:<br><br><a href='" . $resetLink . "' target='_blank'>" . $resetLink . "</a><br><br>In production, this would be sent to your email.";
        } else {
            // Don't reveal if email exists (security best practice)
            $message = "If that email exists in our system, a password reset link has been sent.";
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
    <title>Forgot Password - Hotel TDTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/login.css">
    <style>
        .forgot-container {
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
    <div class="forgot-container">
        <h2 class="text-center mb-4">Forgot Password</h2>
        <p class="text-center text-muted mb-4">Enter your email address and we'll send you a link to reset your password.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-floating mb-3">
                <input type="email" class="form-control" name="email" placeholder=" " required>
                <label for="email">Email Address</label>
            </div>
            <button type="submit" name="forgot_password_submit" class="btn btn-primary w-100 mb-3">Send Reset Link</button>
            <a href="index.php" class="btn btn-secondary w-100">Back to Login</a>
        </form>
    </div>
</body>
</html>

