<?php
include 'config.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['usermail'])) {
    header("Location: index.php");
    exit;
}

$user_email = $_SESSION['usermail'];
$booking_id = intval($_GET['id']);

// Lấy thông tin booking
$booking_query = mysqli_query($conn, "SELECT * FROM roombook WHERE id = $booking_id AND Email = '$user_email' LIMIT 1");
$booking = mysqli_fetch_assoc($booking_query);

if (!$booking) {
    $_SESSION['error'] = "Booking not found or you don't have permission to cancel it.";
    header("Location: profile.php");
    exit;
}

// Chỉ cho phép hủy nếu trạng thái là Not Confirmed hoặc Confirmed nhưng chưa thanh toán
if ($booking['stat'] !== 'Not Confirmed' && !($booking['stat'] === 'Confirmed' && $booking['payment_status'] !== 'Paid')) {
    $_SESSION['error'] = "You cannot cancel this booking.";
    header("Location: profile.php");
    exit;
}

// Cập nhật trạng thái booking
mysqli_query($conn, "UPDATE roombook SET stat = 'Cancelled' WHERE id = $booking_id");

// Cập nhật trạng thái phòng nếu đã được đặt
mysqli_query($conn, "UPDATE room SET status = 'Available', reserved_booking_id = NULL, reserved_until = NULL WHERE reserved_booking_id = $booking_id");

// Cập nhật trạng thái thanh toán nếu có
mysqli_query($conn, "UPDATE payment SET status = 'Cancelled' WHERE id = $booking_id");

// Gửi email thông báo
require_once 'vendor/autoload.php';
$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your_email@gmail.com';
    $mail->Password   = 'your_app_password';
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('your_email@gmail.com', 'Hotel TDTU');
    $mail->addAddress($user_email, $booking['Name']);

    $mail->isHTML(true);
    $mail->Subject = 'Booking Cancelled';

    $mail->Body = "
        <h2>Booking Cancelled</h2>
        <p>Dear <strong>{$booking['Name']}</strong>,</p>
        <p>Your booking has been successfully cancelled.</p>
        <p>Booking Details:</p>
        <ul>
            <li>Booking ID: #{$booking['id']}</li>
            <li>Room Type: {$booking['RoomType']}</li>
            <li>Check-in: " . date('d/m/Y', strtotime($booking['cin'])) . "</li>
            <li>Check-out: " . date('d/m/Y', strtotime($booking['cout'])) . "</li>
            <li>Number of Rooms: {$booking['NoofRoom']}</li>
        </ul>
        <p>If you have any questions, please contact our customer service.</p>
        <p>Best regards,<br><strong>TDTU Hotel</strong></p>";

    $mail->send();
    $_SESSION['success'] = "Booking cancelled successfully and notification email sent!";
} catch (Exception $e) {
    $_SESSION['success'] = "Booking cancelled successfully but failed to send notification email.";
}

header("Location: profile.php");
exit;
?>