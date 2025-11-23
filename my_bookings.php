<?php
include 'config.php';
include 'functions.php';
session_start();
checkSessionTimeout();
requireLogin();

$userId = getUserId();
$userEmail = $_SESSION['usermail'];

// Get user bookings
$stmt = $conn->prepare("SELECT rb.*, r.type as room_type_name, p.status as payment_status, p.method as payment_method, p.finaltotal 
                       FROM roombook rb 
                       LEFT JOIN room r ON rb.room_id = r.id 
                       LEFT JOIN payment p ON rb.id = p.id 
                       WHERE rb.Email = ? 
                       ORDER BY rb.created_at DESC");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();

$upcoming = array();
$past = array();
$today = date('Y-m-d');

while ($row = $result->fetch_assoc()) {
    if ($row['cout'] >= $today) {
        $upcoming[] = $row;
    } else {
        $past[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Hotel TDTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <?php include 'navbar.php'; ?>
    <style>
        .booking-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">My Bookings</h2>
        
        <?php if (isset($_GET['payment']) && $_GET['payment'] == 'success'): ?>
            <div class="alert alert-success">Payment successful! Your booking has been confirmed.</div>
        <?php endif; ?>
        
        <!-- Upcoming Bookings -->
        <h3 class="mt-4 mb-3">Upcoming Bookings</h3>
        <?php if (empty($upcoming)): ?>
            <div class="alert alert-info">No upcoming bookings</div>
        <?php else: ?>
            <?php foreach ($upcoming as $booking): ?>
                <div class="booking-card">
                    <div class="row">
                        <div class="col-md-8">
                            <h4><?php echo escapeOutput($booking['RoomType']); ?></h4>
                            <p><strong>Booking ID:</strong> #<?php echo escapeOutput($booking['id']); ?></p>
                            <p><strong>Check-in:</strong> <?php echo escapeOutput($booking['cin']); ?></p>
                            <p><strong>Check-out:</strong> <?php echo escapeOutput($booking['cout']); ?></p>
                            <p><strong>Nights:</strong> <?php echo escapeOutput($booking['nodays']); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php 
                                    echo $booking['status'] == 'Confirmed' ? 'success' : 
                                        ($booking['status'] == 'Pending' ? 'warning' : 
                                        ($booking['status'] == 'Checked-in' ? 'info' : 'secondary')); 
                                ?>">
                                    <?php echo escapeOutput($booking['status']); ?>
                                </span>
                            </p>
                            <?php if ($booking['payment_status']): ?>
                                <p><strong>Payment:</strong> 
                                    <span class="badge bg-<?php echo $booking['payment_status'] == 'Success' ? 'success' : 'danger'; ?>">
                                        <?php echo escapeOutput($booking['payment_status']); ?>
                                    </span>
                                    <?php if ($booking['payment_method']): ?>
                                        (<?php echo escapeOutput($booking['payment_method']); ?>)
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($booking['finaltotal']): ?>
                                <h5>₹<?php echo number_format($booking['finaltotal'], 2); ?></h5>
                            <?php endif; ?>
                            <a href="my_booking_detail.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary">View Details</a>
                            <?php if ($booking['status'] == 'Pending'): ?>
                                <a href="checkout.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-success mt-2">Pay Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Past Bookings -->
        <h3 class="mt-5 mb-3">Past Bookings</h3>
        <?php if (empty($past)): ?>
            <div class="alert alert-info">No past bookings</div>
        <?php else: ?>
            <?php foreach ($past as $booking): ?>
                <div class="booking-card">
                    <div class="row">
                        <div class="col-md-8">
                            <h4><?php echo escapeOutput($booking['RoomType']); ?></h4>
                            <p><strong>Booking ID:</strong> #<?php echo escapeOutput($booking['id']); ?></p>
                            <p><strong>Check-in:</strong> <?php echo escapeOutput($booking['cin']); ?></p>
                            <p><strong>Check-out:</strong> <?php echo escapeOutput($booking['cout']); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-secondary"><?php echo escapeOutput($booking['status']); ?></span>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($booking['finaltotal']): ?>
                                <h5>₹<?php echo number_format($booking['finaltotal'], 2); ?></h5>
                            <?php endif; ?>
                            <a href="my_booking_detail.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>

