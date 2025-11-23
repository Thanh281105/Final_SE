<?php
include 'config.php';
include 'functions.php';
session_start();
checkSessionTimeout();
requireLogin();
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
    </style>
</head>

<body>

  <nav>
    <div class="logo">
      <img class="bluebirdlogo" src="./image/bluebirdlogo.png" alt="logo">
      <p>TDTU</p>
    </div>
    <ul>
      <li><a href="#firstsection">Home</a></li>
      <li><a href="search_rooms.php">Search Rooms</a></li>
      <li><a href="room_list.php">Rooms</a></li>
      <li><a href="#thirdsection">Facilities</a></li>
      <li><a href="my_bookings.php">My Bookings</a></li>
      <li><a href="profile.php">Profile</a></li>
      <li><a href="support_request.php">Support</a></li>
      <a href="./logout.php"><button class="btn btn-danger">Logout</button></a>
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
                      <input type="text" name="Name" placeholder="Enter Full name">
                      <input type="email" name="Email" placeholder="Enter Email">

                      <?php
                      $countries = array("Vietnam","Japan","USA","England","France","Germany","Australia","Canada","India",
                      "Singapore","Thailand","China","Korea","Brazil","Spain","Italy");
                      ?>
                      <select name="Country" class="selectinput">
                          <option value selected>Select your country</option>
                          <?php foreach($countries as $c): ?>
                            <option value="<?= $c ?>"><?= $c ?></option>
                          <?php endforeach; ?>
                      </select>
                      <input type="text" name="Phone" placeholder="Enter Phone">
                  </div>

                  <div class="line"></div>

                  <div class="reservationinfo">
                      <h4>Reservation information</h4>

                      <select name="RoomType" class="selectinput">
                          <option value selected>Type Of Room</option>
                          <option value="Superior Room">SUPERIOR ROOM</option>
                          <option value="Deluxe Room">DELUX ROOM</option>
                          <option value="Guest House">GUEST HOUSE</option>
                          <option value="Single Room">SINGLE ROOM</option>
                      </select>

                      <select name="Bed" class="selectinput">
                          <option value selected>Bedding Type</option>
                          <option value="Single">Single</option>
                          <option value="Double">Double</option>
                          <option value="Triple">Triple</option>
                          <option value="Quad">Quad</option>
                      </select>

                      <select name="NoofRoom" class="selectinput">
                          <option value selected>No of Room</option>
                          <option value="1">1</option>
                      </select>

                      <select name="Meal" class="selectinput">
                          <option value selected>Meal</option>
                          <option value="Room only">Room only</option>
                          <option value="Breakfast">Breakfast</option>
                          <option value="Half Board">Half Board</option>
                          <option value="Full Board">Full Board</option>
                      </select>

                      <div class="datesection">
                          <span>
                              <label>Check-In</label>
                              <input name="cin" type="date">
                          </span>
                          <span>
                              <label>Check-Out</label>
                              <input name="cout" type="date">
                          </span>
                      </div>
                  </div>
              </div>

              <div class="footer">
                  <button class="btn btn-success" name="guestdetailsubmit">Submit</button>
              </div>
          </form>

          <?php
            if (isset($_POST['guestdetailsubmit'])) {
                $Name = trim($_POST['Name']);
                $Email = trim($_POST['Email']);
                $Country = trim($_POST['Country']);
                $Phone = trim($_POST['Phone']);
                $RoomType = $_POST['RoomType'];
                $Bed = $_POST['Bed'];
                $NoofRoom = intval($_POST['NoofRoom']);
                $Meal = $_POST['Meal'];
                $cin = $_POST['cin'];
                $cout = $_POST['cout'];

                if($Name=="" || $Email=="" || $Country=="" || empty($cin) || empty($cout)){
                    echo "<script>swal({title:'Fill the proper details',icon:'error'});</script>";
                } elseif (strtotime($cout) <= strtotime($cin)) {
                    echo "<script>swal({title:'Check-out date must be after check-in date',icon:'error'});</script>";
                } else {
                    // Check availability
                    $availableRooms = checkAvailability($conn, $cin, $cout, $RoomType);
                    
                    if (empty($availableRooms)) {
                        echo "<script>swal({title:'No rooms available for selected dates',icon:'error'});</script>";
                    } else {
                        // Redirect to booking detail with first available room
                        $roomId = $availableRooms[0]['id'];
                        header("Location: booking_detail.php?room_id=" . $roomId . "&cin=" . urlencode($cin) . "&cout=" . urlencode($cout));
                        exit();
                    }
                }
            }
          ?>
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

            <a href="room_list.php?type=Superior Room"><button class="btn btn-secondary detailbtn">View Details</button></a>
            <button class="btn btn-primary bookbtn" onclick="openbookbox()">Book</button>
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

            <a href="room_list.php?type=Deluxe Room"><button class="btn btn-secondary detailbtn">View Details</button></a>
            <button class="btn btn-primary bookbtn" onclick="openbookbox()">Book</button>
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

            <a href="room_list.php?type=Guest House"><button class="btn btn-secondary detailbtn">View Details</button></a>
            <button class="btn btn-primary bookbtn" onclick="openbookbox()">Book</button>
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

            <a href="room_list.php?type=Single Room"><button class="btn btn-secondary detailbtn">View Details</button></a>
            <button class="btn btn-primary bookbtn" onclick="openbookbox()">Book</button>
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
    <h5>Hotel Management System - TDTU</h5>
    <p>Developed by Group [Your Group Name] - MSSV: [Your MSSV]</p>
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
            <button class="btn btn-primary" onclick="openbookbox()">Book Now</button>
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
