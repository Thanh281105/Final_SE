<?php
include 'config.php';
include 'functions.php';
session_start();
checkSessionTimeout();
requireLogin();

$userId = getUserId();
$userEmail = $_SESSION['usermail'];
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id == 0) {
    header("Location: my_bookings.php");
    exit();
}

// Get booking details
$stmt = $conn->prepare("SELECT rb.*, r.type as room_type_name, r.price as room_price, r.description, r.amenities,
                       p.status as payment_status, p.method as payment_method, p.transaction_id, p.finaltotal, p.roomtotal, p.bedtotal, p.mealtotal
                       FROM roombook rb 
                       LEFT JOIN room r ON rb.room_id = r.id 
                       LEFT JOIN payment p ON rb.id = p.id 
                       WHERE rb.id = ? AND rb.Email = ?");
$stmt->bind_param("is", $booking_id, $userEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: my_bookings.php");
    exit();
}

$booking = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Hotel TDTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <?php include 'navbar.php'; ?>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Booking Details #<?php echo escapeOutput($booking_id); ?></h2>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Booking Information</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Booking ID:</strong> #<?php echo escapeOutput($booking_id); ?></p>
                        <p><strong>Room Type:</strong> <?php echo escapeOutput($booking['RoomType']); ?></p>
                        <p><strong>Bedding:</strong> <?php echo escapeOutput($booking['Bed']); ?></p>
                        <p><strong>Meal Plan:</strong> <?php echo escapeOutput($booking['Meal']); ?></p>
                        <p><strong>Number of Rooms:</strong> <?php echo escapeOutput($booking['NoofRoom']); ?></p>
                        <p><strong>Check-in Date:</strong> <?php echo escapeOutput($booking['cin']); ?></p>
                        <p><strong>Check-out Date:</strong> <?php echo escapeOutput($booking['cout']); ?></p>
                        <p><strong>Number of Nights:</strong> <?php echo escapeOutput($booking['nodays']); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?php 
                                echo $booking['status'] == 'Confirmed' ? 'success' : 
                                    ($booking['status'] == 'Pending' ? 'warning' : 
                                    ($booking['status'] == 'Checked-in' ? 'info' : 
                                    ($booking['status'] == 'Checked-out' ? 'secondary' : 'danger'))); 
                            ?>">
                                <?php echo escapeOutput($booking['status']); ?>
                            </span>
                        </p>
                        <?php if ($booking['room_id']): ?>
                            <p><strong>Assigned Room:</strong> Room #<?php echo escapeOutput($booking['room_id']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($booking['description'] || $booking['amenities']): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Room Details</h4>
                        </div>
                        <div class="card-body">
                            <?php if ($booking['description']): ?>
                                <p><?php echo escapeOutput($booking['description']); ?></p>
                            <?php endif; ?>
                            <?php if ($booking['amenities']): ?>
                                <p><strong>Amenities:</strong> <?php echo escapeOutput($booking['amenities']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Payment Information</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($booking['payment_status']): ?>
                            <p><strong>Payment Status:</strong> 
                                <span class="badge bg-<?php echo $booking['payment_status'] == 'Success' ? 'success' : 'danger'; ?>">
                                    <?php echo escapeOutput($booking['payment_status']); ?>
                                </span>
                            </p>
                            <?php if ($booking['payment_method']): ?>
                                <p><strong>Payment Method:</strong> <?php echo escapeOutput($booking['payment_method']); ?></p>
                            <?php endif; ?>
                            <?php if ($booking['transaction_id']): ?>
                                <p><strong>Transaction ID:</strong> <?php echo escapeOutput($booking['transaction_id']); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted">Payment not processed yet</p>
                        <?php endif; ?>
                        
                        <?php if ($booking['finaltotal']): ?>
                            <hr>
                            <h5>Price Breakdown</h5>
                            <?php if ($booking['roomtotal']): ?>
                                <p>Room: ₹<?php echo number_format($booking['roomtotal'], 2); ?></p>
                            <?php endif; ?>
                            <?php if ($booking['bedtotal']): ?>
                                <p>Bed: ₹<?php echo number_format($booking['bedtotal'], 2); ?></p>
                            <?php endif; ?>
                            <?php if ($booking['mealtotal']): ?>
                                <p>Meal: ₹<?php echo number_format($booking['mealtotal'], 2); ?></p>
                            <?php endif; ?>
                            <hr>
                            <h5><strong>Total: ₹<?php echo number_format($booking['finaltotal'], 2); ?></strong></h5>
                        <?php endif; ?>
                        
                        <?php if ($booking['status'] == 'Pending'): ?>
                            <a href="checkout.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-success w-100 mt-3">Pay Now</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <a href="my_bookings.php" class="btn btn-secondary w-100">Back to Bookings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

