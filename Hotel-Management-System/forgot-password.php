<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function prepareAndExecute($conn, $sql, $params) {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) die('mysqli error: ' . htmlspecialchars($conn->error));
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    return $stmt;
}

$type = $_GET['type'] ?? 'user';
$allowed_types = ['user', 'staff'];
if (!in_array($type, $allowed_types)) $type = 'user';

$message = '';

if (isset($_POST['forgot_submit'])) {
    $email = trim($_POST['Email']);
    $table      = $type === 'staff' ? 'emp_login' : 'signup';
    $emailField = $type === 'staff' ? 'Emp_Email' : 'Email';

    $sql = "SELECT $emailField FROM $table WHERE $emailField = ?";
    $stmt = prepareAndExecute($conn, $sql, [$email]);
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token  = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime('+7 hours'));

        $updateSql = "UPDATE $table SET reset_token = ?, reset_expiry = ? WHERE $emailField = ?";
        prepareAndExecute($conn, $updateSql, [$token, $expiry, $email]);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'phamnguyenanhsva@gmail.com';
            $mail->Password   = 'okad qsbx jplr rznr';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('phamnguyenanhsva@gmail.com', 'Hotel TDTU');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - Hotel TDTU';

            $resetLink = "http://localhost/Web_hotel_Mana/Hotel-Management-System/reset-password.php?type=$type&token=" . urlencode($token);

            $mail->Body = "
                <h3>Hello,</h3>
                <p>We received a request to reset the password for your account.</p>
                <p>Click the button below to set a new password (link expires in 1 hour):</p>
                <p style='text-align:center;'>
                    <a href='$resetLink' style='background:#007bff;color:white;padding:12px 24px;text-decoration:none;border-radius:5px;'>
                        Reset Password
                    </a>
                </p>
                <p>Or copy this link:<br><strong>$resetLink</strong></p>
                <br>
                <p>If you didn't request this, please ignore this email.</p>
                <p>Best regards,<br>Hotel TDTU</p>
            ";

            $mail->send();
            $message = "<script>swal({title:'Success!',text:'A password reset link has been sent to your email.',icon:'success'});</script>";
        } catch (Exception $e) {
            $message = "<script>swal({title:'Email Error',text:'Could not send email. {$mail->ErrorInfo}',icon:'error'});</script>";
        }
    } else {
        $message = "<script>swal({title:'Email Not Found',text:'This email is not registered.',icon:'error'});</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Hotel TDTU</title>
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
        <h2>Forgot Password (<?= $type === 'staff' ? 'Staff' : 'Customer' ?>)</h2>

        <form method="POST" class="mt-4">
            <div class="form-floating mb-3">
                <input type="email" class="form-control" name="Email" placeholder=" " required>
                <label>Email Address</label>
            </div>

            <button type="submit" name="forgot_submit" class="auth_btn w-100">Send Reset Link</button>

            <div class="footer_line text-center mt-3">
                <h6>Back to <span class="page_move_btn" onclick="window.location='index.php'">Login</span></h6>
            </div>
        </form>
    </div>
</section>

<?php if ($message) echo $message; ?>

<script src="./javascript/index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
<script>AOS.init();</script>
</body>
</html>