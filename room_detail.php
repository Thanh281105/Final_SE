<?php
include 'config.php';
include 'functions.php';
session_start();
checkSessionTimeout();

$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($room_id == 0) {
    header("Location: room_list.php");
    exit();
}

// Get room details
$stmt = $conn->prepare("SELECT * FROM room WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: room_list.php");
    exit();
}

$room = $result->fetch_assoc();

// Get feedback
$feedbackStmt = $conn->prepare("SELECT rf.*, s.Username 
                                FROM room_feedback rf 
                                JOIN signup s ON rf.user_id = s.UserID 
                                WHERE rf.room_id = ? 
                                ORDER BY rf.created_at DESC 
                                LIMIT 10");
$feedbackStmt->bind_param("i", $room_id);
$feedbackStmt->execute();
$feedbackResult = $feedbackStmt->get_result();

// Handle feedback submission
$feedbackError = '';
$feedbackSuccess = '';

if (isset($_POST['submit_feedback']) && isset($_SESSION['usermail'])) {
    requireLogin();
    $userId = getUserId();
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    if ($rating < 1 || $rating > 5) {
        $feedbackError = 'Please select a valid rating';
    } else {
        $insertStmt = $conn->prepare("INSERT INTO room_feedback (room_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $insertStmt->bind_param("iiis", $room_id, $userId, $rating, $comment);
        $insertStmt->execute();
        
        if ($insertStmt->affected_rows > 0) {
            $feedbackSuccess = 'Thank you for your feedback!';
            logActivity($conn, $userId, 'feedback_submitted', 'room_feedback', $insertStmt->insert_id, "Feedback submitted for room $room_id");
            
            // Refresh feedback
            $feedbackStmt->execute();
            $feedbackResult = $feedbackStmt->get_result();
        } else {
            $feedbackError = 'Failed to submit feedback';
        }
    }
}

// Calculate average rating
$avgRatingStmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM room_feedback WHERE room_id = ?");
$avgRatingStmt->bind_param("i", $room_id);
$avgRatingStmt->execute();
$avgResult = $avgRatingStmt->get_result();
$avgData = $avgResult->fetch_assoc();
$avgRating = $avgData['avg_rating'] ? round($avgData['avg_rating'], 1) : 0;
$totalReviews = $avgData['total_reviews'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeOutput($room['type']); ?> - Hotel TDTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <?php include 'navbar.php'; ?>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8">
                <h2><?php echo escapeOutput($room['type']); ?></h2>
                
                <!-- Room Image -->
                <div class="mb-4">
                    <?php
                    $roomImages = array(
                        'Superior Room' => './image/hotel1.jpg',
                        'Deluxe Room' => './image/hotel2.jpg',
                        'Guest House' => './image/hotel3.jpg',
                        'Single Room' => './image/hotel4.jpg'
                    );
                    $image = $roomImages[$room['type']] ?? './image/hotel1.jpg';
                    ?>
                    <img src="<?php echo $image; ?>" class="img-fluid rounded" alt="<?php echo escapeOutput($room['type']); ?>">
                </div>
                
                <!-- Room Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Room Details</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Bedding:</strong> <?php echo escapeOutput($room['bedding']); ?></p>
                        <p><strong>Max Guests:</strong> <?php echo escapeOutput($room['max_guests']); ?></p>
                        <p><strong>Price per night:</strong> ₹<?php echo number_format($room['price'], 2); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?php echo $room['status'] == 'Available' ? 'success' : 'danger'; ?>">
                                <?php echo escapeOutput($room['status']); ?>
                            </span>
                        </p>
                        <?php if ($room['description']): ?>
                            <hr>
                            <p><?php echo nl2br(escapeOutput($room['description'])); ?></p>
                        <?php endif; ?>
                        <?php if ($room['amenities']): ?>
                            <hr>
                            <p><strong>Amenities:</strong><br><?php echo nl2br(escapeOutput($room['amenities'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Reviews -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h4>Reviews</h4>
                        <?php if ($avgRating > 0): ?>
                            <div>
                                <span class="badge bg-primary"><?php echo $avgRating; ?>/5</span>
                                <small class="text-muted">(<?php echo $totalReviews; ?> reviews)</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($feedbackResult->num_rows > 0): ?>
                            <?php while ($feedback = $feedbackResult->fetch_assoc()): ?>
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <strong><?php echo escapeOutput($feedback['Username']); ?></strong>
                                        <div>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="text-muted mb-1"><small><?php echo escapeOutput($feedback['created_at']); ?></small></p>
                                    <?php if ($feedback['comment']): ?>
                                        <p><?php echo nl2br(escapeOutput($feedback['comment'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">No reviews yet. Be the first to review!</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Submit Feedback -->
                <?php if (isset($_SESSION['usermail'])): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h4>Write a Review</h4>
                        </div>
                        <div class="card-body">
                            <?php if ($feedbackError): ?>
                                <div class="alert alert-danger"><?php echo escapeOutput($feedbackError); ?></div>
                            <?php endif; ?>
                            <?php if ($feedbackSuccess): ?>
                                <div class="alert alert-success"><?php echo escapeOutput($feedbackSuccess); ?></div>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <select name="rating" class="form-control" required>
                                        <option value="">Choose...</option>
                                        <option value="5">5 - Excellent</option>
                                        <option value="4">4 - Very Good</option>
                                        <option value="3">3 - Good</option>
                                        <option value="2">2 - Fair</option>
                                        <option value="1">1 - Poor</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Comment</label>
                                    <textarea name="comment" class="form-control" rows="3"></textarea>
                                </div>
                                <button type="submit" name="submit_feedback" class="btn btn-primary">Submit Review</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>₹<?php echo number_format($room['price'], 2); ?> per night</h5>
                        <?php if ($room['status'] == 'Available'): ?>
                            <a href="search_rooms.php" class="btn btn-primary w-100 mt-3">Book Now</a>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100 mt-3" disabled>Not Available</button>
                        <?php endif; ?>
                        <a href="room_list.php" class="btn btn-outline-secondary w-100 mt-2">Back to Rooms</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

