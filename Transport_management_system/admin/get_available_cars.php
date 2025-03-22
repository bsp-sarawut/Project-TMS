<?php
require_once 'config/condb.php';

$date = $_GET['date'] ?? null;
$sql = "SELECT * FROM car WHERE car_id NOT IN (
    SELECT car_id FROM queue WHERE queue_date = :date
)";
$stmt = $conn->prepare($sql);
$stmt->execute([':date' => $date]);
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($cars);
?>