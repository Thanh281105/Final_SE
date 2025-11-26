<?php
include 'config.php';
session_start();

// Handle success alert from session (show only once)
if (isset($_SESSION['success'])) {
    echo "<script>swal({title:'Reservation successful! Waiting for admin confirmation.',icon:'success'});</script>";
    unset($_SESSION['success']);
}

// Handle form submission (moved to top) - This part is kept from the NEW code, which already contains the OLD logic
if (isset($_POST['guestdetailsubmit'])) {
    $Name = mysqli_real_escape_string($conn, $_POST['Name']);
    $Email = mysqli_real_escape_string($conn, $_POST['Email']);
    $Country = mysqli_real_escape_string($conn, $_POST['Country']);
    $Phone = mysqli_real_escape_string($conn, $_POST['Phone']);
    $RoomType = mysqli_real_escape_string($conn, $_POST['RoomType']); // Lấy từ hidden input
    $Bed = mysqli_real_escape_string($conn, $_POST['Bed']);
    $NoofRoom = mysqli_real_escape_string($conn, $_POST['NoofRoom']);
    $Meal = mysqli_real_escape_string($conn, $_POST['Meal']);
    $cin = $_POST['cin'];
    $cout = $_POST['cout'];

    if (empty($Name) || empty($Email) || empty($Country) || empty($Phone) || empty($RoomType) || empty($Bed) || empty($NoofRoom) || empty($Meal) || empty($cin) || empty($cout)) {
        echo "<script>swal({title:'Please fill all fields',icon:'error'});</script>";
    } elseif (strtotime($cin) >= strtotime($cout)) {
        echo "<script>swal({title:'Check-out must be after check-in',icon:'error'});</script>";
    } elseif (strtotime($cin) < strtotime('2025-11-26')) {
        echo "<script>swal({title:'Check-in cannot be in the past',icon:'error'});</script>";
    } else {
        $sta = 'Not Confirmed';
        $nodays = (strtotime($cout) - strtotime($cin)) / (60 * 60 * 24);

        $sql = "INSERT INTO roombook (Name, Email, Country, Phone, RoomType, Bed, NoofRoom, Meal, cin, cout, stat, nodays)
                VALUES ('$Name', '$Email', '$Country', '$Phone', '$RoomType', '$Bed', '$NoofRoom', '$Meal', '$cin', '$cout', '$sta', $nodays)";

        if (mysqli_query($conn, $sql)) {
            $last_id = mysqli_insert_id($conn);

            // Insert initial payment with totals = 0
            $payment_sql = "INSERT INTO payment (id, Name, Email, RoomType, Bed, NoofRoom, meal, cin, cout, noofdays, roomtotal, bedtotal, mealtotal, finaltotal)
                            VALUES ($last_id, '$Name', '$Email', '$RoomType', '$Bed', '$NoofRoom', '$Meal', '$cin', '$cout', $nodays, 0, 0, 0, 0)";
            mysqli_query($conn, $payment_sql);

            $_SESSION['success'] = true; // Set session for alert
            header("Location: home.php"); // Redirect to avoid resubmit
            exit;
        } else {
            echo "<script>swal({title:'Something went wrong: " . mysqli_error($conn) . "',icon:'error'});</script>";
        }
    }
}

// Page redirect if not logged in
$usermail = $_SESSION['usermail'];
if (!$usermail) {
    header("location: index.php");
    exit;
}

