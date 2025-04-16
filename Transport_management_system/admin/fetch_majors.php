<?php
include 'config/condb.php';

$response = ['majors' => []];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['faculty_name'])) {
        $faculty_name = trim($_POST['faculty_name']);
        
        // ดึง faculty_id จากชื่อคณะ
        $stmt_faculty = $conn->prepare("SELECT faculty_id FROM faculties WHERE faculty_name = :faculty_name");
        $stmt_faculty->bindParam(':faculty_name', $faculty_name, PDO::PARAM_STR);
        $stmt_faculty->execute();
        $faculty = $stmt_faculty->fetch(PDO::FETCH_ASSOC);

        if ($faculty) {
            // ดึงสาขาที่เกี่ยวข้องกับคณะนี้
            $stmt = $conn->prepare("SELECT major_id, major_name FROM majors WHERE faculty_id = :faculty_id ORDER BY major_name");
            $stmt->bindParam(':faculty_id', $faculty['faculty_id'], PDO::PARAM_INT);
            $stmt->execute();
            $response['majors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    $response['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

echo json_encode($response);
?>