<head>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.5.9/dist/sweetalert2.min.js"></script>
</head>

<?php
include('config/condb.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_user = $_POST['driver_user'];
    $driver_password = $_POST['driver_password'];
    $driver_name = $_POST['driver_name'];
    $driver_lastname = $_POST['driver_lastname'];
    $driver_tel = $_POST['driver_tel'];
    $driver_province = $_POST['driver_province'];
    $driver_amphur = $_POST['driver_amphur'];

    // ตรวจสอบว่ามีการอัปโหลดไฟล์หรือไม่
    $upload_dir = 'uploads/drivers/';
    $driver_image = null;

    if (isset($_FILES['driver_image']) && $_FILES['driver_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['driver_image']['tmp_name'];
        $file_name = uniqid() . '_' . $_FILES['driver_image']['name'];
        $file_dest = $upload_dir . $file_name;

        // ตรวจสอบว่ามีโฟลเดอร์เก็บไฟล์หรือไม่ ถ้าไม่มีให้สร้าง
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // ย้ายไฟล์ไปยังปลายทาง
        if (move_uploaded_file($file_tmp, $file_dest)) {
            $driver_image = $file_name;
        } else {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถอัปโหลดรูปภาพได้',
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        window.history.back();
                    });
                });
            </script>";
            exit;
        }
    }

    try {
        // เพิ่มข้อมูลลงในฐานข้อมูล
        $stmt = $conn->prepare("INSERT INTO driver (driver_user, driver_password, driver_name, driver_lastname, driver_tel, driver_province, driver_amphur, driver_image) 
                                VALUES (:driver_user, :driver_password, :driver_name, :driver_lastname, :driver_tel, :driver_province, :driver_amphur, :driver_image)");
        $stmt->bindParam(':driver_user', $driver_user);
        $stmt->bindParam(':driver_password', $driver_password);
        $stmt->bindParam(':driver_name', $driver_name);
        $stmt->bindParam(':driver_lastname', $driver_lastname);
        $stmt->bindParam(':driver_tel', $driver_tel);
        $stmt->bindParam(':driver_province', $driver_province);
        $stmt->bindParam(':driver_amphur', $driver_amphur);
        $stmt->bindParam(':driver_image', $driver_image);

        $stmt->execute();

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'เพิ่มข้อมูลสำเร็จ',
                    text: 'ข้อมูลคนขับถูกบันทึกเรียบร้อยแล้ว',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = 'driver.php';
                });
            });
        </script>";
    } catch (PDOException $e) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเพิ่มข้อมูลได้: " . $e->getMessage() . "',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.history.back();
                });
            });
        </script>";
    }
}
?>
