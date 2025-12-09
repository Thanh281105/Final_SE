<?php
include 'config.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['usermail'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

$email = $_SESSION['usermail'];

// Lấy dữ liệu từ form
$field_type = $_POST['field_type'] ?? '';
$new_value = $_POST['field_value'] ?? '';
$current_password = $_POST['current_password'] ?? '';

// Kiểm tra các trường bắt buộc
if (empty($field_type) || empty($new_value) || empty($current_password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Kiểm tra loại trường hợp lệ
$allowed_fields = ['username', 'phone', 'email'];
if (!in_array($field_type, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field type']);
    exit;
}

// Lấy thông tin người dùng hiện tại để xác thực mật khẩu
$user_query = mysqli_query($conn, "SELECT * FROM signup WHERE Email = '$email' LIMIT 1");
$user = mysqli_fetch_assoc($user_query);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Xác thực mật khẩu hiện tại
if ($current_password !== $user['Password']) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

// Kiểm tra ràng buộc đặc biệt
if ($field_type === 'email') {
    // Kiểm tra email chưa tồn tại
    $check_email = mysqli_query($conn, "SELECT * FROM signup WHERE Email = '$new_value' AND Email != '$email' LIMIT 1");
    if (mysqli_num_rows($check_email) > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Kiểm tra định dạng email
    if (!filter_var($new_value, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
}

if ($field_type === 'phone') {
    // Kiểm tra định dạng số điện thoại (10-15 chữ số)
    if (!preg_match('/^[0-9]{10,15}$/', $new_value)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
        exit;
    }
}

// Cập nhật thông tin vào database
$update_query = "UPDATE signup SET $field_type = '" . mysqli_real_escape_string($conn, $new_value) . "' WHERE Email = '$email'";

if (mysqli_query($conn, $update_query)) {
    // Nếu cập nhật email, cần cập nhật session
    if ($field_type === 'email') {
        $_SESSION['usermail'] = $new_value;
    }
    
    echo json_encode(['success' => true, 'message' => 'Information updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update information: ' . mysqli_error($conn)]);
}
?>