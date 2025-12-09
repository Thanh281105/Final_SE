<?php
include '../config.php';
session_start();

// Check admin login
if (!isset($_SESSION['usermail'])) {
    header("Location: ../index.php");
    exit;
}

// Handle confirm/reject
if (isset($_POST['action'])) {
    $id = $_POST['id'];
    $status = $_POST['action'] == 'confirm' ? 'Confirmed' : 'Rejected';

    // Fetch booking
    $sql = "SELECT * FROM roombook WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $Name = $row['Name'];
        $Email = $row['Email'];
        $RoomType = $row['RoomType'];
        $Bed = $row['Bed'];
        $NoofRoom = intval($row['NoofRoom']);  // Safe cast to int
        $Meal = $row['Meal'];
        $nodays = $row['nodays'];
        $cin = $row['cin'];
        $cout = $row['cout'];

        // Additional check: cin < cout (in case edited)
        if (strtotime($cin) >= strtotime($cout)) {
            $_SESSION['error'] = "Invalid dates: Check-out must be after check-in!";
            header("Location: roombook.php");
            exit;
        }

        // Calculate totals (đặt trước đoạn kiểm tra phòng trống)
        $type_of_room = match($RoomType) {
            'Superior Room' => 3000,
            'Deluxe Room' => 2000,
            'Guest House' => 1500,
            'Single Room' => 1000,
            default => 0
        };

        $type_of_bed = match($Bed) {
            'Single' => $type_of_room * 1 / 100,
            'Double' => $type_of_room * 2 / 100,
            'Triple' => $type_of_room * 3 / 100,
            'Quad' => $type_of_room * 4 / 100,
            default => 0
        };

        $type_of_meal = match($Meal) {
            'Breakfast' => $type_of_bed * 2,
            'Half Board' => $type_of_bed * 3,
            'Full Board' => $type_of_bed * 4,
            default => 0
        };

        $roomtotal = $type_of_room * $nodays * $NoofRoom * 1000;
        $bedtotal = $type_of_bed * $nodays * $NoofRoom * 1000;
        $mealtotal = $type_of_meal * $nodays * $NoofRoom * 1000;
        $finaltotal = $roomtotal + $bedtotal + $mealtotal;

        // Kiểm tra đầy đủ điều kiện phòng
        if ($status == 'Confirmed') {
            // Lấy tổng số phòng loại này trong chi nhánh với đúng loại giường
            $total_rooms_sql = "SELECT COUNT(*) as total_rooms FROM room 
                                WHERE type = '$RoomType' 
                                AND bedding = '$Bed'
                                AND Country = '$Country'";

            $total_result = mysqli_query($conn, $total_rooms_sql);
            $total_rooms = mysqli_fetch_assoc($total_result)['total_rooms'];

            // Lấy số phòng đang có trạng thái khác Available (đang bận hoặc chờ)
            $non_available_rooms_sql = "SELECT COUNT(*) as non_available_rooms FROM room 
                                        WHERE type = '$RoomType' 
                                        AND bedding = '$Bed'
                                        AND Country = '$Country'
                                        AND status != 'Available'";

            $non_available_result = mysqli_query($conn, $non_available_rooms_sql);
            $non_available_rooms = mysqli_fetch_assoc($non_available_result)['non_available_rooms'];

            // Tính số phòng còn trống
            $available_rooms = $total_rooms - $non_available_rooms;
    
            // Kiểm tra nếu số phòng đặt vượt quá số phòng trống
            if ($NoofRoom > $available_rooms) {
                $_SESSION['error'] = "Not enough rooms available. Only $available_rooms rooms left in $row[Country] branch (Type: $RoomType, Bed: $Bed).";
                header("Location: roombook.php");
                exit;
            }
    
            // Thêm bản ghi vào bảng payment nếu chưa tồn tại
            $check_payment = mysqli_query($conn, "SELECT * FROM payment WHERE id = $id");
            if (mysqli_num_rows($check_payment) == 0) {
                $payment_sql = "INSERT INTO payment (id, Name, Email, RoomType, Bed, NoofRoom, meal, cin, cout, noofdays, roomtotal, bedtotal, mealtotal, finaltotal, status) 
                                VALUES ($id, '$Name', '$Email', '$RoomType', '$Bed', $NoofRoom, '$Meal', '$cin', '$cout', $nodays, $roomtotal, $bedtotal, $mealtotal, $finaltotal, 'Pending')";
                mysqli_query($conn, $payment_sql);
            }

            // Lấy các ID phòng cụ thể để cập nhật
            $reserved_until = date('Y-m-d H:i:s', strtotime('+24 hours')); // Hết hạn sau 24h

            $room_ids_sql = "SELECT id FROM room 
                             WHERE type = '$RoomType' 
                             AND bedding = '$Bed'
                             AND Country = '$row[Country]'
                             AND status = 'Available'
                             LIMIT $NoofRoom";

            $room_ids_result = mysqli_query($conn, $room_ids_sql);
            $room_ids = array();
            while ($room = mysqli_fetch_assoc($room_ids_result)) {
                $room_ids[] = $room['id'];
            }

            if (count($room_ids) < $NoofRoom) {
                $_SESSION['error'] = "Not enough available rooms to reserve. Only " . count($room_ids) . " rooms available.";
                header("Location: roombook.php");
                exit;
            }

            // Cập nhật từng phòng theo ID
            foreach ($room_ids as $room_id) {
                $update_room_sql = "UPDATE room SET status = 'Reserved', reserved_booking_id = $id, reserved_until = '$reserved_until' WHERE id = $room_id";
                mysqli_query($conn, $update_room_sql);
            }
        }

        // Update roombook status
        mysqli_query($conn, "UPDATE roombook SET stat = '$status' WHERE id = $id");

        if ($status == 'Confirmed') {
            $check_payment = mysqli_query($conn, "SELECT * FROM payment WHERE id = $id");
            if (mysqli_num_rows($check_payment) == 0) {
                $payment_sql = "INSERT INTO payment (id, Name, Email, RoomType, Bed, NoofRoom, meal, cin, cout, noofdays, roomtotal, bedtotal, mealtotal, finaltotal, status) 
                                VALUES ($id, '$Name', '$Email', '$RoomType', '$Bed', $NoofRoom, '$Meal', '$cin', '$cout', $nodays, $roomtotal, $bedtotal, $mealtotal, $finaltotal, 'Pending')";
                mysqli_query($conn, $payment_sql);
            }
        }

        // Send email
        require_once '../vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'phamnguyenanhsva@gmail.com';
            $mail->Password   = 'okad qsbx jplr rznr';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('your_email@gmail.com', 'Hotel TDTU');
            $mail->addAddress($Email, $Name);

            $mail->isHTML(true);
            $mail->Subject = $status == 'Confirmed' ? 'Booking confirmed successfully!' : 'Booking declined';

            $status_vn = $status == 'Confirmed' ? 'CONFIRMED' : 'REJECTED';
            $total_vnd = number_format($finaltotal, 0, ',', '.') . ' VND';

            if ($status == 'Confirmed') {
                $payment_deadline = date('d/m/Y H:i', strtotime('+24 hours', strtotime($cin))); // Có thể thay đổi thời gian
                $mail->Body = "
                    <h2>Booking status: <strong style='color: green'>CONFIRMED</strong></h2>
                    <p>Dear <strong>$Name</strong>,</p>
                    <p>Your Reservation has been confirmed. Please complete payment before <strong>$payment_deadline</strong> to secure your booking.</p>
                    <p>Your Reservation Details:</p>
                    <ul>
                        <li>Room Type: $RoomType</li>
                        <li>Number of Rooms: $NoofRoom</li>
                        <li>Check-in: " . date('d/m/Y', strtotime($cin)) . "</li>
                        <li>Check-out: " . date('d/m/Y', strtotime($cout)) . "</li>
                        <li>Total Amount: <strong>$total_vnd</strong></li>
                        <li><strong>Payment Deadline: $payment_deadline</strong></li>
                    </ul>
                    <p><strong>Important:</strong> Your booking will be automatically canceled if payment is not completed by the deadline.</p>
                    <p>Please contact us if you need any assistance with payment.</p>";
            } else {
                $mail->Body = "
                    <h2>Booking status: <strong style='color: red'>REJECTED</strong></h2>
                    <p>Dear <strong>$Name</strong>,</p>
                    <p>Unfortunately, your reservation has been declined.</p>
                    <p>Reservation Details:</p>
                    <ul>
                        <li>Room Type: $RoomType</li>
                        <li>Number of Rooms: $NoofRoom</li>
                        <li>Check-in: " . date('d/m/Y', strtotime($cin)) . "</li>
                        <li>Check-out: " . date('d/m/Y', strtotime($cout)) . "</li>
                        <li>Total Amount: <strong>$total_vnd</strong></li>
                    </ul>
                    <p>Sorry for the inconvenience!</p>";
            }

            $mail->send();
            $_SESSION['success'] = "Updated and email sent successfully!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Email sending error: {$mail->ErrorInfo}";
        }
    }
    header("Location: roombook.php");
    exit;
}

