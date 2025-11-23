<?php
include 'config.php';
include 'functions.php';
session_start();

// Check session timeout
checkSessionTimeout();

// Check if already logged in
if (isset($_SESSION['usermail']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff') {
        header("Location: admin/admin.php");
        exit();
    } else {
        header("Location: home.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <!-- Sweet Alert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <!-- AOS Animation -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <!-- Loading Bar -->
    <script src="https://cdn.jsdelivr.net/npm/pace-js@latest/pace.min.js"></script>
    <link rel="stylesheet" href="./css/flash.css">
    <title>Hotel TDTU</title>
</head>

<body>
    <!-- Carousel -->
    <section id="carouselExampleControls" class="carousel slide carousel_section" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img class="carousel-image" src="./image/hotel1.jpg">
            </div>
            <div class="carousel-item">
                <img class="carousel-image" src="./image/hotel2.jpg">
            </div>
            <div class="carousel-item">
                <img class="carousel-image" src="./image/hotel3.jpg">
            </div>
            <div class="carousel-item">
                <img class="carousel-image" src="./image/hotel4.jpg">
            </div>
        </div>
    </section>

    <!-- Main Section -->
    <section id="auth_section">
        <div class="logo">
            <img class="bluebirdlogo" src="./image/bluebirdlogo.png" alt="logo">
            <p>TDTU</p>
        </div>
        <div class="auth_container">
            <!-- Login -->
            <div id="Log_in">
                <h2>Log In</h2>
                <div class="role_btn">
                    <div class="btns active">User</div>
                    <div class="btns">Staff</div>
                </div>

                <!-- User Login -->
                <?php
                if (isset($_POST['user_login_submit'])) {
                    $email = trim($_POST['Email']);
                    $password = $_POST['Password'];
                    
                    // Check if account is locked
                    if (isAccountLocked($conn, $email, 'signup')) {
                        echo "<script>swal({ title: 'Account is locked. Please try again later.', icon: 'error', });</script>";
                    } else {
                        // Get user from database
                        $stmt = $conn->prepare("SELECT UserID, Username, Email, Password, role, is_active FROM signup WHERE Email = ? AND is_active = 1");
                        $stmt->bind_param("s", $email);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $user = $result->fetch_assoc();
                            $storedPassword = $user['Password'];
                            
                            // Check password (support both hashed and plain text for migration)
                            $passwordValid = false;
                            if (password_verify($password, $storedPassword)) {
                                $passwordValid = true;
                            } elseif (strlen($storedPassword) < 60 && $storedPassword === $password) {
                                // Plain text password - hash it for future use
                                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                                $updateStmt = $conn->prepare("UPDATE signup SET Password = ? WHERE UserID = ?");
                                $updateStmt->bind_param("si", $hashedPassword, $user['UserID']);
                                $updateStmt->execute();
                                $passwordValid = true;
                            }
                            
                            if ($passwordValid) {
                                // Reset failed attempts
                                resetFailedAttempts($conn, $email, 'signup');
                                
                                // Generate 2FA code
                                $userId = $user['UserID'];
                                $code = generate2FACode($conn, $userId);
                                
                                // Store user info in session for 2FA verification
                                $_SESSION['2fa_user_id'] = $userId;
                                $_SESSION['2fa_email'] = $email;
                                $_SESSION['2fa_role'] = $user['role'];
                                
                                // Log activity
                                logActivity($conn, $userId, 'login_attempt', 'signup', $userId, '2FA code generated');
                                
                                // For demo: show code on screen (in production, send email)
                                echo "<script>
                                    swal({ 
                                        title: '2FA Code', 
                                        html: 'Your verification code is: <strong>" . $code . "</strong><br>Please enter this code on the next page.',
                                        icon: 'info',
                                        confirmButtonText: 'Continue'
                                    }).then(() => {
                                        window.location.href = 'verify_2fa.php';
                                    });
                                </script>";
                            } else {
                                // Increment failed attempts
                                incrementFailedAttempts($conn, $email, 'signup');
                                echo "<script>swal({ title: 'Invalid email or password', icon: 'error', });</script>";
                            }
                        } else {
                            incrementFailedAttempts($conn, $email, 'signup');
                            echo "<script>swal({ title: 'Invalid email or password', icon: 'error', });</script>";
                        }
                    }
                }
                ?>
                <form class="user_login authsection active" id="userlogin" action="" method="POST">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="Username" placeholder=" ">
                        <label for="Username">Username</label>
                    </div>
                    <div class="form-floating">
                        <input type="email" class="form-control" name="Email" placeholder=" ">
                        <label for="Email">Email</label>
                    </div>
                    <div class="form-floating">
                        <input type="password" class="form-control" name="Password" placeholder=" ">
                        <label for="Password">Password</label>
                    </div>
                    <button type="submit" name="user_login_submit" class="auth_btn">Log in</button>
                    <div class="footer_line">
                        <h6>Don't have an account? <span class="page_move_btn" onclick="signuppage()">sign up</span></h6>
                        <h6 style="margin-top: 10px;"><a href="forgot_password.php" style="color: #007bff; text-decoration: none;">Forgot Password?</a></h6>
                    </div>
                </form>

                <!-- Employee Login -->
                <?php
                if (isset($_POST['Emp_login_submit'])) {
                    $email = trim($_POST['Emp_Email']);
                    $password = $_POST['Emp_Password'];
                    
                    // Check if account is locked
                    if (isAccountLocked($conn, $email, 'emp_login')) {
                        echo "<script>swal({ title: 'Account is locked. Please try again later.', icon: 'error', });</script>";
                    } else {
                        // Get employee from database
                        $stmt = $conn->prepare("SELECT empid, Emp_Email, Emp_Password, role, is_active FROM emp_login WHERE Emp_Email = ? AND is_active = 1");
                        $stmt->bind_param("s", $email);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $emp = $result->fetch_assoc();
                            $storedPassword = $emp['Emp_Password'];
                            
                            // Check password (support both hashed and plain text for migration)
                            $passwordValid = false;
                            if (password_verify($password, $storedPassword)) {
                                $passwordValid = true;
                            } elseif (strlen($storedPassword) < 60 && $storedPassword === $password) {
                                // Plain text password - hash it
                                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                                $updateStmt = $conn->prepare("UPDATE emp_login SET Emp_Password = ? WHERE empid = ?");
                                $updateStmt->bind_param("si", $hashedPassword, $emp['empid']);
                                $updateStmt->execute();
                                $passwordValid = true;
                            }
                            
                            if ($passwordValid) {
                                // Reset failed attempts
                                resetFailedAttempts($conn, $email, 'emp_login');
                                
                                // Generate 2FA code
                                $code = generate2FACode($conn, $emp['empid']);
                                
                                // Store employee info for 2FA
                                $_SESSION['2fa_user_id'] = $emp['empid'];
                                $_SESSION['2fa_email'] = $email;
                                $_SESSION['2fa_role'] = $emp['role'];
                                $_SESSION['2fa_is_emp'] = true;
                                
                                // For demo: show code
                                echo "<script>
                                    swal({ 
                                        title: '2FA Code', 
                                        html: 'Your verification code is: <strong>" . $code . "</strong><br>Please enter this code on the next page.',
                                        icon: 'info',
                                        confirmButtonText: 'Continue'
                                    }).then(() => {
                                        window.location.href = 'verify_2fa.php';
                                    });
                                </script>";
                            } else {
                                incrementFailedAttempts($conn, $email, 'emp_login');
                                echo "<script>swal({ title: 'Invalid email or password', icon: 'error', });</script>";
                            }
                        } else {
                            incrementFailedAttempts($conn, $email, 'emp_login');
                            echo "<script>swal({ title: 'Invalid email or password', icon: 'error', });</script>";
                        }
                    }
                }
                ?>
                <form class="employee_login authsection" id="employeelogin" action="" method="POST">
                    <div class="form-floating">
                        <input type="email" class="form-control" name="Emp_Email" placeholder=" ">
                        <label for="floatingInput">Email</label>
                    </div>
                    <div class="form-floating">
                        <input type="password" class="form-control" name="Emp_Password" placeholder=" ">
                        <label for="floatingPassword">Password</label>
                    </div>
                    <button type="submit" name="Emp_login_submit" class="auth_btn">Log in</button>
                </form>
            </div>

            <!-- Sign Up -->
            <?php
            if (isset($_POST['user_signup_submit'])) {
                $username = trim($_POST['Username']);
                $email = trim($_POST['Email']);
                $password = $_POST['Password'];
                $cpassword = $_POST['CPassword'];
                $phone = isset($_POST['Phone']) ? trim($_POST['Phone']) : '';
                $address = isset($_POST['Address']) ? trim($_POST['Address']) : '';

                if ($username == "" || $email == "" || $password == "") {
                    echo "<script>swal({ title: 'Fill the proper details', icon: 'error', });</script>";
                } else {
                    if ($password == $cpassword) {
                        if (strlen($password) < 6) {
                            echo "<script>swal({ title: 'Password must be at least 6 characters', icon: 'error', });</script>";
                        } else {
                            // Check if email exists
                            $stmt_check = $conn->prepare("SELECT UserID FROM signup WHERE Email = ?");
                            $stmt_check->bind_param("s", $email);
                            $stmt_check->execute();
                            $result = $stmt_check->get_result();

                            if ($result->num_rows > 0) {
                                echo "<script>swal({ title: 'Email already exists', icon: 'error', });</script>";
                            } else {
                                // Hash password
                                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                                
                                // Insert new user
                                $stmt_insert = $conn->prepare("INSERT INTO signup (Username, Email, Password, Phone, Address, role) VALUES (?, ?, ?, ?, ?, 'customer')");
                                $stmt_insert->bind_param("sssss", $username, $email, $hashedPassword, $phone, $address);
                                $stmt_insert->execute();

                                if ($stmt_insert->affected_rows > 0) {
                                    $userId = $stmt_insert->insert_id;
                                    
                                    // Log activity
                                    logActivity($conn, $userId, 'user_registered', 'signup', $userId, 'New user registration');
                                    
                                    echo "<script>swal({ title: 'Registration successful!', icon: 'success', }).then(() => { window.location.href = 'index.php'; });</script>";
                                } else {
                                    echo "<script>swal({ title: 'Something went wrong', icon: 'error', });</script>";
                                }
                            }
                        }
                    } else {
                        echo "<script>swal({ title: 'Password does not match', icon: 'error', });</script>";
                    }
                }
            }
            ?>
            <div id="sign_up">
                <h2>Sign Up</h2>
                <form class="user_signup" id="usersignup" action="" method="POST">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="Username" placeholder=" " required>
                        <label for="Username">Username</label>
                    </div>
                    <div class="form-floating">
                        <input type="email" class="form-control" name="Email" placeholder=" " required>
                        <label for="Email">Email</label>
                    </div>
                    <div class="form-floating">
                        <input type="password" class="form-control" name="Password" placeholder=" " required minlength="6">
                        <label for="Password">Password (min 6 chars)</label>
                    </div>
                    <div class="form-floating">
                        <input type="password" class="form-control" name="CPassword" placeholder=" " required>
                        <label for="CPassword">Confirm Password</label>
                    </div>
                    <div class="form-floating">
                        <input type="text" class="form-control" name="Phone" placeholder=" ">
                        <label for="Phone">Phone (Optional)</label>
                    </div>
                    <div class="form-floating">
                        <input type="text" class="form-control" name="Address" placeholder=" ">
                        <label for="Address">Address (Optional)</label>
                    </div>
                    <button type="submit" name="user_signup_submit" class="auth_btn">Sign up</button>
                    <div class="footer_line">
                        <h6>Already have an account? <span class="page_move_btn" onclick="loginpage()">Log in</span></h6>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script src="./javascript/index.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>

</html>