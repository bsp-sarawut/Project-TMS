<?php
session_start();
include 'config/condb.php';

$response = ['status' => '', 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('คำขอไม่ถูกต้อง');
    }

    // รับข้อมูลจากฟอร์ม
    $stu_id = trim($_POST['stu_id'] ?? '');
    if (empty($stu_id) || !is_numeric($stu_id)) {
        throw new Exception('รหัสนักศึกษาไม่ถูกต้อง');
    }

    error_log("Received stu_id for update: " . $stu_id); // ดีบัก

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
    if (empty($stu_username) || empty($stu_year) || empty($stu_license) ||
        empty($stu_name) || empty($stu_lastname) || empty($stu_tel) || empty($stu_faculty) || empty($stu_major)) {
        throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
    }

    // ดึงข้อมูลเดิมของนักศึกษา
    $stmt_old = $conn->prepare("SELECT stu_username, stu_img FROM students WHERE stu_ID = :stu_ID");
    $stmt_old->bindParam(':stu_ID', $stu_id, PDO::PARAM_INT);
    $stmt_old->execute();
    $old_data = $stmt_old->fetch(PDO::FETCH_ASSOC);

    if (!$old_data) {
        throw new Exception('ไม่พบข้อมูลนักศึกษา');
    }

    // ตรวจสอบชื่อผู้ใช้ซ้ำ
    if ($stu_username !== $old_data['stu_username']) {
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM students WHERE stu_username = :stu_username AND stu_ID != :stu_ID");
        $stmt_check->bindParam(':stu_username', $stu_username, PDO::PARAM_STR);
        $stmt_check->bindParam(':stu_ID', $stu_id, PDO::PARAM_INT);
        $stmt_check->execute();
        if ($stmt_check->fetchColumn() > 0) {
            throw new Exception('ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว');
        }
    }

    // จัดการรูปภาพ
    $stu_img = $old_data['stu_img'];
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

        // ลบรูปภาพเก่าถ้ามี
        if ($stu_img && file_exists($upload_dir . $stu_img)) {
            unlink($upload_dir . $stu_img);
        }

        if (move_uploaded_file($file_tmp, $file_path)) {
            $stu_img = $new_file_name;
        } else {
            throw new Exception('เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ');
        }
    }

    // อัปเดตข้อมูล
    $sql = "UPDATE students SET 
            stu_username = :stu_username, 
            stu_year = :stu_year, 
            stu_license = :stu_license, 
            stu_name = :stu_name, 
            stu_lastname = :stu_lastname, 
            stu_tel = :stu_tel, 
            stu_faculty = :stu_faculty, 
            stu_major = :stu_major, 
            stu_img = :stu_img";
    if (!empty($stu_password)) {
        $hashed_password = password_hash($stu_password, PASSWORD_DEFAULT);
        $sql .= ", stu_password = :stu_password";
    }
    $sql .= " WHERE stu_ID = :stu_ID";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':stu_username', $stu_username);
    $stmt->bindParam(':stu_year', $stu_year);
    $stmt->bindParam(':stu_license', $stu_license);
    $stmt->bindParam(':stu_name', $stu_name);
    $stmt->bindParam(':stu_lastname', $stu_lastname);
    $stmt->bindParam(':stu_tel', $stu_tel);
    $stmt->bindParam(':stu_faculty', $stu_faculty);
    $stmt->bindParam(':stu_major', $stu_major);
    $stmt->bindParam(':stu_img', $stu_img);
    $stmt->bindParam(':stu_ID', $stu_id, PDO::PARAM_INT);
    if (!empty($stu_password)) {
        $stmt->bindParam(':stu_password', $hashed_password);
    }

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'แก้ไขข้อมูลนักศึกษาเรียบร้อยแล้ว';
    } else {
        throw new Exception('ไม่สามารถแก้ไขข้อมูลนักศึกษาได้');
    }
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>