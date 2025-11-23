<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : '';
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

$reportData = array();
$chartData = '';

if (isset($_GET['generate']) && !empty($reportType)) {
    if ($reportType == 'revenue') {
        // Revenue Report
        $stmt = $conn->prepare("SELECT DATE(created_at) as date, SUM(finaltotal) as total 
                               FROM payment 
                               WHERE status = 'Success' AND created_at BETWEEN ? AND ? 
                               GROUP BY DATE(created_at) 
                               ORDER BY date");
        $stmt->bind_param("ss", $fromDate, $toDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $reportData[] = $row;
            $chartData .= "{ date:'" . $row['date'] . "', revenue:" . $row['total'] . "}, ";
        }
        
        $chartData = substr($chartData, 0, -2);
        
    } elseif ($reportType == 'occupancy') {
        // Occupancy Report
        $stmt = $conn->prepare("SELECT DATE(cin) as date, COUNT(*) as bookings, 
                               (SELECT COUNT(*) FROM room) as total_rooms
                               FROM roombook 
                               WHERE cin BETWEEN ? AND ? AND status IN ('Confirmed', 'Checked-in', 'Checked-out')
                               GROUP BY DATE(cin) 
                               ORDER BY date");
        $stmt->bind_param("ss", $fromDate, $toDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $occupancy = $row['total_rooms'] > 0 ? ($row['bookings'] / $row['total_rooms']) * 100 : 0;
            $reportData[] = array(
                'date' => $row['date'],
                'bookings' => $row['bookings'],
                'total_rooms' => $row['total_rooms'],
                'occupancy' => round($occupancy, 2)
            );
            $chartData .= "{ date:'" . $row['date'] . "', occupancy:" . round($occupancy, 2) . "}, ";
        }
        
        $chartData = substr($chartData, 0, -2);
        
    } elseif ($reportType == 'booking_volume') {
        // Booking Volume Report
        $stmt = $conn->prepare("SELECT DATE(created_at) as date, COUNT(*) as count 
                               FROM roombook 
                               WHERE created_at BETWEEN ? AND ? 
                               GROUP BY DATE(created_at) 
                               ORDER BY date");
        $stmt->bind_param("ss", $fromDate, $toDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $reportData[] = $row;
            $chartData .= "{ date:'" . $row['date'] . "', volume:" . $row['count'] . "}, ";
        }
        
        $chartData = substr($chartData, 0, -2);
    }
}

// Export functionality
if (isset($_POST['export_excel'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"report_" . $reportType . "_" . date('Ymd') . ".xls\"");
    
    echo "<table border='1'>";
    if ($reportType == 'revenue') {
        echo "<tr><th>Date</th><th>Revenue (₹)</th></tr>";
        foreach ($reportData as $row) {
            echo "<tr><td>" . $row['date'] . "</td><td>" . number_format($row['total'], 2) . "</td></tr>";
        }
    } elseif ($reportType == 'occupancy') {
        echo "<tr><th>Date</th><th>Bookings</th><th>Total Rooms</th><th>Occupancy %</th></tr>";
        foreach ($reportData as $row) {
            echo "<tr><td>" . $row['date'] . "</td><td>" . $row['bookings'] . "</td><td>" . $row['total_rooms'] . "</td><td>" . $row['occupancy'] . "%</td></tr>";
        }
    } elseif ($reportType == 'booking_volume') {
        echo "<tr><th>Date</th><th>Bookings</th></tr>";
        foreach ($reportData as $row) {
            echo "<tr><td>" . $row['date'] . "</td><td>" . $row['count'] . "</td></tr>";
        }
    }
    echo "</table>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - TDTU Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
</head>
<body>
    <div class="container-fluid p-4">
        <h2 class="mb-4">Reports</h2>
        
        <!-- Report Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Report Type</label>
                        <select name="report_type" class="form-control" required>
                            <option value="">Choose...</option>
                            <option value="revenue" <?php echo $reportType == 'revenue' ? 'selected' : ''; ?>>Revenue</option>
                            <option value="occupancy" <?php echo $reportType == 'occupancy' ? 'selected' : ''; ?>>Occupancy</option>
                            <option value="booking_volume" <?php echo $reportType == 'booking_volume' ? 'selected' : ''; ?>>Booking Volume</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="<?php echo escapeOutput($fromDate); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="<?php echo escapeOutput($toDate); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" name="generate" class="btn btn-primary w-100">Generate Report</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (!empty($reportData)): ?>
            <!-- Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4><?php echo ucfirst(str_replace('_', ' ', $reportType)); ?> Report</h4>
                </div>
                <div class="card-body">
                    <div id="reportChart" style="height: 300px;"></div>
                </div>
            </div>
            
            <!-- Data Table -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between">
                    <h4>Report Data</h4>
                    <div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="report_type" value="<?php echo $reportType; ?>">
                            <input type="hidden" name="from_date" value="<?php echo $fromDate; ?>">
                            <input type="hidden" name="to_date" value="<?php echo $toDate; ?>">
                            <button type="submit" name="export_excel" class="btn btn-success">Export Excel</button>
                        </form>
                        <button onclick="window.print()" class="btn btn-primary">Print</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <?php if ($reportType == 'revenue'): ?>
                                    <tr>
                                        <th>Date</th>
                                        <th>Revenue (₹)</th>
                                    </tr>
                                <?php elseif ($reportType == 'occupancy'): ?>
                                    <tr>
                                        <th>Date</th>
                                        <th>Bookings</th>
                                        <th>Total Rooms</th>
                                        <th>Occupancy %</th>
                                    </tr>
                                <?php elseif ($reportType == 'booking_volume'): ?>
                                    <tr>
                                        <th>Date</th>
                                        <th>Number of Bookings</th>
                                    </tr>
                                <?php endif; ?>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $row): ?>
                                    <tr>
                                        <td><?php echo escapeOutput($row['date']); ?></td>
                                        <?php if ($reportType == 'revenue'): ?>
                                            <td>₹<?php echo number_format($row['total'], 2); ?></td>
                                        <?php elseif ($reportType == 'occupancy'): ?>
                                            <td><?php echo escapeOutput($row['bookings']); ?></td>
                                            <td><?php echo escapeOutput($row['total_rooms']); ?></td>
                                            <td><?php echo escapeOutput($row['occupancy']); ?>%</td>
                                        <?php elseif ($reportType == 'booking_volume'): ?>
                                            <td><?php echo escapeOutput($row['count']); ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Please select report type and date range, then click Generate Report.</div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($chartData)): ?>
        <script>
            Morris.Line({
                element: 'reportChart',
                data: [<?php echo $chartData; ?>],
                xkey: 'date',
                ykeys: ['<?php echo $reportType == 'revenue' ? 'revenue' : ($reportType == 'occupancy' ? 'occupancy' : 'volume'); ?>'],
                labels: ['<?php echo ucfirst(str_replace('_', ' ', $reportType)); ?>'],
                lineColors: ['#007bff']
            });
        </script>
    <?php endif; ?>
</body>
</html>

