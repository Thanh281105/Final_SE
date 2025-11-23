<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TDTU - Admin</title>
    <!-- boot -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <!-- fontowesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<!-- css for table and search bar -->
	<link rel="stylesheet" href="css/roombook.css">

</head>
<body>
	<div class="searchsection">
        <input type="text" name="search_bar" id="search_bar" placeholder="search..." onkeyup="searchFun()">
    </div>

    <div class="roombooktable table-responsive-xl">
        <?php
            $stmt = $conn->prepare("SELECT * FROM payment ORDER BY created_at DESC");
            $stmt->execute();
            $result = $stmt->get_result();
        ?>
        <table class="table table-bordered" id="table-data">
            <thead>
                <tr>
                    <th scope="col">Id</th>
                    <th scope="col">Name</th>
                    <th scope="col">Room Type</th>
                    <th scope="col">Bed Type</th>
                    <th scope="col">Check In</th>
                    <th scope="col">Check Out</th>
					<th scope="col">No of Day</th>
                    <th scope="col">No of Room</th>
					<th scope="col">Meal Type</th>
                    <th scope="col">Room Rent</th>
                    <th scope="col">Bed Rent</th>
                    <th scope="col">Meals</th>
					<th scope="col">Total Bill</th>
                    <th scope="col">Method</th>
                    <th scope="col">Status</th>
                    <th scope="col">Type</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>

            <tbody>
            <?php
            while ($res = $result->fetch_assoc()) {
            ?>
                <tr>
                    <td><?php echo escapeOutput($res['id']); ?></td>
                    <td><?php echo escapeOutput($res['Name']); ?></td>
                    <td><?php echo escapeOutput($res['RoomType']); ?></td>
                    <td><?php echo escapeOutput($res['Bed']); ?></td>
					<td><?php echo escapeOutput($res['cin']); ?></td>
                    <td><?php echo escapeOutput($res['cout']); ?></td>
					<td><?php echo escapeOutput($res['noofdays']); ?></td>
                    <td><?php echo escapeOutput($res['NoofRoom']); ?></td>
                    <td><?php echo escapeOutput($res['meal']); ?></td>
                    <td>₹<?php echo number_format($res['roomtotal'], 2); ?></td>
					<td>₹<?php echo number_format($res['bedtotal'], 2); ?></td>
					<td>₹<?php echo number_format($res['mealtotal'], 2); ?></td>
					<td><strong>₹<?php echo number_format($res['finaltotal'], 2); ?></strong></td>
                    <td>
                        <?php if ($res['method']): ?>
                            <span class="badge bg-info"><?php echo escapeOutput($res['method']); ?></span>
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?php 
                            echo $res['status'] == 'Success' ? 'success' : 
                                ($res['status'] == 'Failed' ? 'danger' : 
                                ($res['status'] == 'Refunded' ? 'warning' : 'secondary')); 
                        ?>">
                            <?php echo escapeOutput($res['status'] ?? 'Pending'); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($res['type']): ?>
                            <span class="badge bg-primary"><?php echo escapeOutput($res['type']); ?></span>
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td class="action">
                        <a href="invoiceprint.php?id=<?php echo $res['id']; ?>"><button class="btn btn-primary btn-sm"><i class="fa-solid fa-print"></i> Print</button></a>
						<a href="paymantdelete.php?id=<?php echo $res['id']; ?>"><button class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button></a>
                    </td>
                </tr>
            <?php
            }
            ?>
            </tbody>
        </table>
    </div>
</body>

<script>
    //search bar logic using js
    const searchFun = () =>{
        let filter = document.getElementById('search_bar').value.toUpperCase();

        let myTable = document.getElementById("table-data");

        let tr = myTable.getElementsByTagName('tr');

        for(var i = 0; i< tr.length;i++){
            let td = tr[i].getElementsByTagName('td')[1];

            if(td){
                let textvalue = td.textContent || td.innerHTML;

                if(textvalue.toUpperCase().indexOf(filter) > -1){
                    tr[i].style.display = "";
                }else{
                    tr[i].style.display = "none";
                }
            }
        }

    }

</script>

</html>