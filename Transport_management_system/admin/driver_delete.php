<?php
include('config/condb.php');
session_start();

header('Content-Type: application/json');

// ตรวจสอบการลบข้อมูล
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    try {
        // ดึงข้อมูลรูปภาพก่อนลบ
        $stmt = $conn->prepare("SELECT driver_image FROM driver WHERE driver_id = :driver_id");
        $stmt->bindParam(':driver_id', $delete_id);
        $stmt->execute();
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);

        // ลบข้อมูลจากฐานข้อมูล
        $stmt = $conn->prepare("DELETE FROM driver WHERE driver_id = :driver_id");
        $stmt->bindParam(':driver_id', $delete_id);
        $stmt->execute();

        // ลบรูปภาพถ้ามี
        if ($driver['driver_image'] && file_exists("uploads/drivers/" . $driver['driver_image'])) {
            unlink("uploads/drivers/" . $driver['driver_image']);
        }

        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลคนขับเรียบร้อยแล้ว']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีข้อมูลที่ต้องการลบ']);
}
exit;
?>