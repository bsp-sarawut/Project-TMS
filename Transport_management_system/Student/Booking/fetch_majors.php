<?php
require_once 'condb.php'; // ปรับพาธให้ถูกต้องถ้าจำเป็น เช่น require_once '../condb.php'

header('Content-Type: application/json');

if (isset($_POST['faculty_name']) && !empty($_POST['faculty_name'])) {
    $faculty_name = $_POST['faculty_name'];

    try {
        // ดึง faculty_id จาก faculty_name
        $stmt = $conn->prepare("SELECT faculty_id FROM faculties WHERE faculty_name = :faculty_name");
        $stmt->bindParam(':faculty_name', $faculty_name, PDO::PARAM_STR);
        $stmt->execute();
        $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($faculty) {
            $faculty_id = $faculty['faculty_id'];
            // ดึงสาขาที่เกี่ยวข้องกับ faculty_id
            $stmt = $conn->prepare("SELECT major_id, major_name FROM majors WHERE faculty_id = :faculty_id ORDER BY major_name");
            $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
            $stmt->execute();
            $majors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($majors);
        } else {
            echo json_encode([]);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]); // ส่งอาร์เรย์ว่างกลับไปถ้า faculty_name ว่าง
}
?>