// Get user avatar (assume from signup table, or default) - Kept from NEW code
$avatar = './images/Profile.png'; // Default
$user_sql = "SELECT avatar FROM signup WHERE Email = '$usermail'"; // Assume you add 'avatar' column later
$user_res = mysqli_query($conn, $user_sql);
if ($user_res && mysqli_num_rows($user_res) > 0) {
    $user_row = mysqli_fetch_assoc($user_res);
    if (!empty($user_row['avatar'])) $avatar = $user_row['avatar'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/home.css">
    <title>Hotel TDTU</title>

    <!-- bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- fontawesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>

    <!-- sweetalert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <link rel="stylesheet" href="./admin/css/roombook.css">

    <style>
      #guestdetailpanel { display: none; }
      #guestdetailpanel .middle { height: 450px; }

      /* POPUP DETAIL VIEW */
      .roomdetailpanel {
          display: none;
          justify-content: center;
          align-items: center;
          position: fixed;
          top: 0; left: 0;
          width: 100%; height: 100%;
          background: rgba(0,0,0,0.75);
          z-index: 3000;
      }
      .detailbox {
          background: #fff;
          width: 700px;
          max-height: 90vh;
          overflow-y: auto;
          padding: 20px;
          border-radius: 12px;
          position: relative;
      }
      .closebtn {
          position: absolute;
          right: 20px;
          top: 10px;
          font-size: 28px;
          cursor: pointer;
      }
      .roomimages img {
          width: 100%;
          border-radius: 10px;
          margin-bottom: 10px;
      }
      #roomfacilities i {
          font-size: 24px;
          margin-right: 10px;
      }
      .detail-actions {
          margin-top: 15px;
          display: flex;
          justify-content: flex-end;
          gap: 10px;
      }

      .detailbtn { margin-right: 5px; }

      .avatar-dropdown {
          position: relative;
          display: inline-block;
      }
      .avatar-img {
          width: 40px;
          height: 40px;
          border-radius: 50%;
          cursor: pointer;
      }
      .dropdown-menu {
          display: none;
          position: absolute;
          right: 0;
          background: white;
          box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
          z-index: 1;
          min-width: 160px;
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
      .avatar-dropdown:hover .dropdown-menu {
          display: block;
      }

      /* CSS mới để logo có thể click */
      .logo {
          cursor: pointer; /* Con trỏ tay khi hover vào logo */
          display: flex;   /* Đảm bảo layout flex nếu cần */
          align-items: center; /* Căn giữa theo chiều dọc */
          text-decoration: none; /* Loại bỏ gạch chân mặc định nếu là link */
          color: inherit; /* Giữ màu văn bản mặc định */
      }
      .logo:hover {
          opacity: 0.8; /* Hiệu ứng mờ nhẹ khi hover */
      }
    </style>
</head>

<body>

  <nav>
    <!-- Logo được đặt trong một thẻ <a> để click chuyển hướng -->
    <a href="home.php" class="logo" style="text-decoration: none; color: inherit; display: flex; align-items: center;">
        <img class="bluebirdlogo" src="./image/bluebirdlogo.png" alt="logo" style="margin-right: 8px;"> <!-- Khoảng cách nhỏ giữa logo và chữ -->
        <p>TDTU</p>
    </a>
    <ul>
      <li><a href="#firstsection">Home</a></li> <!-- Có thể giữ hoặc bỏ, tùy bạn -->
      <li><a href="#secondsection">Rooms</a></li>
      <li><a href="#thirdsection">Facilities</a></li>
      <li><a href="#contactus">Contact us</a></li>

      <!-- Bỏ <a href="./logout.php"><button class="btn btn-danger">Logout</button></a> -->
      <li class="avatar-dropdown">
        <?php
        $avatar_path = 'image/Profile.png';

        if (isset($_SESSION['usermail'])) {
            $email = $_SESSION['usermail'];
            $check_col = mysqli_query($conn, "SHOW COLUMNS FROM signup LIKE 'avatar'");
            if (mysqli_num_rows($check_col) > 0) {
                $res = mysqli_query($conn, "SELECT avatar FROM signup WHERE Email = '$email' LIMIT 1");
                if ($res && mysqli_num_rows($res) > 0) {
                    $row = mysqli_fetch_assoc($res);
                    if (!empty($row['avatar']) && file_exists($row['avatar'])) {
                        $avatar_path = $row['avatar'];
                    }
                }
            }
        }
        ?>
        <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Profile" class="avatar-img">

        <div class="dropdown-menu">
          <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
          <a href="logout.php"><i class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </li>
    </ul>
  </nav>

  <!-- ========== MAIN SLIDER ========== -->
  <section id="firstsection" class="carousel slide carousel_section" data-bs-ride="carousel">
    <div class="carousel-inner">
        <div class="carousel-item active"><img class="carousel-image" src="./image/hotel1.jpg"></div>
        <div class="carousel-item"><img class="carousel-image" src="./image/hotel2.jpg"></div>
        <div class="carousel-item"><img class="carousel-image" src="./image/hotel3.jpg"></div>
        <div class="carousel-item"><img class="carousel-image" src="./image/hotel4.jpg"></div>

        <div class="welcomeline">
          <h1 class="welcometag">Welcome to heaven on earth</h1>
        </div>

        <!-- ========== BOOKING PANEL ========== -->
        <div id="guestdetailpanel">
            <form action="" method="POST" class="guestdetailpanelform">
                <div class="head">
                    <h3>RESERVATION</h3>
                    <i class="fa-solid fa-circle-xmark" onclick="closebox()"></i>
                </div>

                <div class="middle">
                    <div class="guestinfo">
                        <h4>Guest information</h4>
                        <input type="text" name="Name" placeholder="Enter Full name" required>
                        <input type="email" name="Email" placeholder="Enter Email" required>

                        <?php
                        $countries = array("Vietnam", "China", "Japan", "Korea", "Thailand", "Laos", "Campuchia", "Singapore", "Indonesia", "Philippines");
                        ?>
                        <select name="Country" class="selectinput" required>
                            <option value selected disabled>Select your country</option>
                            <?php foreach($countries as $c): ?>
                              <option value="<?= $c ?>"><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="Phone" placeholder="Enter Phone" required>
                    </div>

                    <div class="line"></div>

                    <div class="reservationinfo">
                        <h4>Reservation information</h4>

                        <!-- Hidden input để lưu RoomType -->
                        <input type="hidden" name="RoomType" id="fixedRoomType" value="">
                          <div class="form-group">
                            <label>Type Of Room</label>
                            <!-- Hiển thị tên phòng đã chọn -->
                            <p id="roomTypeDisplay" style="font-weight: bold; color: #007bff;">(Select a room type)</p>
                          </div>

                        <select name="Bed" class="selectinput" required>
                            <option value selected disabled>Bedding Type</option>
                            <option value="Single">Single</option>
                            <option value="Double">Double</option>
                            <option value="Triple">Triple</option>
                            <option value="Quad">Quad</option>
                        </select>

                        <select name="NoofRoom" class="selectinput" required>
                            <option value selected disabled>No of Room</option>
                            <option value="1">1</option>
                        </select>

                        <select name="Meal" class="selectinput" required>
                            <option value selected disabled>Meal</option>
                            <option value="Room only">Room only</option>
                            <option value="Breakfast">Breakfast</option>
                            <option value="Half Board">Half Board</option>
                            <option value="Full Board">Full Board</option>
                        </select>

                        <div class="datesection">
                            <span>
                                <label>Check-In</label>
                                <input name="cin" type="date" required min="2025-11-26">
                            </span>
                            <span>
                                <label>Check-Out</label>
                                <input name="cout" type="date" required min="2025-11-27">
                            </span>
                        </div>
                    </div>
                </div>

                <div class="footer">
                    <!-- Nút submit vẫn giữ nguyên -->
                    <button class="btn btn-success" name="guestdetailsubmit">Submit</button>
                </div>
            </form>
        </div>
    </div>
  </section>

  <!-- ========== OUR ROOMS ========== -->
  <section id="secondsection">
    <img src="./image/homeanimatebg.svg">
    <div class="ourroom">
      <h1 class="head">≼ Our room ≽</h1>

      <div class="roomselect">

        <!-- Superior Room -->
        <div class="roombox">
          <div class="hotelphoto h1"></div>
          <div class="roomdata">
            <h2>Superior Room</h2>
            <div class="services">
              <i class="fa-solid fa-wifi"></i>
              <i class="fa-solid fa-burger"></i>
              <i class="fa-solid fa-spa"></i>
              <i class="fa-solid fa-dumbbell"></i>
              <i class="fa-solid fa-person-swimming"></i>
            </div>

            <button class="btn btn-secondary detailbtn" onclick="openDetail('superior')">View Details</button>
            <button class="btn btn-primary bookbtn" onclick="openbook('Superior Room')">Book</button> <!-- Gọi openbook với tên phòng -->
          </div>
        </div>

        <!-- Deluxe Room -->
        <div class="roombox">
          <div class="hotelphoto h2"></div>
          <div class="roomdata">
            <h2>Delux Room</h2>
            <div class="services">
              <i class="fa-solid fa-wifi"></i>
              <i class="fa-solid fa-burger"></i>
              <i class="fa-solid fa-spa"></i>
              <i class="fa-solid fa-dumbbell"></i>
            </div>

            <button class="btn btn-secondary detailbtn" onclick="openDetail('deluxe')">View Details</button>
            <button class="btn btn-primary bookbtn" onclick="openbook('Deluxe Room')">Book</button> <!-- Gọi openbook với tên phòng -->
          </div>
        </div>

        <!-- Guest Room -->
        <div class="roombox">
          <div class="hotelphoto h3"></div>
          <div class="roomdata">
            <h2>Guest Room</h2>
            <div class="services">
              <i class="fa-solid fa-wifi"></i>
              <i class="fa-solid fa-burger"></i>
              <i class="fa-solid fa-spa"></i>
            </div>

            <button class="btn btn-secondary detailbtn" onclick="openDetail('guestroom')">View Details</button>
            <button class="btn btn-primary bookbtn" onclick="openbook('Guest House')">Book</button> <!-- Gọi openbook với tên phòng -->
          </div>
        </div>

        <!-- Single Room -->
        <div class="roombox">
          <div class="hotelphoto h4"></div>
          <div class="roomdata">
            <h2>Single Room</h2>
            <div class="services">
              <i class="fa-solid fa-wifi"></i>
              <i class="fa-solid fa-burger"></i>
            </div>

            <button class="btn btn-secondary detailbtn" onclick="openDetail('single')">View Details</button>
            <button class="btn btn-primary bookbtn" onclick="openbook('Single Room')">Book</button> <!-- Gọi openbook với tên phòng -->
          </div>
        </div>

      </div>
    </div>
  </section>
<!-- ========== FACILITIES SECTION ========== -->
<section id="thirdsection">
    <h1 class="head">≼ Facilities ≽</h1>

    <div class="facility">

      <!-- Swimming Pool -->
      <div class="box" onclick="openDetail('pool')" style="cursor:pointer;">
        <h2>Swimming Pool</h2>
        <div class="services mt-2">
          <i class="fa-solid fa-person-swimming"></i>
          <i class="fa-solid fa-water-ladder"></i>
          <i class="fa-solid fa-umbrella-beach"></i>
        </div>
      </div>

      <!-- Spa -->
      <div class="box" onclick="openDetail('spa')" style="cursor:pointer;">
        <h2>Spa</h2>
        <div class="services mt-2">
          <i class="fa-solid fa-spa"></i>
          <i class="fa-solid fa-heart"></i>
          <i class="fa-solid fa-hot-tub-person"></i>
        </div>
      </div>

      <!-- Restaurant -->
      <div class="box" onclick="openDetail('restaurant')" style="cursor:pointer;">
        <h2>24/7 Restaurant</h2>
        <div class="services mt-2">
          <i class="fa-solid fa-utensils"></i>
          <i class="fa-solid fa-mug-hot"></i>
          <i class="fa-solid fa-burger"></i>
        </div>
      </div>

      <!-- Gym -->
      <div class="box" onclick="openDetail('gym')" style="cursor:pointer;">
        <h2>24/7 Gym</h2>
        <div class="services mt-2">
          <i class="fa-solid fa-dumbbell"></i>
          <i class="fa-solid fa-person-running"></i>
          <i class="fa-solid fa-heart-pulse"></i>
        </div>
      </div>

      <!-- Heli -->
      <div class="box" onclick="openDetail('heli')" style="cursor:pointer;">
        <h2>Heli Service</h2>
        <div class="services mt-2">
          <i class="fa-solid fa-helicopter"></i>
          <i class="fa-solid fa-shield-halved"></i>
          <i class="fa-solid fa-user-tie"></i>
        </div>
      </div>

    </div>
</section>


<!-- ========== CONTACT ========== -->
<section id="contactus">
  <div class="social">
    <i class="fa-brands fa-instagram"></i>
    <i class="fa-brands fa-facebook"></i>
    <i class="fa-solid fa-envelope"></i>
  </div>
  <div class="createdby">
    <h5>Created by 1 mình tao</h5>
  </div>
</section>

<!-- ========== POPUP DETAIL VIEW (ROOM + FACILITY) ========== -->
<div class="roomdetailpanel" id="roomdetailpanel">
    <div class="detailbox">

        <i class="fa-solid fa-circle-xmark closebtn" onclick="closeDetail()"></i>

        <h2 id="detailtitle"></h2>

        <div class="roomimages" id="detailimages"></div>

        <h4>Description</h4>
        <p id="detaildesc"></p>

        <h4>Facilities</h4>
        <div id="roomfacilities"></div>

        <div class="detail-actions">
            <button class="btn btn-primary" onclick="openbook()">Book Now</button> <!-- Gọi openbook không tham số để chỉ mở form -->
            <button class="btn btn-secondary" onclick="closeDetail()">Close</button>
        </div>

    </div>
</div>
<!-- ========== JAVASCRIPT DETAIL SYSTEM ========== -->
<script>
    var bookbox = document.getElementById("guestdetailpanel");

    function openbookbox() {
        bookbox.style.display = "flex";
    }
    function closebox() {
        bookbox.style.display = "none";
    }

    // Cập nhật hàm openbook để nhận tham số tên phòng
    function openbook(roomtype = '') {
        document.getElementById('guestdetailpanel').style.display = 'flex';
        if (roomtype) {
            document.getElementById('fixedRoomType').value = roomtype;
            document.getElementById('roomTypeDisplay').innerText = roomtype;
        }
    }

    function closebox() {
        document.getElementById('guestdetailpanel').style.display = 'none';
        // Reset nếu cần
        document.getElementById('fixedRoomType').value = '';
        document.getElementById('roomTypeDisplay').innerText = '(Select a room type)';
      }
    // ======================= DATA FOR ROOMS & FACILITIES =======================
    const viewData = {

        /* ==================== ROOMS ==================== */
        superior: {
            title: "Superior Room",
            images: [
                "./image/hotel1.jpg",
                "./image/hotel2.jpg",
                "./image/hotel3.jpg"
            ],
            desc: "A luxurious room with premium interiors, beautiful balcony view, and excellent amenities.",
            facilities: [
                "fa-wifi",
                "fa-burger",
                "fa-spa",
                "fa-dumbbell",
                "fa-person-swimming"
            ]
        },

        deluxe: {
            title: "Deluxe Room",
            images: [
                "./image/hotel2.jpg",
                "./image/hotel1.jpg",
                "./image/hotel4.jpg"
            ],
            desc: "Spacious deluxe room with modern furniture and top-class room service.",
            facilities: [
                "fa-wifi",
                "fa-burger",
                "fa-spa",
                "fa-dumbbell"
            ]
        },

        guestroom: {
            title: "Guest House Room",
            images: [
                "./image/hotel3.jpg",
                "./image/hotel2.jpg"
            ],
            desc: "Comfortable and affordable guest house room with all basic amenities included.",
            facilities: [
                "fa-wifi",
                "fa-burger",
                "fa-spa"
            ]
        },

        single: {
            title: "Single Room",
            images: [
                "./image/hotel4.jpg",
                "./image/hotel1.jpg"
            ],
            desc: "Budget friendly private single room with essential services.",
            facilities: [
                "fa-wifi",
                "fa-burger"
            ]
        },

        /* ==================== FACILITIES ==================== */
        pool: {
            title: "Swimming Pool",
            images: [
                "./image/pool1.jpg",
                "./image/pool2.jpg"
            ],
            desc: "Our infinity swimming pool is open 24/7 with professional safety staff.",
            facilities: [
                "fa-person-swimming",
                "fa-water-ladder",
                "fa-umbrella-beach"
            ]
        },

        spa: {
            title: "Spa & Wellness",
            images: [
                "./image/spa1.jpg",
                "./image/spa2.jpg"
            ],
            desc: "Relax and refresh with premium spa services from our expert staff.",
            facilities: [
                "fa-spa",
                "fa-heart",
                "fa-hot-tub-person"
            ]
        },

        restaurant: {
            title: "24/7 Restaurant",
            images: [
                "./image/restaurant1.jpg",
                "./image/restaurant2.jpg"
            ],
            desc: "Enjoy delicious dishes anytime with our multi-cultural restaurant.",
            facilities: [
                "fa-utensils",
                "fa-mug-hot",
                "fa-burger"
            ]
        },

        gym: {
            title: "24/7 Gym",
            images: [
                "./image/gym1.jpg",
                "./image/gym2.jpg"
            ],
            desc: "Modern gym with full equipment and AC environment.",
            facilities: [
                "fa-dumbbell",
                "fa-person-running",
                "fa-heart-pulse"
            ]
        },

        heli: {
            title: "Helicopter Service",
            images: [
                "./image/heli1.jpg",
                "./image/heli2.jpg"
            ],
            desc: "Exclusive heli-transportation service for VIP customers.",
            facilities: [
                "fa-helicopter",
                "fa-shield-halved",
                "fa-user-tie"
            ]
        }
    };

    // ======================= SHOW DETAIL POPUP =======================
    function openDetail(key) {
        const data = viewData[key];

        document.getElementById("detailtitle").innerText = data.title;

        let imgHTML = "";
        data.images.forEach(img => {
            imgHTML += `<img src="${img}">`;
        });
        document.getElementById("detailimages").innerHTML = imgHTML;

        document.getElementById("detaildesc").innerText = data.desc;

        let facHTML = "";
        data.facilities.forEach(f => {
            facHTML += `<i class="fa-solid ${f}"></i>`;
        });
        document.getElementById("roomfacilities").innerHTML = facHTML;

        document.getElementById("roomdetailpanel").style.display = "flex";
    }

    function closeDetail() {
        document.getElementById("roomdetailpanel").style.display = "none";
    }

</script>

</body>
</html>