<?php
include('config/condb.php');
session_start();

if (isset($_POST['submit'])) {
    $car_id = $_POST['car_id'];
    $car_license = $_POST['car_license'];
    $car_brand = $_POST['car_brand'];
    $car_color = $_POST['car_color'];
    $car_seat = $_POST['car_seat'];
    $car_status = $_POST['car_status'];
    $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;

    // ตรวจสอบข้อมูลซ้ำ (car_license) ยกเว้นรถคันปัจจุบัน
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM car WHERE car_license = :car_license AND car_id != :car_id");
    $checkStmt->bindParam(':car_license', $car_license);
    $checkStmt->bindParam(':car_id', $car_id);
    $checkStmt->execute();
    if ($checkStmt->fetchColumn() > 0) {
        $_SESSION['error'] = "หมายเลขทะเบียนรถนี้มีอยู่ในระบบแล้ว";
        header("Location: car.php");
        exit();
    }

    // ตรวจสอบว่า driver_id ซ้ำหรือไม่ (ยกเว้นรถคันปัจจุบัน)
    if ($driver_id) {
        $driverCheckStmt = $conn->prepare("SELECT COUNT(*) FROM car WHERE driver_id = :driver_id AND car_id != :car_id");
        $driverCheckStmt->bindParam(':driver_id', $driver_id);
        $driverCheckStmt->bindParam(':car_id', $car_id);
        $driverCheckStmt->execute();
        if ($driverCheckStmt->fetchColumn() > 0) {
            $_SESSION['error'] = "คนขับนี้ถูกผูกกับรถคันอื่นแล้ว";
            header("Location: car.php");
            exit();
        }
    }

    // ดึงข้อมูลรถปัจจุบันเพื่อรักษารูปภาพเดิม
    $currentStmt = $conn->prepare("SELECT car_image FROM car WHERE car_id = :car_id");
    $currentStmt->bindParam(':car_id', $car_id);
    $currentStmt->execute();
    $currentCar = $currentStmt->fetch(PDO::FETCH_ASSOC);
    $car_image = $currentCar['car_image'];

    // ตรวจสอบการอัปโหลดรูปภาพใหม่
    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] == 0) {
        $car_image = $_FILES['car_image']['name'];
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($car_image);
        move_uploaded_file($_FILES["car_image"]["tmp_name"], $target_file);
    }

    try {
        $stmt = $conn->prepare("UPDATE car SET car_license = :car_license, car_brand = :car_brand, car_color = :car_color, 
                                car_seat = :car_seat, car_status = :car_status, car_image = :car_image, driver_id = :driver_id 
                                WHERE car_id = :car_id");
        $stmt->bindParam(':car_license', $car_license);
        $stmt->bindParam(':car_brand', $car_brand);
        $stmt->bindParam(':car_color', $car_color);
        $stmt->bindParam(':car_seat', $car_seat);
        $stmt->bindParam(':car_status', $car_status);
        $stmt->bindParam(':car_image', $car_image);
        $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
        $stmt->bindParam(':car_id', $car_id);
        $stmt->execute();
        $_SESSION['success'] = "ข้อมูลรถยนต์ถูกอัปเดตเรียบร้อยแล้ว";
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $e->getMessage();
    }
    header("Location: car.php");
    exit();
}
?>