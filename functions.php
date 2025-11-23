<?php
/**
 * Common Functions for Hotel Management System
 * Contains reusable functions for security, validation, and business logic
 */

include 'config.php';

/**
 * Check if user is logged in
 * Redirects to login page if not logged in
 */
function requireLogin() {
    session_start();
    if (!isset($_SESSION['usermail']) || empty($_SESSION['usermail'])) {
        header("Location: index.php");
        exit();
    }
}

/**
 * Check if user has admin or staff role
 * Redirects to home if not authorized
 */
function requireAdmin() {
    session_start();
    requireLogin();
    
    $usermail = $_SESSION['usermail'];
    $conn = $GLOBALS['conn'];
    
    // Check in signup table
    $stmt = $conn->prepare("SELECT role FROM signup WHERE Email = ? AND is_active = 1");
    $stmt->bind_param("s", $usermail);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['role'] == 'admin' || $user['role'] == 'staff') {
            return true;
        }
    }
    
    // Check in emp_login table
    $stmt = $conn->prepare("SELECT role FROM emp_login WHERE Emp_Email = ? AND is_active = 1");
    $stmt->bind_param("s", $usermail);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $emp = $result->fetch_assoc();
        if ($emp['role'] == 'admin' || $emp['role'] == 'staff') {
            return true;
        }
    }
    
    header("Location: home.php");
    exit();
}

/**
 * Check if user has specific role
 */
function hasRole($requiredRole) {
    session_start();
    if (!isset($_SESSION['usermail'])) {
        return false;
    }
    
    $usermail = $_SESSION['usermail'];
    $conn = $GLOBALS['conn'];
    
    $stmt = $conn->prepare("SELECT role FROM signup WHERE Email = ? AND is_active = 1");
    $stmt->bind_param("s", $usermail);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $user['role'] == $requiredRole;
    }
    
    return false;
}

/**
 * Check room availability for given dates and room type
 * Returns array of available room IDs or empty array
 */
function checkAvailability($conn, $cin, $cout, $roomType = null, $roomId = null, $excludeBookingId = null) {
    $availableRooms = array();
    
    // Build query
    $sql = "SELECT r.id, r.type, r.bedding, r.price, r.max_guests, r.status 
            FROM room r 
            WHERE r.status = 'Available'";
    
    $params = array();
    $types = "";
    
    if ($roomType) {
        $sql .= " AND r.type = ?";
        $params[] = $roomType;
        $types .= "s";
    }
    
    if ($roomId) {
        $sql .= " AND r.id = ?";
        $params[] = $roomId;
        $types .= "i";
    }
    
    // Exclude rooms that have overlapping bookings
    $sql .= " AND r.id NOT IN (
        SELECT DISTINCT rb.room_id 
        FROM roombook rb 
        WHERE rb.room_id IS NOT NULL 
        AND rb.status NOT IN ('Cancelled', 'Checked-out')
        AND (
            (? BETWEEN rb.cin AND rb.cout) OR
            (? BETWEEN rb.cin AND rb.cout) OR
            (rb.cin BETWEEN ? AND ?) OR
            (rb.cout BETWEEN ? AND ?)
        )";
    
    if ($excludeBookingId) {
        $sql .= " AND rb.id != ?";
        $params[] = $excludeBookingId;
        $types .= "i";
    }
    
    $sql .= " )";
    
    // Add date parameters
    $params = array_merge(
        array($cin, $cout, $cin, $cout, $cin, $cout),
        $params
    );
    $types = "ssssss" . $types;
    
    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $availableRooms[] = $row;
    }
    
    return $availableRooms;
}

/**
 * Check if specific room is available for dates
 */
function isRoomAvailable($conn, $roomId, $cin, $cout, $excludeBookingId = null) {
    $available = checkAvailability($conn, $cin, $cout, null, $roomId, $excludeBookingId);
    return !empty($available);
}

/**
 * Log activity to database
 */
function logActivity($conn, $userId, $action, $tableName = null, $recordId = null, $details = null) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, details, ip_address) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $userId, $action, $tableName, $recordId, $details, $ipAddress);
    $stmt->execute();
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Escape output for XSS prevention
 */
function escapeOutput($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if account is locked
 */
function isAccountLocked($conn, $email, $table = 'signup') {
    if ($table == 'signup') {
        $stmt = $conn->prepare("SELECT locked_until FROM signup WHERE Email = ?");
    } else {
        $stmt = $conn->prepare("SELECT locked_until FROM emp_login WHERE Emp_Email = ?");
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return true;
        }
    }
    
    return false;
}

/**
 * Increment failed login attempts
 */
