<?php
include '../config.php';
session_start();

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['usermail'])) {
    header("Location: ../index.php");
    exit;
}

// Xử lý thêm chi nhánh
if (isset($_POST['add_branch'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    $sql = "INSERT INTO branches (name, address) VALUES ('$name', '$address')";
    mysqli_query($conn, $sql);
}

// Xử lý cập nhật trạng thái
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $current_status = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM branches WHERE id = $id"))['status'];
    $new_status = $current_status == 'Active' ? 'Inactive' : 'Active';
    mysqli_query($conn, "UPDATE branches SET status = '$new_status' WHERE id = $id");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Branches</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Manage Hotel Branches</h2>
        
        <form method="POST" class="row mb-3">
            <div class="col-md-4">
                <input type="text" name="name" class="form-control" placeholder="Branch Name" required>
            </div>
            <div class="col-md-6">
                <input type="text" name="address" class="form-control" placeholder="Address">
            </div>
            <div class="col-md-2">
                <button type="submit" name="add_branch" class="btn btn-primary">Add</button>
            </div>
        </form>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM branches ORDER BY name ASC");
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>{$row['id']}</td>";
                    echo "<td>{$row['name']}</td>";
                    echo "<td>{$row['address']}</td>";
                    echo "<td><span class='badge bg-" . ($row['status'] == 'Active' ? 'success' : 'secondary') . "'>{$row['status']}</span></td>";
                    echo "<td><a href='?toggle_status=1&id={$row['id']}' class='btn btn-sm btn-" . ($row['status'] == 'Active' ? 'warning' : 'success') . "'>Toggle</a></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>