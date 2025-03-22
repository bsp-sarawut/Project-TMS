<?php
include('config/condb.php');
session_start();

if (isset($_POST['submit'])) {
    // รับค่าจากฟอร์มและทำการตรวจสอบ
    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $year = filter_var($_POST['year'], FILTER_SANITIZE_NUMBER_INT);
    $month = filter_var($_POST['month'], FILTER_SANITIZE_NUMBER_INT);
    $num_of_days = filter_var($_POST['num_of_days'], FILTER_SANITIZE_NUMBER_INT);
    
    // ตรวจสอบว่า 'available_dates' ถูกส่งมาและเป็นอาร์เรย์หรือไม่
    if (isset($_POST['available_dates']) && is_array($_POST['available_dates'])) {
        $available_dates = implode(", ", $_POST['available_dates']);
    } else {
        $_SESSION['error'] = "กรุณาเลือกวันที่ขึ้นรถ";
        header("Location: choice.php");
        exit();
    }

    try {
        // คำสั่ง SQL สำหรับอัปเดตข้อมูล
        $stmt = $conn->prepare("UPDATE transport_schedule SET year = :year, month = :month, available_dates = :available_dates, num_of_days = :num_of_days WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':available_dates', $available_dates);
        $stmt->bindParam(':num_of_days', $num_of_days);
        
        // ดำเนินการอัปเดต
        $stmt->execute();

        // ส่งข้อความแจ้งเตือนสำเร็จ
        $_SESSION['success'] = "ข้อมูลการตั้งค่าการขึ้นรถถูกอัปเดตสำเร็จ";
    } catch (PDOException $e) {
        // หากเกิดข้อผิดพลาด
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    // รีไดเรกต์ไปยังหน้า choice.php หลังจากการอัปเดต
    header("Location: choice.php");
    exit();
}
