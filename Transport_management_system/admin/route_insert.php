<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<?php
include 'config/condb.php';
session_start();

if (isset($_POST['submit'])) {
    $province_id = $_POST['PROVINCE_ID'];
    $amphur_id = $_POST['amphur_id'];
    $location = $_POST['location'];
    $price = $_POST['price'];
    $route_image = "";

    // ตรวจสอบข้อมูลไม่ให้ว่าง
    if (empty($province_id) || empty($amphur_id) || empty($location) || empty($price)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
        $message = "error";
        $text = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        // ตรวจสอบและอัปโหลดไฟล์รูปภาพ
        if (isset($_FILES['route_image']) && $_FILES['route_image']['error'] == 0) {
            $targetDir = "uploads/route_img/";
            $fileName = basename($_FILES['route_image']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            // ตรวจสอบนามสกุลไฟล์
            if (in_array($fileExt, $allowedTypes)) {
                $newFileName = time() . "_" . uniqid() . "." . $fileExt;
                $targetFilePath = $targetDir . $newFileName;

                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                if (move_uploaded_file($_FILES['route_image']['tmp_name'], $targetFilePath)) {
                    $route_image = $targetFilePath;
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
                    $message = "error";
                    $text = "อัปโหลดรูปภาพไม่สำเร็จ";
                }
            } else {
                $_SESSION['error'] = "ประเภทไฟล์ไม่ถูกต้อง (รองรับเฉพาะ .jpg, .jpeg, .png, .gif)";
                $message = "error";
                $text = "ประเภทไฟล์รูปภาพไม่ถูกต้อง";
            }
        }

        // บันทึกข้อมูลลงฐานข้อมูล
        if (empty($_SESSION['error'])) {
            try {
                $stmt = $conn->prepare("INSERT INTO routes (province, amphur, location, price, route_image) 
                                        VALUES (:province_id, :amphur_id, :location, :price, :route_image)");
                $stmt->bindParam(':province_id', $province_id);
                $stmt->bindParam(':amphur_id', $amphur_id);
                $stmt->bindParam(':location', $location);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':route_image', $route_image);
                $stmt->execute();

                $_SESSION['success'] = "เพิ่มเส้นทางสำเร็จ";
                $message = "success";
                $text = "ข้อมูลเส้นทางถูกเพิ่มเรียบร้อยแล้ว";
            } catch (PDOException $e) {
                $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
                $message = "error";
                $text = "เกิดข้อผิดพลาดในการเพิ่มข้อมูล";
            }
        }
    }
} else {
    $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
    $message = "error";
    $text = "กรุณากรอกข้อมูลให้ครบถ้วน";
}

header("Location: route.php");
exit();
?>