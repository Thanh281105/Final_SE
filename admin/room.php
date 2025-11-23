<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TDTU - Admin</title>
    <!-- fontowesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- boot -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/room.css">
</head>

<body>
    <div class="addroomsection">
        <form action="" method="POST">
            <label for="troom">Type of Room *</label>
            <select name="troom" class="form-control" required>
                <option value="">Choose...</option>
                <option value="Superior Room">SUPERIOR ROOM</option>
                <option value="Deluxe Room">DELUXE ROOM</option>
                <option value="Guest House">GUEST HOUSE</option>
                <option value="Single Room">SINGLE ROOM</option>
            </select>

            <label for="bed">Type of Bed *</label>
            <select name="bed" class="form-control" required>
                <option value="">Choose...</option>
                <option value="Single">Single</option>
                <option value="Double">Double</option>
                <option value="Triple">Triple</option>
                <option value="Quad">Quad</option>
                <option value="None">None</option>
            </select>
            
            <label for="price">Price per Night (₹) *</label>
            <input type="number" name="price" class="form-control" min="0" step="100" required>
            
            <label for="max_guests">Max Guests *</label>
            <input type="number" name="max_guests" class="form-control" min="1" required>
            
            <label for="status">Status *</label>
            <select name="status" class="form-control" required>
                <option value="Available">Available</option>
                <option value="Occupied">Occupied</option>
                <option value="Needs Cleaning">Needs Cleaning</option>
                <option value="Cleaning">Cleaning</option>
            </select>
            
            <label for="description">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
            
            <label for="amenities">Amenities (comma separated)</label>
            <input type="text" name="amenities" class="form-control" placeholder="WiFi, TV, AC, etc.">

            <button type="submit" class="btn btn-success" name="addroom">Add Room</button>
        </form>

        <?php
        if (isset($_POST['addroom'])) {
            $typeofroom = trim($_POST['troom']);
            $typeofbed = trim($_POST['bed']);
            $price = floatval($_POST['price']);
            $max_guests = intval($_POST['max_guests']);
            $status = $_POST['status'];
            $description = trim($_POST['description']);
            $amenities = trim($_POST['amenities']);

            $stmt = $conn->prepare("INSERT INTO room(type, bedding, price, max_guests, status, description, amenities) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdiss", $typeofroom, $typeofbed, $price, $max_guests, $status, $description, $amenities);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $userId = getUserId();
                logActivity($conn, $userId, 'room_created', 'room', $stmt->insert_id, "New room added: $typeofroom");
                header("Location: room.php");
                exit();
            }
        }
        ?>
    </div>

    <div class="room">
        <?php
        $stmt = $conn->prepare("SELECT * FROM room ORDER BY type, id");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $roomType = $row['type'];
            $roomId = $row['id'];
            $roomClass = '';
            
            if ($roomType == "Superior Room") {
                $roomClass = 'roomboxsuperior';
            } else if ($roomType == "Deluxe Room") {
                $roomClass = 'roomboxdelux';
            } else if ($roomType == "Guest House") {
                $roomClass = 'roomboguest';
            } else if ($roomType == "Single Room") {
                $roomClass = 'roomboxsingle';
            }
            
            echo "<div class='roombox $roomClass'>
                <div class='text-center no-boder'>
                    <i class='fa-solid fa-bed fa-4x mb-2'></i>
                    <h3>" . escapeOutput($row['type']) . "</h3>
                    <div class='mb-1'>" . escapeOutput($row['bedding']) . "</div>";
            
            if ($row['price']) {
                echo "<div class='mb-1'><strong>₹" . number_format($row['price'], 2) . "/night</strong></div>";
            }
            if ($row['max_guests']) {
                echo "<div class='mb-1'>Max: " . escapeOutput($row['max_guests']) . " guests</div>";
            }
            echo "<div class='mb-1'>
                <span class='badge bg-" . ($row['status'] == 'Available' ? 'success' : ($row['status'] == 'Occupied' ? 'danger' : 'warning')) . "'>" . escapeOutput($row['status']) . "</span>
            </div>
            <div class='mt-2'>
                <a href='room_edit.php?id=" . $roomId . "' class='btn btn-warning btn-sm'>Edit</a>
                <a href='roomdelete.php?id=" . $roomId . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure?\")'>Delete</a>
            </div>
        </div>
    </div>";
        }
        ?>
    </div>

</body>

</html>