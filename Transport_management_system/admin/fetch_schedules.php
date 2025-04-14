<?php
include 'config/condb.php';

header('Content-Type: application/json');

// ฟังก์ชันแปลงเลขเดือนเป็นชื่อเดือนภาษาไทย
function getThaiMonth($month) {
    $thaiMonths = [
        1 => "มกราคม", 2 => "กุมภาพันธ์", 3 => "มีนาคม", 4 => "เมษายน",
        5 => "พฤษภาคม", 6 => "มิถุนายน", 7 => "กรกฎาคม", 8 => "สิงหาคม",
        9 => "กันยายน", 10 => "ตุลาคม", 11 => "พฤศจิกายน", 12 => "ธันวาคม"
    ];
    return $thaiMonths[$month];
}

$searchQuery = "";
$params = [];

if (!empty($_POST['search'])) {
    $search = $_POST['search'];
    $searchQuery = "WHERE (year LIKE :search OR month = :month_search OR id LIKE :id_search)";
    $params[':search'] = '%' . $search . '%';
    $params[':id_search'] = '%' . $search . '%';
    $month_search = array_search($search, array_map('getThaiMonth', range(1, 12)));
    $params[':month_search'] = $month_search ? $month_search : 0;
}

if (!empty($_POST['month_filter'])) {
    $month_filter = $_POST['month_filter'];
    $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " month = :month_filter";
    $params[':month_filter'] = $month_filter;
}

if (!empty($_POST['year_filter'])) {
    $year_filter = $_POST['year_filter'];
    $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " year = :year_filter";
    $params[':year_filter'] = $year_filter;
}

try {
    $stmt = $conn->prepare("SELECT * FROM transport_schedule $searchQuery ORDER BY year DESC, month DESC");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // เพิ่มชื่อเดือนภาษาไทยในผลลัพธ์ และตรวจสอบ available_dates
    foreach ($schedules as &$schedule) {
        $schedule['month_name'] = getThaiMonth($schedule['month']);
        // ตรวจสอบว่า available_dates เป็นสตริงหรือไม่ ถ้าไม่ใช่ให้ตั้งเป็นค่าว่าง
        $schedule['available_dates'] = isset($schedule['available_dates']) && is_string($schedule['available_dates'])
            ? $schedule['available_dates']
            : '';
    }

    echo json_encode([
        'schedules' => $schedules,
        'totalRows' => count($schedules)
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage()]);
}
exit();
?>