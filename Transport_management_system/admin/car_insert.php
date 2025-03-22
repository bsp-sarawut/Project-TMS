<?php
include('config/condb.php');
session_start();

if (isset($_POST['submit'])) {
    // รับค่าจากฟอร์ม
    $car_license = $_POST['car_license'];
    $car_brand = $_POST['car_brand'];
    $car_color = $_POST['car_color'];
    $car_seat = $_POST['car_seat'];
    $car_status = $_POST['car_status'];
    
    // ตรวจสอบและอัปโหลดรูปภาพ
    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] == 0) {
        $car_image = $_FILES['car_image']['name'];
        $targetDir = "uploads/";
        $targetFile = $targetDir . basename($car_image);
        move_uploaded_file($_FILES['car_image']['tmp_name'], $targetFile);
    } else {
        $car_image = null; // ไม่มีการอัปโหลดรูป
    }

    try {
        // คำสั่ง SQL สำหรับบันทึกข้อมูลรถยนต์
        $stmt = $conn->prepare("INSERT INTO car (car_license, car_brand, car_color, car_seat, car_status, car_image) VALUES (:car_license, :car_brand, :car_color, :car_seat, :car_status, :car_image)");
        $stmt->bindParam(':car_license', $car_license);
        $stmt->bindParam(':car_brand', $car_brand);
        $stmt->bindParam(':car_color', $car_color);
        $stmt->bindParam(':car_seat', $car_seat);
        $stmt->bindParam(':car_status', $car_status);
        $stmt->bindParam(':car_image', $car_image);

        // การ execute คำสั่ง SQL
        $stmt->execute();
        $_SESSION['success'] = "เพิ่มข้อมูลรถยนต์เรียบร้อยแล้ว";
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มข้อมูล: " . $e->getMessage();
    }
}

// Redirect กลับไปยังหน้า car.php
header("Location: car.php");
exit();
?>
