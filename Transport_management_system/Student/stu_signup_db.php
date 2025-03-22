<?php 

session_start();
require_once("config/condb.php");

if(isset($_POST["signup"])){
    $stu_username = trim($_POST["stu_username"]);
    $stu_password = $_POST["stu_password"];
    $stu_year = $_POST["stu_year"];
    $stu_license = $_POST["stu_license"];
    $stu_name = $_POST["stu_name"];
    $stu_lastname = $_POST["stu_lastname"];
    $stu_tel = $_POST["stu_tel"];
    $stu_faculty = $_POST["stu_faculty"];
    $stu_major = $_POST["stu_major"];
    $stu_status = "available"; // ค่าเริ่มต้น

    // ตรวจสอบการกรอกข้อมูล
    if (empty($stu_username)) {
        $_SESSION["error"] = "กรุณากรอกชื่อผู้ใช้";
        header("location:stu_signup.php");
        exit();
    }

    // ตรวจสอบชื่อผู้ใช้ซ้ำ
    $check_username = $conn->prepare("SELECT stu_username FROM students WHERE stu_username = :stu_username");
    $check_username->bindParam(":stu_username", $stu_username);
    $check_username->execute();
    if ($check_username->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION["warning"] = "ชื่อผู้ใช้นี้มีอยู่แล้ว <a href='index.php'>คลิกที่นี่เพื่อเข้าสู่ระบบ</a>";
        header("location:stu_signup.php");
        exit();
    }

    // ตรวจสอบการอัปโหลดรูปภาพ
    $stu_img = null;
    if (!empty($_FILES["stu_img"]["name"])) {
        $allowed_types = ["jpg", "jpeg", "png", "gif"];
        $file_info = pathinfo($_FILES["stu_img"]["name"]);
        $file_ext = strtolower($file_info["extension"]);
        $file_size = $_FILES["stu_img"]["size"];
        $upload_dir = "uploads/";

        if (!in_array($file_ext, $allowed_types)) {
            $_SESSION["error"] = "ประเภทไฟล์ไม่ถูกต้อง (รองรับ jpg, jpeg, png, gif เท่านั้น)";
            header("location:stu_signup.php");
            exit();
        }
        if ($file_size > 2 * 1024 * 1024) { // จำกัดขนาด 2MB
            $_SESSION["error"] = "ไฟล์รูปภาพมีขนาดใหญ่เกินไป (จำกัด 2MB)";
            header("location:stu_signup.php");
            exit();
        }
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = uniqid() . "." . $file_ext;
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES["stu_img"]["tmp_name"], $file_path)) {
            $stu_img = $file_path;
        } else {
            $_SESSION["error"] = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
            header("location:stu_signup.php");
            exit();
        }
    }

    // เข้ารหัสรหัสผ่าน
    $hashed_password = password_hash($stu_password, PASSWORD_DEFAULT);

    // บันทึกข้อมูลลงฐานข้อมูล
    try {
        $stmt = $conn->prepare("INSERT INTO students 
            (stu_username, stu_password, stu_year, stu_license, stu_name, stu_lastname, stu_tel, stu_faculty, stu_major, stu_status, stu_img) 
            VALUES 
            (:stu_username, :stu_password, :stu_year, :stu_license, :stu_name, :stu_lastname, :stu_tel, :stu_faculty, :stu_major, :stu_status, :stu_img)");

        $stmt->bindParam(":stu_username", $stu_username); 
        $stmt->bindParam(":stu_password", $hashed_password);
        $stmt->bindParam(":stu_year", $stu_year); 
        $stmt->bindParam(":stu_license", $stu_license); 
        $stmt->bindParam(":stu_name", $stu_name); 
        $stmt->bindParam(":stu_lastname", $stu_lastname); 
        $stmt->bindParam(":stu_tel", $stu_tel); 
        $stmt->bindParam(":stu_faculty", $stu_faculty);     
        $stmt->bindParam(":stu_major", $stu_major); 
        $stmt->bindParam(":stu_status", $stu_status); 
        $stmt->bindParam(":stu_img", $stu_img); 
        $stmt->execute();

        $_SESSION["success"] = "สมัครสมาชิกเรียบร้อย <a href='index.php'>คลิกที่นี่เพื่อเข้าสู่ระบบ</a>";
        header("location:stu_signup.php");
        exit();
    } catch(PDOException $e) {
        $_SESSION["error"] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("location:stu_signup.php");
        exit();
    }
}
?>
