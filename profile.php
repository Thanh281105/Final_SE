<?php
include 'config.php';
include 'functions.php';
session_start();
checkSessionTimeout();
requireLogin();

$userId = getUserId();
$userEmail = $_SESSION['usermail'];

// Get user info
$stmt = $conn->prepare("SELECT * FROM signup WHERE UserID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$error = '';
$success = '';
$showEditForm = false;
$reauthError = '';

// Handle re-authentication
if (isset($_POST['verify_password'])) {
    $password = $_POST['current_password'];
    
    if (password_verify($password, $user['Password'])) {
        $_SESSION['profile_edit_verified'] = true;
        $showEditForm = true;
    } else {
        $reauthError = 'Incorrect password';
    }
}

// Handle profile update
if (isset($_POST['update_profile']) && isset($_SESSION['profile_edit_verified'])) {
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $currentPassword = $_POST['current_password_edit'];
    
    // Verify password again
    if (!password_verify($currentPassword, $user['Password'])) {
        $error = 'Current password is incorrect';
    } else {
        // Update profile
        $updateStmt = $conn->prepare("UPDATE signup SET Username = ?, Phone = ?, Address = ?, updated_at = NOW() WHERE UserID = ?");
        $updateStmt->bind_param("sssi", $username, $phone, $address, $userId);
        $updateStmt->execute();
        
        // Log activity
        logActivity($conn, $userId, 'profile_updated', 'signup', $userId, 'Profile information updated');
        
        $success = 'Profile updated successfully!';
        unset($_SESSION['profile_edit_verified']);
        
        // Refresh user data
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password_change'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (!password_verify($currentPassword, $user['Password'])) {
        $error = 'Current password is incorrect';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateStmt = $conn->prepare("UPDATE signup SET Password = ?, updated_at = NOW() WHERE UserID = ?");
        $updateStmt->bind_param("si", $hashedPassword, $userId);
        $updateStmt->execute();
        
        logActivity($conn, $userId, 'password_changed', 'signup', $userId, 'Password changed');
        
        $success = 'Password changed successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Hotel TDTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <?php include 'navbar.php'; ?>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">My Profile</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo escapeOutput($success); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <!-- Profile Information -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Profile Information</h4>
                        <?php if (!$showEditForm && !isset($_SESSION['profile_edit_verified'])): ?>
                            <button class="btn btn-primary" onclick="showEditForm()">Edit Profile</button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($showEditForm || isset($_SESSION['profile_edit_verified'])): ?>
                            <?php if (!$showEditForm && !isset($_SESSION['profile_edit_verified'])): ?>
                                <!-- Re-authentication -->
                                <form method="POST">
                                    <div class="alert alert-info">Please enter your current password to edit your profile</div>
                                    <?php if ($reauthError): ?>
                                        <div class="alert alert-danger"><?php echo escapeOutput($reauthError); ?></div>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <button type="submit" name="verify_password" class="btn btn-primary">Verify</button>
                                    <a href="profile.php" class="btn btn-secondary">Cancel</a>
                                </form>
                            <?php else: ?>
                                <!-- Edit Form -->
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" name="username" class="form-control" value="<?php echo escapeOutput($user['Username']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?php echo escapeOutput($user['Email']); ?>" disabled>
                                        <small class="text-muted">Email cannot be changed</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo escapeOutput($user['Phone'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" class="form-control" rows="3"><?php echo escapeOutput($user['Address'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Current Password (for verification)</label>
                                        <input type="password" name="current_password_edit" class="form-control" required>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-success">Update Profile</button>
                                    <a href="profile.php" class="btn btn-secondary">Cancel</a>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- View Mode -->
                            <p><strong>Username:</strong> <?php echo escapeOutput($user['Username']); ?></p>
                            <p><strong>Email:</strong> <?php echo escapeOutput($user['Email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo escapeOutput($user['Phone'] ?? 'Not provided'); ?></p>
                            <p><strong>Address:</strong> <?php echo escapeOutput($user['Address'] ?? 'Not provided'); ?></p>
                            <p><strong>Role:</strong> <?php echo escapeOutput($user['role'] ?? 'customer'); ?></p>
                            <p><strong>Member Since:</strong> <?php echo escapeOutput($user['created_at'] ?? 'N/A'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="card">
                    <div class="card-header">
                        <h4>Change Password</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password_change" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showEditForm() {
            window.location.href = 'profile.php?edit=1';
        }
    </script>
    <?php if (isset($_GET['edit'])): ?>
        <script>
            document.querySelector('button[onclick="showEditForm()"]').click();
        </script>
    <?php endif; ?>
</body>
</html>

