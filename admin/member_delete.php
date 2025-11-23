<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    header("Location: members.php");
    exit();
}

// Deactivate member (don't delete to keep history)
$updateStmt = $conn->prepare("UPDATE signup SET is_active = 0, updated_at = NOW() WHERE UserID = ?");
$updateStmt->bind_param("i", $id);
$updateStmt->execute();

$userId = getUserId();
logActivity($conn, $userId, 'member_deactivated', 'signup', $id, "Member deactivated");

header("Location: members.php?msg=deactivated");
exit();

