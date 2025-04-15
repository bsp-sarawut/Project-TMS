<?php
session_start();
require_once 'config/condb.php';

// ฟังก์ชันแปลงวันที่เป็นรูปแบบภาษาไทย
function getFormattedThaiDate($date, $show_time = true) {
    if (!$date) return 'ไม่ระบุ';
    $dateTime = new DateTime($date);
    $day = $dateTime->format('j');
    $monthNames = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    $month = $monthNames[(int)$dateTime->format('n')];
    $year = $dateTime->format('Y') + 543;
    if ($show_time) {
        return "$day $month $year " . $dateTime->format('H:i:s');
    }
    return "$day $month $year";
}

// ฟังก์ชันสำหรับ export CSV
function exportToCSV($data, $filename, $student_logs = []) {
    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Cache-Control: max-age=0');

    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $output = fopen('php://output', 'w');

    fputcsv($output, [
        "Queue ID",
        "วันที่คิว",
        "รถ",
        "จังหวัด",
        "อำเภอ",
        "สถานะ",
        "วันที่และเวลา",
        "ช่วง"
    ]);

    foreach ($data as $queue) {
        $queue_id_current = $queue['queue_id'];
        $is_first_phase = true;
        $is_second_phase = false;
        $arrived_count = 0;

        fputcsv($output, ["ขาไปมหาวิทยาลัย"]);
        foreach ($queue['logs'] as $log) {
            if ($log['status_car'] === 'ถึงที่หมาย') {
                $arrived_count++;
            }

            if ($arrived_count === 1 && $log['status_car'] === 'ถึงที่หมาย') {
                $is_first_phase = false;
            } elseif ($arrived_count === 1 && !$is_first_phase && $log['status_car'] === 'ถึงจุดรับ') {
                $is_second_phase = true;
                fputcsv($output, ["ขากลับจากมหาวิทยาลัย"]);
            }

            $phase = $is_first_phase ? "ขาไปมหาวิทยาลัย" : ($is_second_phase ? "ขากลับจากมหาวิทยาลัย" : "ขาไปมหาวิทยาลัย");
            if ($arrived_count >= 2) {
                $phase = "ขากลับจากมหาวิทยาลัย";
            }

            $row = [
                $queue_id_current,
                getFormattedThaiDate($queue['queue_date'], false),
                htmlspecialchars($queue['car_brand'] . " (" . $queue['car_license'] . ", " . $queue['car_color'] . ")"),
                htmlspecialchars($queue['PROVINCE_NAME']),
                htmlspecialchars($queue['AMPHUR_NAME']),
                htmlspecialchars($log['status_car']),
                getFormattedThaiDate($log['log_timestamp'], true),
                $phase
            ];

            $row = array_map(function($value) {
                return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }, $row);

            fputcsv($output, $row);
        }

        if (isset($student_logs[$queue_id_current]) && !empty($student_logs[$queue_id_current])) {
            fputcsv($output, []);
            fputcsv($output, ["Student Logs for Queue #$queue_id_current"]);
            fputcsv($output, ["ชื่อ", "สถานะ", "วันที่และเวลา", "ช่วง"]);

            // แยกข้อมูลนักเรียนเป็นขาไปและขากลับ
            $first_phase_students = [];
            $second_phase_students = [];
            $is_first_phase = true;
            $is_second_phase = false;
            $arrived_count = 0;

            foreach ($queue['logs'] as $log) {
                if ($log['status_car'] === 'ถึงที่หมาย') {
                    $arrived_count++;
                }
                if ($arrived_count === 1 && $log['status_car'] === 'ถึงที่หมาย') {
                    $is_first_phase = false;
                } elseif ($arrived_count === 1 && !$is_first_phase && $log['status_car'] === 'ถึงจุดรับ') {
                    $is_second_phase = true;
                }
            }

            // เก็บข้อมูลนักเรียนตามช่วง
            foreach ($student_logs[$queue_id_current] as $student_log) {
                $student_timestamp = new DateTime($student_log['log_timestamp']);
                $is_first_phase_for_student = true;
                $is_second_phase_for_student = false;
                $arrived_count_for_student = 0;

                foreach ($queue['logs'] as $log) {
                    $log_timestamp = new DateTime($log['log_timestamp']);
                    if ($log['status_car'] === 'ถึงที่หมาย') {
                        $arrived_count_for_student++;
                    }
                    if ($arrived_count_for_student === 1 && $log['status_car'] === 'ถึงที่หมาย') {
                        $is_first_phase_for_student = false;
                    } elseif ($arrived_count_for_student === 1 && !$is_first_phase_for_student && $log['status_car'] === 'ถึงจุดรับ') {
                        $is_second_phase_for_student = true;
                    }

                    if ($student_timestamp <= $log_timestamp) {
                        break;
                    }
                }

                if ($is_first_phase_for_student) {
                    $first_phase_students[] = $student_log;
                } else {
                    $second_phase_students[] = $student_log;
                }
            }

            // ลบข้อมูลซ้ำโดยเก็บสถานะล่าสุดของนักเรียนแต่ละคน
            $processed_students = []; // เก็บข้อมูลนักเรียนที่ประมวลผลแล้ว (ชื่อ + สถานะ)

            // ขาไป
            if (!empty($first_phase_students)) {
                fputcsv($output, ["ขาไปมหาวิทยาลัย"]);
                $unique_first_phase = [];
                foreach ($first_phase_students as $student_log) {
                    $student_name = (isset($student_log['stu_name']) && isset($student_log['stu_lastname'])) 
                        ? htmlspecialchars($student_log['stu_name'] . ' ' . $student_log['stu_lastname']) 
                        : 'ไม่พบชื่อนักเรียน (ID: ' . htmlspecialchars($student_log['student_id']) . ')';
                    $key = $student_name . '|' . $student_log['stu_status']; // ใช้ชื่อและสถานะเป็น key เพื่อตรวจสอบการซ้ำ

                    if (!isset($unique_first_phase[$key])) {
                        $unique_first_phase[$key] = $student_log;
                    } elseif (new DateTime($student_log['log_timestamp']) > new DateTime($unique_first_phase[$key]['log_timestamp'])) {
                        $unique_first_phase[$key] = $student_log; // เก็บข้อมูลล่าสุด
                    }
                }

                foreach ($unique_first_phase as $student_log) {
                    $student_name = (isset($student_log['stu_name']) && isset($student_log['stu_lastname'])) 
                        ? htmlspecialchars($student_log['stu_name'] . ' ' . $student_log['stu_lastname']) 
                        : 'ไม่พบชื่อนักเรียน (ID: ' . htmlspecialchars($student_log['student_id']) . ')';
                    
                    $student_row = [
                        $student_name,
                        htmlspecialchars($student_log['stu_status']),
                        getFormattedThaiDate($student_log['log_timestamp'], true),
                        "ขาไปมหาวิทยาลัย"
                    ];

                    $student_row = array_map(function($value) {
                        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }, $student_row);

                    fputcsv($output, $student_row);
                }
            }

            // ขากลับ
            if (!empty($second_phase_students)) {
                fputcsv($output, ["ขากลับจากมหาวิทยาลัย"]);
                $unique_second_phase = [];
                foreach ($second_phase_students as $student_log) {
                    $student_name = (isset($student_log['stu_name']) && isset($student_log['stu_lastname'])) 
                        ? htmlspecialchars($student_log['stu_name'] . ' ' . $student_log['stu_lastname']) 
                        : 'ไม่พบชื่อนักเรียน (ID: ' . htmlspecialchars($student_log['student_id']) . ')';
                    $key = $student_name . '|' . $student_log['stu_status'];

                    if (!isset($unique_second_phase[$key])) {
                        $unique_second_phase[$key] = $student_log;
                    } elseif (new DateTime($student_log['log_timestamp']) > new DateTime($unique_second_phase[$key]['log_timestamp'])) {
                        $unique_second_phase[$key] = $student_log; // เก็บข้อมูลล่าสุด
                    }
                }

                foreach ($unique_second_phase as $student_log) {
                    $student_name = (isset($student_log['stu_name']) && isset($student_log['stu_lastname'])) 
                        ? htmlspecialchars($student_log['stu_name'] . ' ' . $student_log['stu_lastname']) 
                        : 'ไม่พบชื่อนักเรียน (ID: ' . htmlspecialchars($student_log['student_id']) . ')';
                    
                    $student_row = [
                        $student_name,
                        htmlspecialchars($student_log['stu_status']),
                        getFormattedThaiDate($student_log['log_timestamp'], true),
                        "ขากลับจากมหาวิทยาลัย"
                    ];

                    $student_row = array_map(function($value) {
                        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }, $student_row);

                    fputcsv($output, $student_row);
                }
            }
        }
        fputcsv($output, []);
    }

    fclose($output);
    exit();
}

