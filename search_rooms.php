<?php
include 'config.php';
include 'functions.php';
session_start();
checkSessionTimeout();
requireLogin();

$rooms = array();
$searchPerformed = false;
$error = '';

// Get search parameters
$cin = isset($_GET['cin']) ? $_GET['cin'] : '';
$cout = isset($_GET['cout']) ? $_GET['cout'] : '';
$guests = isset($_GET['guests']) ? intval($_GET['guests']) : 0;
$roomType = isset($_GET['roomType']) ? $_GET['roomType'] : '';
$minPrice = isset($_GET['minPrice']) ? floatval($_GET['minPrice']) : 0;
$maxPrice = isset($_GET['maxPrice']) ? floatval($_GET['maxPrice']) : 0;

if (isset($_GET['search']) && !empty($cin) && !empty($cout)) {
    $searchPerformed = true;
    
    // Validate dates
    if (strtotime($cout) <= strtotime($cin)) {
        $error = 'Check-out date must be after check-in date';
    } else {
        // Search available rooms
        $availableRooms = checkAvailability($conn, $cin, $cout, $roomType);
        
        // Filter by guests and price
        foreach ($availableRooms as $room) {
            if ($guests > 0 && $room['max_guests'] < $guests) {
                continue;
            }
            if ($minPrice > 0 && $room['price'] < $minPrice) {
                continue;
            }
            if ($maxPrice > 0 && $room['price'] > $maxPrice) {
                continue;
            }
            $rooms[] = $room;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Rooms - Hotel TDTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <style>
        .search-form {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
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
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4">Search Available Rooms</h1>
        
        <!-- Search Form -->
        <div class="search-form">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <label>Check-in Date</label>
                        <input type="date" name="cin" class="form-control" value="<?php echo escapeOutput($cin); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label>Check-out Date</label>
                        <input type="date" name="cout" class="form-control" value="<?php echo escapeOutput($cout); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label>Number of Guests</label>
                        <input type="number" name="guests" class="form-control" value="<?php echo $guests; ?>" min="1">
                    </div>
                    <div class="col-md-2">
                        <label>Room Type</label>
                        <select name="roomType" class="form-control">
                            <option value="">All Types</option>
                            <option value="Superior Room" <?php echo $roomType == 'Superior Room' ? 'selected' : ''; ?>>Superior Room</option>
                            <option value="Deluxe Room" <?php echo $roomType == 'Deluxe Room' ? 'selected' : ''; ?>>Deluxe Room</option>
                            <option value="Guest House" <?php echo $roomType == 'Guest House' ? 'selected' : ''; ?>>Guest House</option>
                            <option value="Single Room" <?php echo $roomType == 'Single Room' ? 'selected' : ''; ?>>Single Room</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" name="search" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label>Min Price</label>
                        <input type="number" name="minPrice" class="form-control" value="<?php echo $minPrice; ?>" min="0" step="100">
                    </div>
                    <div class="col-md-3">
                        <label>Max Price</label>
                        <input type="number" name="maxPrice" class="form-control" value="<?php echo $maxPrice; ?>" min="0" step="100">
                    </div>
                </div>
            </form>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
        <?php endif; ?>
        
        <!-- Results -->
        <?php if ($searchPerformed): ?>
            <?php if (empty($rooms)): ?>
                <div class="alert alert-info">No rooms found matching your criteria.</div>
            <?php else: ?>
                <h3 class="mb-3">Available Rooms (<?php echo count($rooms); ?>)</h3>
                <div class="row">
                    <?php foreach ($rooms as $room): ?>
                        <div class="col-md-6">
                            <div class="room-card">
                                <h4><?php echo escapeOutput($room['type']); ?></h4>
                                <p><strong>Bedding:</strong> <?php echo escapeOutput($room['bedding']); ?></p>
                                <p><strong>Max Guests:</strong> <?php echo escapeOutput($room['max_guests']); ?></p>
                                <p><strong>Price per night:</strong> ₹<?php echo number_format($room['price'], 2); ?></p>
                                <?php if ($room['description']): ?>
                                    <p><?php echo escapeOutput($room['description']); ?></p>
                                <?php endif; ?>
                                <a href="booking_detail.php?room_id=<?php echo $room['id']; ?>&cin=<?php echo urlencode($cin); ?>&cout=<?php echo urlencode($cout); ?>" class="btn btn-primary">Book Now</a>
                                <a href="room_detail.php?id=<?php echo $room['id']; ?>" class="btn btn-secondary">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">Please enter search criteria to find available rooms.</div>
        <?php endif; ?>
    </div>
</body>
</html>

