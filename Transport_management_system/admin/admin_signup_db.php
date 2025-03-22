<?php

session_start();
require_once("config/condb.php");

if(isset($_POST["signup"])){
    // รับค่าจากฟอร์ม
    $admin_username = $_POST["admin_username"];
    $admin_password = $_POST["admin_password"];
    $admin_name = $_POST["admin_name"];
    $admin_lastname = $_POST["admin_lastname"];
    
    // ตรวจสอบการกรอกข้อมูล
    if (empty($admin_username)) {
        $_SESSION["error"] = "กรุณากรอกชื่อผู้ใช้";
        header("location:admin_signup.php");
        exit();
    }
    else if (empty($admin_password)) {
        $_SESSION["error"] = "กรุณากรอกรหัสผ่าน";
        header("location:admin_signup.php");
        exit();
    }
    else if (empty($admin_name)) {
        $_SESSION["error"] = "กรุณากรอกชื่อ";
        header("location:admin_signup.php");
        exit();
    }
    else if (empty($admin_lastname)) {
        $_SESSION["error"] = "กรุณากรอกนามสกุล";
        header("location:admin_signup.php");
        exit();
    } else {
        try {
            // ตรวจสอบชื่อผู้ใช้ในฐานข้อมูล
            $check_username = $conn->prepare("SELECT admin_username FROM admin WHERE admin_username = :admin_username");
            $check_username->bindParam(":admin_username", $admin_username);
            $check_username->execute();
            $row = $check_username->fetch(PDO::FETCH_ASSOC);

            if  ($row["admin_username"] == $admin_username) {
                $_SESSION["warning"] = "มีชื่อผู้ใช้นี้อยู่ในระบบอยู่แล้ว   <a href='index.php'>คลิ๊กที่นี้เพื่อเข้าสู่ระบบ</a>";
                header("location:admin_signup.php");
            }
            else {
                // ใช้ password_hash เพื่อเข้ารหัสรหัสผ่านก่อนเก็บในฐานข้อมูล
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

                // ทำการบันทึกข้อมูลใหม่ลงฐานข้อมูล
                $stmt = $conn->prepare("INSERT INTO admin(admin_username, admin_password, admin_name, admin_lastname, created_at) 
                                            VALUES (:admin_username, :admin_password, :admin_name, :admin_lastname, NOW())");

                $stmt->bindParam(":admin_username", $admin_username); 
                $stmt->bindParam(":admin_password", $hashed_password);  // ใช้ hashed password
                $stmt->bindParam(":admin_name", $admin_name); 
                $stmt->bindParam(":admin_lastname", $admin_lastname); 
                $stmt->execute();

                $_SESSION["success"] = "สมัครสมาชิกเรียบร้อย <a href='index.php' class='alert-link'>คลิ๊กที่นี้เพื่อเข้าสู่ระบบ</a>";
                header("location:admin_signup.php");
                exit();
            }
        } catch(PDOException $e) {
            echo $e->getMessage();
        }
    }
}
?>
