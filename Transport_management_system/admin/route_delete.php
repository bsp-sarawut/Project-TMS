<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head> 
<?php
include 'config/condb.php';
session_start();

// ตรวจสอบการลบข้อมูล
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    try {
        // ดึงข้อมูลรูปภาพก่อนลบ
        $stmt = $conn->prepare("SELECT route_image FROM routes WHERE route_ID = :route_ID");
        $stmt->bindParam(':route_ID', $delete_id);
        $stmt->execute();
        $route = $stmt->fetch(PDO::FETCH_ASSOC);

        // ลบข้อมูลจากฐานข้อมูล
        $stmt = $conn->prepare("DELETE FROM routes WHERE route_ID = :route_ID");
        $stmt->bindParam(':route_ID', $delete_id);
        $stmt->execute();

        // ลบไฟล์รูปภาพถ้ามี
        if ($route && !empty($route['route_image']) && file_exists($route['route_image'])) {
            unlink($route['route_image']);
        }

        // ส่งข้อความ success ไปยังหน้า route.php
        $_SESSION['success'] = "ลบข้อมูลเส้นทางสำเร็จ";
    } catch (PDOException $e) {
        // หากเกิดข้อผิดพลาด
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "ไม่พบข้อมูลที่จะลบ";
}

header("Location: route.php");
exit();
?>