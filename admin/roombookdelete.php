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

// Check if booking can be deleted (only if status is Pending or Cancelled)
$checkStmt = $conn->prepare("SELECT status FROM roombook WHERE id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $booking = $checkResult->fetch_assoc();
    
    // Only allow delete if status is Pending or Cancelled
    if ($booking['status'] == 'Pending' || $booking['status'] == 'Cancelled') {
        $deleteStmt = $conn->prepare("DELETE FROM roombook WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();
        
        $userId = getUserId();
        if ($userId) {
            logActivity($conn, $userId, 'booking_deleted', 'roombook', $id, "Booking deleted");
        }
    }
}

header("Location: roombook.php");
exit();
?>
