<?php
include 'config/condb.php';
session_start();

header('Content-Type: application/json');

try {
    // รับข้อมูลจากฟอร์ม
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $year = isset($_POST['year']) ? trim($_POST['year']) : '';
    $month = isset($_POST['month']) ? trim($_POST['month']) : '';
    $available_dates = isset($_POST['available_dates']) ? trim($_POST['available_dates']) : '';
    $num_of_days = isset($_POST['num_of_days']) ? trim($_POST['num_of_days']) : '';

    // ตรวจสอบข้อมูลที่ส่งมา
    if (empty($id) || empty($year) || empty($month) || empty($available_dates) || empty($num_of_days)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
        ]);
        exit();
    }

    // แปลง available_dates เป็น array เพื่อใช้ในการเปรียบเทียบ
    $new_dates = array_map('intval', array_map('trim', explode(',', $available_dates)));
    sort($new_dates); // จัดเรียงวันที่เพื่อให้เปรียบเทียบได้ง่าย
    $new_dates_str = implode(', ', $new_dates); // แปลงกลับเป็นสตริงหลังจัดเรียง

    // ตรวจสอบว่ามีตารางอื่นที่มี year, month, และ available_dates ซ้ำกันหรือไม่ (ยกเว้นตารางที่กำลังแก้ไข)
    $stmt = $conn->prepare("SELECT * FROM transport_schedule WHERE year = :year AND month = :month AND id != :id");
    $stmt->bindParam(':year', $year);
    $stmt->bindParam(':month', $month);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $existing_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($existing_schedules as $schedule) {
        $existing_dates = array_map('intval', array_map('trim', explode(',', $schedule['available_dates'])));
        sort($existing_dates);
        $existing_dates_str = implode(', ', $existing_dates);

        if ($new_dates_str === $existing_dates_str) {
            echo json_encode([
                'status' => 'error',
                'message' => "ตารางสำหรับ ปี $year เดือน $month และวันที่ $new_dates_str มีอยู่ในระบบแล้ว"
            ]);
            exit();
        }
    }

    // ถ้าไม่ซ้ำ ให้อัปเดตข้อมูล
    $stmt = $conn->prepare("UPDATE transport_schedule SET year = :year, month = :month, available_dates = :available_dates, num_of_days = :num_of_days WHERE id = :id");
    $stmt->bindParam(':year', $year);
    $stmt->bindParam(':month', $month);
    $stmt->bindParam(':available_dates', $new_dates_str);
    $stmt->bindParam(':num_of_days', $num_of_days);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    echo json_encode([
        'status' => 'success',
        'message' => 'แก้ไขตารางเรียบร้อยแล้ว'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
exit();
?>