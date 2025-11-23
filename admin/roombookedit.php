<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

// fetch room data
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    header("Location: roombook.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM roombook WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: roombook.php");
    exit();
}

$row = $result->fetch_assoc();
$Name = $row['Name'];
$Email = $row['Email'];
$Country = $row['Country'];
$Phone = $row['Phone'];
$cin = $row['cin'];
$cout = $row['cout'];
$noofday = $row['nodays'];
$stat = $row['stat'];
$currentRoomType = $row['RoomType'];
$currentBed = $row['Bed'];
$currentMeal = $row['Meal'];
$currentNoofRoom = $row['NoofRoom'];

if (isset($_POST['guestdetailedit'])) {
    $EditName = trim($_POST['Name']);
    $EditEmail = trim($_POST['Email']);
    $EditCountry = trim($_POST['Country']);
    $EditPhone = trim($_POST['Phone']);
    $EditRoomType = $_POST['RoomType'];
    $EditBed = $_POST['Bed'];
    $EditNoofRoom = intval($_POST['NoofRoom']);
    $EditMeal = $_POST['Meal'];
    $Editcin = $_POST['cin'];
    $Editcout = $_POST['cout'];
    
    // Validate dates
    if (strtotime($Editcout) <= strtotime($Editcin)) {
        echo "<script>alert('Check-out date must be after check-in date'); window.history.back();</script>";
        exit();
    }
    
    // Calculate days
    $cinDate = new DateTime($Editcin);
    $coutDate = new DateTime($Editcout);
    $Editnoofday = $cinDate->diff($coutDate)->days;
    
    // Check availability if dates or room type changed
    if ($Editcin != $cin || $Editcout != $cout || $EditRoomType != $currentRoomType) {
        // Check if room is available (if room_id is set, check that specific room)
        if ($row['room_id']) {
            if (!isRoomAvailable($conn, $row['room_id'], $Editcin, $Editcout, $id)) {
                echo "<script>alert('Selected room is not available for the new dates'); window.history.back();</script>";
                exit();
            }
        } else {
            // Check if any room of this type is available
            $availableRooms = checkAvailability($conn, $Editcin, $Editcout, $EditRoomType, null, $id);
            if (empty($availableRooms)) {
                echo "<script>alert('No rooms of this type are available for the selected dates'); window.history.back();</script>";
                exit();
            }
        }
    }

    // Update roombook with prepared statement
    $updateStmt = $conn->prepare("UPDATE roombook SET Name = ?, Email = ?, Country = ?, Phone = ?, RoomType = ?, Bed = ?, NoofRoom = ?, Meal = ?, cin = ?, cout = ?, nodays = ?, updated_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("ssssssisssii", $EditName, $EditEmail, $EditCountry, $EditPhone, $EditRoomType, $EditBed, $EditNoofRoom, $EditMeal, $Editcin, $Editcout, $Editnoofday, $id);
    $updateStmt->execute();
    
    if ($updateStmt->affected_rows >= 0) {

        // Calculate price using function
        $priceInfo = calculateBookingPrice($EditRoomType, $EditBed, $EditMeal, $Editnoofday, $EditNoofRoom);
        
        // Update payment if exists
        $checkPayment = $conn->prepare("SELECT id FROM payment WHERE id = ?");
        $checkPayment->bind_param("i", $id);
        $checkPayment->execute();
        $paymentExists = $checkPayment->get_result()->num_rows > 0;
        
        if ($paymentExists) {
            $updatePayment = $conn->prepare("UPDATE payment SET Name = ?, Email = ?, RoomType = ?, Bed = ?, NoofRoom = ?, Meal = ?, cin = ?, cout = ?, noofdays = ?, roomtotal = ?, bedtotal = ?, mealtotal = ?, finaltotal = ? WHERE id = ?");
            $updatePayment->bind_param("ssssssssiddddi", $EditName, $EditEmail, $EditRoomType, $EditBed, $EditNoofRoom, $EditMeal, $Editcin, $Editcout, $Editnoofday, $priceInfo['roomtotal'], $priceInfo['bedtotal'], $priceInfo['mealtotal'], $priceInfo['finaltotal'], $id);
            $updatePayment->execute();
        }
        
        // Log activity
        $userId = getUserId();
        logActivity($conn, $userId, 'booking_updated', 'roombook', $id, 'Booking updated');
        
        header("Location: roombook.php");
        exit();
    } else {
        echo "<script>alert('Failed to update booking');</script>";
    }

}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- boot -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <!-- fontowesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- sweet alert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <link rel="stylesheet" href="./css/roombook.css">
    <style>
        #editpanel{
            position : fixed;
            z-index: 1000;
            height: 100%;
            width: 100%;
            display: flex;
            justify-content: center;
            /* align-items: center; */
            background-color: #00000079;
        }
        #editpanel .guestdetailpanelform{
            height: 620px;
            width: 1170px;
            background-color: #ccdff4;
            border-radius: 10px;  
            /* temp */
            position: relative;
            top: 20px;
            animation: guestinfoform .3s ease;
        }

    </style>
    <title>Document</title>
