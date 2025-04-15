<?php
include 'config/condb.php';

header('Content-Type: application/json');

// รับค่าจาก AJAX
$searchQuery = "";
$params = [];
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = 10; // จำนวนข้อมูลต่อหน้า
$offset = ($page - 1) * $limit;

// เงื่อนไขการค้นหา
if (!empty($_POST['search'])) {
    $search = $_POST['search'];
    $searchQuery = "WHERE (d.driver_user LIKE :search 
                    OR d.driver_name LIKE :search 
                    OR d.driver_lastname LIKE :search 
                    OR d.driver_tel LIKE :search 
                    OR p.PROVINCE_NAME LIKE :search 
                    OR a.AMPHUR_NAME LIKE :search)";
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

// นับจำนวนข้อมูลทั้งหมด
try {
    $sql_count = "SELECT COUNT(*) as total 
                  FROM driver d 
                  LEFT JOIN province p ON d.driver_province = p.PROVINCE_ID 
                  LEFT JOIN amphur a ON d.driver_amphur = a.AMPHUR_ID 
                  $searchQuery";
    $stmt_count = $conn->prepare($sql_count);
    foreach ($params as $key => $value) {
        $stmt_count->bindValue($key, $value);
    }
    $stmt_count->execute();
    $totalRows = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    echo json_encode(['error' => "เกิดข้อผิดพลาดในการนับข้อมูล: " . $e->getMessage()]);
    exit();
}

// คำนวณจำนวนหน้าทั้งหมด
$totalPages = ceil($totalRows / $limit);

// ดึงข้อมูลตามหน้า
try {
    $sql = "SELECT d.*, p.PROVINCE_NAME, a.AMPHUR_NAME 
            FROM driver d 
            LEFT JOIN province p ON d.driver_province = p.PROVINCE_ID 
            LEFT JOIN amphur a ON d.driver_amphur = a.AMPHUR_ID 
            $searchQuery 
            ORDER BY d.driver_id DESC 
            LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ส่งข้อมูลกลับไปในรูปแบบ JSON
    echo json_encode([
        'drivers' => $drivers,
        'totalRows' => $totalRows,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage()]);
}
exit();
?>