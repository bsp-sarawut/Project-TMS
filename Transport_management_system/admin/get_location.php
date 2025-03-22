<?php
require_once 'config/condb.php';

// ตรวจสอบว่ามีการส่งค่า province_id และ amphur_id มาหรือไม่
if (isset($_GET['province_id']) && isset($_GET['amphur_id'])) {
    $provinceID = $_GET['province_id'];
    $amphurID = $_GET['amphur_id'];

    // ดึงข้อมูลสถานที่จากฐานข้อมูล
    $locationQuery = $conn->prepare("SELECT DISTINCT location FROM routes WHERE province = :province_id AND amphur = :amphur_id");
    $locationQuery->bindParam(':province_id', $provinceID);
    $locationQuery->bindParam(':amphur_id', $amphurID);
    $locationQuery->execute();

    // แสดงผลลัพธ์
    $options = '<option value="">ทั้งหมด</option>';
    while ($location = $locationQuery->fetch(PDO::FETCH_ASSOC)) {
        $options .= '<option value="' . htmlspecialchars($location['location']) . '">' . htmlspecialchars($location['location']) . '</option>';
    }
    echo $options;
} else {
    echo '<option value="">กรุณาเลือกจังหวัดและอำเภอ</option>';
}
?>
