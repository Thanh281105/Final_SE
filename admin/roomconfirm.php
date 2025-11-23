<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    header("Location: roombook.php");
    exit();
}

// Get booking
$stmt = $conn->prepare("SELECT * FROM roombook WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: roombook.php");
    exit();
}

$row = $result->fetch_assoc();
$Name = $row['Name'];
$Email = $row['Email'];
$Country = $row['Country'];
$Phone = $row['Phone'];
$RoomType = $row['RoomType'];
$Bed = $row['Bed'];
$NoofRoom = $row['NoofRoom'];
$Meal = $row['Meal'];
$cin = $row['cin'];
$cout = $row['cout'];
$noofday = $row['nodays'];
$stat = $row['stat'];
$status = $row['status'] ?? 'Pending';

// Only confirm if status is Pending
if ($status == 'Pending' || $stat == "NotConfirm") {
    // Update booking status
    $updateStmt = $conn->prepare("UPDATE roombook SET stat = 'Confirm', status = 'Confirmed', updated_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $id);
    $updateStmt->execute();

    if ($updateStmt->affected_rows >= 0) {
        // Calculate price using function
        $priceInfo = calculateBookingPrice($RoomType, $Bed, $Meal, $noofday, $NoofRoom);
        
        // Check if payment already exists
        $checkPayment = $conn->prepare("SELECT id FROM payment WHERE id = ?");
        $checkPayment->bind_param("i", $id);
        $checkPayment->execute();
        $paymentExists = $checkPayment->get_result()->num_rows > 0;
        
        if (!$paymentExists) {
            // Insert payment
            $insertStmt = $conn->prepare("INSERT INTO payment(id, Name, Email, RoomType, Bed, NoofRoom, cin, cout, noofdays, roomtotal, bedtotal, meal, mealtotal, finaltotal, status, type, created_at) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Booking', NOW())");
            $insertStmt->bind_param("issssissssddddss", $id, $Name, $Email, $RoomType, $Bed, $NoofRoom, $cin, $cout, $noofday, $priceInfo['roomtotal'], $priceInfo['bedtotal'], $Meal, $priceInfo['mealtotal'], $priceInfo['finaltotal']);
            $insertStmt->execute();
        }
        
        $userId = getUserId();
        logActivity($conn, $userId, 'booking_confirmed', 'roombook', $id, "Booking confirmed by admin");
        
        header("Location: roombook.php?msg=confirmed");
        exit();
    }
} else {
    header("Location: roombook.php?error=already_confirmed");
    exit();
}
?>