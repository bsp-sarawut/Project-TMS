<head>
    <!-- Include SweetAlert2 script -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.0/dist/sweetalert2.all.min.js"></script>
</head>

<?php
require_once 'config/condb.php';  // เชื่อมต่อฐานข้อมูล
session_start(); // เรียกใช้งาน session หากต้องใช้ session

// ตรวจสอบว่ามีการส่ง id และ payment_status มาหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['payment_status'])) {
    $id = $_POST['id'];
    $new_status = $_POST['payment_status'];

    // ตรวจสอบให้แน่ใจว่าสถานะที่รับเข้ามาถูกต้อง (ไม่รวม Pending)
    $valid_statuses = ['Paid', 'Pending Confirmation'];  // ไม่เอา 'Pending' ออก
    if (in_array($new_status, $valid_statuses)) {
        try {
            // เตรียมคำสั่ง SQL สำหรับการอัพเดทสถานะการชำระเงิน
            $sql = "UPDATE transport_registration SET payment_status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$new_status, $id]);

            // ถ้าอัพเดทสำเร็จ แสดงการแจ้งเตือนด้วย SweetAlert2
            echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        Swal.fire({
                            title: "สำเร็จ!",
                            text: "สถานะการชำระเงินอัพเดทเรียบร้อยแล้ว",
                            icon: "success",
                            confirmButtonText: "ตกลง"
                        }).then(() => {
                            window.location.href = "enrollment.php";  // รีไดเร็กไปที่หน้า enrollment.php
                        });
                    });
                  </script>';
        } catch (PDOException $e) {
            // หากเกิดข้อผิดพลาดในการอัพเดท แสดงข้อผิดพลาด
            echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        Swal.fire({
                            title: "ข้อผิดพลาด!",
                            text: "ไม่สามารถอัพเดทสถานะได้: ' . $e->getMessage() . '",
                            icon: "error",
                            confirmButtonText: "ตกลง"
                        });
                    });
                  </script>';
        }
    } else {
        // ถ้าสถานะไม่ถูกต้อง แสดงข้อผิดพลาด
        echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        title: "ข้อผิดพลาด!",
                        text: "สถานะที่เลือกไม่ถูกต้อง",
                        icon: "error",
                        confirmButtonText: "ตกลง"
                    }).then(() => {
                        window.location.href = "enrollment.php";  // รีไดเร็กไปที่หน้า enrollment.php
                    });
                });
              </script>';
    }
} else {
    // ถ้าข้อมูลที่ส่งมาไม่ครบ ให้กลับไปหน้าหลัก
    echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    title: "ข้อผิดพลาด!",
                    text: "คำขอไม่ถูกต้อง",
                    icon: "error",
                    confirmButtonText: "ตกลง"
                }).then(() => {
                    window.location.href = "enrollment.php";  // รีไดเร็กไปที่หน้า enrollment.php
                });
            });
          </script>';
}
?>
