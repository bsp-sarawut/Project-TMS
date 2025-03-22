
<head>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
<?php
include('config/condb.php');
session_start();

// ตรวจสอบการลบข้อมูล
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    try {
        // ลบข้อมูลจากฐานข้อมูล
        $stmt = $conn->prepare("DELETE FROM car WHERE car_id = :car_id");
        $stmt->bindParam(':car_id', $delete_id);
        $stmt->execute();

        // เมื่อสำเร็จ, แจ้งผ่าน SweetAlert
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'ลบข้อมูลสำเร็จ',
                text: 'ลบข้อมูลรถยนต์เรียบร้อยแล้ว',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.location.href = 'car.php'; // เปลี่ยนเส้นทางไปที่หน้า car.php
            });
        </script>";

    } catch (PDOException $e) {
        // หากเกิดข้อผิดพลาด, แจ้งผ่าน SweetAlert
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage() . "',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.location.href = 'car.php'; // เปลี่ยนเส้นทางไปที่หน้า car.php
            });
        </script>";
    }
} else {
    // หากไม่ได้รับ `delete_id` จะเปลี่ยนเส้นทางไปที่หน้า car.php
    echo "<script>window.location.href = 'car.php';</script>";
}
?>
