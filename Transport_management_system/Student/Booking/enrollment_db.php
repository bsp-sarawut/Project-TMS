<html>
    <!-- โหลด SweetAlert2 และฟอนต์ Chakra Petch -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
</html>
<?php
session_start(); // เริ่มต้นเซสชัน

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
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

if (!$route) {
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
            confirmButtonText: 'ตกลง',
            background: '#263238',
            color: '#eceff1',
            iconColor: '#ef5350',
            confirmButtonColor: '#ffca28',
            customClass: {
                title: 'swal-title',
                content: 'swal-text',
                confirmButton: 'swal-button'
            }
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
if (!in_array($imageFileType, $allowed_types)) {
    die("Error: Only JPG, JPEG, PNG & GIF files are allowed.");
}

// อัปโหลดไฟล์
if (!move_uploaded_file($payment_receipt["tmp_name"], $target_file)) {
    die("Error: Failed to move uploaded file to destination folder.");
}

// แทรกข้อมูลลงในฐานข้อมูล โดยกำหนด payment_status เป็น "Pending Confirmation"
$stmt_insert = $conn->prepare(
    "INSERT INTO transport_registration (transport_schedule_id, route_id, num_of_days, total_price, payment_receipt_image, payment_status, created_at, stu_username) 
    VALUES (:schedule_id, :route_id, :num_of_days, :total_price, :payment_receipt_image, 'Pending Confirmation', NOW(), :stu_username)"
);
$stmt_insert->execute([
    'schedule_id' => $schedule_id,
    'route_id' => $route_id,
    'num_of_days' => $num_of_days,
    'total_price' => $total_price,
    'payment_receipt_image' => $target_file,
    'stu_username' => $stu_username
]);

// แจ้งเตือนผู้ใช้
echo "<script>
    Swal.fire({
        icon: 'success',
        title: 'ลงทะเบียนสำเร็จ!',
        text: 'คุณลงทะเบียนเรียบร้อยแล้ว สถานะ: รอการยืนยัน (Pending Confirmation)',
        confirmButtonText: 'ตกลง',
        background: '#263238',
        color: '#eceff1',
        iconColor: '#4caf50',
        confirmButtonColor: '#ffca28',
        customClass: {
            title: 'swal-title',
            content: 'swal-text',
            confirmButton: 'swal-button'
        }
    }).then(function() {
        window.location = 'enrollment.php';
    });
</script>";
?>

<style>
    .swal-title {
        font-family: 'Chakra Petch', sans-serif;
        font-weight: 700;
        font-size: 1.5rem;
        color: #ffca28;
        text-transform: uppercase;
    }
    .swal-text {
        font-family: 'Chakra Petch', sans-serif;
        font-weight: 400;
        font-size: 1rem;
    }
    .swal-button {
        font-family: 'Chakra Petch', sans-serif;
        font-weight: 600;
        background: linear-gradient(45deg, #ffca28, #ff8f00) !important;
        color: #1c2526 !important;
        border-radius: 25px !important;
        padding: 10px 20px !important;
        transition: background 0.3s ease, transform 0.3s ease !important;
    }
    .swal-button:hover {
        background: linear-gradient(45deg, #ff8f00, #ffca28) !important;
        transform: scale(1.05) !important;
    }
</style>