<?php
session_start();
require_once("condb.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['queue_id'])) { // เปลี่ยนจาก $_GET เป็น $_POST
    echo json_encode(['success' => false, 'error' => 'Queue ID is required']);
    exit;
}

$queue_id = (int)$_POST['queue_id']; // เปลี่ยนจาก $_GET เป็น $_POST

try {
    $conn->beginTransaction();

    // ดึง student_id ทั้งหมดที่อยู่ในคิวนี้
    $stmt = $conn->prepare("
        SELECT student_id
        FROM queue_student
        WHERE queue_id = :queue_id
    ");
    $stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
    $stmt->execute();
    $student_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($student_ids)) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'error' => 'No students found in this queue']);
        exit;
    }

    // รีเซ็ตสถานะนักเรียนในตาราง students
    $student_ids_str = implode(',', array_map('intval', $student_ids));
    $update_stmt = $conn->prepare("
        UPDATE students
        SET stu_status = ''
        WHERE stu_ID IN ($student_ids_str)
    ");
    $update_stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Student statuses reset successfully']);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>