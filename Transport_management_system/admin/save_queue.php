<head>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>>
<body>
<?php

require_once 'config/condb.php';

// ตรวจสอบว่า status_car ถูกส่งมาหรือไม่ ถ้าไม่ส่งมาหรือค่าว่าง จะตั้งค่าเป็น "ว่าง"
$status_car = isset($_POST['status_car']) ? $_POST['status_car'] : 'ว่าง';

// ตรวจสอบการส่งข้อมูลจากฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        isset($_POST['date_picker'], $_POST['province'], $_POST['amphur'], $_POST['location'], $_POST['car'], $_POST['students'])
    ) {
        $date_picker = $_POST['date_picker']; // ค่าที่เลือกจากปฏิทิน (ช่วงวันที่)
        $province = $_POST['province']; // จังหวัด
        $amphur = $_POST['amphur']; // อำเภอ
        $location = $_POST['location']; // จุดขึ้นรถ
        $car = $_POST['car']; // รถ
        $students = $_POST['students']; // อาร์เรย์ของนักเรียนที่เลือก

        // แยกวันที่จากช่วงวันที่ที่เลือกด้วย Flatpickr
        $dates = explode(' to ', $date_picker); // Flatpickr จะส่งรูปแบบ "YYYY-MM-DD to YYYY-MM-DD"

        if (count($dates) === 2) {
            $start_date = $dates[0];
            $end_date = $dates[1];
        } else {
            $start_date = $end_date = $dates[0]; // ถ้าเลือกวันเดียว
        }

        try {
            $conn->beginTransaction(); // เริ่มต้น Transaction

            // เพิ่มข้อมูลลงในตาราง queue สำหรับแต่ละวันที่เลือก
            $current_date = new DateTime($start_date);
            $end_date_obj = new DateTime($end_date);

            while ($current_date <= $end_date_obj) {
                $queue_date = $current_date->format('Y-m-d');
                $created_at = date('Y-m-d H:i:s');

                $sql = "INSERT INTO queue (province_id, amphur_id, location, car_id, created_at, year, status_car, queue_date)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $province,
                    $amphur,
                    $location,
                    $car,
                    $created_at,
                    date('Y'), // ปีปัจจุบัน
                    $status_car, // ใช้ค่า status_car ที่ตรวจสอบแล้ว
                    $queue_date
                ]);

                if ($stmt->rowCount() > 0) {
                    $queue_id = $conn->lastInsertId();

                    // บันทึกนักเรียนแต่ละคนลงใน queue_student
                    foreach ($students as $stu_id) {
                        $sql_student = "INSERT INTO queue_student (queue_id, student_id) VALUES (?, ?)";
                        $stmt_student = $conn->prepare($sql_student);
                        $stmt_student->execute([$queue_id, $stu_id]);
                    }
                }
                $current_date->modify('+1 day'); // ไปยังวันถัดไป
            }

            $conn->commit(); // ยืนยันการทำธุรกรรม

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

        } catch (PDOException $e) {
            $conn->rollBack(); // ยกเลิกการทำธุรกรรมถ้ามีข้อผิดพลาด
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
    header('Location:queue.php'); // ถ้าเข้าถึงไฟล์โดยตรงให้ redirect กลับ
    exit();
}

?>