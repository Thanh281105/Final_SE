<?php
session_start();
include '../config.php';

if (!isset($_GET['id'])) {
    header("Location: room.php");
    exit;
}

$room_id = intval($_GET['id']);

// Kiểm tra xem phòng có đang được sử dụng không
$check_sql = "SELECT * FROM roombook WHERE id = (SELECT current_booking_id FROM room WHERE id = $room_id) AND stat = 'Occupied'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    header("Location: room.php?error=Cannot delete room that is currently occupied");
    exit;
}

// Xóa phòng
$delete_sql = "DELETE FROM room WHERE id = $room_id";
if (mysqli_query($conn, $delete_sql)) {
    header("Location: room.php?success=Room deleted successfully");
} else {
    header("Location: room.php?error=Error deleting room: " . mysqli_error($conn));
}
?>