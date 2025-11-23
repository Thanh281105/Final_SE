<?php 
session_start();
include 'config.php';
include 'functions.php';

// Log activity if user was logged in
if (isset($_SESSION['usermail'])) {
    $userId = getUserId();
    if ($userId) {
        logActivity($conn, $userId, 'logout', 'signup', $userId, 'User logged out');
    }
}

session_destroy();

header("Location: index.php");
exit();
?>
