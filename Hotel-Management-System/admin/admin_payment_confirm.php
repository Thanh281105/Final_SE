<?php
include '../config.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Kiểm tra xem có ID booking không
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<script>alert('Invalid booking ID'); window.history.back();</script>");
}

$booking_id = intval($_GET['id']);

// Lấy thông tin booking từ bảng payment
$booking_query = mysqli_query($conn, "SELECT * FROM payment WHERE id = $booking_id LIMIT 1");
$booking = mysqli_fetch_assoc($booking_query);

if (!$booking) {
    die("<script>alert('Booking not found'); window.history.back();</script>");
}

// Kiểm tra nếu đã thanh toán rồi
if ($booking['status'] === 'Paid') {
    die("<script>alert('This booking has already been paid'); window.history.back();</script>");
}

// Cập nhật trạng thái thanh toán
$update_status = mysqli_query($conn, "UPDATE payment SET status = 'Paid' WHERE id = $booking_id");

if ($update_status) {
    // Lấy thông tin từ roombook để cập nhật vào room
    $roombook_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT Name, Email, Phone, RoomType, Bed, Meal, cin, cout, nodays FROM roombook WHERE id = $booking_id"));
    
    if ($roombook_info) {
        // Cập nhật phòng từ trạng thái chờ sang trạng thái bận và đồng bộ thông tin
        $update_room_sql = "UPDATE room SET 
                           status = 'Occupied', 
                           current_booking_id = $booking_id,
                           reserved_booking_id = NULL, 
                           reserved_until = NULL
                           WHERE reserved_booking_id = $booking_id";
        mysqli_query($conn, $update_room_sql);
    }
    
    // Lấy thông tin người dùng để gửi email
    $user_query = mysqli_query($conn, "SELECT Username FROM signup WHERE Email = '" . $booking['Email'] . "' LIMIT 1");
    $user = mysqli_fetch_assoc($user_query);
    
    // Gửi email xác nhận
    $email_sent = sendConfirmationEmail($booking, $booking['Email'], $user['Username']);
    
    if ($email_sent) {
        echo "<script>alert('Payment confirmed successfully and confirmation email sent to customer.'); window.location.href='payment.php';</script>";
    } else {
        echo "<script>alert('Payment confirmed successfully but failed to send confirmation email.'); window.location.href='payment.php';</script>";
    }
} else {
    echo "<script>alert('Error updating payment status'); window.history.back();</script>";
}

// Hàm gửi email (giữ nguyên như trước)
function sendConfirmationEmail($booking_info, $user_email, $user_name) {
    $mail = new PHPMailer(true);

    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'phamnguyenanhsva@gmail.com'; // Thay bằng email thật của bạn
        $mail->Password   = 'okad qsbx jplr rznr';    // Thay bằng mật khẩu ứng dụng
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Người gửi và người nhận
        $mail->setFrom('your_email@gmail.com', 'Hotel TDTU');
        $mail->addAddress($user_email, $user_name);

        // Nội dung email
        $mail->isHTML(true);
        $mail->Subject = 'Payment Confirmation - Booking #' . $booking_info['id'];
        $mail->Body    = '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
                <h2 style="color: #007bff;">Payment Confirmation</h2>
                <p>Dear <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
                <p>We are pleased to inform you that your payment for the booking has been successfully confirmed.</p>
                
                <h3>Booking Details:</h3>
                <ul>
                    <li><strong>Booking ID:</strong> #' . $booking_info['id'] . '</li>
                    <li><strong>Room Type:</strong> ' . htmlspecialchars($booking_info['RoomType']) . '</li>
                    <li><strong>Check-in Date:</strong> ' . date('d/m/Y', strtotime($booking_info['cin'])) . '</li>
                    <li><strong>Check-out Date:</strong> ' . date('d/m/Y', strtotime($booking_info['cout'])) . '</li>
                    <li><strong>Total Amount:</strong> ' . number_format($booking_info['finaltotal']) . ' VND</li>
                </ul>
                
                <p>Thank you for choosing our hotel. If you have any questions, please contact our customer service.</p>
                
                <p>Best regards,<br><strong>TDTU Hotel</strong></p>
            </div>
        </body>
        </html>';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>