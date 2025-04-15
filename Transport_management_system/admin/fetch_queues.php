<?php
require_once 'config/condb.php';

$search = $_POST['search'] ?? '';
$queue_date = $_POST['queue_date'] ?? '';
$province_id = $_POST['province_id'] ?? '';
$amphur_id = $_POST['amphur_id'] ?? '';
$location = $_POST['location'] ?? '';

$where = [];
$params = [];
if (!empty($queue_date)) {
    $where[] = "q.queue_date = :queue_date";
    $params[':queue_date'] = $queue_date;
}
if (!empty($province_id)) {
    $where[] = "q.province_id = :province_id";
    $params[':province_id'] = $province_id;
}
if (!empty($amphur_id)) {
    $where[] = "q.amphur_id = :amphur_id";
    $params[':amphur_id'] = $amphur_id;
}
if (!empty($location)) {
    $where[] = "q.location = :location";
    $params[':location'] = $location;
}
if (!empty($search)) {
    $where[] = "(c.car_license LIKE :search OR s.stu_name LIKE :search OR s.stu_lastname LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql = "
    SELECT 
        q.queue_id, 
        p.PROVINCE_NAME AS province_name, 
        a.AMPHUR_NAME AS amphur_name, 
        q.location, 
        c.car_license, 
        c.car_brand, 
        q.created_at, 
        q.year, 
        q.status_car, 
        q.queue_date
    FROM queue q
    LEFT JOIN province p ON q.province_id = p.PROVINCE_ID
    LEFT JOIN amphur a ON q.amphur_id = a.AMPHUR_ID
    LEFT JOIN car c ON q.car_id = c.car_id
    LEFT JOIN queue_student qs ON q.queue_id = qs.queue_id
    LEFT JOIN students s ON qs.student_id = s.stu_ID
";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " GROUP BY q.queue_id ORDER BY q.queue_date DESC, q.created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $queues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'queues' => $queues,
        'totalRows' => count($queues)
    ];
} catch (Exception $e) {
    $response = ['error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response);
?>