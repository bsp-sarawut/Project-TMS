<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Driver</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include SweetAlert2 script here -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<?php
include('config/condb.php');
session_start();

// ตรวจสอบว่ามีข้อมูล POST จากฟอร์มแก้ไขหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['driver_id'])) {
    $driver_id = $_POST['driver_id'];
    $driver_user = $_POST['driver_user'];
    $driver_password = $_POST['driver_password'];
    $driver_name = $_POST['driver_name'];
    $driver_lastname = $_POST['driver_lastname'];
    $driver_tel = $_POST['driver_tel'];
    $driver_province = $_POST['driver_province'];
    $driver_amphur = $_POST['driver_amphur'];

    // ตรวจสอบและอัปเดตรูปภาพ
    $driver_image = null;
    if (isset($_FILES['driver_image']) && $_FILES['driver_image']['error'] == 0) {
        $image_tmp_name = $_FILES['driver_image']['tmp_name'];
        $image_name = $_FILES['driver_image']['name'];
        $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
        $image_new_name = 'driver_' . $driver_id . '.' . $image_ext;
        $image_upload_path = 'uploads/' . $image_new_name;

        // อัปโหลดรูปภาพ
        if (move_uploaded_file($image_tmp_name, $image_upload_path)) {
            $driver_image = $image_new_name;
        } else {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถอัปโหลดรูปภาพได้',
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        window.location.href = 'driver.php';
                    });
                });
            </script>";
            exit;
        }
    }

    // อัปเดตข้อมูลคนขับในฐานข้อมูล
    try {
        $query = "UPDATE driver SET
                    driver_user = :driver_user,
                    driver_password = :driver_password,
                    driver_name = :driver_name,
                    driver_lastname = :driver_lastname,
                    driver_tel = :driver_tel,
                    driver_province = :driver_province,
                    driver_amphur = :driver_amphur";

        if ($driver_image) {
            $query .= ", driver_image = :driver_image";
        }

        $query .= " WHERE driver_id = :driver_id";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':driver_user', $driver_user);
        $stmt->bindParam(':driver_password', $driver_password);
        $stmt->bindParam(':driver_name', $driver_name);
        $stmt->bindParam(':driver_lastname', $driver_lastname);
        $stmt->bindParam(':driver_tel', $driver_tel);
        $stmt->bindParam(':driver_province', $driver_province);
        $stmt->bindParam(':driver_amphur', $driver_amphur);
        $stmt->bindParam(':driver_id', $driver_id);

        // ถ้ามีการอัปเดตรูปภาพ
        if ($driver_image) {
            $stmt->bindParam(':driver_image', $driver_image);
        }

        $stmt->execute();

        // แจ้งเตือนสำเร็จ
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'อัปเดตข้อมูลสำเร็จ',
                    text: 'ข้อมูลคนขับถูกอัปเดตเรียบร้อยแล้ว',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = 'driver.php';
                });
            });
        </script>";
    } catch (PDOException $e) {
        // แจ้งข้อผิดพลาด
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $e->getMessage() . "',
                    confirmButtonText: 'ตกลง'
                });
            });
        </script>";
    }
}
?>
