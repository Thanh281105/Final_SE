<?php
include 'config.php';
include 'functions.php';
session_start();
checkSessionTimeout();

$userId = null;
$userEmail = '';
$userName = '';

if (isset($_SESSION['usermail'])) {
    requireLogin();
    $userId = getUserId();
    $userEmail = $_SESSION['usermail'];
    
    // Get user name
    $stmt = $conn->prepare("SELECT Username FROM signup WHERE UserID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $userName = $user['Username'];
    }
}

$error = '';
$success = '';

if (isset($_POST['submit_support'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $channel = $_POST['channel'];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill all required fields';
    } else {
        $insertStmt = $conn->prepare("INSERT INTO support_tickets (user_id, name, email, channel, subject, message, status, created_at) 
                                     VALUES (?, ?, ?, ?, ?, ?, 'open', NOW())");
        $insertStmt->bind_param("isssss", $userId, $name, $email, $channel, $subject, $message);
        $insertStmt->execute();
        
        if ($insertStmt->affected_rows > 0) {
            $ticketId = $insertStmt->insert_id;
            
            if ($userId) {
                logActivity($conn, $userId, 'support_ticket_created', 'support_tickets', $ticketId, "Support ticket created: $subject");
            }
            
            $success = 'Your support request has been submitted successfully! Ticket ID: #' . $ticketId;
            
            // Clear form
            $name = $email = $subject = $message = '';
        } else {
            $error = 'Failed to submit support request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Request - Hotel TDTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <?php if (isset($_SESSION['usermail'])): ?>
        <?php include 'navbar.php'; ?>
    <?php endif; ?>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Contact Support</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo escapeOutput($success); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Submit Support Request</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" value="<?php echo escapeOutput($userName); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?php echo escapeOutput($userEmail); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Channel *</label>
                                <select name="channel" class="form-control" required>
                                    <option value="">Choose...</option>
                                    <option value="Hotline">Hotline</option>
                                    <option value="Chat">Chat</option>
                                    <option value="Email">Email</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject *</label>
                                <input type="text" name="subject" class="form-control" value="<?php echo escapeOutput($subject ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message *</label>
                                <textarea name="message" class="form-control" rows="5" required><?php echo escapeOutput($message ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="submit_support" class="btn btn-primary">Submit Request</button>
                            <?php if (isset($_SESSION['usermail'])): ?>
                                <a href="my_tickets.php" class="btn btn-secondary">View My Tickets</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Support Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Hotline:</strong><br>+84 123 456 789</p>
                        <p><strong>Email:</strong><br>support@tdtuhotel.com</p>
                        <p><strong>Working Hours:</strong><br>24/7</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

