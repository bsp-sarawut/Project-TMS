<?php
session_start();
require_once("config/condb.php");

header('Content-Type: application/json');

// ตรวจสอบว่าแอดมินล็อกอินหรือไม่
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบแอดมินก่อน']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_user = trim($_POST['driver_user'] ?? '');
    $driver_password = trim($_POST['driver_password'] ?? '');
    $driver_name = trim($_POST['driver_name'] ?? '');
    $driver_lastname = trim($_POST['driver_lastname'] ?? '');
    $driver_tel = trim($_POST['driver_tel'] ?? '');
    $driver_province = trim($_POST['driver_province'] ?? '');
    $driver_amphur = trim($_POST['driver_amphur'] ?? '');

    // ตรวจสอบข้อมูล
    $errors = [];
    if (empty($driver_user) || strlen($driver_user) < 4) {
        $errors[] = "ชื่อผู้ใช้ต้องมีอย่างน้อย 4 ตัวอักษร";
    }
    if (empty($driver_password) || strlen($driver_password) < 6) {
        $errors[] = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
    }
    if (empty($driver_name) || empty($driver_lastname)) {
        $errors[] = "กรุณากรอกชื่อและนามสกุล";
    }
    if (!preg_match('/^[0-9]{10}$/', $driver_tel)) {
        $errors[] = "เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก";
    }

    // ตรวจสอบว่า driver_user ซ้ำหรือไม่
    $stmt = $conn->prepare("SELECT COUNT(*) FROM driver WHERE driver_user = :driver_user");
    $stmt->bindParam(':driver_user', $driver_user);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "ชื่อผู้ใช้นี้มีอยู่แล้ว";
    }

    // ตรวจสอบ province และ amphur
    $stmt = $conn->prepare("SELECT COUNT(*) FROM province WHERE PROVINCE_ID = :province_id");
    $stmt->bindParam(':province_id', $driver_province);
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $errors[] = "จังหวัดไม่ถูกต้อง";
    }
    $stmt = $conn->prepare("SELECT COUNT(*) FROM amphur WHERE AMPHUR_ID = :amphur_id AND PROVINCE_ID = :province_id");
    $stmt->bindParam(':amphur_id', $driver_amphur);
    $stmt->bindParam(':province_id', $driver_province);
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $errors[] = "อำเภอไม่ถูกต้อง";
    }

    if (!empty($errors)) {
        echo json_encode(['status' => 'error', 'message' => implode("<br>", $errors)]);
        exit;
    }

    try {
        // แฮชรหัสผ่าน
        $hashed_password = password_hash($driver_password, PASSWORD_DEFAULT);

        // เพิ่มข้อมูลคนขับ
        $stmt = $conn->prepare("INSERT INTO driver (driver_user, driver_password, driver_name, driver_lastname, driver_tel, driver_province, driver_amphur) 
                                VALUES (:driver_user, :driver_password, :driver_name, :driver_lastname, :driver_tel, :driver_province, :driver_amphur)");
        $stmt->bindParam(':driver_user', $driver_user);
        $stmt->bindParam(':driver_password', $hashed_password);
        $stmt->bindParam(':driver_name', $driver_name);
        $stmt->bindParam(':driver_lastname', $driver_lastname);
        $stmt->bindParam(':driver_tel', $driver_tel);
        $stmt->bindParam(':driver_province', $driver_province);
        $stmt->bindParam(':driver_amphur', $driver_amphur);
        $stmt->execute();

        // ดึง driver_id ที่เพิ่งเพิ่ม
        $driver_id = $conn->lastInsertId();

        // ตรวจสอบและอัปโหลดรูปภาพ
        if (isset($_FILES['driver_image']) && $_FILES['driver_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($_FILES['driver_image']['tmp_name']);
            $file_size = $_FILES['driver_image']['size'];
            $image_ext = pathinfo($_FILES['driver_image']['name'], PATHINFO_EXTENSION);
            $image_new_name = 'driver_' . time() . '.' . $image_ext; // ใช้ timestamp เพื่อป้องกันชื่อซ้ำ
            $image_upload_path = 'uploads/drivers/' . $image_new_name;

            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("ไฟล์รูปภาพต้องเป็น JPEG, PNG หรือ GIF เท่านั้น");
            }
            if ($file_size > 2 * 1024 * 1024) { // จำกัดขนาด 2MB
                throw new Exception("ไฟล์รูปภาพต้องมีขนาดไม่เกิน 2MB");
            }

            if (!is_dir('uploads/drivers/')) {
                mkdir('uploads/drivers/', 0777, true);
            }

            if (!move_uploaded_file($_FILES['driver_image']['tmp_name'], $image_upload_path)) {
                throw new Exception("ไม่สามารถอัปโหลดรูปภาพได้");
            }

            // อัปเดต driver_image ในฐานข้อมูล
            $update_stmt = $conn->prepare("UPDATE driver SET driver_image = :driver_image WHERE driver_id = :driver_id");
            $update_stmt->bindParam(':driver_image', $image_new_name);
            $update_stmt->bindParam(':driver_id', $driver_id);
            $update_stmt->execute();
        }

        echo json_encode(['status' => 'success', 'message' => 'เพิ่มข้อมูลคนขับเรียบร้อยแล้ว']);
    } catch (Exception $e) {
        // ลบข้อมูลคนขับถ้ามีข้อผิดพลาด
        if (isset($driver_id)) {
            $delete_stmt = $conn->prepare("DELETE FROM driver WHERE driver_id = :driver_id");
            $delete_stmt->bindParam(':driver_id', $driver_id);
            $delete_stmt->execute();
        }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
exit;
?>