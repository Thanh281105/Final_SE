<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    header("Location: room.php");
    exit();
}

// Get room
$stmt = $conn->prepare("SELECT * FROM room WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: room.php");
    exit();
}

$room = $result->fetch_assoc();

$error = '';
$success = '';

if (isset($_POST['update_room'])) {
    $type = trim($_POST['type']);
    $bedding = trim($_POST['bedding']);
    $price = floatval($_POST['price']);
    $max_guests = intval($_POST['max_guests']);
    $status = $_POST['status'];
    $description = trim($_POST['description']);
    $amenities = trim($_POST['amenities']);
    
    $updateStmt = $conn->prepare("UPDATE room SET type = ?, bedding = ?, price = ?, max_guests = ?, status = ?, description = ?, amenities = ? WHERE id = ?");
    $updateStmt->bind_param("ssdissi", $type, $bedding, $price, $max_guests, $status, $description, $amenities, $id);
    $updateStmt->execute();
    
    if ($updateStmt->affected_rows >= 0) {
        $userId = getUserId();
        logActivity($conn, $userId, 'room_updated', 'room', $id, "Room updated");
        $success = 'Room updated successfully';
        
        // Refresh data
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
    } else {
        $error = 'Failed to update room';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room - TDTU Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/room.css">
</head>
<body>
    <div class="container-fluid p-4">
        <h2 class="mb-4">Edit Room</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo escapeOutput($success); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Type of Room *</label>
                            <select name="type" class="form-control" required>
                                <option value="Superior Room" <?php echo $room['type'] == 'Superior Room' ? 'selected' : ''; ?>>SUPERIOR ROOM</option>
                                <option value="Deluxe Room" <?php echo $room['type'] == 'Deluxe Room' ? 'selected' : ''; ?>>DELUXE ROOM</option>
                                <option value="Guest House" <?php echo $room['type'] == 'Guest House' ? 'selected' : ''; ?>>GUEST HOUSE</option>
                                <option value="Single Room" <?php echo $room['type'] == 'Single Room' ? 'selected' : ''; ?>>SINGLE ROOM</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type of Bed *</label>
                            <select name="bedding" class="form-control" required>
                                <option value="Single" <?php echo $room['bedding'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                                <option value="Double" <?php echo $room['bedding'] == 'Double' ? 'selected' : ''; ?>>Double</option>
                                <option value="Triple" <?php echo $room['bedding'] == 'Triple' ? 'selected' : ''; ?>>Triple</option>
                                <option value="Quad" <?php echo $room['bedding'] == 'Quad' ? 'selected' : ''; ?>>Quad</option>
                                <option value="None" <?php echo $room['bedding'] == 'None' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price per Night (₹) *</label>
                            <input type="number" name="price" class="form-control" value="<?php echo escapeOutput($room['price'] ?? 0); ?>" min="0" step="100" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max Guests *</label>
                            <input type="number" name="max_guests" class="form-control" value="<?php echo escapeOutput($room['max_guests'] ?? 2); ?>" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="Available" <?php echo $room['status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                                <option value="Occupied" <?php echo $room['status'] == 'Occupied' ? 'selected' : ''; ?>>Occupied</option>
                                <option value="Needs Cleaning" <?php echo $room['status'] == 'Needs Cleaning' ? 'selected' : ''; ?>>Needs Cleaning</option>
                                <option value="Cleaning" <?php echo $room['status'] == 'Cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo escapeOutput($room['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Amenities (comma separated)</label>
                            <input type="text" name="amenities" class="form-control" value="<?php echo escapeOutput($room['amenities'] ?? ''); ?>" placeholder="WiFi, TV, AC, etc.">
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="update_room" class="btn btn-primary">Update Room</button>
                        <a href="room.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

