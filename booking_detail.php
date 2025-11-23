<?php
include 'config.php';
include 'functions.php';
session_start();
checkSessionTimeout();
requireLogin();

$userId = getUserId();
$error = '';
$success = '';

// Get parameters
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$cin = isset($_GET['cin']) ? $_GET['cin'] : '';
$cout = isset($_GET['cout']) ? $_GET['cout'] : '';

if (empty($cin) || empty($cout) || $room_id == 0) {
    header("Location: search_rooms.php");
    exit();
}

// Get room details
$stmt = $conn->prepare("SELECT * FROM room WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    header("Location: search_rooms.php");
    exit();
}

// Calculate days
$cinDate = new DateTime($cin);
$coutDate = new DateTime($cout);
$days = $cinDate->diff($coutDate)->days;

// Calculate price
$priceInfo = calculateBookingPrice($room['type'], $room['bedding'], 'Room only', $days, 1);

// Handle booking submission
if (isset($_POST['confirm_booking'])) {
    // Check availability one more time
    if (!isRoomAvailable($conn, $room_id, $cin, $cout)) {
        $error = 'Room is no longer available for selected dates';
    } else {
        // Get user info
        $userStmt = $conn->prepare("SELECT Username, Email, Phone, Country FROM signup WHERE UserID = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $user = $userResult->fetch_assoc();
        
        // Insert booking
        $name = $user['Username'];
        $email = $user['Email'];
        $phone = $user['Phone'] ?? '';
        $country = 'Vietnam'; // Default
        $roomType = $room['type'];
        $bed = $room['bedding'];
        $meal = 'Room only';
        $noofroom = 1;
        $status = 'Pending';
        
        $insertStmt = $conn->prepare("INSERT INTO roombook (Name, Email, Country, Phone, RoomType, Bed, NoofRoom, Meal, cin, cout, nodays, stat, status, created_at) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'NotConfirm', ?, NOW())");
        $insertStmt->bind_param("ssssssssssis", $name, $email, $country, $phone, $roomType, $bed, $noofroom, $meal, $cin, $cout, $days, $status);
        $insertStmt->execute();
        
        if ($insertStmt->affected_rows > 0) {
            $bookingId = $insertStmt->insert_id;
            logActivity($conn, $userId, 'booking_created', 'roombook', $bookingId, 'New booking created');
            header("Location: checkout.php?booking_id=" . $bookingId);
            exit();
        } else {
            $error = 'Failed to create booking. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Hotel TDTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include 'navbar.php'; ?>
</head>
<body>
    <div class="container mt-5">
        <h2>Confirm Your Booking</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Room Details</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Room Type:</strong> <?php echo escapeOutput($room['type']); ?></p>
                        <p><strong>Bedding:</strong> <?php echo escapeOutput($room['bedding']); ?></p>
                        <p><strong>Max Guests:</strong> <?php echo escapeOutput($room['max_guests']); ?></p>
                        <p><strong>Check-in:</strong> <?php echo escapeOutput($cin); ?></p>
                        <p><strong>Check-out:</strong> <?php echo escapeOutput($cout); ?></p>
                        <p><strong>Number of Nights:</strong> <?php echo $days; ?></p>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h4>Price Summary</h4>
                    </div>
                    <div class="card-body">
                        <p>Room Price (<?php echo $days; ?> nights): ₹<?php echo number_format($priceInfo['roomtotal'], 2); ?></p>
                        <p><strong>Total: ₹<?php echo number_format($priceInfo['finaltotal'], 2); ?></strong></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Confirm Booking</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <p>By confirming, you agree to our terms and conditions.</p>
                            <button type="submit" name="confirm_booking" class="btn btn-primary w-100">Confirm Booking</button>
                            <a href="search_rooms.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

