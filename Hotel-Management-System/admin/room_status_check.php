<?php
include '../config.php';

// Cập nhật phòng đã hết hạn checkout về trạng thái Available
$update_occupied_sql = "UPDATE room r
                        JOIN roombook rb ON r.current_booking_id = rb.id
                        SET r.status = 'Available', r.current_booking_id = NULL
                        WHERE r.status = 'Occupied' AND rb.cout < CURDATE()";

mysqli_query($conn, $update_occupied_sql);

// Cập nhật phòng đã hết hạn chờ thanh toán về trạng thái Available
$update_reserved_sql = "UPDATE room 
                        SET status = 'Available', reserved_booking_id = NULL, reserved_until = NULL
                        WHERE status = 'Reserved' AND reserved_until < NOW()";

mysqli_query($conn, $update_reserved_sql);

echo "Room status check completed. " . mysqli_affected_rows($conn) . " rooms updated.";
?>