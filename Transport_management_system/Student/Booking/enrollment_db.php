 
<html>
    <!-- โหลด SweetAlert2 ก่อนการใช้ JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</html>
<?php
    session_start(); // เริ่มต้นเซสชัน

    if (! isset($_SESSION['user_id']) || ! isset($_SESSION['user_name'])) {
        die("Please log in first.");
    }

    // รับค่าจากฟอร์ม
    $stu_username = $_SESSION['user_name'];
    $province_id  = $_POST['province'];
    $amphur_id    = $_POST['amphur'];
    $location     = $_POST['location'];
    $schedule_id  = $_POST['schedule'];
    $num_of_days  = $_POST['num_of_days'];

    // เชื่อมต่อฐานข้อมูล
    $conn = new PDO("mysql:host=localhost;dbname=transport_management_system", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ค้นหา route_id จาก province, amphur, และ location
    $stmt_route = $conn->prepare("SELECT route_ID, price FROM routes WHERE province = :province AND amphur = :amphur AND location = :location");
    $stmt_route->execute(['province' => $province_id, 'amphur' => $amphur_id, 'location' => $location]);
    $route = $stmt_route->fetch(PDO::FETCH_ASSOC);

    if (! $route) {
        die("Error: Route does not exist.");
    }

    $route_id = $route['route_ID'];
    $price    = $route['price'];
    $total_price = $num_of_days * $price;

// ตรวจสอบว่ามีการอัปโหลดไฟล์จริงหรือไม่
if (!isset($_FILES['payment_receipt']) || $_FILES['payment_receipt']['error'] !== UPLOAD_ERR_OK) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด!',
            text: 'กรุณาอัปโหลดไฟล์ก่อนดำเนินการ',
            confirmButtonText: 'ตกลง'
        }).then(() => {
            window.history.back(); // กลับไปหน้าก่อนหน้า
        });
    </script>";
    exit;
}


    $payment_receipt = $_FILES['payment_receipt'];
    $target_dir      = "uploads/receipts/";
    $unique_name     = uniqid() . "_" . basename($payment_receipt["name"]);
    $target_file     = $target_dir . $unique_name;
    $imageFileType   = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // ตรวจสอบว่าเป็นไฟล์รูปภาพหรือไม่
    if (!file_exists($payment_receipt["tmp_name"]) || getimagesize($payment_receipt["tmp_name"]) === false) {
        die("Error: File is not an image.");
    }

    // ตรวจสอบขนาดไฟล์
    if ($payment_receipt["size"] > 500000) {
        die("Error: File is too large.");
    }

    // ตรวจสอบประเภทไฟล์
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (! in_array($imageFileType, $allowed_types)) {
        die("Error: Only JPG, JPEG, PNG & GIF files are allowed.");
    }

    // อัปโหลดไฟล์
    if (! move_uploaded_file($payment_receipt["tmp_name"], $target_file)) {
        die("Error: Failed to move uploaded file to destination folder.");
    }

    // แทรกข้อมูลลงในฐานข้อมูล
    $stmt_insert = $conn->prepare("INSERT INTO transport_registration (transport_schedule_id, route_id, num_of_days, total_price, payment_receipt_image, created_at, stu_username) VALUES (:schedule_id, :route_id, :num_of_days, :total_price, :payment_receipt_image, NOW(), :stu_username)");
    $stmt_insert->execute([ 'schedule_id' => $schedule_id, 'route_id' => $route_id, 'num_of_days' => $num_of_days, 'total_price' => $total_price, 'payment_receipt_image' => $target_file, 'stu_username' => $stu_username ]);

    echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Registration successful!',
            text: 'คุณลงทะเบียนสำเร็จ',
            confirmButtonText: 'OK'
        }).then(function() {
            window.location = 'enrollment.php';
        });
    </script>";
?>

