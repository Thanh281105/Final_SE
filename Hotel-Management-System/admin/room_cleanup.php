<?php
include '../config.php';

$cleanup_sql = "UPDATE room SET status = 'Available', reserved_booking_id = NULL, reserved_until = NULL
                WHERE status = 'Reserved' AND reserved_until < NOW()";

mysqli_query($conn, $cleanup_sql);

echo "Room cleanup completed. " . mysqli_affected_rows($conn) . " rooms released.";
?>