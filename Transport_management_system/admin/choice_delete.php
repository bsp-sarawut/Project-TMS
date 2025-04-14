<?php
include('config/condb.php');
session_start();

header('Content-Type: application/json');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    // ตรวจสอบว่า ID ถูกต้อง
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID ไม่ถูกต้อง']);
        exit;
    }

    try {
        // ตรวจสอบว่ามีตารางนี้ในฐานข้อมูลหรือไม่
        $stmt = $conn->prepare("SELECT COUNT(*) FROM transport_schedule WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบตารางที่ต้องการลบ']);
            exit;
        }

        // ลบข้อมูล
        $stmt = $conn->prepare("DELETE FROM transport_schedule WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลตารางเรียบร้อยแล้ว']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีข้อมูลที่ต้องการลบ']);
}
exit();
?>