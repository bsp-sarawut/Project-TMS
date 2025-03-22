<?php
require_once 'config/condb.php';

// รับค่าจาก GET
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

// ✅ กรองวันที่ (รองรับวันที่เดียวและช่วงวันที่)
if ($date_picker) {
    $dates = explode(" to ", $date_picker);
    if (count($dates) === 2) {
        $startDay = date('d', strtotime($dates[0]));
        $endDay = date('d', strtotime($dates[1]));
        $sql .= " AND (FIND_IN_SET(:start_day, ts.available_dates) OR FIND_IN_SET(:end_day, ts.available_dates))";
        $params[':start_day'] = $startDay;
        $params[':end_day'] = $endDay;
    } else {
        $day = date('d', strtotime($date_picker));
        $sql .= " AND FIND_IN_SET(:day, ts.available_dates)";
        $params[':day'] = $day;
    }
}

// ✅ กรองจังหวัด
if ($province_id) {
    $sql .= " AND r.province = :province_id";
    $params[':province_id'] = $province_id;
}

// ✅ กรองอำเภอ
if ($amphur_id) {
    $sql .= " AND r.amphur = :amphur_id";
    $params[':amphur_id'] = $amphur_id;
}

// ✅ กรองจุดขึ้นรถ (ใช้ LIKE สำหรับการค้นหาหลายคำ)
if ($location) {
    $sql .= " AND r.location LIKE :location";
    $params[':location'] = "%" . $location . "%"; // ใช้ % เพื่อรองรับการค้นหาหลายคำ
}

// 🔎 ดึงข้อมูล
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ส่งข้อมูล JSON
header('Content-Type: application/json');
echo json_encode($registrations);
?>
