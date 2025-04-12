<?php
session_start();
require_once("config/condb.php");

// เปิดการแสดงข้อผิดพลาด (ใช้เฉพาะตอนพัฒนา)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['driver_user'])) {
    echo <<<EOD
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'ไม่ได้รับอนุญาต',
                text: 'กรุณาเข้าสู่ระบบก่อน',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.location.href = 'driver_signin.php';
            });
        });
    </script>
    EOD;
    exit;
}

$driver_id = isset($_SESSION['driver_id']) ? $_SESSION['driver_id'] : null;
if (!$driver_id) {
    try {
        $stmt = $conn->prepare("SELECT driver_id FROM driver WHERE driver_user = :driver_user");
        $stmt->bindParam(':driver_user', $_SESSION['driver_user']);
        $stmt->execute();
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);
        $driver_id = $driver ? $driver['driver_id'] : null;
    } catch (Exception $e) {
        $driver_id = null;
    }
}

if (!$driver_id) {
    echo <<<EOD
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'ข้อผิดพลาด',
                text: 'ไม่พบข้อมูลคนขับ กรุณาติดต่อผู้ดูแลระบบ',
                confirmButtonText: 'ตกลง'
            });
        });
    </script>
    EOD;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_car']) && isset($_POST['queue_id'])) {
    try {
        $status_car = $_POST['status_car'];
        $queue_id = (int)$_POST['queue_id'];
        $valid_statuses = ['ว่าง', 'ถึงจุดรับ', 'ออกเดินทาง', 'ถึงที่หมาย', 'ปิดงาน'];
        if (!in_array($status_car, $valid_statuses)) {
            throw new Exception('สถานะรถไม่ถูกต้อง');
        }

        $check_stmt = $conn->prepare("
            SELECT q.queue_id
            FROM queue q
            JOIN car c ON q.car_id = c.car_id
            WHERE q.queue_id = :queue_id AND c.driver_id = :driver_id
        ");
        $check_stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
        $check_stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
        $check_stmt->execute();
        if (!$check_stmt->fetch()) {
            throw new Exception('คิวนี้ไม่เกี่ยวข้องกับคนขับ');
        }

        $conn->beginTransaction();

        $stmt = $conn->prepare("
            UPDATE queue
            SET status_car = :status_car
            WHERE queue_id = :queue_id
        ");
        $stmt->bindParam(':status_car', $status_car, PDO::PARAM_STR);
        $stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
        $stmt->execute();

        if (in_array($status_car, ['ถึงจุดรับ', 'ออกเดินทาง', 'ถึงที่หมาย', 'ปิดงาน'])) {
            $log_stmt = $conn->prepare("
                INSERT INTO queue_log (queue_id, status_car, log_timestamp)
                VALUES (:queue_id, :status_car, NOW())
            ");
            $log_stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
            $log_stmt->bindParam(':status_car', $status_car, PDO::PARAM_STR);
            $log_stmt->execute();
        }

        if ($status_car === 'ปิดงาน') {
            $students_stmt = $conn->prepare("
                SELECT s.stu_ID, s.stu_status
                FROM queue_student qs
                JOIN students s ON qs.student_id = s.stu_ID
                WHERE qs.queue_id = :queue_id
            ");
            $students_stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
            $students_stmt->execute();
            $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($students as $student) {
                $stu_status = $student['stu_status'] ?: 'ขาด';
                $log_stmt = $conn->prepare("
                    INSERT INTO student_status_log (queue_id, student_id, stu_status, log_timestamp)
                    VALUES (:queue_id, :student_id, :stu_status, NOW())
                ");
                $log_stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
                $log_stmt->bindParam(':student_id', $student['stu_ID'], PDO::PARAM_INT);
                $log_stmt->bindParam(':stu_status', $stu_status, PDO::PARAM_STR);
                $log_stmt->execute();
            }

            $delete_qs_stmt = $conn->prepare("DELETE FROM queue_student WHERE queue_id = :queue_id");
            $delete_qs_stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
            $delete_qs_stmt->execute();

            $delete_ql_stmt = $conn->prepare("DELETE FROM queue_log WHERE queue_id = :queue_id");
            $delete_ql_stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
            $delete_ql_stmt->execute();

            $delete_ssl_stmt = $conn->prepare("DELETE FROM student_status_log WHERE queue_id = :queue_id");
            $delete_ssl_stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
            $delete_ssl_stmt->execute();

            $delete_q_stmt = $conn->prepare("DELETE FROM queue WHERE queue_id = :queue_id");
            $delete_q_stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
            $delete_q_stmt->execute();
        }

        $conn->commit();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'อัปเดตสถานะรถสำเร็จ' . ($status_car === 'ปิดงาน' ? ' และลบข้อมูลคิวเรียบร้อย' : '')]);
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

$queues = [];
function getMonthName($monthNumber) {
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
    ];
    return $months[$monthNumber] ?? 'ไม่ทราบเดือน';
}
try {
    // ตรวจสอบ $driver_id
    if (empty($driver_id)) {
        throw new Exception('Driver ID is empty or invalid');
    }
    echo "<!-- Debugging: Driver ID = $driver_id -->";

    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $stmt = $conn->prepare("
        SELECT q.queue_id, s.stu_ID, s.stu_name, s.stu_lastname, s.stu_tel, 
               q.location, q.queue_date, q.status_car, 
               s.stu_status,
               p.PROVINCE_NAME,
               a.AMPHUR_NAME,
               (SELECT COUNT(DISTINCT qs2.student_id) 
                FROM queue_student qs2 
                WHERE qs2.queue_id = q.queue_id) AS student_count
        FROM queue q
        JOIN car c ON q.car_id = c.car_id
        LEFT JOIN queue_student qs ON q.queue_id = qs.queue_id
        LEFT JOIN students s ON qs.student_id = s.stu_ID
        LEFT JOIN province p ON q.province_id = p.PROVINCE_ID
        LEFT JOIN amphur a ON q.amphur_id = a.AMPHUR_ID
        WHERE c.driver_id = :driver_id
    ");
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->execute();
    $queues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped_queues = [];
    $current_date = new DateTime();
    foreach ($queues as $queue) {
        $queue_id = $queue['queue_id'];
        $grouped_queues[$queue_id][] = $queue;
    }

    $queue_with_distance = [];
    $queue_dates = [];
    foreach ($grouped_queues as $queue_id => $queue_rows) {
        $queue_date_str = $queue_rows[0]['queue_date'];
        if ($queue_date_str) {
            $queue_date = new DateTime($queue_date_str);
            $interval = $current_date->diff($queue_date);
            $days_difference = $interval->days;
            if ($queue_date < $current_date) {
                $days_difference = -$days_difference;
            }
            $queue_dates[] = $queue_date->format('Y-m-d');
        } else {
            $days_difference = PHP_INT_MAX;
        }
        $queue_with_distance[] = [
            'queue_id' => $queue_id,
            'days_difference' => $days_difference,
            'queue_date' => $queue_date_str ? new DateTime($queue_date_str) : null,
            'rows' => $queue_rows
        ];
    }

    usort($queue_with_distance, function($a, $b) {
        if ($a['days_difference'] === $b['days_difference']) {
            return $b['queue_id'] <=> $a['queue_id'];
        }
        return $a['days_difference'] <=> $b['days_difference'];
    });

    $queues_per_page = 1;
    $total_queues = count($queue_with_distance);
    $total_pages = ceil($total_queues / $queues_per_page);
    $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $start_index = ($current_page - 1) * $queues_per_page;
    $end_index = min($start_index + $queues_per_page, $total_queues);
    $paginated_queues = array_slice($queue_with_distance, $start_index, $queues_per_page);

    $logs = [];
    $student_logs = [];
    foreach (array_unique(array_column($queues, 'queue_id')) as $queue_id) {
        $log_stmt = $conn->prepare("
            SELECT status_car, log_timestamp
            FROM queue_log
            WHERE queue_id = :queue_id
            ORDER BY log_timestamp ASC
        ");
        $log_stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
        $log_stmt->execute();
        $logs[$queue_id] = $log_stmt->fetchAll(PDO::FETCH_ASSOC);

        $student_log_stmt = $conn->prepare("
            SELECT s.stu_name, s.stu_lastname, ssl.stu_status, ssl.log_timestamp
            FROM student_status_log ssl
            JOIN students s ON ssl.student_id = s.stu_ID
            WHERE ssl.queue_id = :queue_id
            ORDER BY ssl.log_timestamp ASC
        ");
        $student_log_stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
        $student_log_stmt->execute();
        $student_logs[$queue_id] = $student_log_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $errorMessage = htmlspecialchars($e->getMessage());
    echo <<<EOD
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถดึงข้อมูลคิวงานได้: $errorMessage',
                confirmButtonText: 'ตกลง'
            });
        });
    </script>
    EOD;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>ดูคิวงาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/flatpickr/flatpickr.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/flatpickr/flatpickr.js"></script>
    <script src="assets/flatpickr/th.js"></script>
    <style>
        body {
            background: #f5f7fa;
            font-family: 'IBM Plex Sans Thai', sans-serif;
            min-height: 100vh;
            padding-top: 80px;
        }
        .container {
            max-width: 100%;
            margin: auto;
            padding: 15px;
        }
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-body {
            padding: 20px;
        }
        .card-title {
            font-size: 1.6rem;
            color: #1a1a1a;
            font-weight: 600;
            text-align: center;
            margin-bottom: 15px;
        }
        .queue-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        .queue-details p {
            margin: 5px 0;
        }
        .queue-details strong {
            color: #003087;
        }
        .table {
            font-size: 0.9rem;
            color: #4a4a4a;
            width: 100%;
            table-layout: fixed;
        }
        .table th {
            background-color: #003087;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 10px;
        }
        .table td {
            vertical-align: middle;
            padding: 8px;
            font-size: 0.85rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .table th:nth-child(1), .table td:nth-child(1) { width: 40%; }
        .table th:nth-child(2), .table td:nth-child(2) { width: 30%; }
        .table th:nth-child(3), .table td:nth-child(3) { width: 30%; }
        .status-student-idle {
            background-color: #6c757d;
            color: #fff;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
        }
        .status-student-no-show {
            background-color: #dc3545;
            color: #fff;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
        }
        .status-student-absent {
            background-color: #ff6f61;
            color: #fff;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
        }
        .status-student-boarded {
            background-color: #28a745;
            color: #fff;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
        }
        .status-student-finished {
            background-color: #17a2b8;
            color: #fff;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
        }
        .no-data {
            text-align: center;
            color: #6c757d;
            font-size: 1rem;
            padding: 15px;
        }
        .status-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
            justify-content: center;
        }
        .status-btn {
            font-weight: 500;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            transition: background-color 0.3s, transform 0.2s;
            flex: 1;
            max-width: 120px;
            text-align: center;
            color: #ffffff;
            font-size: 0.85rem;
        }
        .status-btn:hover {
            transform: scale(1.05);
        }
        .status-btn.active {
            filter: brightness(120%);
            font-weight: 600;
        }
        .status-vacant {
            background-color: #6c757d;
        }
        .status-vacant:hover {
            background-color: #5a6268;
        }
        .status-arrived {
            background-color: #28a745;
        }
        .status-arrived:hover {
            background-color: #218838;
        }
        .status-departed {
            background-color: #ffc107;
        }
        .status-departed:hover {
            background-color: #e0a800;
        }
        .status-destination {
            background-color: #17a2b8;
        }
        .status-destination:hover {
            background-color: #138496;
        }
        .status-closed {
            background-color: #dc3545;
        }
        .status-closed:hover {
            background-color: #c82333;
        }
        .current-status {
            text-align: center;
            margin-bottom: 15px;
            font-size: 1rem;
            color: #003087;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .current-status select {
            background-color: #ffffff;
            border: 1px solid #003087;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 0.9rem;
            color: #4a4a4a;
            max-width: 150px;
        }
        .current-status select:focus {
            outline: none;
            border-color: #28a745;
        }
        .log-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 0.9rem;
        }
        .log-section h6 {
            color: #003087;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .log-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .log-section li {
            padding: 5px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .log-section li:last-child {
            border-bottom: none;
        }
        .log-section .status-label {
            font-weight: 500;
            color: #4a4a4a;
        }
        .log-section .timestamp {
            color: #6c757d;
            font-size: 0.85rem;
        }
        .queue-section {
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .queue-section:last-child {
            border-bottom: none;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 8px;
        }
        .pagination ul {
            display: flex;
            flex-direction: row;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 8px;
        }
        .pagination .page-item {
            display: inline-flex;
        }
        .pagination .page-link {
            padding: 8px 14px;
            border: 1px solid #003087;
            border-radius: 5px;
            color: #003087;
            background-color: #ffffff;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background-color 0.3s, color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pagination .page-link:hover {
            background-color: #003087;
            color: #ffffff;
        }
        .pagination .page-item.active .page-link {
            background-color: #003087;
            color: #ffffff;
            border-color: #003087;
        }
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            border-color: #6c757d;
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        .pagination-info {
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        .calendar-filter {
            margin-bottom: 20px;
            text-align: center;
        }
        .calendar-filter label {
            font-size: 1rem;
            color: #003087;
            margin-right: 10px;
        }
        .calendar-filter input {
            padding: 8px 12px;
            border: 1px solid #003087;
            border-radius: 5px;
            font-size: 0.9rem;
            color: #4a4a4a;
            width: 200px;
        }
        .flatpickr-calendar {
            z-index: 9999 !important;
            width: 100% !important;
            max-width: 300px !important;
            margin: 0 auto !important;
        }
        .flatpickr-day.has-queue {
            position: relative;
        }
        .flatpickr-day.has-queue::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background-color: #28a745;
            border-radius: 50%;
        }
        .clear-filter {
            margin-left: 10px;
            padding: 8px 12px;
            background-color: #dc3545;
            color: #ffffff;
            border: none;
            border-radius: 5px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .clear-filter:hover {
            background-color: #c82333;
        }
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .card-body {
                padding: 15px;
            }
            .card-title {
                font-size: 1.4rem;
            }
            .queue-details, .log-section {
                font-size: 0.9rem;
                padding: 10px;
            }
            .table {
                font-size: 0.85rem;
            }
            .table th, .table td {
                padding: 6px;
                font-size: 0.8rem;
            }
            .table th:nth-child(1), .table td:nth-child(1) { width: 45%; }
            .table th:nth-child(2), .table td:nth-child(2) { width: 30%; }
            .table th:nth-child(3), .table td:nth-child(3) { width: 25%; }
            .status-btn {
                padding: 6px 12px;
                font-size: 0.8rem;
                max-width: 100px;
            }
            .status-buttons {
                gap: 6px;
            }
            .current-status {
                font-size: 0.9rem;
            }
            .log-section {
                font-size: 0.85rem;
            }
            .pagination ul {
                gap: 6px;
            }
            .pagination .page-link {
                padding: 6px 12px;
                font-size: 0.85rem;
            }
            .pagination-info {
                font-size: 0.85rem;
            }
            .calendar-filter input {
                width: 180px;
                font-size: 0.85rem;
            }
            .clear-filter {
                padding: 6px 10px;
                font-size: 0.85rem;
            }
        }
        @media (max-width: 576px) {
            .container {
                padding: 8px;
            }
            .card-title {
                font-size: 1.2rem;
            }
            .queue-details, .log-section {
                font-size: 0.85rem;
            }
            .table-responsive {
                font-size: 0.8rem;
            }
            .table th, .table td {
                padding: 5px;
                font-size: 0.75rem;
            }
            .table th:nth-child(1), .table td:nth-child(1) { width: 50%; }
            .table th:nth-child(2), .table td:nth-child(2) { width: 25%; }
            .table th:nth-child(3), .table td:nth-child(3) { width: 25%; }
            .status-student-idle, .status-student-no-show, 
            .status-student-absent, .status-student-boarded, 
            .status-student-finished {
                padding: 3px 6px;
                font-size: 0.7rem;
            }
            .status-buttons {
                display: none;
            }
            .current-status {
                font-size: 0.85rem;
                flex-direction: column;
                gap: 5px;
            }
            .current-status select {
                width: 100%;
                max-width: none;
                font-size: 0.85rem;
            }
            .log-section {
                font-size: 0.8rem;
            }
            .pagination ul {
                gap: 5px;
                flex-wrap: wrap;
                justify-content: center;
            }
            .pagination .page-link {
                padding: 5px 10px;
                font-size: 0.8rem;
            }
            .pagination-info {
                font-size: 0.8rem;
            }
            .calendar-filter {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            .calendar-filter label {
                font-size: 0.9rem;
            }
            .calendar-filter input {
                width: 100%;
                max-width: 200px;
                font-size: 0.8rem;
            }
            .flatpickr-calendar {
                left: 50% !important;
                transform: translateX(-50%) !important;
            }
            .clear-filter {
                padding: 5px 8px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>

    <div class="container">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">คิวงานของฉัน (คุณดูแลทั้งหมด <?php echo $total_queues; ?> คิว)</h5>
                <div class="calendar-filter">
                    <label for="date-filter">เลือกวันที่เพื่อกรองคิวงาน:</label>
                    <input type="text" id="date-filter" placeholder="เลือกวันที่">
                    <button class="clear-filter" id="clear-filter">ล้างตัวกรอง</button>
                </div>
                <div id="queue-list">
                    <?php if (empty($queue_with_distance)): ?>
                        <div class="no-data">ไม่มีคิวงานในขณะนี้</div>
                    <?php else: ?>
                        <?php foreach ($queue_with_distance as $queue_data): ?>
                            <?php
                            $queue_id = $queue_data['queue_id'];
                            $queue_rows = $queue_data['rows'];
                            $queue_date = $queue_rows[0]['queue_date'] ? (new DateTime($queue_rows[0]['queue_date']))->format('Y-m-d') : '';
                            ?>
                            <div class="queue-section" data-queue-id="<?php echo htmlspecialchars($queue_id); ?>" data-queue-date="<?php echo htmlspecialchars($queue_date); ?>">
                                <div class="queue-details">
                                    <p><strong>รหัสคิว:</strong> <?php echo htmlspecialchars($queue_id); ?></p>
                                    <p><strong>ประจำวันที่:</strong> 
                                        <?php 
                                        if ($queue_rows[0]['queue_date']) {
                                            $date = new DateTime($queue_rows[0]['queue_date']);
                                            $day = $date->format('j');
                                            $month = getMonthName($date->format('n'));
                                            $year = $date->format('Y') + 543;
                                            echo "$day $month $year";
                                        } else {
                                            echo 'ไม่ระบุ';
                                        }
                                        ?>
                                    </p>
                                    <p><strong>สถานที่รับ:</strong> <?php echo htmlspecialchars($queue_rows[0]['location']); ?></p>
                                    <p><strong>จังหวัด:</strong> <?php echo htmlspecialchars($queue_rows[0]['PROVINCE_NAME'] ?? 'ไม่ระบุ'); ?></p>
                                    <p><strong>อำเภอ:</strong> <?php echo htmlspecialchars($queue_rows[0]['AMPHUR_NAME'] ?? 'ไม่ระบุ'); ?></p>
                                    <p><strong>จำนวนนักศึกษา:</strong> <?php echo htmlspecialchars($queue_rows[0]['student_count'] ?? '0'); ?> คน</p>
                                </div>
                                <div class="current-status">
                                    <span>สถานะรถปัจจุบัน:</span>
                                    <select class="status-select" data-queue-id="<?php echo htmlspecialchars($queue_id); ?>">
                                        <option value="ว่าง" <?php echo $queue_rows[0]['status_car'] === 'ว่าง' ? 'selected' : ''; ?>>ว่าง</option>
                                        <option value="ถึงจุดรับ" <?php echo $queue_rows[0]['status_car'] === 'ถึงจุดรับ' ? 'selected' : ''; ?>>ถึงจุดรับ</option>
                                        <option value="ออกเดินทาง" <?php echo $queue_rows[0]['status_car'] === 'ออกเดินทาง' ? 'selected' : ''; ?>>ออกเดินทาง</option>
                                        <option value="ถึงที่หมาย" <?php echo $queue_rows[0]['status_car'] === 'ถึงที่หมาย' ? 'selected' : ''; ?>>ถึงที่หมาย</option>
                                        <option value="ปิดงาน" <?php echo $queue_rows[0]['status_car'] === 'ปิดงาน' ? 'selected' : ''; ?>>ปิดงาน</option>
                                    </select>
                                </div>
                                <div class="status-buttons">
                                    <button class="status-btn status-vacant <?php echo $queue_rows[0]['status_car'] === 'ว่าง' ? 'active' : ''; ?>" data-status="ว่าง" data-queue-id="<?php echo htmlspecialchars($queue_id); ?>">ว่าง</button>
                                    <button class="status-btn status-arrived <?php echo $queue_rows[0]['status_car'] === 'ถึงจุดรับ' ? 'active' : ''; ?>" data-status="ถึงจุดรับ" data-queue-id="<?php echo htmlspecialchars($queue_id); ?>">ถึงจุดรับ</button>
                                    <button class="status-btn status-departed <?php echo $queue_rows[0]['status_car'] === 'ออกเดินทาง' ? 'active' : ''; ?>" data-status="ออกเดินทาง" data-queue-id="<?php echo htmlspecialchars($queue_id); ?>">ออกเดินทาง</button>
                                    <button class="status-btn status-destination <?php echo $queue_rows[0]['status_car'] === 'ถึงที่หมาย' ? 'active' : ''; ?>" data-status="ถึงที่หมาย" data-queue-id="<?php echo htmlspecialchars($queue_id); ?>">ถึงที่หมาย</button>
                                    <button class="status-btn status-closed <?php echo $queue_rows[0]['status_car'] === 'ปิดงาน' ? 'active' : ''; ?>" data-status="ปิดงาน" data-queue-id="<?php echo htmlspecialchars($queue_id); ?>">ปิดงาน</button>
                                </div>
                                <div class="table-responsive" data-queue-id="<?php echo htmlspecialchars($queue_id); ?>">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>ชื่อ-นามสกุล</th>
                                                <th>เบอร์โทร</th>
                                                <th>สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($queue_rows as $row): ?>
                                                <?php
                                                $display_status = '-';
                                                $status_class = 'idle';
                                                if ($row['stu_status'] !== null && $row['stu_status'] !== '') {
                                                    switch ($row['stu_status']) {
                                                        case 'ขึ้นรถแล้ว':
                                                            $display_status = 'ขึ้นรถแล้ว';
                                                            $status_class = 'boarded';
                                                            break;
                                                        case 'ลา':
                                                            $display_status = 'ลา';
                                                            $status_class = 'absent';
                                                            break;
                                                        case 'สาย':
                                                            $display_status = 'สาย';
                                                            $status_class = 'no-show';
                                                            break;
                                                        case 'เลิกเรียนแล้ว':
                                                            $display_status = 'เลิกเรียนแล้ว';
                                                            $status_class = 'finished';
                                                            break;
                                                        default:
                                                            $display_status = 'ว่าง';
                                                            $status_class = 'idle';
                                                            break;
                                                    }
                                                }
                                                ?>
                                                <tr data-stu-status="<?php echo htmlspecialchars($row['stu_status'] ?? ''); ?>">
                                                    <td title="<?php echo htmlspecialchars(($row['stu_name'] && $row['stu_lastname']) ? $row['stu_name'] . ' ' . $row['stu_lastname'] : '-'); ?>">
                                                        <?php echo htmlspecialchars(($row['stu_name'] && $row['stu_lastname']) ? $row['stu_name'] . ' ' . $row['stu_lastname'] : '-'); ?>
                                                    </td>
                                                    <td title="<?php echo htmlspecialchars($row['stu_tel'] ?? 'ไม่ระบุ'); ?>">
                                                        <?php echo htmlspecialchars($row['stu_tel'] ?? 'ไม่ระบุ'); ?>
                                                    </td>
                                                    <td>
                                                        <span class="status-student-<?php echo $status_class; ?>">
                                                            <?php echo htmlspecialchars($display_status); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="log-section">
                                    <h6>ประวัติการทำงาน (คิว <?php echo htmlspecialchars($queue_id); ?>)</h6>
                                    <ul>
                                        <?php if (!empty($logs[$queue_id])): ?>
                                            <?php foreach ($logs[$queue_id] as $log): ?>
                                                <li>
                                                    <span class="status-label"><?php echo htmlspecialchars($log['status_car']); ?>:</span>
                                                    <span class="timestamp">
                                                        <?php 
                                                        $log_date = new DateTime($log['log_timestamp']);
                                                        $log_day = $log_date->format('j');
                                                        $log_month = getMonthName($log_date->format('n'));
                                                        $log_year = $log_date->format('Y') + 543;
                                                        $log_time = $log_date->format('H:i');
                                                        echo "$log_day $log_month $log_year, $log_time";
                                                        ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <?php if (!empty($student_logs[$queue_id])): ?>
                                            <li>
                                                <span class="status-label">สถานะนักเรียน:</span>
                                                <ul>
                                                    <?php foreach ($student_logs[$queue_id] as $slog): ?>
                                                        <li>
                                                            <?php echo htmlspecialchars($slog['stu_name'] . ' ' . $slog['stu_lastname'] . ' - ' . ($slog['stu_status'] ?: 'ขาด')); ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (empty($logs[$queue_id]) && empty($student_logs[$queue_id])): ?>
                                            <li>ยังไม่มีประวัติการทำงาน</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <nav aria-label="Queue Pagination" class="pagination">
                    <ul>
                        <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">« ก่อนหน้า</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true">ถัดไป »</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div class="pagination-info">
                    หน้า <?php echo $current_page; ?> จาก <?php echo $total_pages; ?> (ทั้งหมด <?php echo $total_queues; ?> คิว)
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof flatpickr === 'undefined') {
                console.error('Flatpickr is not loaded. Please check if flatpickr.js is included correctly.');
                return;
            }

            const queueDates = <?php echo json_encode(array_unique($queue_dates)); ?>;
            const allQueues = document.querySelectorAll('.queue-section');
            const queueList = document.getElementById('queue-list');
            const pagination = document.querySelector('.pagination');
            const paginationInfo = document.querySelector('.pagination-info');

            console.log('Queue Dates:', queueDates);

            flatpickr("#date-filter", {
                locale: "th",
                dateFormat: "Y-m-d",
                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    const date = dayElem.dateObj;
                    const dateStr = date.getFullYear() + '-' + 
                                    String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                                    String(date.getDate()).padStart(2, '0');
                    if (queueDates.includes(dateStr)) {
                        dayElem.classList.add('has-queue');
                    }
                },
                onChange: function(selectedDates, dateStr, instance) {
                    console.log('Selected Date:', dateStr);
                    filterQueuesByDate(dateStr);
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    instance.redraw();
                }
            });

            window.addEventListener('resize', function() {
                const flatpickrInstance = document.getElementById('date-filter')._flatpickr;
                if (flatpickrInstance && flatpickrInstance.isOpen) {
                    flatpickrInstance.redraw();
                }
            });

            document.getElementById('clear-filter').addEventListener('click', function() {
                const flatpickrInstance = document.getElementById('date-filter')._flatpickr;
                if (flatpickrInstance) {
                    flatpickrInstance.clear();
                    filterQueuesByDate('');
                }
            });

            function filterQueuesByDate(dateStr) {
                let visibleQueues = [];
                allQueues.forEach(queue => {
                    const queueDate = queue.getAttribute('data-queue-date');
                    console.log('Comparing:', queueDate, 'with', dateStr);
                    if (!dateStr || queueDate === dateStr) {
                        queue.style.display = 'block';
                        visibleQueues.push(queue);
                    } else {
                        queue.style.display = 'none';
                    }
                });

                console.log('Visible Queues:', visibleQueues.length);

                if (visibleQueues.length === 0) {
                    queueList.innerHTML = '<div class="no-data">ไม่มีคิวงานในวันที่เลือก</div>';
                    pagination.style.display = 'none';
                    paginationInfo.style.display = 'none';
                } else {
                    queueList.innerHTML = '';
                    visibleQueues.forEach(queue => {
                        queueList.appendChild(queue);
                    });

                    const queuesPerPage = 1;
                    const totalPages = Math.ceil(visibleQueues.length / queuesPerPage);
                    let currentPage = 1;

                    function updatePagination() {
                        const startIndex = (currentPage - 1) * queuesPerPage;
                        const endIndex = startIndex + queuesPerPage;
                        visibleQueues.forEach((queue, index) => {
                            queue.style.display = (index >= startIndex && index < endIndex) ? 'block' : 'none';
                        });

                        pagination.innerHTML = '';
                        const ul = document.createElement('ul');
                        ul.style.display = 'flex';
                        ul.style.flexDirection = 'row';
                        ul.style.gap = '8px';
                        ul.style.listStyle = 'none';
                        ul.style.padding = '0';
                        ul.style.margin = '0';

                        const prevLi = document.createElement('li');
                        prevLi.className = `page-item ${currentPage <= 1 ? 'disabled' : ''}`;
                        prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">« ก่อนหน้า</span></a>`;
                        prevLi.addEventListener('click', (e) => {
                            e.preventDefault();
                            if (currentPage > 1) {
                                currentPage--;
                                updatePagination();
                            }
                        });
                        ul.appendChild(prevLi);

                        for (let i = 1; i <= totalPages; i++) {
                            const li = document.createElement('li');
                            li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                            li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                            li.addEventListener('click', (e) => {
                                e.preventDefault();
                                currentPage = i;
                                updatePagination();
                            });
                            ul.appendChild(li);
                        }

                        const nextLi = document.createElement('li');
                        nextLi.className = `page-item ${currentPage >= totalPages ? 'disabled' : ''}`;
                        nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">ถัดไป »</span></a>`;
                        nextLi.addEventListener('click', (e) => {
                            e.preventDefault();
                            if (currentPage < totalPages) {
                                currentPage++;
                                updatePagination();
                            }
                        });
                        ul.appendChild(nextLi);

                        pagination.appendChild(ul);
                        paginationInfo.textContent = `หน้า ${currentPage} จาก ${totalPages} (ทั้งหมด ${visibleQueues.length} คิว)`;
                        pagination.style.display = 'flex';
                        paginationInfo.style.display = 'block';
                    }

                    updatePagination();
                }
            }

            function updateStatus(queueId, status, currentStatus) {
                Swal.fire({
                    icon: 'question',
                    title: 'ยืนยันการเปลี่ยนสถานะ',
                    text: `คุณต้องการเปลี่ยนสถานะรถของคิว ${queueId} เป็น "${status}" จาก "${currentStatus}" หรือไม่?` + (status === 'ปิดงาน' ? ' คิวนี้จะถูกลบหลังจากปิดงาน' : ''),
                    showCancelButton: true,
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `status_car=${encodeURIComponent(status)}&queue_id=${encodeURIComponent(queueId)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ',
                                    text: data.message,
                                    confirmButtonText: 'ตกลง'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ข้อผิดพลาด',
                                    text: data.error || 'ไม่สามารถอัปเดตสถานะรถได้',
                                    confirmButtonText: 'ตกลง'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'ข้อผิดพลาด',
                                text: 'เกิดข้อผิดพลาดในการอัปเดตสถานะรถ',
                                confirmButtonText: 'ตกลง'
                            });
                        });
                    }
                });
            }

            document.querySelectorAll('.status-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const queueId = this.getAttribute('data-queue-id');
                    const status = this.getAttribute('data-status');
                    const currentStatus = document.querySelector(`.status-select[data-queue-id="${queueId}"]`).value;
                    updateStatus(queueId, status, currentStatus);
                });
            });

            document.querySelectorAll('.status-select').forEach(select => {
                select.addEventListener('change', function () {
                    const queueId = this.getAttribute('data-queue-id');
                    const status = this.value;
                    const currentStatus = this.dataset.currentStatus || this.querySelector('option[selected]')?.value;
                    if (status !== currentStatus) {
                        updateStatus(queueId, status, currentStatus);
                    }
                });
            });

            function checkAllStudentsFinished(queueId) {
                const table = document.querySelector(`.table-responsive[data-queue-id="${queueId}"]`);
                if (!table) return;

                const rows = table.querySelectorAll('tbody tr');
                if (rows.length === 0) {
                    console.log(`Queue ${queueId}: No students`);
                    return;
                }

                const currentStatus = document.querySelector(`.status-select[data-queue-id="${queueId}"]`)?.value;
                const notifiedQueues = JSON.parse(localStorage.getItem(`notified_queues_${queueId}`) || '[]');

                const allFinished = Array.from(rows).every(row => {
                    const status = row.getAttribute('data-stu-status');
                    return status === 'เลิกเรียนแล้ว';
                });

                if (allFinished && !notifiedQueues.includes(queueId) && currentStatus !== 'ปิดงาน') {
                    Swal.fire({
                        icon: 'success',
                        title: `ทุกคนเลิกเรียนแล้ว (คิว ${queueId})`,
                        text: 'ทุกคนในคิวนี้เลิกเรียนแล้ว สามารถเตรียมรถได้',
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#28a745'
                    });
                    notifiedQueues.push(queueId);
                    localStorage.setItem(`notified_queues_${queueId}`, JSON.stringify(notifiedQueues));
                    table.classList.add('notified');
                }
            }

            function fetchStudentStatuses(queueId) {
                const table = document.querySelector(`.table-responsive[data-queue-id="${queueId}"]`);
                if (!table) return;

                fetch(`get_queue_statuses.php?queue_id=${queueId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            const rows = table.querySelectorAll('tbody tr');
                            rows.forEach((row, index) => {
                                const status = data.students[index]?.stu_status || '';
                                row.setAttribute('data-stu-status', status);
                                const statusCell = row.querySelector('td:last-child span');
                                let displayStatus = '-';
                                let statusClass = 'idle';
                                switch (status) {
                                    case 'ขึ้นรถแล้ว':
                                        displayStatus = 'ขึ้นรถแล้ว';
                                        statusClass = 'boarded';
                                        break;
                                    case 'ลา':
                                        displayStatus = 'ลา';
                                        statusClass = 'absent';
                                        break;
                                    case 'สาย':
                                        displayStatus = 'สาย';
                                        statusClass = 'no-show';
                                        break;
                                    case 'เลิกเรียนแล้ว':
                                        displayStatus = 'เลิกเรียนแล้ว';
                                        statusClass = 'finished';
                                        break;
                                    default:
                                        displayStatus = 'ว่าง';
                                        statusClass = 'idle';
                                        break;
                                }
                                statusCell.textContent = displayStatus;
                                statusCell.className = `status-student-${statusClass}`;
                            });
                            checkAllStudentsFinished(queueId);
                        } else {
                            console.error(`Queue ${queueId}: Error fetching statuses - ${data.error}`);
                        }
                    })
                    .catch(error => {
                        console.error(`Queue ${queueId}: Fetch error - ${error.message}`);
                    });
            }

            const today = new Date().toDateString();
            const queueIds = <?php echo json_encode(array_column($queue_with_distance, 'queue_id')); ?>;
            queueIds.forEach(queueId => {
                const lastCleared = localStorage.getItem(`last_cleared_${queueId}`);
                if (lastCleared !== today) {
                    localStorage.removeItem(`notified_queues_${queueId}`);
                    localStorage.setItem(`last_cleared_${queueId}`, today);
                }
                checkAllStudentsFinished(queueId);
                fetchStudentStatuses(queueId);
                setInterval(() => fetchStudentStatuses(queueId), 10000);
            });

            filterQueuesByDate('');
        });
    </script>
</body>
</html>