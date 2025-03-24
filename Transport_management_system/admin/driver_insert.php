<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Driver</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    try {
        // เพิ่มข้อมูลคนขับก่อนเพื่อให้ได้ driver_id
        $stmt = $conn->prepare("INSERT INTO driver (driver_user, driver_password, driver_name, driver_lastname, driver_tel, driver_province, driver_amphur) 
                                VALUES (:driver_user, :driver_password, :driver_name, :driver_lastname, :driver_tel, :driver_province, :driver_amphur)");
        $stmt->bindParam(':driver_user', $driver_user);
        $stmt->bindParam(':driver_password', $driver_password);
        $stmt->bindParam(':driver_name', $driver_name);
        $stmt->bindParam(':driver_lastname', $driver_lastname);
        $stmt->bindParam(':driver_tel', $driver_tel);
        $stmt->bindParam(':driver_province', $driver_province);
        $stmt->bindParam(':driver_amphur', $driver_amphur);
        $stmt->execute();

        // ดึง driver_id ที่เพิ่งเพิ่ม
        $driver_id = $conn->lastInsertId();

        // ตรวจสอบและอัปโหลดรูปภาพ
        $driver_image = null;
        if (isset($_FILES['driver_image']) && $_FILES['driver_image']['error'] === UPLOAD_ERR_OK) {
            $image_tmp_name = $_FILES['driver_image']['tmp_name'];
            $image_name = $_FILES['driver_image']['name'];
            $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
            $image_new_name = 'driver_' . $driver_id . '.' . $image_ext;
            $image_upload_path = 'uploads/drivers/' . $image_new_name;

            if (!is_dir('uploads/drivers/')) {
                mkdir('uploads/drivers/', 0777, true);
            }

            if (move_uploaded_file($image_tmp_name, $image_upload_path)) {
                $driver_image = $image_new_name;

                // อัปเดต driver_image ในฐานข้อมูล
                $update_stmt = $conn->prepare("UPDATE driver SET driver_image = :driver_image WHERE driver_id = :driver_id");
                $update_stmt->bindParam(':driver_image', $driver_image);
                $update_stmt->bindParam(':driver_id', $driver_id);
                $update_stmt->execute();
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
                    window.location.href = 'driver.php';
                });
            });
        </script>";
    }
}
?>