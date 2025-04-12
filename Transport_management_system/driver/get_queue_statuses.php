<?php
require_once 'config/condb.php';

header('Content-Type: application/json');

$queue_id = $_GET['queue_id'] ?? '';
if (empty($queue_id)) {
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุคิว']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT s.stu_ID, s.stu_status
        FROM queue_student qs
        JOIN students s ON qs.student_id = s.stu_ID
        WHERE qs.queue_id = :queue_id
        ORDER BY qs.queue_student_id
    ");
    $stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'students' => $students]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>