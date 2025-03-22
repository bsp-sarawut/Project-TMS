<?php
include('config/condb.php');

// ตรวจสอบว่ามีการส่งค่า province_id มาหรือไม่
if (isset($_POST['province_id']) && !empty($_POST['province_id'])) {
    $province_id = $_POST['province_id'];

    try {
        // ดึงข้อมูลอำเภอจากฐานข้อมูลตาม province_id
        $stmt = $conn->prepare("SELECT * FROM amphur WHERE PROVINCE_ID = :province_id");
        $stmt->bindParam(':province_id', $province_id);
        $stmt->execute();

        $amphurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ตรวจสอบว่ามีข้อมูลอำเภอหรือไม่
        if ($amphurs) {
            foreach ($amphurs as $amphur) {
                echo '<option value="' . $amphur['AMPHUR_ID'] . '">' . $amphur['AMPHUR_NAME'] . '</option>';
            }
        } else {
            echo '<option value="">ไม่มีข้อมูลอำเภอ</option>';
        }

    } catch (PDOException $e) {
        echo '<option value="">เกิดข้อผิดพลาดในการดึงข้อมูลอำเภอ</option>';
    }
} else {
    echo '<option value="">กรุณาเลือกจังหวัดก่อน</option>';
}
?>