// ตรวจสอบว่าได้รับ queue_id หรือไม่
if (!isset($_GET['queue_id']) || empty($_GET['queue_id'])) {
    $_SESSION['error'] = "ไม่พบ Queue ID";
    header("location: queue_log.php");
    exit();
}

$queue_id = (int)$_GET['queue_id'];

// ดึงข้อมูลคิวที่เลือก
try {
    $sql = "SELECT q.queue_id, q.created_at, q.queue_date, p.PROVINCE_NAME, a.AMPHUR_NAME, 
                   c.car_license, c.car_brand, c.car_color
            FROM queue q
            JOIN province p ON q.province_id = p.PROVINCE_ID
            JOIN amphur a ON q.amphur_id = a.AMPHUR_ID
            JOIN car c ON q.car_id = c.car_id
            WHERE q.status_car = 'ปิดงาน' AND q.queue_id = :queue_id";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':queue_id', $queue_id, PDO::PARAM_INT);
    $stmt->execute();
    $queue = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$queue) {
        $_SESSION['error'] = "ไม่พบข้อมูลคิวที่ระบุ";
        header("location: queue_log.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: queue_log.php");
    exit();
}

// ดึงข้อมูลจาก queue_log
$queue_logs = [];
try {
    $stmt_queue_logs = $conn->prepare(
        "SELECT queue_id, status_car, log_timestamp
         FROM queue_log
         WHERE queue_id = :queue_id
         ORDER BY log_timestamp ASC"
    );
    $stmt_queue_logs->bindValue(':queue_id', $queue_id, PDO::PARAM_INT);
    $stmt_queue_logs->execute();
    $queue_logs = $stmt_queue_logs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: queue_log.php");
    exit();
}

