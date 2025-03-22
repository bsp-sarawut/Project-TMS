<?php
require_once 'config/condb.php';

$date = $_GET['date'] ?? null;
$province_id = $_GET['province_id'] ?? null;
$amphur_id = $_GET['amphur_id'] ?? null;

$sql = "
    SELECT c.car_id, c.car_license, c.car_brand, c.car_seat, 
           d.driver_name, d.driver_lastname, 
           p.PROVINCE_NAME AS driver_province_name, 
           a.AMPHUR_NAME AS driver_amphur_name
    FROM car c
    LEFT JOIN driver d ON c.driver_id = d.driver_id
    LEFT JOIN province p ON d.driver_province = p.PROVINCE_ID
    LEFT JOIN amphur a ON d.driver_amphur = a.AMPHUR_ID
    WHERE c.car_id NOT IN (
        SELECT car_id FROM queue WHERE queue_date = :date
    )";

$params = [':date' => $date];

// เพิ่มเงื่อนไขกรองตามจังหวัดและอำเภอ
if ($province_id) {
    $sql .= " AND d.driver_province = :province_id";
    $params[':province_id'] = $province_id;
}
if ($amphur_id) {
    $sql .= " AND d.driver_amphur = :amphur_id";
    $params[':amphur_id'] = $amphur_id;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($cars);
?>