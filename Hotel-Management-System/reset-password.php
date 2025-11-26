<?php
// bật lỗi để lần cuối cùng chắc chắn thấy nếu còn lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
session_start();

// === HÀM prepareAndExecute BẮT BUỘC PHẢI CÓ TRONG FILE NÀY ===
function prepareAndExecute($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}
// =================================================

$type  = isset($_GET['type']) ? $_GET['type'] : 'user';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!in_array($type, ['user', 'staff'])) $type = 'user';
if (empty($token)) {
    echo "<script>
        alert('Invalid link');
        location.href='index.php';
    </script>";
    exit;
}

$table      = $type === 'staff' ? 'emp_login' : 'signup';
$emailCol   = $type === 'staff' ? 'Emp_Email' : 'Email';
$passCol    = $type === 'staff' ? 'Emp_Password' : 'Password';

// Kiểm tra token + còn hạn
$sql = "SELECT $emailCol FROM $table WHERE reset_token = ? AND reset_expiry > NOW() LIMIT 1";
$stmt = prepareAndExecute($conn, $sql, [$token]);
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>
        alert('Link đã hết hạn hoặc không hợp lệ!');
        location.href='index.php';
    </script>";
    exit;
}

$row = $result->fetch_assoc();
$userEmail = $row[$emailCol];

$message = '';
if (isset($_POST['reset_submit'])) {
    $pass1 = $_POST['Password'];
    $pass2 = $_POST['CPassword'];

    if ($pass1 === $pass2 && strlen($pass1) >= 6) {
        $update = "UPDATE $table SET $passCol = ?, reset_token = NULL, reset_expiry = NULL WHERE $emailCol = ?";
        prepareAndExecute($conn, $update, [$pass1, $userEmail]);

        $message = "<script>
            alert('Password changed successfully! Please login again.');
            location.href='index.php';
        </script>";
    } else {
        $message = "<script>alert('Passwords do not match or too short!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Hotel TDTU</title>
    <link rel="stylesheet" href="./css/login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <script src="https://cdn.jsdelivr.net/npm/pace-js@latest/pace.min.js"></script>
    <link rel="stylesheet" href="./css/flash.css">
</head>
<body>

<section id="auth_section">
    <div class="logo">
        <img class="bluebirdlogo" src="./image/bluebirdlogo.png" alt="logo">
        <p>TDTU</p>
    </div>

    <div class="auth_container">
        <h2>Reset Password</h2>
        <p>Create a new password for:</p>
        <strong style="color:#007bff; word-break:break-all;"><?= htmlspecialchars($userEmail) ?></strong>

        <form method="POST" class="mt-4">
            <div class="form-floating mb-3">
                <input type="password" class="form-control" name="Password" required minlength="6" placeholder=" ">
                <label>New Password (min 6 characters)</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" name="CPassword" required minlength="6" placeholder=" ">
                <label>Confirm New Password</label>
            </div>
            <button type="submit" name="reset_submit" class="auth_btn w-100">Update Password</button>
        </form>

        <div class="footer_line text-center mt-3">
            <h6><span class="page_move_btn" onclick="window.location='index.php'">← Back to Login</span></h6>
        </div>
    </div>
</section>

<?php if ($message) echo $message; ?>

<script src="./javascript/index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
<script>AOS.init();</script>
</body>
</html>