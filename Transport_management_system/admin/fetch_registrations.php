<?php
require_once 'config/condb.php';

header('Content-Type: application/json');

$sql = "
    SELECT tr.*, r.location, p.PROVINCE_NAME, a.AMPHUR_NAME, s.stu_name, s.stu_lastname, ts.num_of_days AS schedule_num_of_days, ts.available_dates
    FROM transport_registration tr
    LEFT JOIN routes r ON tr.route_id = r.route_ID
    LEFT JOIN province p ON r.province = p.PROVINCE_ID
    LEFT JOIN amphur a ON r.amphur = a.AMPHUR_ID
    LEFT JOIN students s ON tr.stu_username = s.stu_username
    LEFT JOIN transport_schedule ts ON tr.transport_schedule_id = ts.id
    WHERE 1=1
";

$params = [];

if (!empty($_POST['search'])) {
    $search = "%" . $_POST['search'] . "%";
    $sql .= " AND (s.stu_name LIKE :search 
                OR s.stu_lastname LIKE :search 
                OR r.location LIKE :search
                OR p.PROVINCE_NAME LIKE :search
                OR a.AMPHUR_NAME LIKE :search
                OR tr.created_at LIKE :search
                OR ts.num_of_days LIKE :search
                OR tr.payment_status LIKE :search)";
    $params[':search'] = $search;
}

if (!empty($_POST['payment_status'])) {
    $sql .= " AND tr.payment_status = :payment_status";
    $params[':payment_status'] = $_POST['payment_status'];
}

if (!empty($_POST['province'])) {
    $sql .= " AND r.province = :province";
    $params[':province'] = $_POST['province'];
}

if (!empty($_POST['amphur'])) {
    $sql .= " AND r.amphur = :amphur";
    $params[':amphur'] = $_POST['amphur'];
}

if (!empty($_POST['location'])) {
    $sql .= " AND r.location = :location";
    $params[':location'] = $_POST['location'];
}

$sql .= " ORDER BY tr.created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'registrations' => $registrations,
        'totalRows' => count($registrations)
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage()]);
}
exit();
?>