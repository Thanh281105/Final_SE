<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$bookingId = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

$error = '';
$success = '';

// Handle check-out
if (isset($_POST['process_checkout'])) {
    $bookingId = intval($_POST['booking_id']);
    $paymentMethod = $_POST['payment_method'];
    
    // Get booking
    $bookingStmt = $conn->prepare("SELECT rb.*, r.id as room_id FROM roombook rb LEFT JOIN room r ON rb.room_id = r.id WHERE rb.id = ? AND rb.status = 'Checked-in'");
    $bookingStmt->bind_param("i", $bookingId);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    
    if ($bookingResult->num_rows == 0) {
        $error = 'Booking not found or not checked-in';
    } else {
        $booking = $bookingResult->fetch_assoc();
        $roomId = $booking['room_id'];
        
        // Calculate final bill
        $priceInfo = calculateBookingPrice($booking['RoomType'], $booking['Bed'], $booking['Meal'], $booking['nodays'], $booking['NoofRoom']);
        
        // Get total paid (deposits)
        $depositStmt = $conn->prepare("SELECT SUM(finaltotal) as total_paid FROM payment WHERE id = ? AND type = 'Deposit' AND status = 'Success'");
        $depositStmt->bind_param("i", $bookingId);
        $depositStmt->execute();
        $depositResult = $depositStmt->get_result();
        $depositData = $depositResult->fetch_assoc();
        $totalPaid = $depositData['total_paid'] ?? 0;
        
        $remaining = $priceInfo['finaltotal'] - $totalPaid;
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert final payment
            $transactionId = 'FIN' . time();
            $insertPayment = $conn->prepare("INSERT INTO payment (id, Name, Email, RoomType, Bed, NoofRoom, cin, cout, noofdays, roomtotal, bedtotal, meal, mealtotal, finaltotal, method, status, transaction_id, type, created_at) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Success', ?, 'Final', NOW())");
            $finalAmount = $remaining;
            $insertPayment->bind_param("issssissssddddsss", $bookingId, $booking['Name'], $booking['Email'], $booking['RoomType'], $booking['Bed'], $booking['NoofRoom'], $booking['cin'], $booking['cout'], $booking['nodays'], $priceInfo['roomtotal'], $priceInfo['bedtotal'], $booking['Meal'], $priceInfo['mealtotal'], $finalAmount, $paymentMethod, $transactionId);
            $insertPayment->execute();
            
            // Update booking status
            $updateBooking = $conn->prepare("UPDATE roombook SET status = 'Checked-out', checked_out_at = NOW(), updated_at = NOW() WHERE id = ?");
            $updateBooking->bind_param("i", $bookingId);
            $updateBooking->execute();
            
            // Update room status
            if ($roomId) {
                $updateRoom = $conn->prepare("UPDATE room SET status = 'Needs Cleaning' WHERE id = ?");
                $updateRoom->bind_param("i", $roomId);
                $updateRoom->execute();
            }
            
            $conn->commit();
            
            $userId = getUserId();
            logActivity($conn, $userId, 'checkout_completed', 'roombook', $bookingId, "Guest checked out, final payment processed");
            
            $success = 'Check-out completed successfully!';
            header("Location: checkout.php?success=1&booking_id=" . $bookingId);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Failed to process check-out: ' . $e->getMessage();
        }
    }
}

// Get checked-in bookings
$sql = "SELECT rb.*, r.id as room_id FROM roombook rb 
        LEFT JOIN room r ON rb.room_id = r.id 
        WHERE rb.status = 'Checked-in'";
$params = array();
$types = '';

if (!empty($search)) {
    $sql .= " AND (rb.Name LIKE ? OR rb.Email LIKE ? OR rb.Phone LIKE ? OR rb.id = ? OR r.id = ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = is_numeric($search) ? intval($search) : 0;
    $params[] = is_numeric($search) ? intval($search) : 0;
    $types .= 'sssiii';
}

