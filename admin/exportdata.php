<?php
session_start();
include '../config.php';
include '../functions.php';
requireAdmin();

$stmt = $conn->prepare("SELECT * FROM roombook ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$roombook_record = array();

while ($rows = $result->fetch_assoc()) {
    $roombook_record[] = $rows;
}

if (isset($_POST["exportexcel"])) {
    $filename = "bluebird_roombook_data_" . date('Ymd') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    $show_coloumn = false;
    if (!empty($roombook_record)) {
        foreach ($roombook_record as $record) {
            if (!$show_coloumn) {
                echo implode("\t", array_keys($record)) . "\n";
                $show_coloumn = true;
            }
            echo implode("\t", array_values($record)) . "\n";
        }
    }
    
    $userId = getUserId();
    logActivity($conn, $userId, 'data_exported', 'roombook', 0, "Roombook data exported to Excel");
    exit;
}
?>
