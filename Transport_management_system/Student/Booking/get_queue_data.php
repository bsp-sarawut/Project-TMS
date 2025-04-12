<?php
session_start();
require_once '../config/condb.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_name'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$stu_username = $_SESSION['user_name'];

function getMonthName($monthNumber) {
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
    ];
    return $months[$monthNumber] ?? 'ไม่ทราบเดือน';
}

$sql_queues = "SELECT DISTINCT q.queue_id
               FROM queue q
               INNER JOIN queue_student qs ON q.queue_id = qs.queue_id
               INNER JOIN students s ON qs.student_id = s.stu_ID
               WHERE s.stu_username = :stu_username";

try {
    $stmt_queues = $conn->prepare($sql_queues);
    $stmt_queues->bindParam(':stu_username', $stu_username, PDO::PARAM_STR);
    $stmt_queues->execute();
    $queue_ids = $stmt_queues->fetchAll(PDO::FETCH_COLUMN);

    $queues = [];
    if (!empty($queue_ids)) {
        $queue_ids_str = implode(',', array_map('intval', $queue_ids));
        $sql = "SELECT q.queue_id, q.location, q.queue_date, q.status_car,
                       p.PROVINCE_NAME,
                       c.car_license,
                       s.stu_ID, s.stu_name, s.stu_lastname, s.stu_tel, s.stu_faculty, s.stu_status,
                       d.driver_name, d.driver_lastname, d.driver_tel
                FROM queue q
                INNER JOIN queue_student qs ON q.queue_id = qs.queue_id
                INNER JOIN students s ON qs.student_id = s.stu_ID
                INNER JOIN province p ON q.province_id = p.PROVINCE_ID
                INNER JOIN car c ON q.car_id = c.car_id
                LEFT JOIN driver d ON c.driver_id = d.driver_id
                WHERE q.queue_id IN ($queue_ids_str)
                ORDER BY q.queue_date DESC, q.queue_id, s.stu_ID";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $queue_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped_data = [];
        foreach ($queue_data as $row) {
            $grouped_data[$row['queue_id']][] = $row;
        }

        foreach ($grouped_data as $queue_id => $rows) {
            $header_data = $rows[0];
            $formatted_date = 'ไม่ระบุ';
            if ($header_data['queue_date']) {
                $date = new DateTime($header_data['queue_date']);
                $day = $date->format('j');
                $month = getMonthName($date->format('n'));
                $year = $date->format('Y') + 543;
                $formatted_date = "$day $month $year";
            }

            $queues[] = [
                'queue_id' => $queue_id,
                'location' => $header_data['location'],
                'queue_date' => $header_data['queue_date'],
                'formatted_date' => $formatted_date,
                'status_car' => $header_data['status_car'],
                'PROVINCE_NAME' => $header_data['PROVINCE_NAME'],
                'car_license' => $header_data['car_license'],
                'driver_name' => $header_data['driver_name'],
                'driver_lastname' => $header_data['driver_lastname'],
                'driver_tel' => $header_data['driver_tel'],
                'students' => array_map(function($row) {
                    return [
                        'stu_ID' => $row['stu_ID'],
                        'stu_name' => $row['stu_name'],
                        'stu_lastname' => $row['stu_lastname'],
                        'stu_tel' => $row['stu_tel'],
                        'stu_faculty' => $row['stu_faculty'],
                        'stu_status' => $row['stu_status'],
                    ];
                }, $rows)
            ];
        }
    }

    echo json_encode(['success' => true, 'queues' => $queues]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>