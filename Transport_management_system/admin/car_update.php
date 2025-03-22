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
    $car_image = $_FILES['car_image']['name'];

    // ตรวจสอบการอัพโหลดไฟล์
    if ($car_image) {
        $target_dir = "uploads/cars";
        $target_file = $target_dir . basename($car_image);
        move_uploaded_file($_FILES["car_image"]["tmp_name"], $target_file);
        $stmt = $conn->prepare("UPDATE car SET car_license = :car_license, car_brand = :car_brand, car_color = :car_color, car_seat = :car_seat, car_status = :car_status, car_image = :car_image WHERE car_id = :car_id");
        $stmt->bindParam(':car_image', $car_image);
    } else {
        $stmt = $conn->prepare("UPDATE car SET car_license = :car_license, car_brand = :car_brand, car_color = :car_color, car_seat = :car_seat, car_status = :car_status WHERE car_id = :car_id");
    }

    // Binding params
    $stmt->bindParam(':car_license', $car_license);
    $stmt->bindParam(':car_brand', $car_brand);
    $stmt->bindParam(':car_color', $car_color);
    $stmt->bindParam(':car_seat', $car_seat);
    $stmt->bindParam(':car_status', $car_status);
    $stmt->bindParam(':car_id', $car_id);

    // Execute the query
    try {
        $stmt->execute();
        $_SESSION['success'] = "ข้อมูลรถยนต์ถูกอัพเดตเรียบร้อยแล้ว";
        header("Location: car.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัพเดตข้อมูล: " . $e->getMessage();
        header("Location: car.php");
        exit();
    }
}
