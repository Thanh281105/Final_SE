<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$error = '';
$success = '';

// Handle add member
if (isset($_POST['add_member'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill all required fields';
    } else {
        // Check if email exists
        $checkStmt = $conn->prepare("SELECT UserID FROM signup WHERE Email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = 'Email already exists';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $insertStmt = $conn->prepare("INSERT INTO signup (Username, Email, Password, Phone, Address, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
            $insertStmt->bind_param("ssssss", $username, $email, $hashedPassword, $phone, $address, $role);
            $insertStmt->execute();
            
            if ($insertStmt->affected_rows > 0) {
                $userId = getUserId();
                logActivity($conn, $userId, 'member_created', 'signup', $insertStmt->insert_id, "New member created: $email");
                $success = 'Member added successfully';
            } else {
                $error = 'Failed to add member';
            }
        }
    }
}

// Build query
$sql = "SELECT * FROM signup WHERE 1=1";
$params = array();
$types = '';

if (!empty($search)) {
    $sql .= " AND (Username LIKE ? OR Email LIKE ? OR Phone LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

$sql .= " ORDER BY created_at DESC";

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
    <title>Member Management - TDTU Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
</head>
<body>
    <div class="container-fluid p-4">
        <h2 class="mb-4">Member Management</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo escapeOutput($success); ?></div>
        <?php endif; ?>
        
        <!-- Add Member Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>Add New Member</h4>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" name="add_member" class="btn btn-success w-100">Add Member</button>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Search -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="<?php echo escapeOutput($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    <div class="col-md-2">
                        <a href="members.php" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Member List -->
        <div class="card">
            <div class="card-header">
                <h4>All Members</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($member = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo escapeOutput($member['UserID']); ?></td>
                                        <td><?php echo escapeOutput($member['Username']); ?></td>
                                        <td><?php echo escapeOutput($member['Email']); ?></td>
                                        <td><?php echo escapeOutput($member['Phone'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $member['role'] == 'admin' ? 'danger' : 
                                                    ($member['role'] == 'staff' ? 'warning' : 'primary'); 
                                            ?>">
                                                <?php echo escapeOutput($member['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $member['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo escapeOutput($member['created_at'] ?? 'N/A'); ?></td>
                                        <td>
                                            <a href="member_edit.php?id=<?php echo $member['UserID']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="member_delete.php?id=<?php echo $member['UserID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure? This will deactivate the account.')">Deactivate</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No members found</td>
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

