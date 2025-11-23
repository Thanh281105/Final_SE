<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    header("Location: room.php");
    exit();
}

// Check if room has active bookings
$checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM roombook WHERE room_id = ? AND status NOT IN ('Checked-out', 'Cancelled')");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$checkData = $checkResult->fetch_assoc();

if ($checkData['count'] > 0) {
    header("Location: room.php?error=cannot_delete");
    exit();
}

// Delete room
$deleteStmt = $conn->prepare("DELETE FROM room WHERE id = ?");
$deleteStmt->bind_param("i", $id);
$deleteStmt->execute();

$userId = getUserId();
logActivity($conn, $userId, 'room_deleted', 'room', $id, "Room deleted");

header("Location: room.php?msg=deleted");
exit();
?>
