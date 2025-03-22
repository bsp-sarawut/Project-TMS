<head>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<?php
include('config/condb.php');
session_start();

// ตรวจสอบการลบข้อมูล
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    try {
        // เริ่มการลบข้อมูลจากฐานข้อมูล
        $stmt = $conn->prepare("DELETE FROM driver WHERE driver_id = :driver_id");
        $stmt->bindParam(':driver_id', $delete_id);
        $stmt->execute();

        // การลบข้อมูลสำเร็จ
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'ลบข้อมูลสำเร็จ',
                    text: 'ลบข้อมูลคนขับเรียบร้อยแล้ว',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = 'driver.php'; // เปลี่ยนกลับไปหน้า driver.php
                });
            });
        </script>";
    } catch (PDOException $e) {
        // เกิดข้อผิดพลาดในการลบข้อมูล
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage() . "',
                    confirmButtonText: 'ตกลง'
                });
            });
        </script>";
    }
} else {
    // ไม่มี delete_id
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                title: 'ไม่มีข้อมูลที่ต้องการลบ',
                text: 'กรุณาลองใหม่อีกครั้ง',
                confirmButtonText: 'ตกลง'
            });
        });
    </script>";
}
?>
