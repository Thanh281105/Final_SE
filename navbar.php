<?php
if (!isset($hideNavbar)) {
    include 'functions.php';
    session_start();
    checkSessionTimeout();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="home.php">
            <img src="./image/bluebirdlogo.png" alt="Logo" height="30" class="d-inline-block align-top">
            TDTU Hotel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="search_rooms.php">Search Rooms</a></li>
                <li class="nav-item"><a class="nav-link" href="room_list.php">Rooms</a></li>
                <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="support_request.php">Support</a></li>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['usermail'])): ?>
                    <li class="nav-item"><span class="navbar-text me-3"><?php echo escapeOutput($_SESSION['usermail']); ?></span></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="index.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<?php } ?>

