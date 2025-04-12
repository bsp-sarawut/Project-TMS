<?php
session_start();
require_once 'condb.php';

header('Content-Type: application/json');

$queue_id = isset($_GET['queue_id']) ? (int)$_GET['queue_id'] : 0;

if ($queue_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid queue ID']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT status_car 
        FROM queue 
        WHERE queue_id = :queue_id
    ");
    $stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Queue not found']);
        exit;
    }

    if ($result['status_car'] === 'ปิดงาน') {
        $reset_stmt = $conn->prepare("
            UPDATE students s
            INNER JOIN queue_student qs ON s.stu_ID = qs.student_id
            SET s.stu_status = ''
            WHERE qs.queue_id = :queue_id
        ");
        $reset_stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
        $reset_stmt->execute();
    }

    echo json_encode([
        'success' => true,
        'status_car' => $result['status_car']
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>