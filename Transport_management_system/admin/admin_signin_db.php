<?php
session_start();
require_once("config/condb.php");

if (isset($_POST["signin"])) {
    $admin_username = $_POST["admin_username"];  // Username ที่แอดมินป้อน
    $admin_password = $_POST["admin_password"];  // Password ที่แอดมินป้อน

    // ตรวจสอบว่าชื่อผู้ใช้และรหัสผ่านไม่ว่างเปล่า
    if (empty($admin_username)) {
        $_SESSION["error"] = "กรุณากรอกชื่อผู้ใช้";  
        header("location:index.php");
        exit();
    } elseif (empty($admin_password)) {
        $_SESSION["error"] = "กรุณากรอกรหัสผ่าน";  
        header("location:index.php");
        exit();
    } else {
        try {
            // ตรวจสอบในตาราง admin
            $check_data = $conn->prepare("SELECT * FROM admin WHERE admin_username = :admin_username");
            $check_data->bindParam(":admin_username", $admin_username, PDO::PARAM_STR);
            $check_data->execute();
            $row = $check_data->fetch(PDO::FETCH_ASSOC);

            // ตรวจสอบรหัสผ่านที่แฮช
            if ($row && password_verify($admin_password, $row['admin_password'])) {
                $_SESSION['admin_id'] = $row['admin_ID'];  
                $_SESSION['admin_name'] = $row['admin_username'];
                $_SESSION['success'] = "เข้าสู่ระบบสำเร็จ";
                header("location:admin_dashboard.php"); // Redirect to admin dashboard
            } else {
                $_SESSION['error'] = "ชื่อผู้ใช้หรือรหัสผ่านแอดมินผิด";  
                header("location:index.php");
            }
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage();  
            header("location:index.php");
            exit();
        }
    }
}
