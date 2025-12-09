<?php
session_start();
include '../config.php';

// Xử lý thêm phòng
if (isset($_POST['addroom'])) {
    $typeofroom = mysqli_real_escape_string($conn, $_POST['troom']);
    $typeofbed = mysqli_real_escape_string($conn, $_POST['bed']);
    $country = mysqli_real_escape_string($conn, $_POST['country']);
    $room_number = mysqli_real_escape_string($conn, $_POST['room_number']);
    $floor = intval($_POST['floor']);

    // Kiểm tra xem phòng đã tồn tại chưa (trùng tọa độ)
    $check_sql = "SELECT * FROM room WHERE Country = '$country' AND floor = $floor AND room_number = '$room_number'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['error'] = "Room with this location already exists in this branch!";
    } else {
        $sql = "INSERT INTO room(type, bedding, Country, room_number, floor, status) VALUES ('$typeofroom', '$typeofbed', '$country', '$room_number', $floor, 'Available')";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $_SESSION['success'] = "Room added successfully!";
        } else {
            $_SESSION['error'] = "Error adding room: " . mysqli_error($conn);
        }
    }
    // Giữ nguyên chi nhánh hiện tại
    $selected_branch = $_POST['country'];
    header("Location: room.php?branch=" . urlencode($selected_branch));
    exit;
}

// Xử lý cập nhật trạng thái phòng
if (isset($_GET['update_status']) && isset($_GET['room_id'])) {
    $room_id = intval($_GET['room_id']);
    $new_status = $_GET['update_status'] == 'available' ? 'Available' : 'Occupied';
    $selected_branch = $_GET['branch'] ?? 'Ho Chi Minh city'; // Giữ chi nhánh hiện tại
    
    if ($new_status == 'Available') {
        // Lấy thông tin phòng hiện tại
        $room_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT current_booking_id, type, bedding, Country FROM room WHERE id = $room_id"));
        
        // Cập nhật trạng thái phòng
        $update_room_sql = "UPDATE room SET 
                          status = 'Available', 
                          current_booking_id = NULL, 
                          reserved_booking_id = NULL, 
                          reserved_until = NULL 
                          WHERE id = $room_id";
        mysqli_query($conn, $update_room_sql);
        
        // Nếu phòng có booking hiện tại, cập nhật lại trạng thái booking
        if (!empty($room_info['current_booking_id'])) {
            $booking_id = $room_info['current_booking_id'];
            
            // Cập nhật lại trạng thái booking trong roombook
            $update_booking_sql = "UPDATE roombook SET stat = 'Not Confirmed' WHERE id = $booking_id";
            mysqli_query($conn, $update_booking_sql);
            
            // Cập nhật lại trạng thái trong payment nếu có
            $update_payment_sql = "UPDATE payment SET status = 'Pending' WHERE id = $booking_id";
            mysqli_query($conn, $update_payment_sql);
        }
    } else {
        // Nếu chuyển sang Occupied, cập nhật trạng thái phòng
        $update_room_sql = "UPDATE room SET status = 'Occupied' WHERE id = $room_id";
        mysqli_query($conn, $update_room_sql);
    }
    
    header("Location: room.php?branch=" . urlencode($selected_branch));
    exit;
}

// Lấy giá trị chi nhánh từ URL (chỉ 2 chi nhánh)
$selected_branch = isset($_GET['branch']) ? $_GET['branch'] : 'Ho Chi Minh city';

// Xây dựng điều kiện WHERE mới cho bảng room
$room_branch_condition = '';
if (!empty($selected_branch)) {
    $room_branch_condition = " AND r.Country = '" . mysqli_real_escape_string($conn, $selected_branch) . "'";
}

$sql = "SELECT r.*, rb.Name as customer_name, rb.Email as customer_email, rb.Phone as customer_phone, rb.RoomType as room_type, rb.Bed as bed, rb.Meal as meal, rb.cin as checkin_date, rb.cout as checkout_date, rb.nodays as no_of_days 
        FROM room r 
        LEFT JOIN roombook rb ON r.current_booking_id = rb.id 
        WHERE 1 $room_branch_condition
        ORDER BY r.floor ASC, r.room_number ASC";

$re = mysqli_query($conn, $sql);

