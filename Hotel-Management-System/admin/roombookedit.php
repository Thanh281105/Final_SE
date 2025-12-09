<?php
include '../config.php';

// Fetch room data
$id = $_GET['id'];

$sql = "SELECT * FROM roombook WHERE id = '$id'";
$re = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($re);
$Name = $row['Name'];
$Email = $row['Email'];
$Country = $row['Country'];
$Phone = $row['Phone'];
$RoomType = $row['RoomType'];
$Bed = $row['Bed'];
$NoofRoom = $row['NoofRoom'];
$Meal = $row['Meal'];
$cin = $row['cin'];
$cout = $row['cout'];
$nodays = $row['nodays'];
$stat = $row['stat'];

if (isset($_POST['guestdetailedit'])) {
    $EditName = mysqli_real_escape_string($conn, $_POST['Name']);
    $EditEmail = mysqli_real_escape_string($conn, $_POST['Email']);
    $EditCountry = mysqli_real_escape_string($conn, $_POST['Country']);
    $EditPhone = mysqli_real_escape_string($conn, $_POST['Phone']);
    $EditRoomType = mysqli_real_escape_string($conn, $_POST['RoomType']);
    $EditBed = mysqli_real_escape_string($conn, $_POST['Bed']);
    $EditNoofRoom = mysqli_real_escape_string($conn, $_POST['NoofRoom']);
    $EditMeal = mysqli_real_escape_string($conn, $_POST['Meal']);
    $Editcin = $_POST['cin'];
    $Editcout = $_POST['cout'];

    if (strtotime($Editcin) >= strtotime($Editcout)) {
        echo "<script>swal({title:'Check-out must be after check-in',icon:'error'});</script>";
    } elseif (strtotime($Editcin) < strtotime('2025-11-26')) {
        echo "<script>swal({title:'Check-in cannot be in the past',icon:'error'});</script>";
    } else {
        $Editnodays = (strtotime($Editcout) - strtotime($Editcin)) / (60 * 60 * 24); // Manual calc for safety

        $sql = "UPDATE roombook SET Name = '$EditName', Email = '$EditEmail', Country='$EditCountry', Phone='$EditPhone', 
                RoomType='$EditRoomType', Bed='$EditBed', NoofRoom='$EditNoofRoom', Meal='$EditMeal', cin='$Editcin', cout='$Editcout', 
                nodays = $Editnodays WHERE id = '$id'";

        $result = mysqli_query($conn, $sql);

        if ($result) {
            // Calculate totals
            $type_of_room = 0;
            if ($EditRoomType == "Superior Room") $type_of_room = 3000;
            else if ($EditRoomType == "Deluxe Room") $type_of_room = 2000;
            else if ($EditRoomType == "Guest House") $type_of_room = 1500;
            else if ($EditRoomType == "Single Room") $type_of_room = 1000;

            $type_of_bed = 0;
            if ($EditBed == "Single") $type_of_bed = $type_of_room * 1 / 100;
            else if ($EditBed == "Double") $type_of_bed = $type_of_room * 2 / 100;
            else if ($EditBed == "Triple") $type_of_bed = $type_of_room * 3 / 100;
            else if ($EditBed == "Quad") $type_of_bed = $type_of_room * 4 / 100;
            else if ($EditBed == "None") $type_of_bed = 0;

            $type_of_meal = 0;
            if ($EditMeal == "Room only") $type_of_meal = 0;
            else if ($EditMeal == "Breakfast") $type_of_meal = $type_of_bed * 2;
            else if ($EditMeal == "Half Board") $type_of_meal = $type_of_bed * 3;
            else if ($EditMeal == "Full Board") $type_of_meal = $type_of_bed * 4;

            $editttot = $type_of_room * $Editnodays * (int)$EditNoofRoom;
            $editmepr = $type_of_meal * $Editnodays;
            $editbtot = $type_of_bed * $Editnodays;
            $editfintot = $editttot + $editmepr + $editbtot;

            // Update payment
            $psql = "UPDATE payment SET Name = '$EditName', Email = '$EditEmail', RoomType='$EditRoomType', Bed='$EditBed', 
                    NoofRoom='$EditNoofRoom', meal='$EditMeal', cin='$Editcin', cout='$Editcout', noofdays = '$Editnodays', 
                    roomtotal = '$editttot', bedtotal = '$editbtot', mealtotal = '$editmepr', finaltotal = '$editfintot' WHERE id = '$id'";

            $paymentresult = mysqli_query($conn, $psql);

            if ($paymentresult) {
                echo "<script>swal({title:'Edit successful!',icon:'success'}).then(() => { window.location.href = 'roombook.php'; });</script>";
            } else {
                echo "<script>swal({title:'Payment update failed',icon:'error'});</script>";
            }
        } else {
            echo "<script>swal({title:'Update failed',icon:'error'});</script>";
        }
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
                <i class="fa-solid fa-circle-xmark" onclick="window.location.href='roombook.php'"></i> 
            </div>
            <div class="middle">
                <div class="guestinfo">
                    <h4>Guest information</h4>
                    <input type="text" name="Name" placeholder="Enter Full name" value="<?php echo $Name ?>">
                    <input type="email" name="Email" placeholder="Enter Email" value="<?php echo $Email ?>">

                    <?php
                    $countries = array("Vietnam", "China", "Japan", "Korea", "Thailand", "Laos", "Campuchia", "Singapore", "Indonesia", "Philippines");
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
                    <input type="text" name="Phone" placeholder="Enter Phoneno"  value="<?php echo $Phone ?>">
                </div>

                <div class="line"></div>

                <div class="reservationinfo">
                    <h4>Reservation information</h4>
                    <select name="RoomType" class="selectinput">
						<option value selected >Type Of Room</option>
                        <option value="Superior Room">SUPERIOR ROOM</option>
                        <option value="Deluxe Room">DELUXE ROOM</option>
						<option value="Guest House">GUEST HOUSE</option>
						<option value="Single Room">SINGLE ROOM</option>
                    </select>
                    <select name="Bed" class="selectinput">
						<option value selected >Bedding Type</option>
                        <option value="Single">Single</option>
                        <option value="Double">Double</option>
						<option value="Triple">Triple</option>
                        <option value="Quad">Quad</option>
						<option value="None">None</option>
                    </select>
                    <select name="NoofRoom" class="selectinput">
						<option value selected >No of Room</option>
                        <option value="1">1</option>
                        <!-- <option value="1">2</option>
                        <option value="1">3</option> -->
                    </select>
                    <select name="Meal" class="selectinput">
						<option value selected >Meal</option>
                        <option value="Room only">Room only</option>
                        <option value="Breakfast">Breakfast</option>
						<option value="Half Board">Half Board</option>
						<option value="Full Board">Full Board</option>
					</select>
                    <div class="datesection">
                        <span>
                            <label for="cin"> Check-In</label>
                            <input name="cin" type ="date" value="<?php echo $cin ?>">
                        </span>
                        <span>
                            <label for="cin"> Check-Out</label>
                            <input name="cout" type ="date" value="<?php echo $cout ?>">
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