// Fetch all bookings
$sql = "SELECT * FROM roombook ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Booking</title>
    <link rel="stylesheet" href="./css/admin.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: center; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .status-not-confirmed { background-color: #fff3cd; color: #856404; } 
        .status-confirmed { background-color: #d1edff; color: #0c5460; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .btn { padding: 5px 10px; margin: 2px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-confirm { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-edit, .btn-delete { background: #007bff; color: white; text-decoration: none; }
        .alert { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .table-container {
            overflow: auto; /* Enable scroll */
            max-height: 500px; /* Adjust if needed */
            max-width: 100%;
        }
    </style>
</head>
<body>
    <h2><i class="fas fa-bed"></i> Room Booking Management</h2>

    <?php if (isset($_SESSION['success'])) { echo "<div class='alert alert-success'>{$_SESSION['success']}</div>"; unset($_SESSION['success']); } ?>
    <?php if (isset($_SESSION['error'])) { echo "<div class='alert alert-error'>{$_SESSION['error']}</div>"; unset($_SESSION['error']); } ?>
    
    <div class="table-container table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Branch</th> 
                    <th>Type of Room</th>
                    <th>Bed</th>
                    <th>Meal</th>
                    <th>No of Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>No of Days</th>
                    <th>State</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['Email']; ?></td>
                    <td><?php echo $row['Phone']; ?></td>
                    <td><?php echo $row['Country']; ?></td> 
                    <td><?php echo $row['RoomType']; ?></td>
                    <td><?php echo $row['Bed']; ?></td>
                    <td><?php echo $row['Meal']; ?></td>
                    <td><?php echo $row['NoofRoom']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['cin'])); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['cout'])); ?></td>
                    <td><?php echo $row['nodays']; ?></td>
                    <td class="status-<?php echo strtolower(str_replace(' ', '-', $row['stat'])); ?>"><?php echo $row['stat']; ?></td> 
                    <td>
                        <a href="roombookedit.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">Edit</a>
                        <a href="roombookdelete.php?id=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete?')">Delete</a>
                        <?php if ($row['stat'] == 'Not Confirmed'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="action" value="confirm" class="btn btn-confirm">Confirm</button>
                                <button type="submit" name="action" value="reject" class="btn btn-reject">Reject</button>
                            </form>
        
                            <!-- Hiển thị thông tin phòng trống -->
                            <div style="margin-top: 5px; font-size: 12px; color: #666;">
                                <?php
                                // Tính số phòng trống
                                $RoomType = $row['RoomType'];
                                $Bed = $row['Bed'];  // Thêm loại giường
                                $Country = $row['Country'];
                                $cin = $row['cin'];
                                $cout = $row['cout'];
            
                                // Lấy tổng số phòng loại này trong chi nhánh với đúng loại giường
                                $total_rooms_sql = "SELECT COUNT(*) as total_rooms FROM room 
                                                    WHERE type = '$RoomType' 
                                                    AND bedding = '$Bed'
                                                    AND Country = '$Country'";
            
                                $total_result = mysqli_query($conn, $total_rooms_sql);
                                $total_rooms = mysqli_fetch_assoc($total_result)['total_rooms'];
                                
                                // Lấy số phòng đang có trạng thái khác Available (đang bận hoặc chờ)
                                $non_available_rooms_sql = "SELECT COUNT(*) as non_available_rooms FROM room 
                                                            WHERE type = '$RoomType' 
                                                            AND bedding = '$Bed'
                                                            AND Country = '$Country'
                                                            AND status != 'Available'";
            
                                $non_available_result = mysqli_query($conn, $non_available_rooms_sql);
                                $non_available_rooms = mysqli_fetch_assoc($non_available_result)['non_available_rooms'];
                                
                                // Tính số phòng còn trống
                                $available_rooms = $total_rooms - $non_available_rooms;
            
                                echo "<strong>Available:</strong> $available_rooms rooms (Bed: $Bed)";
                                ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>