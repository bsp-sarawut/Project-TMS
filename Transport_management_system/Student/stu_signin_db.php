<?php
session_start();  // เริ่มต้นเซสชัน
if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
    echo "User is logged in with ID: " . $_SESSION['user_id'] . " and Name: " . $_SESSION['user_name'];
} else {
    echo "Please log in first.";
}

require_once("config/condb.php");

if (isset($_POST["signin"])) {
    $stu_username = $_POST["stu_username"];  // Username ที่ผู้ใช้ป้อน
    $stu_password = $_POST["stu_password"];  // Password ที่ผู้ใช้ป้อน

    // ตรวจสอบว่าชื่อผู้ใช้และรหัสผ่านไม่ว่างเปล่า
    if (empty($stu_username)) {
        $_SESSION["error"] = "กรุณากรอกชื่อผู้ใช้";  
        header("location:index.php");
        exit();
    } elseif (empty($stu_password)) {
        $_SESSION["error"] = "กรุณากรอกรหัสผ่าน";  
        header("location:index.php");
        exit();
    } else {
        try {
            // ตรวจสอบในตาราง students
            $check_data = $conn->prepare("SELECT * FROM students WHERE stu_username = :stu_username");
            $check_data->bindParam(":stu_username", $stu_username, PDO::PARAM_STR);
            $check_data->execute();
            $row = $check_data->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($stu_password, $row['stu_password'])) {
                // ตั้งค่า session เมื่อผู้ใช้ล็อกอินสำเร็จ
                $_SESSION['user_id'] = $row['stu_ID'];  
                $_SESSION['user_name'] = $row['stu_username'];
                $_SESSION['stu_name'] = $row['stu_name'];  // เพิ่มชื่อจริงของผู้ใช้
                $_SESSION['stu_lastname'] = $row['stu_lastname'];  // เพิ่มนามสกุลของผู้ใช้
                $_SESSION['stu_img'] = $row['stu_img'];  // เพิ่มภาพผู้ใช้
                $_SESSION['success'] = "เข้าสู่ระบบสำเร็จ";

                // อัพเดทเวลาของการเข้าสู่ระบบ (last_login)
                $update_login_time = $conn->prepare("UPDATE students SET last_login = NOW() WHERE stu_ID = :stu_ID");
                $update_login_time->bindParam(":stu_ID", $_SESSION['user_id'], PDO::PARAM_INT);
                $update_login_time->execute();

                // อัพเดตสถานะ login_status จาก inactive เป็น active
                $update_status = $conn->prepare("UPDATE students SET login_status = 'active' WHERE stu_ID = :stu_ID");
                $update_status->bindParam(":stu_ID", $_SESSION['user_id'], PDO::PARAM_INT);
                $update_status->execute();

                // รีไดเรกต์ไปยังหน้า Booking หลังจากเข้าสู่ระบบสำเร็จ
                header("location: Booking/stu_booking.php");
                exit(); // หยุดการทำงานหลังจากรีไดเรกต์
            } else {
                $_SESSION['error'] = "ชื่อผู้ใช้หรือรหัสผ่านนักศึกษาผิด";  
                header("location:index.php");
                exit(); // หยุดการทำงานหากชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage();  
            header("location:index.php");
            exit(); // หยุดการทำงานหากเกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล
        }
    }
}
?>
