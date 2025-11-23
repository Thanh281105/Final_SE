<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    header("Location: payment.php");
    exit();
}

$deleteStmt = $conn->prepare("DELETE FROM payment WHERE id = ?");
$deleteStmt->bind_param("i", $id);
$deleteStmt->execute();

$userId = getUserId();
logActivity($conn, $userId, 'payment_deleted', 'payment', $id, "Payment record deleted");

header("Location: payment.php?msg=deleted");
exit();
?>
