<?php
include 'config/condb.php';

header('Content-Type: application/json');

$searchQuery = "";
$params = [];

if (!empty($_POST['search'])) {
    $search = $_POST['search'];
    $searchQuery = "WHERE d.driver_name LIKE :search OR d.driver_lastname LIKE :search OR p.PROVINCE_NAME LIKE :search OR a.AMPHUR_NAME LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($_POST['province_filter'])) {
    $province_filter = $_POST['province_filter'];
    $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " d.driver_province = :province_filter";
    $params[':province_filter'] = $province_filter;
}

if (!empty($_POST['amphur_filter'])) {
    $amphur_filter = $_POST['amphur_filter'];
    $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " d.driver_amphur = :amphur_filter";
    $params[':amphur_filter'] = $amphur_filter;
}

try {
    $stmt = $conn->prepare("SELECT d.*, p.PROVINCE_NAME, a.AMPHUR_NAME
                            FROM driver d
                            LEFT JOIN province p ON d.driver_province = p.PROVINCE_ID
                            LEFT JOIN amphur a ON d.driver_amphur = a.AMPHUR_ID
                            $searchQuery");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'drivers' => $drivers,
        'totalRows' => count($drivers)
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage()]);
}
exit();
?>