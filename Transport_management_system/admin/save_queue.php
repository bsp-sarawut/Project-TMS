<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config/condb.php';
ob_end_clean();

echo '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บันทึกคิว</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['date_picker'], $_POST['province'], $_POST['amphur'], $_POST['location'], $_POST['car'], $_POST['students'])) {
        $date_picker = $_POST['date_picker'];
        $province = $_POST['province'];
        $amphur = $_POST['amphur'];
        $location = $_POST['location'];
        $car = $_POST['car'];
        $students = $_POST['students'];
        $status_car = isset($_POST['status_car']) ? $_POST['status_car'] : 'ว่าง';

        $dates = explode(' to ', $date_picker);
        $start_date = $dates[0];
        $end_date = count($dates) === 2 ? $dates[1] : $dates[0];

        try {
            $conn->beginTransaction();

            $current_date = new DateTime($start_date);
            $end_date_obj = new DateTime($end_date);

            while ($current_date <= $end_date_obj) {
                $queue_date = $current_date->format('Y-m-d');
                $created_at = date('Y-m-d H:i:s');

                $checkCar = $conn->prepare("SELECT COUNT(*) FROM queue WHERE car_id = :car_id AND queue_date = :queue_date");
                $checkCar->execute([':car_id' => $car, ':queue_date' => $queue_date]);
                if ($checkCar->fetchColumn() > 0) {
                    throw new Exception("ยานพาหนะนี้ถูกจองสำหรับวันที่ $queue_date แล้ว");
                }

                $sql = "INSERT INTO queue (province_id, amphur_id, location, car_id, created_at, year, status_car, queue_date)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$province, $amphur, $location, $car, $created_at, date('Y'), $status_car, $queue_date]);

                $queue_id = $conn->lastInsertId();

                foreach ($students as $stu_id) {
                    $checkStudent = $conn->prepare("SELECT COUNT(*) FROM queue_student qs 
                                                    JOIN queue q ON qs.queue_id = q.queue_id 
                                                    WHERE qs.student_id = :student_id AND q.queue_date = :queue_date");
                    $checkStudent->execute([':student_id' => $stu_id, ':queue_date' => $queue_date]);
                    if ($checkStudent->fetchColumn() == 0) {
                        $sql_student = "INSERT INTO queue_student (queue_id, student_id) VALUES (?, ?)";
                        $stmt_student = $conn->prepare($sql_student);
                        $stmt_student->execute([$queue_id, $stu_id]);
                    }
                }

                $current_date->modify('+1 day');
            }

            $conn->commit();

            // Debug เพิ่มเติม
            echo '<script>
                console.log("Before commit");
                console.log("After commit, before Swal");
                Swal.fire({
                    icon: "success",
                    title: "บันทึกข้อมูลสำเร็จ",
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    console.log("Redirecting to queue.php");
                    window.location.href = "queue.php";
                });
                console.log("After Swal call");
            </script>';

        } catch (Exception $e) {
            $conn->rollBack();
            echo '<script>
                console.log("Error: ' . addslashes($e->getMessage()) . '");
                Swal.fire({
                    icon: "error",
                    title: "เกิดข้อผิดพลาด",
                    text: "' . addslashes($e->getMessage()) . '",
                    confirmButtonText: "ตกลง"
                });
            </script>';
        }
    } else {
        echo '<script>
            console.log("Warning: Data incomplete");
            Swal.fire({
                icon: "warning",
                title: "ข้อมูลไม่ครบถ้วน",
                text: "กรุณากรอกข้อมูลให้ครบทุกช่อง",
                confirmButtonText: "ตกลง"
            });
        </script>';
    }
} else {
    header('Location: queue.php');
    exit();
}

echo '</body></html>';
exit();
?>