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

// Get booking details
$stmt = $conn->prepare("SELECT * FROM roombook WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: roombook.php");
    exit();
}

$booking = $result->fetch_assoc();

// Update booking status to Cancelled
$updateStmt = $conn->prepare("UPDATE roombook SET status = 'Cancelled', updated_at = NOW() WHERE id = ?");
$updateStmt->bind_param("i", $id);
$updateStmt->execute();

// If payment exists, mark as Refunded
$checkPayment = $conn->prepare("SELECT id FROM payment WHERE id = ?");
$checkPayment->bind_param("i", $id);
$checkPayment->execute();
$paymentExists = $checkPayment->get_result()->num_rows > 0;

if ($paymentExists) {
    $refundStmt = $conn->prepare("UPDATE payment SET status = 'Refunded' WHERE id = ?");
    $refundStmt->bind_param("i", $id);
    $refundStmt->execute();
}

// If room was assigned, make it available again
if ($booking['room_id']) {
    $roomUpdateStmt = $conn->prepare("UPDATE room SET status = 'Available' WHERE id = ?");
    $roomUpdateStmt->bind_param("i", $booking['room_id']);
    $roomUpdateStmt->execute();
}

// Log activity
$userId = getUserId();
logActivity($conn, $userId, 'booking_cancelled', 'roombook', $id, 'Booking cancelled and refunded if payment exists');

header("Location: roombook.php?msg=cancelled");
exit();

