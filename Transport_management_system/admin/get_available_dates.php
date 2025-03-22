<?php
require_once 'config/condb.php';

// ตรวจสอบว่ามีการส่งข้อมูลเดือนและปีมาหรือไม่
if (isset($_GET['month_year'])) {
    $month_year = $_GET['month_year'];
    list($month, $year) = explode('-', $month_year); // แยกเดือนและปี

    // ดึงข้อมูลวันที่จาก transport_schedule
    $scheduleQuery = $conn->prepare("SELECT available_dates FROM transport_schedule WHERE month = :month AND year = :year ORDER BY available_dates DESC");
    $scheduleQuery->bindParam(':month', $month, PDO::PARAM_INT);
    $scheduleQuery->bindParam(':year', $year, PDO::PARAM_INT);
    $scheduleQuery->execute();

    $availableDates = $scheduleQuery->fetchAll(PDO::FETCH_ASSOC);

    // ส่งข้อมูลวันที่กลับไปยังฟอร์ม
    if ($availableDates) {
        foreach ($availableDates as $date) {
            echo '<option value="' . $date['available_dates'] . '">' . $date['available_dates'] . '</option>';
        }
    } else {
        echo '<option value="">ไม่มีวันที่สำหรับเดือนนี้</option>';
    }
}
?>
