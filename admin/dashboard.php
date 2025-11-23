<?php
session_start();
include '../config.php';
include '../functions.php';

// roombook
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM roombook");
$stmt->execute();
$result = $stmt->get_result();
$roombookrow = $result->fetch_assoc()['count'];

// staff
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM staff");
$stmt->execute();
$result = $stmt->get_result();
$staffrow = $result->fetch_assoc()['count'];

// room
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM room");
$stmt->execute();
$result = $stmt->get_result();
$roomrow = $result->fetch_assoc()['count'];

//roombook roomtype
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM roombook WHERE RoomType='Superior Room'");
$stmt->execute();
$result = $stmt->get_result();
$chartroom1row = $result->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM roombook WHERE RoomType='Deluxe Room'");
$stmt->execute();
$result = $stmt->get_result();
$chartroom2row = $result->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM roombook WHERE RoomType='Guest House'");
$stmt->execute();
$result = $stmt->get_result();
$chartroom3row = $result->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM roombook WHERE RoomType='Single Room'");
$stmt->execute();
$result = $stmt->get_result();
$chartroom4row = $result->fetch_assoc()['count'];

// Profit chart
$query = $conn->prepare("SELECT * FROM payment WHERE status = 'Success'");
$query->execute();
$result = $query->get_result();
$chart_data = '';
$tot = 0;
while($row = $result->fetch_assoc()) {
    $chart_data .= "{ date:'".escapeOutput($row["cout"])."', profit:".($row["finaltotal"]*10/100)."}, ";
    $tot = $tot + $row["finaltotal"]*10/100;
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
          <h2>Total Booked Room</h1>  
          <h1><?php echo escapeOutput($roombookrow); ?> / <?php echo escapeOutput($roomrow); ?></h1>
        </div>
        <div class="box guestbox">
        <h2>Total Staff</h1>  
          <h1><?php echo escapeOutput($staffrow); ?></h1>
        </div>
        <div class="box profitbox">
        <h2>Profit</h1>  
          <h1><?php echo number_format($tot, 2); ?> <span>&#8377</span></h1>
        </div>
    </div>
    <div class="chartbox">
        <div class="bookroomchart">
            <canvas id="bookroomchart"></canvas>
            <h3 style="text-align: center;margin:10px 0;">Booked Room</h3>
        </div>
        <div class="profitchart" >
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