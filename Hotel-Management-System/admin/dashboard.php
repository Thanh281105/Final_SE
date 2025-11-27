<?php
    session_start();
    include '../config.php';

    // roombook - tính tổng số phòng từ trường NoofRoom trong bảng payment với status = 'Paid'
    $roombooksql = "SELECT SUM(NoofRoom) as total_rooms FROM payment WHERE status = 'Paid'";
    $roombookre = mysqli_query($conn, $roombooksql);
    $roombookrow_result = mysqli_fetch_assoc($roombookre);
    $roombookrow = $roombookrow_result['total_rooms'] ?? 0; // Nếu không có kết quả thì = 0

    // staff
    $staffsql ="Select * from staff";
    $staffre = mysqli_query($conn, $staffsql);
    $staffrow = mysqli_num_rows($staffre);

    // room
    $roomsql ="Select * from room";
    $roomre = mysqli_query($conn, $roomsql);
    $roomrow = mysqli_num_rows($roomre);

    //roombook roomtype - tính tổng số phòng theo từng loại từ bảng payment với status = 'Paid'
    $chartroom1 = "SELECT SUM(NoofRoom) as total_rooms FROM payment WHERE RoomType='Superior Room' AND status = 'Paid'";
    $chartroom1re = mysqli_query($conn, $chartroom1);
    $chartroom1row_result = mysqli_fetch_assoc($chartroom1re);
    $chartroom1row = $chartroom1row_result['total_rooms'] ?? 0;

    $chartroom2 = "SELECT SUM(NoofRoom) as total_rooms FROM payment WHERE RoomType='Deluxe Room' AND status = 'Paid'";
    $chartroom2re = mysqli_query($conn, $chartroom2);
    $chartroom2row_result = mysqli_fetch_assoc($chartroom2re);
    $chartroom2row = $chartroom2row_result['total_rooms'] ?? 0;

    $chartroom3 = "SELECT SUM(NoofRoom) as total_rooms FROM payment WHERE RoomType='Guest House' AND status = 'Paid'";
    $chartroom3re = mysqli_query($conn, $chartroom3);
    $chartroom3row_result = mysqli_fetch_assoc($chartroom3re);
    $chartroom3row = $chartroom3row_result['total_rooms'] ?? 0;

    $chartroom4 = "SELECT SUM(NoofRoom) as total_rooms FROM payment WHERE RoomType='Single Room' AND status = 'Paid'";
    $chartroom4re = mysqli_query($conn, $chartroom4);
    $chartroom4row_result = mysqli_fetch_assoc($chartroom4re);
    $chartroom4row = $chartroom4row_result['total_rooms'] ?? 0;
?>
<!-- moriss profit -->
<?php 	
	$query = "SELECT * FROM payment WHERE status = 'Paid'"; 
	$result = mysqli_query($conn, $query);
	$chart_data = '';
	$tot = 0;
	while($row = mysqli_fetch_array($result))
	{
        $chart_data .= "{ date:'".$row["cout"]."', profit:".$row["finaltotal"] ."}, ";
        $tot = $tot + $row["finaltotal"]*1/1000;
	}

	$chart_data = substr($chart_data, 0, -2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css">
    <!-- chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- morish bar -->
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>

    <title>TDTU - Admin </title>
</head>
<body>
    <div class="databox">
        <div class="box roombookbox">
            <h2>Total Booked Room</h2>
            <h1><?php echo $roombookrow ?> / <?php echo $roomrow ?></h1>
        </div>

        <div class="box guestbox">
            <h2>Total Staff</h2>
            <h1><?php echo $staffrow ?></h1>
        </div>

        <div class="box profitbox">
            <h2>Profit</h2>
            <h1><?php echo $tot ?> <span>&#8363;</span></h1>
        </div>
    </div>

    <div class="chartbox">
        <div class="bookroomchart">
            <canvas id="bookroomchart"></canvas>
            <h3 style="text-align: center;margin:10px 0;">Booked Room</h3>
        </div>

        <div class="profitchart">
            <div id="profitchart"></div>
            <h3 style="text-align: center;margin:10px 0;">Profit</h3>
        </div>
    </div>
</body>




<script>
        const labels = [
          'Superior Room',
          'Deluxe Room',
          'Guest House',
          'Single Room',
        ];
      
        const data = {
          labels: labels,
          datasets: [{
            label: 'My First dataset',
            backgroundColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(153, 102, 255, 1)',
            ],
            borderColor: 'black',
            data: [<?php echo $chartroom1row ?>,<?php echo $chartroom2row ?>,<?php echo $chartroom3row ?>,<?php echo $chartroom4row ?>],
          }]
        };
  
        const doughnutchart = {
          type: 'doughnut',
          data: data,
          options: {}
        };
        
      const myChart = new Chart(
      document.getElementById('bookroomchart'),
      doughnutchart);
</script>

<script>
Morris.Bar({
 element : 'profitchart',
 data:[<?php echo $chart_data;?>],
 xkey:'date',
 ykeys:['profit'],
 labels:['Profit'],
 hideHover:'auto',
 stacked:true,
 barColors:[
  'rgba(153, 102, 255, 1)',
 ]
});
</script>

</html>