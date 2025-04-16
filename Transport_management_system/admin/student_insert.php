<?php
session_start();
include 'config/condb.php';

$response = ['status' => '', 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('คำขอไม่ถูกต้อง');
    }

    // รับข้อมูลจากฟอร์ม
    $stu_username = trim($_POST['stu_username'] ?? '');
    $stu_password = trim($_POST['stu_password'] ?? '');
    $stu_year = trim($_POST['stu_year'] ?? '');
    $stu_license = trim($_POST['stu_license'] ?? '');
    $stu_name = trim($_POST['stu_name'] ?? '');
    $stu_lastname = trim($_POST['stu_lastname'] ?? '');
    $stu_tel = trim($_POST['stu_tel'] ?? '');
    $stu_faculty = trim($_POST['stu_faculty'] ?? '');
    $stu_major = trim($_POST['stu_major'] ?? '');

    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($stu_username) || empty($stu_password) || empty($stu_year) || empty($stu_license) ||
        empty($stu_name) || empty($stu_lastname) || empty($stu_tel) || empty($stu_faculty) || empty($stu_major)) {
        throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
    }

    // ตรวจสอบชื่อผู้ใช้ซ้ำ
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM students WHERE stu_username = :stu_username");
    $stmt_check->bindParam(':stu_username', $stu_username, PDO::PARAM_STR);
    $stmt_check->execute();
    if ($stmt_check->fetchColumn() > 0) {
        throw new Exception('ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว');
    }

    // Hash รหัสผ่าน
    $hashed_password = password_hash($stu_password, PASSWORD_DEFAULT);

    // จัดการรูปภาพ
    $stu_img = null;
    $upload_dir = '../Student/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (isset($_FILES['stu_img']) && $_FILES['stu_img']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['stu_img'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = uniqid() . '.' . $file_ext;
        $file_path = $upload_dir . $new_file_name;

        if (move_uploaded_file($file_tmp, $file_path)) {
            $stu_img = $new_file_name;
        } else {
            throw new Exception('เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ');
        }
    }

    // เพิ่มข้อมูลนักศึกษา
    $sql = "INSERT INTO students (stu_username, stu_password, stu_year, stu_license, stu_name, stu_lastname, stu_tel, stu_faculty, stu_major, stu_img, created_at) 
            VALUES (:stu_username, :stu_password, :stu_year, :stu_license, :stu_name, :stu_lastname, :stu_tel, :stu_faculty, :stu_major, :stu_img, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':stu_username', $stu_username);
    $stmt->bindParam(':stu_password', $hashed_password);
    $stmt->bindParam(':stu_year', $stu_year);
    $stmt->bindParam(':stu_license', $stu_license);
    $stmt->bindParam(':stu_name', $stu_name);
    $stmt->bindParam(':stu_lastname', $stu_lastname);
    $stmt->bindParam(':stu_tel', $stu_tel);
    $stmt->bindParam(':stu_faculty', $stu_faculty);
    $stmt->bindParam(':stu_major', $stu_major);
    $stmt->bindParam(':stu_img', $stu_img);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'เพิ่มข้อมูลนักศึกษาเรียบร้อยแล้ว';
    } else {
        throw new Exception('ไม่สามารถเพิ่มข้อมูลนักศึกษาได้');
    }
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>