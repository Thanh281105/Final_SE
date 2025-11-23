<?php
include 'config.php';
include 'functions.php';
session_start();
checkSessionTimeout();
requireLogin();

$userId = getUserId();
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if ($booking_id == 0) {
    header("Location: my_bookings.php");
    exit();
}

// Get booking details
$stmt = $conn->prepare("SELECT rb.*, r.type as room_type_name, r.price as room_price 
                       FROM roombook rb 
                       LEFT JOIN room r ON rb.room_id = r.id 
                       WHERE rb.id = ? AND rb.Email = (SELECT Email FROM signup WHERE UserID = ?)");
$stmt->bind_param("ii", $booking_id, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: my_bookings.php");
    exit();
}

$booking = $result->fetch_assoc();

// Calculate price
$priceInfo = calculateBookingPrice($booking['RoomType'], $booking['Bed'], $booking['Meal'], $booking['nodays'], $booking['NoofRoom']);

$error = '';
$success = '';

// Handle payment
if (isset($_POST['process_payment'])) {
    $method = $_POST['payment_method'];
    $cardNumber = isset($_POST['card_number']) ? trim($_POST['card_number']) : '';
    $cardName = isset($_POST['card_name']) ? trim($_POST['card_name']) : '';
    $cardExpiry = isset($_POST['card_expiry']) ? trim($_POST['card_expiry']) : '';
    $cardCvv = isset($_POST['card_cvv']) ? trim($_POST['card_cvv']) : '';
    
    // Validate payment method specific fields
    if ($method == 'card') {
        if (empty($cardNumber) || empty($cardName) || empty($cardExpiry) || empty($cardCvv)) {
            $error = 'Please fill all card details';
        } elseif (strlen($cardNumber) < 16) {
            $error = 'Invalid card number';
        } else {
            // Simulate payment processing
            $paymentSuccess = true; // In real system, call payment gateway
            
            if ($paymentSuccess) {
                // Generate transaction ID
                $transactionId = 'TXN' . time() . rand(1000, 9999);
                
                // Check if payment record exists
                $checkPayment = $conn->prepare("SELECT id FROM payment WHERE id = ?");
                $checkPayment->bind_param("i", $booking_id);
                $checkPayment->execute();
                $paymentExists = $checkPayment->get_result()->num_rows > 0;
                
                if ($paymentExists) {
                    // Update existing payment
                    $updateStmt = $conn->prepare("UPDATE payment SET method = ?, status = 'Success', transaction_id = ?, type = 'Booking', created_at = NOW() WHERE id = ?");
                    $updateStmt->bind_param("ssi", $method, $transactionId, $booking_id);
                    $updateStmt->execute();
                } else {
                    // Insert new payment
                    $insertStmt = $conn->prepare("INSERT INTO payment (id, Name, Email, RoomType, Bed, NoofRoom, cin, cout, noofdays, roomtotal, bedtotal, meal, mealtotal, finaltotal, method, status, transaction_id, type, created_at) 
                                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Success', ?, 'Booking', NOW())");
                    $insertStmt->bind_param("issssissssddddsss", $booking_id, $booking['Name'], $booking['Email'], $booking['RoomType'], $booking['Bed'], $booking['NoofRoom'], $booking['cin'], $booking['cout'], $booking['nodays'], $priceInfo['roomtotal'], $priceInfo['bedtotal'], $booking['Meal'], $priceInfo['mealtotal'], $priceInfo['finaltotal'], $method, $transactionId);
                    $insertStmt->execute();
                }
                
                // Update booking status
                $updateBooking = $conn->prepare("UPDATE roombook SET status = 'Confirmed', updated_at = NOW() WHERE id = ?");
                $updateBooking->bind_param("i", $booking_id);
                $updateBooking->execute();
                
                // Log activity
                logActivity($conn, $userId, 'payment_success', 'payment', $booking_id, "Payment successful via $method");
                
                header("Location: my_bookings.php?payment=success");
                exit();
            } else {
                // Payment failed
                $insertStmt = $conn->prepare("INSERT INTO payment (id, Name, Email, RoomType, Bed, NoofRoom, cin, cout, noofdays, roomtotal, bedtotal, meal, mealtotal, finaltotal, method, status, type, created_at) 
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Failed', 'Booking', NOW())");
                $insertStmt->bind_param("issssissssddddss", $booking_id, $booking['Name'], $booking['Email'], $booking['RoomType'], $booking['Bed'], $booking['NoofRoom'], $booking['cin'], $booking['cout'], $booking['nodays'], $priceInfo['roomtotal'], $priceInfo['bedtotal'], $booking['Meal'], $priceInfo['mealtotal'], $priceInfo['finaltotal'], $method);
                $insertStmt->execute();
                
                logActivity($conn, $userId, 'payment_failed', 'payment', $booking_id, "Payment failed via $method");
                $error = 'Payment failed. Please try again.';
            }
        }
    } elseif ($method == 'ewallet' || $method == 'cash') {
        // For e-wallet and cash, simulate success
        $transactionId = 'TXN' . time() . rand(1000, 9999);
        
        $checkPayment = $conn->prepare("SELECT id FROM payment WHERE id = ?");
        $checkPayment->bind_param("i", $booking_id);
        $checkPayment->execute();
        $paymentExists = $checkPayment->get_result()->num_rows > 0;
        
        if ($paymentExists) {
            $updateStmt = $conn->prepare("UPDATE payment SET method = ?, status = 'Success', transaction_id = ?, type = 'Booking', created_at = NOW() WHERE id = ?");
            $updateStmt->bind_param("ssi", $method, $transactionId, $booking_id);
            $updateStmt->execute();
        } else {
            $insertStmt = $conn->prepare("INSERT INTO payment (id, Name, Email, RoomType, Bed, NoofRoom, cin, cout, noofdays, roomtotal, bedtotal, meal, mealtotal, finaltotal, method, status, transaction_id, type, created_at) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Success', ?, 'Booking', NOW())");
            $insertStmt->bind_param("issssissssddddsss", $booking_id, $booking['Name'], $booking['Email'], $booking['RoomType'], $booking['Bed'], $booking['NoofRoom'], $booking['cin'], $booking['cout'], $booking['nodays'], $priceInfo['roomtotal'], $priceInfo['bedtotal'], $booking['Meal'], $priceInfo['mealtotal'], $priceInfo['finaltotal'], $method, $transactionId);
            $insertStmt->execute();
        }
        
        $updateBooking = $conn->prepare("UPDATE roombook SET status = 'Confirmed', updated_at = NOW() WHERE id = ?");
        $updateBooking->bind_param("i", $booking_id);
        $updateBooking->execute();
        
        logActivity($conn, $userId, 'payment_success', 'payment', $booking_id, "Payment successful via $method");
        
        header("Location: my_bookings.php?payment=success");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Hotel TDTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <?php include 'navbar.php'; ?>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Payment Checkout</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Booking Summary</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Booking ID:</strong> #<?php echo escapeOutput($booking_id); ?></p>
                        <p><strong>Room Type:</strong> <?php echo escapeOutput($booking['RoomType']); ?></p>
                        <p><strong>Bedding:</strong> <?php echo escapeOutput($booking['Bed']); ?></p>
                        <p><strong>Meal:</strong> <?php echo escapeOutput($booking['Meal']); ?></p>
                        <p><strong>Check-in:</strong> <?php echo escapeOutput($booking['cin']); ?></p>
                        <p><strong>Check-out:</strong> <?php echo escapeOutput($booking['cout']); ?></p>
                        <p><strong>Number of Nights:</strong> <?php echo escapeOutput($booking['nodays']); ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h4>Payment Method</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Select Payment Method</label>
                                <select name="payment_method" class="form-control" id="payment_method" required>
                                    <option value="">Choose...</option>
                                    <option value="card">Credit/Debit Card</option>
                                    <option value="ewallet">E-Wallet</option>
                                    <option value="cash">Cash on Arrival</option>
                                </select>
                            </div>
                            
                            <div id="card_details" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Card Number</label>
                                    <input type="text" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cardholder Name</label>
                                    <input type="text" name="card_name" class="form-control" placeholder="John Doe">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Expiry Date</label>
                                        <input type="text" name="card_expiry" class="form-control" placeholder="MM/YY">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">CVV</label>
                                        <input type="text" name="card_cvv" class="form-control" placeholder="123" maxlength="3">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h5>Total Amount: ₹<?php echo number_format($priceInfo['finaltotal'], 2); ?></h5>
                                <button type="submit" name="process_payment" class="btn btn-primary btn-lg w-100 mt-3">Pay Now</button>
                                <a href="my_bookings.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Price Breakdown</h4>
                    </div>
                    <div class="card-body">
                        <p>Room (<?php echo $booking['nodays']; ?> nights): ₹<?php echo number_format($priceInfo['roomtotal'], 2); ?></p>
                        <p>Bed: ₹<?php echo number_format($priceInfo['bedtotal'], 2); ?></p>
                        <p>Meal: ₹<?php echo number_format($priceInfo['mealtotal'], 2); ?></p>
                        <hr>
                        <h5><strong>Total: ₹<?php echo number_format($priceInfo['finaltotal'], 2); ?></strong></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('payment_method').addEventListener('change', function() {
            const cardDetails = document.getElementById('card_details');
            if (this.value === 'card') {
                cardDetails.style.display = 'block';
            } else {
                cardDetails.style.display = 'none';
            }
        });
    </script>
</body>
</html>

