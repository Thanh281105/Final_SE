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

        // Check availability (simple: exists room with matching type & bedding)
        $check_room = "SELECT COUNT(*) as count FROM room WHERE type = '$RoomType' AND bedding = '$Bed'";
        $room_result = mysqli_query($conn, $check_room);
        $room_count = mysqli_fetch_assoc($room_result)['count'];

        if ($status == 'Confirmed' && $room_count == 0) {
            $_SESSION['error'] = "Room $RoomType ($Bed) does not exist!";
            header("Location: roombook.php");
            exit;
        }

        // Calculate totals (exact same as roombookedit.php)
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

            $mail->setFrom('phamnguyenanhsva@gmail.com', 'Hotel TDTU');
            $mail->addAddress($Email, $Name);

            $mail->isHTML(true);
            $mail->Subject = $status == 'Confirmed' ? 'Booking confirmed successfully!' : 'Booking declined';

            $status_vn = $status == 'Confirmed' ? 'CONFIRMED' : 'REJECTED';
            $total_vnd = number_format($finaltotal, 0, ',', '.') . ' VND';

            $mail->Body = "
                <h2>Booking status: <strong style='color: " . ($status == 'Confirmed' ? 'green' : 'red') . "'>$status_vn</strong></h2>
                <p>Dear <strong>$Name</strong>,</p>
                <p>Your Reservation:</p>
                <ul>
                    <li>Room Type: $RoomType</li>
                    <li>Room Number: $NoofRoom</li>
                    <li>Check-in: $cin</li>
                    <li>Check-out: $cout</li>
                    <li>Total: <strong>$total_vnd</strong></li>
                </ul>
                " . ($status == 'Confirmed' ? '<p>Please pay before coming!</p>' : '<p>Sorry for the inconvenience!</p>');

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
                    <th>Country</th> 
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
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>