<?php
session_start();
require_once 'condb.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_name'])) {
    echo json_encode(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$stu_id = isset($_POST['stu_id']) ? (int)$_POST['stu_id'] : null;
$queue_id = isset($_POST['queue_id']) ? (int)$_POST['queue_id'] : null;
$stu_status = isset($_POST['stu_status']) ? trim($_POST['stu_status']) : '';

$valid_statuses = ['', 'ขึ้นรถแล้ว', 'ลา', 'สาย', 'เลิกเรียนแล้ว'];
if (!in_array($stu_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'error' => 'สถานะไม่ถูกต้อง']);
    exit;
}

if (!$stu_id || !$queue_id) {
    echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    // ตรวจสอบว่านักเรียนอยู่ในคิวนี้
    $check_stmt = $conn->prepare("
        SELECT qs.student_id
        FROM queue_student qs
        JOIN queue q ON qs.queue_id = q.queue_id
        WHERE qs.student_id = :stu_id AND qs.queue_id = :queue_id
    ");
    $check_stmt->bindParam(':stu_id', $stu_id, PDO::PARAM_INT);
    $check_stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
    $check_stmt->execute();
    if (!$check_stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบนักเรียนในคิวนี้']);
        exit;
    }

    // อัปเดตสถานะใน students (หรือ queue_student ถ้าต้องการแยกสถานะต่อคิว)
    $stmt = $conn->prepare("
        UPDATE students
        SET stu_status = :stu_status
        WHERE stu_ID = :stu_id
    ");
    $stmt->bindParam(':stu_status', $stu_status, PDO::PARAM_STR);
    $stmt->bindParam(':stu_id', $stu_id, PDO::PARAM_INT);
    $stmt->execute();

    // บันทึก log การเปลี่ยนสถานะ
    if ($stu_status !== '') {
        $log_stmt = $conn->prepare("
            INSERT INTO student_status_log (queue_id, student_id, stu_status, log_timestamp)
            VALUES (:queue_id, :student_id, :stu_status, NOW())
        ");
        $log_stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':student_id', $stu_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':stu_status', $stu_status, PDO::PARAM_STR);
        $log_stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'อัปเดตสถานะนักเรียนสำเร็จ']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'ข้อผิดพลาด: ' . $e->getMessage()]);
}
?>