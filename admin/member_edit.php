<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    header("Location: members.php");
    exit();
}

// Get member
$stmt = $conn->prepare("SELECT * FROM signup WHERE UserID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: members.php");
    exit();
}

$member = $result->fetch_assoc();

$error = '';
$success = '';

if (isset($_POST['update_member'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $role = $_POST['role'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Check if email is changed and exists
    if ($email != $member['Email']) {
        $checkStmt = $conn->prepare("SELECT UserID FROM signup WHERE Email = ? AND UserID != ?");
        $checkStmt->bind_param("si", $email, $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = 'Email already exists';
        }
    }
    
    if (empty($error)) {
        $updateStmt = $conn->prepare("UPDATE signup SET Username = ?, Email = ?, Phone = ?, Address = ?, role = ?, is_active = ?, updated_at = NOW() WHERE UserID = ?");
        $updateStmt->bind_param("sssssii", $username, $email, $phone, $address, $role, $isActive, $id);
        $updateStmt->execute();
        
        $userId = getUserId();
        logActivity($conn, $userId, 'member_updated', 'signup', $id, "Member updated");
        
        $success = 'Member updated successfully';
        
        // Refresh data
        $stmt->execute();
        $result = $stmt->get_result();
        $member = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Member - TDTU Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid p-4">
        <h2 class="mb-4">Edit Member</h2>
        
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
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" class="form-control" value="<?php echo escapeOutput($member['Username']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" value="<?php echo escapeOutput($member['Email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo escapeOutput($member['Phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="<?php echo escapeOutput($member['Address'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role *</label>
                            <select name="role" class="form-control" required>
                                <option value="customer" <?php echo $member['role'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                <option value="admin" <?php echo $member['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="staff" <?php echo $member['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" value="1" <?php echo $member['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="update_member" class="btn btn-primary">Update Member</button>
                        <a href="members.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

