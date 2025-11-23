<?php
include 'config.php';
include 'functions.php';
session_start();
checkSessionTimeout();
requireLogin();

$userId = getUserId();

// Get user tickets
$stmt = $conn->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Support Tickets - Hotel TDTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <?php include 'navbar.php'; ?>
    <style>
        .ticket-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">My Support Tickets</h2>
        
        <a href="support_request.php" class="btn btn-primary mb-3">New Support Request</a>
        
        <?php if ($result->num_rows > 0): ?>
            <?php while ($ticket = $result->fetch_assoc()): ?>
                <div class="ticket-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h4>#<?php echo escapeOutput($ticket['id']); ?> - <?php echo escapeOutput($ticket['subject']); ?></h4>
                            <p class="text-muted mb-2">
                                <strong>Channel:</strong> <?php echo escapeOutput($ticket['channel']); ?> | 
                                <strong>Created:</strong> <?php echo escapeOutput($ticket['created_at']); ?>
                            </p>
                            <p><strong>Message:</strong><br><?php echo nl2br(escapeOutput($ticket['message'])); ?></p>
                            
                            <?php if ($ticket['admin_response']): ?>
                                <div class="alert alert-info mt-3">
                                    <strong>Admin Response:</strong><br>
                                    <?php echo nl2br(escapeOutput($ticket['admin_response'])); ?>
                                    <?php if ($ticket['resolved_at']): ?>
                                        <br><small class="text-muted">Resolved on: <?php echo escapeOutput($ticket['resolved_at']); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="badge bg-<?php 
                                echo $ticket['status'] == 'resolved' ? 'success' : 
                                    ($ticket['status'] == 'waiting_customer' ? 'warning' : 'primary'); 
                            ?>">
                                <?php echo escapeOutput($ticket['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">You have no support tickets yet.</div>
        <?php endif; ?>
    </div>
</body>
</html>

