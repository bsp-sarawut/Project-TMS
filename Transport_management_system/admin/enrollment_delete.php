<head>
    <!-- Include SweetAlert2 script -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.0/dist/sweetalert2.all.min.js"></script>
</head>

<?php
require_once 'config/condb.php';  // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่ามีการส่ง id และคำสั่ง delete มาหรือไม่
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $id = $_POST['id'];

    // เตรียมคำสั่ง SQL สำหรับลบข้อมูล
    $sql = "DELETE FROM transport_registration WHERE id = :id";
    $stmt = $conn->prepare($sql);

    // ผูกค่าพารามิเตอร์เพื่อป้องกัน SQL Injection
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    try {
        // ลบข้อมูลจากฐานข้อมูล
        $stmt->execute();

        // ถ้าลบสำเร็จ แสดงการแจ้งเตือนด้วย SweetAlert2
        echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        title: "สำเร็จ!",
                        text: "การลงทะเบียนถูกลบแล้ว!",
                        icon: "success",
                        confirmButtonText: "ตกลง"
                    }).then(() => {
                        window.location.href = "enrollment.php";  // รีไดเร็กไปที่หน้า enrollment.php
                    });
                });
              </script>';
    } catch (PDOException $e) {
        // หากเกิดข้อผิดพลาดในการลบ แสดงข้อผิดพลาด
        echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        title: "ข้อผิดพลาด!",
                        text: "ไม่สามารถลบข้อมูลได้: ' . $e->getMessage() . '",
                        icon: "error",
                        confirmButtonText: "ตกลง"
                    });
                });
              </script>';
    }
} else {
    // ถ้าการส่ง id หรือ delete ไม่ถูกต้อง ให้รีไดเร็กไปยังหน้า enrollment.php
    echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    title: "ข้อผิดพลาด!",
                    text: "ไม่พบข้อมูลที่ต้องการลบ",
                    icon: "error",
                    confirmButtonText: "ตกลง"
                }).then(() => {
                    window.location.href = "enrollment.php";  // รีไดเร็กไปที่หน้า enrollment.php
                });
            });
          </script>';
}
?>
