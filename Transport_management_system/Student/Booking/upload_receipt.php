<?php
require_once 'condb.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['registration_id']) || !isset($_FILES['receipt_image'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'คำขอไม่ถูกต้องหรือข้อมูลไม่ครบถ้วน'
    ]);
    exit();
}

$registration_id = $_POST['registration_id'];
$stu_username = $_SESSION['user_name'];

// Validate registration ID and user authorization
try {
    $sql = "SELECT stu_username FROM transport_registration WHERE id = ? AND stu_username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$registration_id, $stu_username]);
    if (!$stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'คุณไม่มีสิทธิ์อัปโหลดใบเสร็จสำหรับการลงทะเบียนนี้'
        ]);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการตรวจสอบ: ' . $e->getMessage()
    ]);
    exit();
}

// Validate file
$file = $_FILES['receipt_image'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5MB

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์'
    ]);
    exit();
}

if (!in_array($file['type'], $allowed_types)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ประเภทไฟล์ไม่ถูกต้อง กรุณาอัปโหลดไฟล์ JPEG, PNG หรือ GIF'
    ]);
    exit();
}

if ($file['size'] > $max_size) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไฟล์มีขนาดใหญ่เกินไป กรุณาอัปโหลดไฟล์ที่มีขนาดไม่เกิน 5MB'
    ]);
    exit();
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '_' . time() . '.' . $extension;
$upload_dir = 'Uploads/receipts/';
$upload_path = $upload_dir . $filename;

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่สามารถบันทึกไฟล์ได้'
    ]);
    exit();
}

// Update database
try {
    $sql = "UPDATE transport_registration SET payment_receipt_image = ?, payment_status = 'Pending Confirmation' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$upload_path, $registration_id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'อัปโหลดใบเสร็จสำเร็จ',
        'image_path' => $upload_path,
        'payment_status' => 'Pending Confirmation'
    ]);
} catch (PDOException $e) {
    // Remove uploaded file if database update fails
    if (file_exists($upload_path)) {
        unlink($upload_path);
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่สามารถอัปเดตฐานข้อมูลได้: ' . $e->getMessage()
    ]);
}
exit();
?>