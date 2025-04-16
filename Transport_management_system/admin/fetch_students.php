<?php
include 'config/condb.php';

$response = ['students' => [], 'totalRows' => 0, 'totalPages' => 0];

try {
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';
    $faculty_filter = isset($_POST['faculty_filter']) ? trim($_POST['faculty_filter']) : '';
    $major_filter = isset($_POST['major_filter']) ? trim($_POST['major_filter']) : '';
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT * FROM students WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (stu_name LIKE :search OR stu_lastname LIKE :search OR stu_faculty LIKE :search OR stu_major LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    if (!empty($faculty_filter)) {
        $sql .= " AND stu_faculty = :faculty_filter";
        $params[':faculty_filter'] = $faculty_filter;
    }

    if (!empty($major_filter)) {
        $sql .= " AND stu_major = :major_filter";
        $params[':major_filter'] = $major_filter;
    }

    // นับจำนวนแถวทั้งหมด
    $stmt_count = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt_count->bindValue($key, $value);
    }
    $stmt_count->execute();
    $totalRows = $stmt_count->rowCount();
    $totalPages = ceil($totalRows / $limit);

    // ดึงข้อมูลนักศึกษา
    $sql .= " LIMIT :offset, :limit";
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ดีบักข้อมูลที่ดึงมา
    error_log("Fetched students: " . print_r($students, true));

    $response['students'] = $students;
    $response['totalRows'] = $totalRows;
    $response['totalPages'] = $totalPages;
} catch (Exception $e) {
    $response['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>