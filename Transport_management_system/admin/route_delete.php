
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
        // ลบข้อมูลจากฐานข้อมูล
        $stmt = $conn->prepare("DELETE FROM routes WHERE route_ID = :route_ID");
        $stmt->bindParam(':route_ID', $delete_id);
        $stmt->execute();

        // ส่งข้อความ success ไปยังหน้า route.php
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'ลบข้อมูลสำเร็จ',
                    text: 'ลบข้อมูลเส้นทางเรียบร้อยแล้ว',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = 'route.php';
                });
            });
        </script>";
    } catch (PDOException $e) {
        // หากเกิดข้อผิดพลาด
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
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'ข้อผิดพลาด',
                text: 'ไม่พบข้อมูลที่จะลบ',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.location.href = 'route.php';
            });
        });
    </script>";
}
?>
