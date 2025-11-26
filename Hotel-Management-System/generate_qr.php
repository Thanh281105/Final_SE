<?php
include 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['usermail'])) {
    header("Location: index.php");
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    die("<script>alert('Invalid booking ID'); window.history.back();</script>");
}

$booking_id = intval($_GET['booking_id']);
$email = $_SESSION['usermail'];

// Get booking information from roombook table
$booking_query = mysqli_query($conn, "SELECT * FROM roombook WHERE id = $booking_id AND Email = '$email' LIMIT 1");
$booking = mysqli_fetch_assoc($booking_query);

if (!$booking) {
    die("<script>alert('Booking not found or you do not have permission to access it.'); window.history.back();</script>");
}

// Check if booking can be paid
if ($booking['stat'] !== 'Confirmed') {
    die("<script>alert('This booking is not confirmed yet. Please wait for confirmation.'); window.history.back();</script>");
}

// Check if already paid (from payment table)
$payment_query = mysqli_query($conn, "SELECT status, finaltotal FROM payment WHERE id = $booking_id LIMIT 1");
$payment = mysqli_fetch_assoc($payment_query);

if ($payment && $payment['status'] === 'Paid') {
    die("<script>alert('This booking has already been paid.'); window.history.back();</script>");
}

// Get total amount from payment table, fallback to 0 if not available
$total_amount = 0;
if ($payment) {
    $total_amount = $payment['finaltotal'];
} else {
    // If no payment record exists, show error
    die("<script>alert('Total amount not available for this booking.'); window.history.back();</script>");
}

if ($total_amount <= 0) {
    die("<script>alert('Invalid amount to pay.'); window.history.back();</script>");
}

// Create payment content
$payment_content = "BOOKING#" . $booking_id . " - " . $booking['Name'];

// Bank account information
$bank_account = "0936765762"; // Your MB Bank account
$bank_name = "MB Bank";
$bank_branch = "Ho Chi Minh Branch";

// Create QR code data (simple text format for display)
$qrcode_url = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode("Payment Information:\nAccount: $bank_account\nAmount: $total_amount VND\nContent: $payment_content") . "&size=300x300";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Booking #<?php echo $booking_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .qr-container {
            text-align: center;
            margin: 30px 0;
        }
        .qr-image {
            width: 250px;
            height: 250px;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            background: white;
        }
        .payment-info {
            background: #f1f8ff;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn-back:hover {
            background: #5a6268;
        }
        h1 {
            color: #007bff;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            background: #fff3cd;
            border: 1px solid #ffe082;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center">Pay Booking #<?php echo $booking_id; ?></h1>
        
        <div class="alert">
            <strong>Note:</strong> Please make payment to the bank account below. After payment, please wait for admin confirmation.
        </div>

        <div class="qr-container">
            <img src="<?php echo $qrcode_url; ?>" alt="QR Code" class="qr-image">
        </div>

        <div class="payment-info">
            <h5>Payment Information:</h5>
            <p><strong>Amount:</strong> <?php echo number_format($total_amount); ?> VND</p>
            <p><strong>Transfer Content:</strong> <?php echo htmlspecialchars($payment_content); ?></p>
            <p><strong>Bank:</strong> <?php echo htmlspecialchars($bank_name); ?> (Account: <?php echo htmlspecialchars($bank_account); ?>)</p>
        </div>

        <div class="text-center">
            <a href="profile.php" class="btn-back">Back to Profile</a>
        </div>
    </div>
</body>
</html>