<?php
include('config/condb.php');
session_start();

// ตรวจสอบว่าได้ส่งข้อมูลมาจากลิงก์ที่มีการลบหรือไม่
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // คำสั่ง SQL สำหรับลบข้อมูล
        $stmt = $conn->prepare("DELETE FROM transport_schedule WHERE id = :id");
        $stmt->bindParam(':id', $id);
        
        // ดำเนินการลบ
        $stmt->execute();

        // ส่งข้อความแจ้งเตือนสำเร็จ
        $_SESSION['success'] = "ข้อมูลการตั้งค่าการขึ้นรถถูกลบสำเร็จ";
    } catch (PDOException $e) {
        // หากเกิดข้อผิดพลาด
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    // รีไดเรกต์ไปยังหน้า choice.php หลังจากการลบ
    header("Location: choice.php");
    exit();
}
?>