</head>
<body>
    <div id="editpanel">
        <form method="POST" class="guestdetailpanelform">
            <div class="head">
                <h3>EDIT RESERVATION</h3>
                <a href="./roombook.php"><i class="fa-solid fa-circle-xmark"></i></a>
            </div>
            <div class="middle">
                <div class="guestinfo">
                    <h4>Guest information</h4>
                    <input type="text" name="Name" placeholder="Enter Full name" value="<?php echo escapeOutput($Name); ?>">
                    <input type="email" name="Email" placeholder="Enter Email" value="<?php echo escapeOutput($Email); ?>">

                    <?php
                    $countries = array("Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe");
                    ?>

                    <select name="Country" class="selectinput">
						<option value selected >Select your country</option>
                        <?php
							foreach($countries as $key => $value):
							echo '<option value="'.$value.'">'.$value.'</option>';
                            //close your tags!!
							endforeach;
						?>
                    </select>
                    <input type="text" name="Phone" placeholder="Enter Phoneno"  value="<?php echo escapeOutput($Phone); ?>">
                </div>

                <div class="line"></div>

                <div class="reservationinfo">
                    <h4>Reservation information</h4>
                    <select name="RoomType" class="selectinput">
						<option value="">Type Of Room</option>
                        <option value="Superior Room" <?php echo $currentRoomType == 'Superior Room' ? 'selected' : ''; ?>>SUPERIOR ROOM</option>
                        <option value="Deluxe Room" <?php echo $currentRoomType == 'Deluxe Room' ? 'selected' : ''; ?>>DELUXE ROOM</option>
						<option value="Guest House" <?php echo $currentRoomType == 'Guest House' ? 'selected' : ''; ?>>GUEST HOUSE</option>
						<option value="Single Room" <?php echo $currentRoomType == 'Single Room' ? 'selected' : ''; ?>>SINGLE ROOM</option>
                    </select>
                    <select name="Bed" class="selectinput">
						<option value="">Bedding Type</option>
                        <option value="Single" <?php echo $currentBed == 'Single' ? 'selected' : ''; ?>>Single</option>
                        <option value="Double" <?php echo $currentBed == 'Double' ? 'selected' : ''; ?>>Double</option>
						<option value="Triple" <?php echo $currentBed == 'Triple' ? 'selected' : ''; ?>>Triple</option>
                        <option value="Quad" <?php echo $currentBed == 'Quad' ? 'selected' : ''; ?>>Quad</option>
						<option value="None" <?php echo $currentBed == 'None' ? 'selected' : ''; ?>>None</option>
                    </select>
                    <select name="NoofRoom" class="selectinput">
						<option value="">No of Room</option>
                        <option value="1" <?php echo $currentNoofRoom == '1' ? 'selected' : ''; ?>>1</option>
                    </select>
                    <select name="Meal" class="selectinput">
						<option value="">Meal</option>
                        <option value="Room only" <?php echo $currentMeal == 'Room only' ? 'selected' : ''; ?>>Room only</option>
                        <option value="Breakfast" <?php echo $currentMeal == 'Breakfast' ? 'selected' : ''; ?>>Breakfast</option>
						<option value="Half Board" <?php echo $currentMeal == 'Half Board' ? 'selected' : ''; ?>>Half Board</option>
						<option value="Full Board" <?php echo $currentMeal == 'Full Board' ? 'selected' : ''; ?>>Full Board</option>
					</select>
                    <div class="datesection">
                        <span>
                            <label for="cin"> Check-In</label>
                            <input name="cin" type ="date" value="<?php echo escapeOutput($cin); ?>">
                        </span>
                        <span>
                            <label for="cout"> Check-Out</label>
                            <input name="cout" type ="date" value="<?php echo escapeOutput($cout); ?>">
                        </span>
                    </div>
                </div>
            </div>
            <div class="footer">
                <button class="btn btn-success" name="guestdetailedit">Edit</button>
            </div>
        </form>
    </div>
</body>
</html>