$sql .= " ORDER BY rb.checked_in_at ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get selected booking
$selectedBooking = null;
if ($bookingId > 0) {
    $bookingStmt = $conn->prepare("SELECT rb.*, r.id as room_id FROM roombook rb LEFT JOIN room r ON rb.room_id = r.id WHERE rb.id = ?");
    $bookingStmt->bind_param("i", $bookingId);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    if ($bookingResult->num_rows > 0) {
        $selectedBooking = $bookingResult->fetch_assoc();
        
        // Calculate bill
        $priceInfo = calculateBookingPrice($selectedBooking['RoomType'], $selectedBooking['Bed'], $selectedBooking['Meal'], $selectedBooking['nodays'], $selectedBooking['NoofRoom']);
        
        // Get deposits
        $depositStmt = $conn->prepare("SELECT SUM(finaltotal) as total_paid FROM payment WHERE id = ? AND type = 'Deposit' AND status = 'Success'");
        $depositStmt->bind_param("i", $bookingId);
        $depositStmt->execute();
        $depositResult = $depositStmt->get_result();
        $depositData = $depositResult->fetch_assoc();
        $totalPaid = $depositData['total_paid'] ?? 0;
        $remaining = $priceInfo['finaltotal'] - $totalPaid;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-out - TDTU Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
</head>
<body>
    <div class="container-fluid p-4">
        <h2 class="mb-4">Guest Check-out</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success || isset($_GET['success'])): ?>
            <div class="alert alert-success">Check-out completed successfully! 
                <?php if ($bookingId > 0): ?>
                    <a href="invoiceprint.php?id=<?php echo $bookingId; ?>" target="_blank" class="btn btn-sm btn-primary">Print Invoice</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Search -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone, booking ID, or room number..." value="<?php echo escapeOutput($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    <div class="col-md-2">
                        <a href="checkout.php" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="row">
            <!-- Booking List -->
            <div class="col-md-<?php echo $selectedBooking ? '6' : '12'; ?>">
                <div class="card">
                    <div class="card-header">
                        <h4>Checked-in Guests</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Room</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($booking = $result->fetch_assoc()): ?>
                                            <tr class="<?php echo $selectedBooking && $selectedBooking['id'] == $booking['id'] ? 'table-active' : ''; ?>">
                                                <td>#<?php echo escapeOutput($booking['id']); ?></td>
                                                <td><?php echo escapeOutput($booking['Name']); ?></td>
                                                <td><?php echo escapeOutput($booking['Email']); ?></td>
                                                <td>
                                                    <?php if ($booking['room_id']): ?>
                                                        Room #<?php echo escapeOutput($booking['room_id']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not assigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo escapeOutput($booking['checked_in_at'] ?? $booking['cin']); ?></td>
                                                <td><?php echo escapeOutput($booking['cout']); ?></td>
                                                <td>
                                                    <a href="checkout.php?booking_id=<?php echo $booking['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-warning">Check-out</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No checked-in guests</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Check-out Form -->
            <?php if ($selectedBooking): ?>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Check-out Booking #<?php echo escapeOutput($selectedBooking['id']); ?></h4>
                        </div>
                        <div class="card-body">
                            <p><strong>Guest:</strong> <?php echo escapeOutput($selectedBooking['Name']); ?></p>
                            <p><strong>Room:</strong> <?php echo $selectedBooking['room_id'] ? 'Room #' . escapeOutput($selectedBooking['room_id']) : 'Not assigned'; ?></p>
                            <p><strong>Check-in:</strong> <?php echo escapeOutput($selectedBooking['checked_in_at'] ?? $selectedBooking['cin']); ?></p>
                            <p><strong>Check-out:</strong> <?php echo escapeOutput($selectedBooking['cout']); ?></p>
                            <p><strong>Nights:</strong> <?php echo escapeOutput($selectedBooking['nodays']); ?></p>
                            
                            <hr>
                            <h5>Bill Summary</h5>
                            <p>Room Total: ₹<?php echo number_format($priceInfo['roomtotal'], 2); ?></p>
                            <p>Bed Total: ₹<?php echo number_format($priceInfo['bedtotal'], 2); ?></p>
                            <p>Meal Total: ₹<?php echo number_format($priceInfo['mealtotal'], 2); ?></p>
                            <p><strong>Total Bill: ₹<?php echo number_format($priceInfo['finaltotal'], 2); ?></strong></p>
                            
                            <?php if ($totalPaid > 0): ?>
                                <p>Deposit Paid: ₹<?php echo number_format($totalPaid, 2); ?></p>
                                <p><strong>Remaining: ₹<?php echo number_format($remaining, 2); ?></strong></p>
                            <?php else: ?>
                                <p><strong>Amount Due: ₹<?php echo number_format($priceInfo['finaltotal'], 2); ?></strong></p>
                            <?php endif; ?>
                            
                            <form method="POST" class="mt-4">
                                <input type="hidden" name="booking_id" value="<?php echo $selectedBooking['id']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Payment Method *</label>
                                    <select name="payment_method" class="form-control" required>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="ewallet">E-Wallet</option>
                                    </select>
                                </div>
                                
                                <button type="submit" name="process_checkout" class="btn btn-success w-100">Complete Check-out</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

