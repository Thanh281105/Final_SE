<?php
session_start();
include '../config.php';

if (!isset($_GET['id'])) {
    header("Location: room.php");
    exit;
}

$room_id = intval($_GET['id']);

// Lấy thông tin phòng
$room_sql = "SELECT * FROM room WHERE id = $room_id";
$room_result = mysqli_query($conn, $room_sql);
$room = mysqli_fetch_assoc($room_result);

if (!$room) {
    header("Location: room.php");
    exit;
}

// Xử lý cập nhật phòng
if (isset($_POST['update_room'])) {
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $bedding = mysqli_real_escape_string($conn, $_POST['bedding']);
    $country = mysqli_real_escape_string($conn, $_POST['country']);
    $room_number = mysqli_real_escape_string($conn, $_POST['room_number']);
    $floor = intval($_POST['floor']);
    
    // Kiểm tra trùng tọa độ (trừ phòng hiện tại)
    $check_sql = "SELECT * FROM room WHERE Country = '$country' AND floor = $floor AND room_number = '$room_number' AND id != $room_id";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Room with this location already exists in this branch!";
    } else {
        $update_sql = "UPDATE room SET type = '$type', bedding = '$bedding', Country = '$country', room_number = '$room_number', floor = $floor WHERE id = $room_id";
        if (mysqli_query($conn, $update_sql)) {
            header("Location: room.php?success=Room updated successfully");
            exit;
        } else {
            $error = "Error updating room: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Room</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="type" class="form-label">Room Type</label>
                <select name="type" class="form-control" required>
                    <option value="Superior Room" <?php echo $room['type'] == 'Superior Room' ? 'selected' : ''; ?>>Superior Room</option>
                    <option value="Deluxe Room" <?php echo $room['type'] == 'Deluxe Room' ? 'selected' : ''; ?>>Deluxe Room</option>
                    <option value="Guest House" <?php echo $room['type'] == 'Guest House' ? 'selected' : ''; ?>>Guest House</option>
                    <option value="Single Room" <?php echo $room['type'] == 'Single Room' ? 'selected' : ''; ?>>Single Room</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="bedding" class="form-label">Bedding Type</label>
                <select name="bedding" class="form-control" required>
                    <option value="Single" <?php echo $room['bedding'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                    <option value="Double" <?php echo $room['bedding'] == 'Double' ? 'selected' : ''; ?>>Double</option>
                    <option value="Triple" <?php echo $room['bedding'] == 'Triple' ? 'selected' : ''; ?>>Triple</option>
                    <option value="Quad" <?php echo $room['bedding'] == 'Quad' ? 'selected' : ''; ?>>Quad</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="country" class="form-label">Branch</label>
                <select name="country" class="form-control" required>
                    <option value="Ho Chi Minh city" <?php echo $room['Country'] == 'Ho Chi Minh city' ? 'selected' : ''; ?>>Ho Chi Minh City</option>
                    <option value="Ha Noi" <?php echo $room['Country'] == 'Ha Noi' ? 'selected' : ''; ?>>Ha Noi</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="room_number" class="form-label">Room Number</label>
                <input type="text" name="room_number" class="form-control" value="<?php echo htmlspecialchars($room['room_number']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="floor" class="form-label">Floor</label>
                <input type="number" name="floor" class="form-control" value="<?php echo $room['floor']; ?>" min="1" max="10" required>
            </div>
            
            <button type="submit" name="update_room" class="btn btn-primary">Update Room</button>
            <a href="room.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>