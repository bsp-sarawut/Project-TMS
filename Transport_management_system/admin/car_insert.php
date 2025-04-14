<?php
include('config/condb.php');
session_start();

header('Content-Type: application/json'); // กำหนดให้ response เป็น JSON

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $car_license = $_POST['car_license'];
    $car_brand = $_POST['car_brand'];
    $car_color = $_POST['car_color'];
    $car_seat = $_POST['car_seat'];
    $car_status = $_POST['car_status'];
    $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;

    // ตรวจสอบข้อมูลซ้ำ (car_license)
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM car WHERE car_license = :car_license");
    $checkStmt->bindParam(':car_license', $car_license);
    $checkStmt->execute();
    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'หมายเลขทะเบียนรถนี้มีอยู่ในระบบแล้ว']);
        exit();
    }

    // ตรวจสอบว่า driver_id ซ้ำหรือไม่
    if ($driver_id) {
        $driverCheckStmt = $conn->prepare("SELECT COUNT(*) FROM car WHERE driver_id = :driver_id");
        $driverCheckStmt->bindParam(':driver_id', $driver_id);
        $driverCheckStmt->execute();
        if ($driverCheckStmt->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'คนขับนี้ถูกผูกกับรถคันอื่นแล้ว']);
            exit();
        }
    }

    // ตรวจสอบและอัปโหลดรูปภาพ (ถ้ามีในฟอร์ม)
    $car_image = null;
    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['car_image']['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            echo json_encode(['status' => 'error', 'message' => 'รูปภาพต้องเป็นไฟล์ประเภท JPEG, PNG หรือ GIF เท่านั้น']);
            exit();
        }

        $car_image = time() . '_' . basename($_FILES['car_image']['name']); // เพิ่ม timestamp เพื่อป้องกันชื่อไฟล์ซ้ำ
        $targetDir = "uploads/cars/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $targetFile = $targetDir . $car_image;
        if (!move_uploaded_file($_FILES['car_image']['tmp_name'], $targetFile)) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถอัปโหลดรูปภาพได้']);
            exit();
        }
    }

    try {
        $stmt = $conn->prepare("INSERT INTO car (car_license, car_brand, car_color, car_seat, car_status, car_image, driver_id) 
                                VALUES (:car_license, :car_brand, :car_color, :car_seat, :car_status, :car_image, :driver_id)");
        $stmt->bindParam(':car_license', $car_license);
        $stmt->bindParam(':car_brand', $car_brand);
        $stmt->bindParam(':car_color', $car_color);
        $stmt->bindParam(':car_seat', $car_seat);
        $stmt->bindParam(':car_status', $car_status);
        $stmt->bindParam(':car_image', $car_image);
        $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'เพิ่มข้อมูลรถยนต์เรียบร้อยแล้ว']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
exit();
?>