// Nhóm phòng theo tầng
$rooms_by_floor = array();
while ($row = mysqli_fetch_array($re)) {
    $rooms_by_floor[$row['floor']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TDTU - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .filter-section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .add-room-form {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }

        .floor-section {
            margin-bottom: 30px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .floor-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 15px;
            color: #495057;
        }

        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .room-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            position: relative;
            background: white;
        }

        .room-card.available {
            border-color: #28a745;
            background: #d4edda;
        }

        .room-card.occupied {
            border-color: #dc3545;
            background: #f8d7da;
        }

        .room-card.reserved {
            border-color: #ffc107;
            background: #fff3cd;
        }

        .room-number {
            font-size: 18px;
            font-weight: bold;
            margin: 8px 0;
        }

        .room-info {
            font-size: 12px;
            color: #666;
            margin: 4px 0;
        }

        .room-status {
            position: absolute;
            top: 5px;
            right: 5px;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            color: white;
        }

        .available .room-status {
            background: #28a745;
        }

        .occupied .room-status {
            background: #dc3545;
        }

        .reserved .room-status {
            background: #ffc107;
        }

        .booking-info {
            margin-top: 8px;
            padding: 6px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 11px;
        }

        .action-buttons {
            margin-top: 8px;
        }

        .btn-sm {
            margin: 1px;
            padding: 4px 8px;
            font-size: 11px;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: none;
            border-radius: 10px;
            width: 600px;
            text-align: center;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
    </style>
</head>

<body>
    <div class="filter-section">
        <label for="branchFilter" style="font-weight: bold; margin-right: 10px;">Branch:</label>
        <select id="branchFilter" onchange="applyBranchFilter()" style="padding: 5px; border-radius: 4px; border: 1px solid #ced4da;">
            <option value="Ho Chi Minh city" <?php echo $selected_branch == 'Ho Chi Minh city' ? 'selected' : ''; ?>>Ho Chi Minh City</option>
            <option value="Ha Noi" <?php echo $selected_branch == 'Ha Noi' ? 'selected' : ''; ?>>Ha Noi</option>
        </select>
    </div>

    <div class="add-room-form">
        <h5>Add New Room</h5>
        <form method="POST" class="row">
            <div class="col-md-2">
                <select name="troom" class="form-control" required>
                    <option value="">Type</option>
                    <option value="Superior Room">Superior</option>
                    <option value="Deluxe Room">Deluxe</option>
                    <option value="Guest House">Guest House</option>
                    <option value="Single Room">Single</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="bed" class="form-control" required>
                    <option value="">Bed</option>
                    <option value="Single">Single</option>
                    <option value="Double">Double</option>
                    <option value="Triple">Triple</option>
                    <option value="Quad">Quad</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="country" class="form-control" required>
                    <option value="Ho Chi Minh city">Ho Chi Minh City</option>
                    <option value="Ha Noi">Ha Noi</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="room_number" class="form-control" placeholder="Room No" required>
            </div>
            <div class="col-md-2">
                <input type="number" name="floor" class="form-control" min="1" max="10" value="1" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success" name="addroom">Add</button>
            </div>
        </form>

        <?php
        if (isset($_SESSION['success'])) {
            echo "<div class='alert alert-success mt-2'>{$_SESSION['success']}</div>";
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo "<div class='alert alert-danger mt-2'>{$_SESSION['error']}</div>";
            unset($_SESSION['error']);
        }
        ?>
    </div>

    <?php foreach ($rooms_by_floor as $floor => $rooms): ?>
    <div class="floor-section">
        <div class="floor-title">Floor <?php echo $floor; ?></div>
        <div class="room-grid">
        <?php foreach ($rooms as $room): ?>
        <div class='room-card <?php echo strtolower($room['status']); ?>'>
            <div class='room-status'><?php echo $room['status']; ?></div>
            <div class='room-number'><?php echo $room['room_number']; ?></div>
            <div class='room-info'><?php echo $room['type']; ?></div>
            <div class='room-info'>Bed: <?php echo $room['bedding']; ?></div>

            <?php if ($room['status'] == 'Occupied'): ?>
                <!-- Debug info -->
                <div style="font-size: 10px; color: red;">
                    Debug: Status=<?php echo $room['status']; ?>, Customer=<?php echo !empty($room['customer_name']) ? 'YES' : 'NO'; ?>, ID=<?php echo $room['id']; ?>
                </div>
            <?php endif; ?>

            <?php if ($room['status'] == 'Occupied' && !empty($room['current_booking_id']) && !empty($room['customer_name'])): ?>
            <div class='booking-info' style='display:none; border: 1px solid #007bff; margin-top: 8px; padding: 8px; background: #e7f3ff; border-radius: 4px;' id='info_<?php echo $room['id']; ?>'>
                <strong>Name:</strong> <?php echo htmlspecialchars($room['customer_name']); ?><br>
                <strong>Email:</strong> <?php echo htmlspecialchars($room['customer_email']); ?><br>
                <strong>Phone:</strong> <?php echo htmlspecialchars($room['customer_phone']); ?><br>
                <strong>Room Type:</strong> <?php echo htmlspecialchars($room['room_type']); ?><br>
                <strong>Bed:</strong> <?php echo htmlspecialchars($room['bed']); ?><br>
                <strong>Meal:</strong> <?php echo htmlspecialchars($room['meal']); ?><br>
                <strong>Check-in:</strong> <?php echo date('d/m/Y', strtotime($room['checkin_date'])); ?><br>
                <strong>Checkout:</strong> <?php echo date('d/m/Y', strtotime($room['checkout_date'])); ?><br>
                <strong>Days:</strong> <?php echo $room['no_of_days']; ?>
            </div>
            <?php endif; ?>
    
            <?php if ($room['status'] == 'Reserved' && !empty($room['reserved_until'])): ?>
            <div class='booking-info'>
                <strong>Reserved Until:</strong> <?php echo date('d/m H:i', strtotime($room['reserved_until']) + 6*3600); ?><br>
                <?php
                $time_left = strtotime($room['reserved_until']) - time();
                $hours_left = floor($time_left / 3600);
                $minutes_left = floor(($time_left % 3600) / 60);
                echo "<strong>Time Left:</strong> $hours_left h $minutes_left m";
                ?>
            </div>
            <?php endif; ?>
    
            <div class='action-buttons'>
                <?php if ($room['status'] == 'Available'): ?>
                    <span class='btn btn-sm btn-secondary disabled'>Occupied</span>
                <?php elseif ($room['status'] == 'Occupied'): ?>
                    <button onclick="toggleBookingInfo(<?php echo $room['id']; ?>)" class='btn btn-sm btn-info'>View Info</button>
                    <a href='?update_status=available&room_id=<?php echo $room['id']; ?>&branch=<?php echo urlencode($selected_branch); ?>' class='btn btn-sm btn-success'>Available</a>
                <?php elseif ($room['status'] == 'Reserved'): ?>
                    <a href='?update_status=available&room_id=<?php echo $room['id']; ?>&branch=<?php echo urlencode($selected_branch); ?>' class='btn btn-sm btn-success'>Available</a>
                <?php endif; ?>
                <a href='roomedit.php?id=<?php echo $room['id']; ?>&branch=<?php echo urlencode($selected_branch); ?>' class='btn btn-sm btn-primary'>Edit</a>
                <a href='roomdelete.php?id=<?php echo $room['id']; ?>&branch=<?php echo urlencode($selected_branch); ?>' class='btn btn-sm btn-danger' onclick='return confirm("Are you sure you want to delete this room?")'>Delete</a>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

<script>
function applyBranchFilter() {
    const branch = document.getElementById('branchFilter').value;
    const url = new URL(window.location.href);
    url.searchParams.set('branch', branch);
    window.location.href = url.toString();
}

function toggleBookingInfo(roomId) {
    console.log('Toggle booking info for room: ' + roomId);
    
    // Thử cả 2 cách để tìm phần tử
    let infoDiv = document.getElementById('info_' + roomId);
    
    if (infoDiv) {
        console.log('Found element with ID: info_' + roomId);
        if (infoDiv.style.display === 'none' || infoDiv.style.display === '') {
            infoDiv.style.display = 'block';
        } else {
            infoDiv.style.display = 'none';
        }
    } else {
        console.log('Element with ID info_' + roomId + ' not found');
        // Kiểm tra xem phần tử có tồn tại không bằng cách tìm tất cả các phần tử có ID chứa 'info_'
        const allElements = document.querySelectorAll('[id*="info_"]');
        console.log('Found ' + allElements.length + ' elements with ID containing "info_"');
        allElements.forEach(function(el) {
            console.log('Element ID: ' + el.id);
        });
    }
}
</script>
</body>
</html>