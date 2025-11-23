<?php
include 'config.php';
include 'functions.php';
session_start();
checkSessionTimeout();

$roomType = isset($_GET['type']) ? $_GET['type'] : '';
$minPrice = isset($_GET['minPrice']) ? floatval($_GET['minPrice']) : 0;
$maxPrice = isset($_GET['maxPrice']) ? floatval($_GET['maxPrice']) : 0;

// Build query
$sql = "SELECT * FROM room WHERE status = 'Available'";
$params = array();
$types = '';

if (!empty($roomType)) {
    $sql .= " AND type = ?";
    $params[] = $roomType;
    $types .= 's';
}

if ($minPrice > 0) {
    $sql .= " AND price >= ?";
    $params[] = $minPrice;
    $types .= 'd';
}

if ($maxPrice > 0) {
    $sql .= " AND price <= ?";
    $params[] = $maxPrice;
    $types .= 'd';
}

$sql .= " ORDER BY type, price";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - Hotel TDTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <?php include 'navbar.php'; ?>
    <style>
        .room-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Available Rooms</h2>
        
        <!-- Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label>Room Type</label>
                        <select name="type" class="form-control">
                            <option value="">All Types</option>
                            <option value="Superior Room" <?php echo $roomType == 'Superior Room' ? 'selected' : ''; ?>>Superior Room</option>
                            <option value="Deluxe Room" <?php echo $roomType == 'Deluxe Room' ? 'selected' : ''; ?>>Deluxe Room</option>
                            <option value="Guest House" <?php echo $roomType == 'Guest House' ? 'selected' : ''; ?>>Guest House</option>
                            <option value="Single Room" <?php echo $roomType == 'Single Room' ? 'selected' : ''; ?>>Single Room</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Min Price</label>
                        <input type="number" name="minPrice" class="form-control" value="<?php echo $minPrice; ?>" min="0" step="100">
                    </div>
                    <div class="col-md-3">
                        <label>Max Price</label>
                        <input type="number" name="maxPrice" class="form-control" value="<?php echo $maxPrice; ?>" min="0" step="100">
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Room List -->
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($room = $result->fetch_assoc()): ?>
                    <div class="col-md-6">
                        <div class="room-card">
                            <h4><?php echo escapeOutput($room['type']); ?></h4>
                            <p><strong>Bedding:</strong> <?php echo escapeOutput($room['bedding']); ?></p>
                            <p><strong>Max Guests:</strong> <?php echo escapeOutput($room['max_guests']); ?></p>
                            <p><strong>Price per night:</strong> ₹<?php echo number_format($room['price'], 2); ?></p>
                            <?php if ($room['description']): ?>
                                <p><?php echo escapeOutput(substr($room['description'], 0, 100)); ?>...</p>
                            <?php endif; ?>
                            <a href="room_detail.php?id=<?php echo $room['id']; ?>" class="btn btn-primary">View Details</a>
                            <?php if (isset($_SESSION['usermail'])): ?>
                                <a href="search_rooms.php" class="btn btn-success">Book Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">No rooms found matching your criteria.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

