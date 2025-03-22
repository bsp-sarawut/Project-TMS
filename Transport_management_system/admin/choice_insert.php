<?php
include('config/condb.php');
session_start();

if (isset($_POST['submit'])) {
    // รับค่าจากฟอร์ม
    $year = $_POST['year'];
    $month = $_POST['month'];
    $available_dates = implode(", ", $_POST['available_dates']);
    $num_of_days = $_POST['num_of_days'];

    try {
        // คำสั่ง SQL สำหรับเพิ่มข้อมูลใหม่
        $stmt = $conn->prepare("INSERT INTO transport_schedule (year, month, available_dates, num_of_days) VALUES (:year, :month, :available_dates, :num_of_days)");
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':available_dates', $available_dates);
        $stmt->bindParam(':num_of_days', $num_of_days);
        
        // ดำเนินการเพิ่มข้อมูล
        $stmt->execute();

        // ส่งข้อความแจ้งเตือนสำเร็จ
        $_SESSION['success'] = "ข้อมูลการตั้งค่าการขึ้นรถถูกเพิ่มสำเร็จ";
    } catch (PDOException $e) {
        // หากเกิดข้อผิดพลาด
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    // รีไดเรกต์ไปยังหน้า choice.php หลังจากการเพิ่มข้อมูล
    header("Location: choice.php");
    exit();
}
?>