// ดึงข้อมูล student_status_log พร้อมชื่อนักเรียน
$student_logs = [];
try {
    $stmt_student_logs = $conn->prepare(
        "SELECT `ssl`.`queue_id`, `ssl`.`student_id`, `ssl`.`stu_status`, `ssl`.`log_timestamp`,
                `s`.`stu_name`, `s`.`stu_lastname`
        FROM `student_status_log` `ssl`
        LEFT JOIN `students` `s` ON `ssl`.`student_id` = `s`.`stu_ID`
        WHERE `ssl`.`queue_id` = :queue_id
        ORDER BY `ssl`.`log_timestamp` ASC"
    );
    $stmt_student_logs->bindValue(':queue_id', $queue_id, PDO::PARAM_INT);
    $stmt_student_logs->execute();
    $student_log_data = $stmt_student_logs->fetchAll(PDO::FETCH_ASSOC);

    foreach ($student_log_data as $log) {
        $student_logs[$queue_id][] = $log;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: queue_log.php");
    exit();
}

// รวมข้อมูลคิวและ logs เพื่อใช้ใน export
$export_data = [];
$queue['logs'] = $queue_logs;
$export_data[] = $queue;

// เรียกฟังก์ชัน export
exportToCSV($export_data, "queue_" . $queue_id . "_" . date('Ymd') . ".csv", $student_logs);
?>