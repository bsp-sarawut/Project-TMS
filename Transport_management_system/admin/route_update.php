<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<?php
    include 'config/condb.php';
    session_start();

    if (isset($_POST['route_ID']) && ! empty($_POST['route_ID'])) {
        $route_ID        = intval($_POST['route_ID']); // ป้องกัน SQL Injection
        $province_ID     = $_POST['PROVINCE_ID'];
        $amphur_ID       = $_POST['amphur_id'];
        $location        = $_POST['location'];
        $price           = $_POST['price'];
        $old_route_image = $_POST['old_route_image']; // รับค่ารูปเดิม

        $route_image = $old_route_image; // ตั้งค่ารูปเดิมเป็นค่าเริ่มต้น

        // ตรวจสอบว่ามีการอัปโหลดไฟล์ใหม่หรือไม่
        if (isset($_FILES['route_image']) && $_FILES['route_image']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']; // ประเภทไฟล์ที่อนุญาต
            $fileType     = mime_content_type($_FILES['route_image']['tmp_name']);
            $fileExt      = strtolower(pathinfo($_FILES['route_image']['name'], PATHINFO_EXTENSION));

            // ตรวจสอบประเภทไฟล์
            if (in_array($fileType, $allowedTypes)) {
                $targetDir      = "uploads/";
                $newFileName    = time() . "_" . uniqid() . "." . $fileExt; // ตั้งชื่อไฟล์ใหม่
                $targetFilePath = $targetDir . $newFileName;

                // อัปโหลดไฟล์
                if (move_uploaded_file($_FILES['route_image']['tmp_name'], $targetFilePath)) {
                    // ลบรูปเดิมหากมีและไม่ใช่ค่าเริ่มต้น
                    if (! empty($old_route_image) && file_exists($old_route_image)) {
                        unlink($old_route_image);
                    }
                    $route_image = $targetFilePath;
                }
            } else {
                $_SESSION['error'] = "ประเภทไฟล์ไม่ถูกต้อง ต้องเป็น JPG, PNG หรือ GIF เท่านั้น";
                echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาด',
                    text: 'ประเภทไฟล์ไม่ถูกต้อง ต้องเป็น JPG, PNG หรือ GIF เท่านั้น',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = 'route.php';
                });
            </script>";
                exit();
            }
        }

        try {
            // อัปเดตข้อมูลเส้นทางรวมถึงรูปภาพ
            $stmt = $conn->prepare("UPDATE routes
                                SET province = :province, amphur = :amphur, location = :location, price = :price, route_image = :route_image
                                WHERE route_ID = :route_ID");

            $stmt->bindParam(':province', $province_ID);
            $stmt->bindParam(':amphur', $amphur_ID);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':route_image', $route_image);
            $stmt->bindParam(':route_ID', $route_ID);

            $stmt->execute();

            $_SESSION['success'] = "อัปเดตข้อมูลเส้นทางเรียบร้อยแล้ว";
            $message             = "success";
            $text                = "ข้อมูลเส้นทางถูกอัปเดตเรียบร้อยแล้ว";
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $e->getMessage();
            $message           = "error";
            $text              = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล";
        }
    } else {
        $message = "error";
        $text    = "ไม่มีข้อมูลที่ต้องการอัปเดต";
    }

    // แสดง SweetAlert2
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: '" . ($message === 'success' ? 'success' : 'error') . "',
            title: '" . ($message === 'success' ? 'สำเร็จ' : 'ข้อผิดพลาด') . "',
            text: '$text',
            confirmButtonText: 'ตกลง'
        }).then(() => {
            window.location.href = 'route.php';
        });
    });
</script>";

    exit();
?>
