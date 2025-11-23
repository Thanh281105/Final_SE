<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$sql = "SELECT rb.*, r.type as room_type_name 
        FROM roombook rb 
        LEFT JOIN room r ON rb.room_id = r.id 
        WHERE 1=1";
$params = array();
$types = '';

if (!empty($search)) {
    $sql .= " AND (rb.Name LIKE ? OR rb.Email LIKE ? OR rb.Phone LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

if (!empty($statusFilter)) {
    $sql .= " AND rb.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$sql .= " ORDER BY rb.created_at DESC";

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
    <title>Room Booking Management - TDTU Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <link rel="stylesheet" href="css/roombook.css">
</head>
<body>
    <div class="container-fluid p-4">
        <h2 class="mb-4">Room Booking Management</h2>
        
        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone..." value="<?php echo escapeOutput($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="Pending" <?php echo $statusFilter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Confirmed" <?php echo $statusFilter == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Checked-in" <?php echo $statusFilter == 'Checked-in' ? 'selected' : ''; ?>>Checked-in</option>
                            <option value="Checked-out" <?php echo $statusFilter == 'Checked-out' ? 'selected' : ''; ?>>Checked-out</option>
                            <option value="Cancelled" <?php echo $statusFilter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    <div class="col-md-2">
                        <a href="roombook.php" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Booking Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Room Type</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Room</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo escapeOutput($row['id']); ?></td>
                                        <td><?php echo escapeOutput($row['Name']); ?></td>
                                        <td><?php echo escapeOutput($row['Email']); ?></td>
                                        <td><?php echo escapeOutput($row['Phone']); ?></td>
                                        <td><?php echo escapeOutput($row['RoomType']); ?></td>
                                        <td><?php echo escapeOutput($row['cin']); ?></td>
                                        <td><?php echo escapeOutput($row['cout']); ?></td>
                                        <td><?php echo escapeOutput($row['nodays']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $row['status'] == 'Confirmed' ? 'success' : 
                                                    ($row['status'] == 'Pending' ? 'warning' : 
                                                    ($row['status'] == 'Checked-in' ? 'info' : 
                                                    ($row['status'] == 'Checked-out' ? 'secondary' : 'danger'))); 
                                            ?>">
                                                <?php echo escapeOutput($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['room_id']): ?>
                                                Room #<?php echo escapeOutput($row['room_id']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="roombookedit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($row['status'] == 'Pending'): ?>
                                                    <a href="roomconfirm.php?id=<?php echo $row['id']; ?>" class="btn btn-success" title="Confirm">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($row['status'] != 'Cancelled' && $row['status'] != 'Checked-out'): ?>
                                                    <a href="booking_cancel.php?id=<?php echo $row['id']; ?>" class="btn btn-danger" title="Cancel" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center">No bookings found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

