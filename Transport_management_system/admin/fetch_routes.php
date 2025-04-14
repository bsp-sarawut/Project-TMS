<?php
include 'config/condb.php';

// ปิดการแสดงข้อผิดพลาดในหน้าเว็บ (เพื่อป้องกันการรบกวน JSON)
ini_set('display_errors', 0);
error_reporting(0);

// ตั้งค่า Content-Type เป็น JSON
header('Content-Type: application/json; charset=UTF-8');

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
$param_types = [];

if (!empty($_POST['search'])) {
    $where[] = "(p.PROVINCE_NAME LIKE ? OR a.AMPHUR_NAME LIKE ? OR r.location LIKE ?)";
    $search_term = "%" . $_POST['search'] . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types[] = PDO::PARAM_STR;
    $param_types[] = PDO::PARAM_STR;
    $param_types[] = PDO::PARAM_STR;
}
if (!empty($_POST['province_filter'])) {
    $where[] = "r.province = ?";
    $params[] = $_POST['province_filter'];
    $param_types[] = PDO::PARAM_INT;
}
if (!empty($_POST['amphur_filter'])) {
    $where[] = "r.amphur = ?";
    $params[] = $_POST['amphur_filter'];
    $param_types[] = PDO::PARAM_INT;
}
if (!empty($_POST['location_filter'])) {
    $where[] = "r.location = ?";
    $params[] = $_POST['location_filter'];
    $param_types[] = PDO::PARAM_STR;
}

try {
    $sql = "SELECT r.route_ID, r.province, r.amphur, r.location, r.price, r.route_image, p.PROVINCE_NAME, a.AMPHUR_NAME 
            FROM routes r 
            LEFT JOIN province p ON r.province = p.PROVINCE_ID 
            LEFT JOIN amphur a ON r.amphur = a.AMPHUR_ID";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);

    // ผูกพารามิเตอร์ก่อนหน้า (ถ้ามี)
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i], $param_types[$i]);
    }

    // ผูกพารามิเตอร์สำหรับ LIMIT และ OFFSET (ต้องเป็น integer)
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);

    $stmt->execute();
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // นับจำนวนทั้งหมด
    $count_sql = "SELECT COUNT(*) FROM routes r 
                  LEFT JOIN province p ON r.province = p.PROVINCE_ID 
                  LEFT JOIN amphur a ON r.amphur = a.AMPHUR_ID";
    if (!empty($where)) {
        $count_sql .= " WHERE " . implode(" AND ", $where);
    }
    $count_stmt = $conn->prepare($count_sql);

    // ผูกพารามิเตอร์สำหรับการนับ (ไม่มี LIMIT และ OFFSET)
    for ($i = 0; $i < count($params); $i++) {
        $count_stmt->bindValue($i + 1, $params[$i], $param_types[$i]);
    }

    $count_stmt->execute();
    $totalRows = $count_stmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);

    // ส่ง JSON กลับ
    echo json_encode([
        'routes' => $routes,
        'totalRows' => $totalRows,
        'totalPages' => $totalPages
    ]);
} catch (Exception $e) {
    // หากเกิดข้อผิดพลาด ให้ส่ง JSON กลับไป
    echo json_encode([
        'error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}

exit();
?>