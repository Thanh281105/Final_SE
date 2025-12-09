<?php
include 'config.php';
session_start();

if (!isset($_SESSION['usermail'])) {
    header("Location: index.php");
    exit;
}

$email = $_SESSION['usermail'];

// Lấy thông tin người dùng
$user_query = mysqli_query($conn, "SELECT * FROM signup WHERE Email = '$email' LIMIT 1");
$user = mysqli_fetch_assoc($user_query);

$avatar = 'image/Profile.png';
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM signup LIKE 'avatar'");
if (mysqli_num_rows($check_col) > 0) {
    if (!empty($user['avatar']) && file_exists($user['avatar'])) {
        $avatar = $user['avatar'];
    }
}

// Truy vấn booking với thông tin thanh toán từ bảng payment
$bookings_query = mysqli_query($conn, 
    "SELECT r.*, 
            COALESCE(p.finaltotal, 0) as finaltotal,
            COALESCE(p.status, r.stat) as payment_status,
            p.qr_url
     FROM roombook r 
     LEFT JOIN payment p ON r.id = p.id 
     WHERE r.Email = '$email' 
     ORDER BY r.id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Hotel TDTU</title>
    
    <link rel="stylesheet" href="./css/home.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css  " rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css  "/>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js  "></script>

    <style>
        /* Avatar và camera overlay */
        .avatar-large-container {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .avatar-large-container:hover .camera-overlay {
            opacity: 1;
        }

        .camera-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .camera-icon {
            color: white;
            font-size: 40px;
            transform: scale(0.8);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: none;
            border-radius: 10px;
            width: 600px;
            text-align: center;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        #file-input {
            margin: 15px 0;
        }
        
        #upload-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        #upload-btn:hover {
            background-color: #0056b3;
        }
        
        :root {
            --primary: #007bff;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        .profile-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
        }
        .avatar-large {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .info-item {
            display: flex;
            align-items: center;
            font-size: 16px;
        }
        .info-item i {
            font-size: 22px;
            color: var(--primary);
            width: 50px;
        }
        .status-not-confirmed { background:#fff3cd; color:#856404; }
        .status-confirmed     { background:#d4edda; color:#155724; }
        .status-paid          { background:#d1ecf1; color:#0c5460; }
        .status-rejected      { background:#f8d7da; color:#721c24; }
        .status-cancelled     { background:#e2e3e5; color:#383d41; }
        .booking-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .back-home-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 5px 15px rgba(0,123,255,0.4);
            z-index: 1000;
            transition: all 0.3s;
            display: none;
        }
        .back-home-btn:hover {
            transform: scale(1.1);
            background: #0056b3;
        }

        .avatar-img {
            width: 40px; 
            height: 40px; 
            border-radius: 50%;
            object-fit: cover; 
            border: 2px solid white; 
            cursor: pointer; 
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 5px;
            overflow: hidden;
        }
        .dropdown-menu a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        .dropdown-menu a:hover {
            background-color: #f1f1f1;
        }
        .avatar-dropdown {
            position: relative; 
        }

        .logo {
            cursor: pointer; 
            display: flex; 
            align-items: center; 
        }
        .logo:hover {
            opacity: 0.8;
        }
        
        /* Nút QR Payment */
        .qr-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .qr-btn:hover {
            background: #218838;
            color: white;
        }
        
        .cancel-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .cancel-btn:hover {
            background: #c82333;
            color: white;
        }
    </style>
</head>
<body>

<nav>
    <a href="home.php" class="logo" style="text-decoration: none; color: inherit; display: flex; align-items: center;">
        <img class="bluebirdlogo" src="./image/bluebirdlogo.png" alt="logo" style="margin-right: 8px;">
        <p>TDTU</p>
    </a>
    <ul>
        <li class="avatar-dropdown">
            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Profile" class="avatar-img">
            <div class="dropdown-menu">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </li>
    </ul>
</nav>

<div class="profile-container">

    <div class="profile-header">
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" class="avatar-large">
        <h2 class="mt-3"><?php echo htmlspecialchars($user['Username'] ?? 'Guest'); ?></h2>
        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($email); ?></p>
    </div>

    <div class="info-card">
        <h4><i class="fas fa-id-card"></i> Personal Information</h4>
        <hr>
        <div class="info-grid">
            <div class="info-item">
                <i class="fas fa-user"></i>
                <div>
                    <strong>Username</strong><br>
                    <?php echo htmlspecialchars($user['Username'] ?? '<em>Not set</em>'); ?>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-phone"></i>
                <div>
                    <strong>Phone</strong><br>
                    <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<em>Not provided</em>'; ?>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <strong>Email</strong><br>
                    <?php echo htmlspecialchars($email); ?>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-calendar-check"></i>
                <div>
                    <strong>Member Since</strong><br>
                    <?php echo date('d/m/Y', strtotime($user['created_at'] ?? 'now')); ?> 
                </div>
            </div>
        </div>
    </div>

    <div class="info-card">
        <h4><i class="fas fa-bed"></i> Booking History</h4>
        <hr>
        <?php if (mysqli_num_rows($bookings_query) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered booking-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Room Type</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Nights</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($b = mysqli_fetch_assoc($bookings_query)): 
                            // Xác định trạng thái thanh toán
                            $status = $b['payment_status'] ?? $b['stat'];
                            $status_class = strtolower(str_replace(' ', '-', $status));
                            
                            // Kiểm tra xem có thể thanh toán không
                            $can_pay = ($b['stat'] === 'Confirmed' && $b['finaltotal'] > 0 && $status !== 'Paid');
                            
                            // Kiểm tra xem có thể hủy không
                            $can_cancel = ($b['stat'] === 'Not Confirmed' || ($b['stat'] === 'Confirmed' && $status !== 'Paid'));
                        ?>
                            <tr>
                                <td>#<?php echo $b['id']; ?></td>
                                <td><?php echo $b['RoomType']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($b['cin'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($b['cout'])); ?></td>
                                <td><?php echo $b['nodays']; ?></td>
                                <td>
                                    <span class="px-3 py-1 rounded status-<?php echo $status_class; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($b['finaltotal']) && $b['finaltotal'] > 0): ?>
                                        <?php echo number_format($b['finaltotal']); ?> VND
                                    <?php else: ?>
                                        <em>Pending</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($can_pay): ?>
                                        <a href="generate_qr.php?booking_id=<?php echo $b['id']; ?>" class="qr-btn">
                                            <i class="fas fa-qrcode"></i> Pay Now
                                        </a>
                                    <?php elseif ($status === 'Paid'): ?>
                                        <span class="text-success">Paid</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($can_cancel): ?>
                                        <a href="cancel_booking.php?id=<?php echo $b['id']; ?>" class="cancel-btn" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted py-5">
                <i class="fas fa-calendar-times fa-3x mb-3"></i><br>
                No booking history yet.
            </p>
        <?php endif; ?>
    </div>

</div>

<script>
document.querySelectorAll('.avatar-dropdown').forEach(d => {
    d.addEventListener('mouseenter', () => d.querySelector('.dropdown-menu').style.display = 'block');
    d.addEventListener('mouseleave', () => d.querySelector('.dropdown-menu').style.display = 'none');
});
</script>

</body>
</html>