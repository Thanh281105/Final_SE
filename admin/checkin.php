<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$bookingId = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

$error = '';
$success = '';

// Handle check-in
if (isset($_POST['process_checkin'])) {
    $bookingId = intval($_POST['booking_id']);
    $roomId = intval($_POST['room_id']);
    $deposit = isset($_POST['deposit']) ? floatval($_POST['deposit']) : 0;
    
    // Get booking
    $bookingStmt = $conn->prepare("SELECT * FROM roombook WHERE id = ? AND status = 'Confirmed'");
    $bookingStmt->bind_param("i", $bookingId);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    
    if ($bookingResult->num_rows == 0) {
        $error = 'Booking not found or not confirmed';
    } else {
        $booking = $bookingResult->fetch_assoc();
        
        // Check room availability
        if (!isRoomAvailable($conn, $roomId, $booking['cin'], $booking['cout'], $bookingId)) {
            $error = 'Selected room is not available for these dates';
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update booking
                $updateBooking = $conn->prepare("UPDATE roombook SET room_id = ?, status = 'Checked-in', checked_in_at = NOW(), updated_at = NOW() WHERE id = ?");
                $updateBooking->bind_param("ii", $roomId, $bookingId);
                $updateBooking->execute();
                
                // Update room status
                $updateRoom = $conn->prepare("UPDATE room SET status = 'Occupied' WHERE id = ?");
                $updateRoom->bind_param("i", $roomId);
                $updateRoom->execute();
                
                // Insert deposit payment if provided
                if ($deposit > 0) {
                    $priceInfo = calculateBookingPrice($booking['RoomType'], $booking['Bed'], $booking['Meal'], $booking['nodays'], $booking['NoofRoom']);
                    
                    $insertPayment = $conn->prepare("INSERT INTO payment (id, Name, Email, RoomType, Bed, NoofRoom, cin, cout, noofdays, roomtotal, bedtotal, meal, mealtotal, finaltotal, method, status, transaction_id, type, created_at) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'cash', 'Success', ?, 'Deposit', NOW())");
                    $transactionId = 'DEP' . time();
                    $insertPayment->bind_param("issssissssddddss", $bookingId, $booking['Name'], $booking['Email'], $booking['RoomType'], $booking['Bed'], $booking['NoofRoom'], $booking['cin'], $booking['cout'], $booking['nodays'], $priceInfo['roomtotal'], $priceInfo['bedtotal'], $booking['Meal'], $priceInfo['mealtotal'], $priceInfo['finaltotal'], $transactionId);
                    $insertPayment->execute();
                }
                
                $conn->commit();
                
                $userId = getUserId();
                logActivity($conn, $userId, 'checkin_completed', 'roombook', $bookingId, "Guest checked in, room $roomId assigned");
                
                $success = 'Check-in completed successfully!';
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Failed to process check-in: ' . $e->getMessage();
            }
        }
    }
}

// Get bookings ready for check-in
$sql = "SELECT rb.* FROM roombook rb 
        WHERE rb.status = 'Confirmed' 
        AND rb.cin <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        AND rb.cin >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
$params = array();
$types = '';

if (!empty($search)) {
    $sql .= " AND (rb.Name LIKE ? OR rb.Email LIKE ? OR rb.Phone LIKE ? OR rb.id = ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = is_numeric($search) ? intval($search) : 0;
    $types .= 'sssi';
}

$sql .= " ORDER BY rb.cin ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get selected booking for check-in
$selectedBooking = null;
if ($bookingId > 0) {
    $bookingStmt = $conn->prepare("SELECT * FROM roombook WHERE id = ?");
    $bookingStmt->bind_param("i", $bookingId);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    if ($bookingResult->num_rows > 0) {
        $selectedBooking = $bookingResult->fetch_assoc();
        
        // Get available rooms
        $availableRooms = checkAvailability($conn, $selectedBooking['cin'], $selectedBooking['cout'], $selectedBooking['RoomType']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in - TDTU Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
</head>
<body>
    <div class="container-fluid p-4">
        <h2 class="mb-4">Guest Check-in</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo escapeOutput($success); ?></div>
        <?php endif; ?>
        
        <!-- Search -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone, or booking ID..." value="<?php echo escapeOutput($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    <div class="col-md-2">
                        <a href="checkin.php" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="row">
            <!-- Booking List -->
            <div class="col-md-<?php echo $selectedBooking ? '6' : '12'; ?>">
                <div class="card">
                    <div class="card-header">
                        <h4>Confirmed Bookings (Ready for Check-in)</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Room Type</th>
                                        <th>Check-in</th>
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
                                                <td><?php echo escapeOutput($booking['RoomType']); ?></td>
                                                <td><?php echo escapeOutput($booking['cin']); ?></td>
                                                <td>
                                                    <a href="checkin.php?booking_id=<?php echo $booking['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-primary">Check-in</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No bookings ready for check-in</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Check-in Form -->
            <?php if ($selectedBooking && !empty($availableRooms)): ?>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Check-in Booking #<?php echo escapeOutput($selectedBooking['id']); ?></h4>
                        </div>
                        <div class="card-body">
                            <p><strong>Guest:</strong> <?php echo escapeOutput($selectedBooking['Name']); ?></p>
                            <p><strong>Email:</strong> <?php echo escapeOutput($selectedBooking['Email']); ?></p>
                            <p><strong>Room Type:</strong> <?php echo escapeOutput($selectedBooking['RoomType']); ?></p>
                            <p><strong>Check-in:</strong> <?php echo escapeOutput($selectedBooking['cin']); ?></p>
                            <p><strong>Check-out:</strong> <?php echo escapeOutput($selectedBooking['cout']); ?></p>
                            
                            <form method="POST">
                                <input type="hidden" name="booking_id" value="<?php echo $selectedBooking['id']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Select Room *</label>
                                    <select name="room_id" class="form-control" required>
                                        <option value="">Choose a room...</option>
                                        <?php foreach ($availableRooms as $room): ?>
                                            <option value="<?php echo $room['id']; ?>">
                                                Room #<?php echo escapeOutput($room['id']); ?> - 
                                                <?php echo escapeOutput($room['type']); ?> - 
                                                <?php echo escapeOutput($room['bedding']); ?> - 
                                                ₹<?php echo number_format($room['price'], 2); ?>/night
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Deposit Amount (Optional)</label>
                                    <input type="number" name="deposit" class="form-control" min="0" step="100" value="0">
                                </div>
                                
                                <button type="submit" name="process_checkin" class="btn btn-success w-100">Complete Check-in</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php elseif ($selectedBooking && empty($availableRooms)): ?>
                <div class="col-md-6">
                    <div class="alert alert-warning">No available rooms of type "<?php echo escapeOutput($selectedBooking['RoomType']); ?>" for the selected dates.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

