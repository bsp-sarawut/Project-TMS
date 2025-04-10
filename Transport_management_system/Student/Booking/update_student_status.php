<?php
    require_once 'condb.php';

    // ตรวจสอบว่ามีพารามิเตอร์ที่จำเป็นหรือไม่
    if (!isset($_POST['stu_id']) || !isset($_POST['stu_status'])) {
        echo json_encode(['error' => 'Missing stu_id or stu_status']);
        exit();
    }

    $stu_id = $_POST['stu_id'];
    $stu_status = $_POST['stu_status'];

    // รายการสถานะที่อนุญาต
    $allowed_statuses = ['ขึ้นรถแล้ว', 'ลา', 'สาย', ''];
    if (!in_array($stu_status, $allowed_statuses)) {
        echo json_encode(['error' => 'Invalid status']);
        exit();
    }

    // อัปเดตสถานะในตาราง students
    $sql = "UPDATE students SET stu_status = :stu_status WHERE stu_ID = :stu_id";
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':stu_status', $stu_status, PDO::PARAM_STR);
        $stmt->bindParam(':stu_id', $stu_id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'อัปเดตสถานะนักเรียนสำเร็จ']);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
?>