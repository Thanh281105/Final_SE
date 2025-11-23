<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    header("Location: staff.php");
    exit();
}

$deleteStmt = $conn->prepare("DELETE FROM staff WHERE id = ?");
$deleteStmt->bind_param("i", $id);
$deleteStmt->execute();

$userId = getUserId();
logActivity($conn, $userId, 'staff_deleted', 'staff', $id, "Staff deleted");

header("Location: staff.php?msg=deleted");
exit();
?>
