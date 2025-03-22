<?php
include('config/condb.php');
session_start();

if (isset($_POST['submit'])) {
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
        $_SESSION['error'] = "หมายเลขทะเบียนรถนี้มีอยู่ในระบบแล้ว";
        header("Location: car.php");
        exit();
    }

    // ตรวจสอบว่า driver_id ซ้ำหรือไม่
    if ($driver_id) {
        $driverCheckStmt = $conn->prepare("SELECT COUNT(*) FROM car WHERE driver_id = :driver_id");
        $driverCheckStmt->bindParam(':driver_id', $driver_id);
        $driverCheckStmt->execute();
        if ($driverCheckStmt->fetchColumn() > 0) {
            $_SESSION['error'] = "คนขับนี้ถูกผูกกับรถคันอื่นแล้ว";
            header("Location: car.php");
            exit();
        }
    }

    // ตรวจสอบและอัปโหลดรูปภาพ
    $car_image = null;
    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] == 0) {
        $car_image = $_FILES['car_image']['name'];
        $targetDir = "uploads/";
        $targetFile = $targetDir . basename($car_image);
        move_uploaded_file($_FILES['car_image']['tmp_name'], $targetFile);
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
        $_SESSION['success'] = "เพิ่มข้อมูลรถยนต์เรียบร้อยแล้ว";
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มข้อมูล: " . $e->getMessage();
    }
}
header("Location: car.php");
exit();
?>