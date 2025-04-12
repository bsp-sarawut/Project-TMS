<?php
require_once 'condb.php';

$stu_id = $_POST['stu_id'] ?? '';
$stu_status = $_POST['stu_status'] ?? '';

$allowed_statuses = ['', 'ขึ้นรถแล้ว', 'ลา', 'สาย', 'เลิกเรียนแล้ว'];

if (empty($stu_id) || !in_array($stu_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'error' => 'สถานะไม่ถูกต้อง']);
    exit;
}

try {
    $sql = "UPDATE students SET stu_status = :stu_status WHERE stu_ID = :stu_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':stu_status', $stu_status, PDO::PARAM_STR);
    $stmt->bindParam(':stu_id', $stu_id, PDO::PARAM_STR);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'อัปเดตสถานะสำเร็จ']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'ข้อผิดพลาดฐานข้อมูล: ' . $e->getMessage()]);
}
?>