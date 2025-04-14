<?php
include 'config/condb.php';
session_start();

header('Content-Type: application/json');

try {
    // รับข้อมูลจากฟอร์ม
    $year = isset($_POST['year']) ? trim($_POST['year']) : '';
    $month = isset($_POST['month']) ? trim($_POST['month']) : '';
    $available_dates = isset($_POST['available_dates']) ? trim($_POST['available_dates']) : '';
    $num_of_days = isset($_POST['num_of_days']) ? trim($_POST['num_of_days']) : '';

    // ตรวจสอบข้อมูลที่ส่งมา
    if (empty($year) || empty($month) || empty($available_dates) || empty($num_of_days)) {
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

    // ตรวจสอบว่ามีตารางที่มี year, month, และ available_dates ซ้ำกันหรือไม่
    $stmt = $conn->prepare("SELECT * FROM transport_schedule WHERE year = :year AND month = :month");
    $stmt->bindParam(':year', $year);
    $stmt->bindParam(':month', $month);
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

    // ถ้าไม่ซ้ำ ให้เพิ่มข้อมูล
    $stmt = $conn->prepare("INSERT INTO transport_schedule (year, month, available_dates, num_of_days) VALUES (:year, :month, :available_dates, :num_of_days)");
    $stmt->bindParam(':year', $year);
    $stmt->bindParam(':month', $month);
    $stmt->bindParam(':available_dates', $new_dates_str);
    $stmt->bindParam(':num_of_days', $num_of_days);
    $stmt->execute();

    echo json_encode([
        'status' => 'success',
        'message' => 'เพิ่มตารางเรียบร้อยแล้ว'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
exit();
?>