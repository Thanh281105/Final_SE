<?php
include 'config.php';
session_start();

if (!isset($_SESSION['usermail'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $email = $_SESSION['usermail'];
    
    // Kiểm tra file upload
    $file = $_FILES['avatar'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    
    if ($file_error === 0) {
        if ($file_size <= 5000000) { // 5MB
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');
            
            if (in_array($file_ext, $allowed_ext)) {
                $new_file_name = uniqid('avatar_', true) . '.' . $file_ext;
                $file_destination = 'image/' . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $file_destination)) {
                    // Cập nhật vào database
                    $stmt = $conn->prepare("UPDATE signup SET avatar = ? WHERE Email = ?");
                    $stmt->bind_param("ss", $file_destination, $email);
                    
                    if ($stmt->execute()) {
                        $_SESSION['avatar'] = $file_destination;
                        header("Location: profile.php?success=1");
                        exit;
                    } else {
                        $error = "Database error";
                    }
                } else {
                    $error = "Failed to upload file";
                }
            } else {
                $error = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed";
            }
        } else {
            $error = "File too large. Maximum 5MB allowed";
        }
    } else {
        $error = "Upload error";
    }
    
    header("Location: profile.php?error=" . urlencode($error));
    exit;
}

header("Location: profile.php");
?>