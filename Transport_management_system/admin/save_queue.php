<?php
require_once 'config/condb.php';
?>
<head>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php
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

                // ตรวจสอบว่ายานพาหนะถูกจองสำหรับวันที่นี้หรือยัง
                $checkCar = $conn->prepare("SELECT COUNT(*) FROM queue WHERE car_id = :car_id AND queue_date = :queue_date");
                $checkCar->execute([':car_id' => $car, ':queue_date' => $queue_date]);
                if ($checkCar->fetchColumn() > 0) {
                    throw new Exception("ยานพาหนะนี้ถูกจองสำหรับวันที่ $queue_date แล้ว");
                }

                // บันทึกข้อมูลลงตาราง queue
                $sql = "INSERT INTO queue (province_id, amphur_id, location, car_id, created_at, year, status_car, queue_date)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$province, $amphur, $location, $car, $created_at, date('Y'), $status_car, $queue_date]);

                $queue_id = $conn->lastInsertId();

                // บันทึกนักเรียน
                foreach ($students as $stu_id) {
                    // ตรวจสอบว่านักเรียนถูกจองสำหรับวันที่นี้หรือยัง
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

            echo '<script>
                Swal.fire({
                    icon: "success",
                    title: "บันทึกข้อมูลสำเร็จ",
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    window.location.href = "queue.php";
                });
            </script>';

        } catch (Exception $e) {
            $conn->rollBack();
            echo '<script>
                Swal.fire({
                    icon: "error",
                    title: "เกิดข้อผิดพลาด",
                    text: "' . $e->getMessage() . '",
                    confirmButtonText: "ตกลง"
                });
            </script>';
        }
    } else {
        echo '<script>
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
?>
</body>