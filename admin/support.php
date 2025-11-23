<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$ticketId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$error = '';
$success = '';

// Handle ticket resolution
if (isset($_POST['resolve_ticket'])) {
    $id = intval($_POST['ticket_id']);
    $response = trim($_POST['admin_response']);
    
    if (empty($response)) {
        $error = 'Please enter a response';
    } else {
        $updateStmt = $conn->prepare("UPDATE support_tickets SET status = 'resolved', admin_response = ?, resolved_at = NOW(), updated_at = NOW() WHERE id = ?");
        $updateStmt->bind_param("si", $response, $id);
        $updateStmt->execute();
        
        $userId = getUserId();
        logActivity($conn, $userId, 'support_ticket_resolved', 'support_tickets', $id, "Ticket resolved");
        
        $success = 'Ticket resolved successfully';
    }
}

// Handle request more info
if (isset($_POST['request_info'])) {
    $id = intval($_POST['ticket_id']);
    $response = trim($_POST['admin_response']);
    
    if (empty($response)) {
        $error = 'Please enter your request';
    } else {
        $updateStmt = $conn->prepare("UPDATE support_tickets SET status = 'waiting_customer', admin_response = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->bind_param("si", $response, $id);
        $updateStmt->execute();
        
        $userId = getUserId();
        logActivity($conn, $userId, 'support_ticket_waiting', 'support_tickets', $id, "Requested more info from customer");
        
        $success = 'Request sent to customer';
    }
}

// Build query
$sql = "SELECT * FROM support_tickets WHERE 1=1";
$params = array();
$types = '';

if (!empty($statusFilter)) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get ticket detail if requested
$ticketDetail = null;
if ($ticketId > 0) {
    $detailStmt = $conn->prepare("SELECT * FROM support_tickets WHERE id = ?");
    $detailStmt->bind_param("i", $ticketId);
    $detailStmt->execute();
    $detailResult = $detailStmt->get_result();
    if ($detailResult->num_rows > 0) {
        $ticketDetail = $detailResult->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Management - TDTU Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
</head>
<body>
    <div class="container-fluid p-4">
        <h2 class="mb-4">Support Ticket Management</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo escapeOutput($success); ?></div>
        <?php endif; ?>
        
        <!-- Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="open" <?php echo $statusFilter == 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="waiting_customer" <?php echo $statusFilter == 'waiting_customer' ? 'selected' : ''; ?>>Waiting Customer</option>
                            <option value="resolved" <?php echo $statusFilter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-2">
                        <a href="support.php" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="row">
            <!-- Ticket List -->
            <div class="col-md-<?php echo $ticketDetail ? '6' : '12'; ?>">
                <div class="card">
                    <div class="card-header">
                        <h4>Support Tickets</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subject</th>
                                        <th>Channel</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($ticket = $result->fetch_assoc()): ?>
                                            <tr class="<?php echo $ticketDetail && $ticketDetail['id'] == $ticket['id'] ? 'table-active' : ''; ?>">
                                                <td><?php echo escapeOutput($ticket['id']); ?></td>
                                                <td><?php echo escapeOutput($ticket['name']); ?></td>
                                                <td><?php echo escapeOutput($ticket['email']); ?></td>
                                                <td><?php echo escapeOutput($ticket['subject']); ?></td>
                                                <td><?php echo escapeOutput($ticket['channel']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $ticket['status'] == 'resolved' ? 'success' : 
                                                            ($ticket['status'] == 'waiting_customer' ? 'warning' : 'primary'); 
                                                    ?>">
                                                        <?php echo escapeOutput($ticket['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo escapeOutput($ticket['created_at']); ?></td>
                                                <td>
                                                    <a href="support.php?id=<?php echo $ticket['id']; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>" class="btn btn-sm btn-info">View</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No tickets found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ticket Detail -->
            <?php if ($ticketDetail): ?>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Ticket #<?php echo escapeOutput($ticketDetail['id']); ?></h4>
                        </div>
                        <div class="card-body">
                            <p><strong>Name:</strong> <?php echo escapeOutput($ticketDetail['name']); ?></p>
                            <p><strong>Email:</strong> <?php echo escapeOutput($ticketDetail['email']); ?></p>
                            <p><strong>Channel:</strong> <?php echo escapeOutput($ticketDetail['channel']); ?></p>
                            <p><strong>Subject:</strong> <?php echo escapeOutput($ticketDetail['subject']); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php 
                                    echo $ticketDetail['status'] == 'resolved' ? 'success' : 
                                        ($ticketDetail['status'] == 'waiting_customer' ? 'warning' : 'primary'); 
                                ?>">
                                    <?php echo escapeOutput($ticketDetail['status']); ?>
                                </span>
                            </p>
                            <p><strong>Message:</strong><br><?php echo nl2br(escapeOutput($ticketDetail['message'])); ?></p>
                            
                            <?php if ($ticketDetail['admin_response']): ?>
                                <div class="alert alert-info">
                                    <strong>Admin Response:</strong><br>
                                    <?php echo nl2br(escapeOutput($ticketDetail['admin_response'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($ticketDetail['status'] != 'resolved'): ?>
                                <hr>
                                <h5>Respond to Ticket</h5>
                                
                                <!-- Resolve Ticket -->
                                <form method="POST" class="mb-3">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticketDetail['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Response (Resolve Ticket)</label>
                                        <textarea name="admin_response" class="form-control" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" name="resolve_ticket" class="btn btn-success">Resolve Ticket</button>
                                </form>
                                
                                <!-- Request More Info -->
                                <form method="POST">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticketDetail['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Request More Information</label>
                                        <textarea name="admin_response" class="form-control" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" name="request_info" class="btn btn-warning">Request More Info</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