function incrementFailedAttempts($conn, $email, $table = 'signup') {
    $maxAttempts = 5;
    $lockDuration = 15; // minutes
    
    if ($table == 'signup') {
        $stmt = $conn->prepare("UPDATE signup SET failed_attempts = failed_attempts + 1 WHERE Email = ?");
    } else {
        $stmt = $conn->prepare("UPDATE emp_login SET failed_attempts = failed_attempts + 1 WHERE Emp_Email = ?");
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    // Check if should lock
    if ($table == 'signup') {
        $stmt = $conn->prepare("SELECT failed_attempts FROM signup WHERE Email = ?");
    } else {
        $stmt = $conn->prepare("SELECT failed_attempts FROM emp_login WHERE Emp_Email = ?");
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['failed_attempts'] >= $maxAttempts) {
            $lockUntil = date('Y-m-d H:i:s', time() + ($lockDuration * 60));
            
            if ($table == 'signup') {
                $stmt = $conn->prepare("UPDATE signup SET locked_until = ? WHERE Email = ?");
            } else {
                $stmt = $conn->prepare("UPDATE emp_login SET locked_until = ? WHERE Emp_Email = ?");
            }
            
            $stmt->bind_param("ss", $lockUntil, $email);
            $stmt->execute();
        }
    }
}

/**
 * Reset failed login attempts
 */
function resetFailedAttempts($conn, $email, $table = 'signup') {
    if ($table == 'signup') {
        $stmt = $conn->prepare("UPDATE signup SET failed_attempts = 0, locked_until = NULL WHERE Email = ?");
    } else {
        $stmt = $conn->prepare("UPDATE emp_login SET failed_attempts = 0, locked_until = NULL WHERE Emp_Email = ?");
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
}

/**
 * Generate 2FA code
 */
function generate2FACode($conn, $userId) {
    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes
    
    // Invalidate old codes
    $stmt = $conn->prepare("UPDATE two_factor_codes SET used = 1 WHERE user_id = ? AND used = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    // Insert new code
    $stmt = $conn->prepare("INSERT INTO two_factor_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $code, $expiresAt);
    $stmt->execute();
    
    return $code;
}

/**
 * Verify 2FA code
 */
function verify2FACode($conn, $userId, $code) {
    $stmt = $conn->prepare("SELECT id FROM two_factor_codes 
                           WHERE user_id = ? AND code = ? AND used = 0 AND expires_at > NOW()");
    $stmt->bind_param("is", $userId, $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Mark as used
        $stmt = $conn->prepare("UPDATE two_factor_codes SET used = 1 WHERE user_id = ? AND code = ?");
        $stmt->bind_param("is", $userId, $code);
        $stmt->execute();
        
        return true;
    }
    
    return false;
}

/**
 * Calculate booking price
 */
function calculateBookingPrice($roomType, $bed, $meal, $noofdays, $noofroom) {
    // Room prices
    $roomPrices = array(
        'Superior Room' => 3000,
        'Deluxe Room' => 2000,
        'Guest House' => 1500,
        'Single Room' => 1000
    );
    
    $type_of_room = $roomPrices[$roomType] ?? 0;
    
    // Bed prices (percentage of room price)
    $bedMultipliers = array(
        'Single' => 0.01,
        'Double' => 0.02,
        'Triple' => 0.03,
        'Quad' => 0.04,
        'None' => 0
    );
    
    $type_of_bed = $type_of_room * ($bedMultipliers[$bed] ?? 0);
    
    // Meal prices (multiplier of bed price)
    $mealMultipliers = array(
        'Room only' => 0,
        'Breakfast' => 2,
        'Half Board' => 3,
        'Full Board' => 4
    );
    
    $type_of_meal = $type_of_bed * ($mealMultipliers[$meal] ?? 0);
    
    // Calculate totals
    $roomtotal = $type_of_room * $noofdays * $noofroom;
    $bedtotal = $type_of_bed * $noofdays;
    $mealtotal = $type_of_meal * $noofdays;
    $finaltotal = $roomtotal + $bedtotal + $mealtotal;
    
    return array(
        'roomtotal' => $roomtotal,
        'bedtotal' => $bedtotal,
        'mealtotal' => $mealtotal,
        'finaltotal' => $finaltotal,
        'room_price_per_night' => $type_of_room,
        'bed_price_per_night' => $type_of_bed,
        'meal_price_per_night' => $type_of_meal
    );
}

/**
 * Check session timeout (30 minutes)
 */
function checkSessionTimeout() {
    session_start();
    $timeout = 30 * 60; // 30 minutes in seconds
    
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout) {
            session_destroy();
            header("Location: index.php?timeout=1");
            exit();
        }
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Get user ID from session
 */
function getUserId() {
    session_start();
    if (!isset($_SESSION['usermail'])) {
        return null;
    }
    
    $usermail = $_SESSION['usermail'];
    $conn = $GLOBALS['conn'];
    
    $stmt = $conn->prepare("SELECT UserID FROM signup WHERE Email = ?");
    $stmt->bind_param("s", $usermail);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $user['UserID'];
    }
    
    return null;
}

?>

