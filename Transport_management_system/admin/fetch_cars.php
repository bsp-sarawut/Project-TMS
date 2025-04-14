<?php
include('config/condb.php');

header('Content-Type: application/json');

$limit = 10;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$offset = ($page - 1) * $limit;

$searchQuery = "";
$params = [];

if (!empty($_POST['search'])) {
    $search = $_POST['search'];
    $searchQuery .= " WHERE (c.car_license LIKE :search OR c.car_brand LIKE :search OR c.car_color LIKE :search OR CONCAT(d.driver_name, ' ', d.driver_lastname) LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if (!empty($_POST['queue_filter'])) {
    $queue_filter = $_POST['queue_filter'];
    if ($queue_filter == 'มีคิวรถ') {
        $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " q.queue_id IS NOT NULL";
    } elseif ($queue_filter == 'ไม่มีคิวรถ') {
        $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " q.queue_id IS NULL";
    }
}
if (!empty($_POST['status_filter'])) {
    $status_filter = $_POST['status_filter'];
    $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " c.car_status = :status_filter";
    $params[':status_filter'] = $status_filter;
}

try {
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM car c 
                                 LEFT JOIN driver d ON c.driver_id = d.driver_id 
                                 LEFT JOIN queue q ON c.car_id = q.car_id 
                                 $searchQuery");
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);

    $sql = "SELECT c.*, 
            CONCAT(d.driver_name, ' ', d.driver_lastname) AS driver_fullname,
            q.queue_id AS queue_status
            FROM car c 
            LEFT JOIN driver d ON c.driver_id = d.driver_id 
            LEFT JOIN queue q ON c.car_id = q.car_id 
            $searchQuery
            LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'cars' => $cars,
        'totalRows' => $totalRows,
        'totalPages' => $totalPages
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => "เกิดข้อผิดพลาด: " . $e->getMessage()]);
}
?>


