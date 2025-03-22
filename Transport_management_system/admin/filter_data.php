<?php
require_once 'config/condb.php';

$date_picker = $_GET['date_picker'] ?? null;
$province_id = $_GET['province_id'] ?? null;
$amphur_id = $_GET['amphur_id'] ?? null;
$location = $_GET['location'] ?? null;

$sql = "
    SELECT tr.transport_schedule_id, ts.available_dates, ts.month, 
        tr.*, s.stu_name, s.stu_lastname, s.stu_ID, 
        p.PROVINCE_NAME AS province_name, a.AMPHUR_NAME AS amphur_name, r.location 
    FROM transport_registration tr 
    LEFT JOIN students s ON tr.stu_username = s.stu_username
    LEFT JOIN routes r ON tr.route_id = r.route_ID
    LEFT JOIN province p ON r.province = p.PROVINCE_ID
    LEFT JOIN amphur a ON r.amphur = a.AMPHUR_ID
    LEFT JOIN transport_schedule ts ON tr.transport_schedule_id = ts.id  
    WHERE 1=1
";

$params = [];

if ($date_picker) {
    $dates = explode(" to ", $date_picker);
    $date = $dates[0]; // ใช้เฉพาะวันแรก
    $day = date('d', strtotime($date)); // เช่น "23"
    $sql .= " AND FIND_IN_SET(:day, REPLACE(ts.available_dates, ' ', ''))
              AND (s.stu_ID IS NULL OR s.stu_ID NOT IN (
                  SELECT qs.student_id 
                  FROM queue_student qs 
                  JOIN queue q ON qs.queue_id = q.queue_id 
                  WHERE q.queue_date = :queue_date
              ))";
    $params[':day'] = $day;
    $params[':queue_date'] = $date;
}

if ($province_id) {
    $sql .= " AND r.province = :province_id";
    $params[':province_id'] = $province_id;
}

if ($amphur_id) {
    $sql .= " AND r.amphur = :amphur_id";
    $params[':amphur_id'] = $amphur_id;
}

if ($location && !empty(trim($location))) {
    $sql .= " AND r.location LIKE :location";
    $params[':location'] = "%" . $location . "%";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($registrations);
?>