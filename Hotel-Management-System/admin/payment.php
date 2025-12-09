<?php
    session_start();
    include '../config.php';
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
    <title>TDTU - Payment Management</title>
    <style>
        .status-pending { background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 5px; }
        .status-paid { background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 5px; }
        .confirm-btn { background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 5px; text-decoration: none; display: inline-block; }
        .confirm-btn:hover { background: #218838; color: white; }
    </style>
</head>
<body>
    <div class="searchsection">
        <input type="text" name="search_bar" id="search_bar" placeholder="Search..." onkeyup="searchFun()">
    </div>

    <div class="roombooktable table-responsive"> <!-- Add responsive for scroll -->
        <?php
        $paymanttablesql = "SELECT * FROM payment";
        $paymantresult = mysqli_query($conn, $paymanttablesql);
        ?>
        <table class="table table-bordered" id="table-data">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Room Type</th>
                    <th scope="col">Bed Type</th>
                    <th scope="col">Check In</th>
                    <th scope="col">Check Out</th> 
                    <th scope="col">No of Days</th> 
                    <th scope="col">No of Rooms</th>
                    <th scope="col">Meal Type</th>
                    <th scope="col">Room Total</th>
                    <th scope="col">Bed Total</th> 
                    <th scope="col">Meal Total</th> 
                    <th scope="col">Final Total</th>
                    <th scope="col">Status</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>

            <tbody>
            <?php while ($res = mysqli_fetch_array($paymantresult)): ?>
                <tr>
                    <td><?php echo $res['id'] ?></td>
                    <td><?php echo $res['Name'] ?></td>
                    <td><?php echo $res['RoomType'] ?></td>
                    <td><?php echo $res['Bed'] ?></td>
                    <td><?php echo $res['cin'] ?></td>
                    <td><?php echo $res['cout'] ?></td>
                    <td><?php echo $res['noofdays'] ?></td>
                    <td><?php echo $res['NoofRoom'] ?></td>
                    <td><?php echo $res['meal'] ?></td>
                    <td><?php echo $res['roomtotal'] ?></td>
                    <td><?php echo $res['bedtotal'] ?></td>
                    <td><?php echo $res['mealtotal'] ?></td>
                    <td><?php echo $res['finaltotal'] ?></td>
                    <td>
                        <?php 
                        if ($res['status'] === 'Paid'): 
                            echo '<span class="status-paid">Paid</span>';
                        else: 
                            echo '<span class="status-pending">Pending</span>';
                        endif; 
                        ?>
                    </td>
                    <td class="action">
                        <a href="invoiceprint.php?id=<?php echo $res['id']?>"><button class="btn btn-primary"><i class="fa-solid fa-print"></i> Print</button></a>
                        <?php if ($res['status'] !== 'Paid'): ?>
                            <a href="admin_payment_confirm.php?id=<?php echo $res['id']?>" onclick="return confirm('Confirm this payment?')"><button class="confirm-btn">Confirm</button></a>
                        <?php endif; ?>
                        <a href="paymantdelete.php?id=<?php echo $res['id']?>" onclick="return confirm('Delete this payment?')"><button class="btn btn-danger">Delete</button></a>
                    </td>
                </tr>
            <?php endwhile; ?>
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