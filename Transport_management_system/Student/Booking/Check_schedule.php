<?php
require_once 'condb.php';
include 'navbar.php';

if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

$stu_username = $_SESSION['user_name'];
$current_date = date('Y-m-d'); // วันที่ปัจจุบัน เช่น 2025-04-12

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
               WHERE s.stu_username = :stu_username AND q.status_car != 'ปิดงาน'";

try {
    $stmt_queues = $conn->prepare($sql_queues);
    $stmt_queues->bindParam(':stu_username', $stu_username, PDO::PARAM_STR);
    $stmt_queues->execute();
    $queue_ids = $stmt_queues->fetchAll(PDO::FETCH_COLUMN);

    if (empty($queue_ids)) {
        $queue_data = [];
    } else {
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
                ORDER BY q.queue_date ASC, q.queue_id, s.stu_ID";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $queue_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped_data = [];
        $nearest_date = null;
        $queue_dates = []; // เก็บวันที่ของคิวทั้งหมด
        foreach ($queue_data as $row) {
            $queue_date = $row['queue_date'];
            if ($queue_date < $current_date) {
                continue; // ข้ามคิวในอดีต
            }
            if ($nearest_date === null || $queue_date < $nearest_date) {
                $nearest_date = $queue_date;
            }
            $grouped_data[$row['queue_id']][] = $row;
            $queue_dates[] = $queue_date; // เก็บวันที่ของคิว
        }
        $queue_dates = array_unique($queue_dates); // ลบวันที่ซ้ำ

        $sql_faculties = "SELECT DISTINCT stu_faculty FROM students WHERE stu_faculty IS NOT NULL AND stu_faculty != '' ORDER BY stu_faculty";
        $stmt_faculties = $conn->prepare($sql_faculties);
        $stmt_faculties->execute();
        $faculties = $stmt_faculties->fetchAll(PDO::FETCH_COLUMN);

        // นับจำนวนคิวทั้งหมด
        $total_queues = count($grouped_data);
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger text-center'>เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบตารางรถ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/flatpickr/flatpickr.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/flatpickr/flatpickr.js"></script>
    <script src="assets/flatpickr/th.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #1c2526 0%, #2c3e50 100%);
            font-family: 'Chakra Petch', sans-serif;
            min-height: 100vh;
            margin: 0;
            position: relative;
            overflow-x: hidden;
            padding-top: 80px;
            color: #eceff1;
        }
        .container {
            padding-top: 20px;
            padding-bottom: 50px;
        }
        h2 {
            color: #ffca28;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 40px;
            text-align: center;
        }
        .queue-card {
            background: #263238;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 40px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            border-left: 5px solid #ffca28;
            transition: transform 0.3s ease;
        }
        .queue-card:hover {
            transform: translateY(-5px);
        }
        .queue-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .queue-header i {
            color: #ffca28;
            margin-right: 10px;
            font-size: 1.5rem;
        }
        .queue-header h4 {
            color: #ffca28;
            font-weight: 600;
            margin: 0;
        }
        .info-section {
            background: #2c3e50;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-section p {
            margin-bottom: 10px;
            font-size: 1.1rem;
            margin-top: 10px;
        }
        .info-section strong {
            color: #ffca28;
            font-weight: 600;
        }
        .driver-info {
            background: #37474f;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        .driver-info p {
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .driver-info i {
            color: #ffca28;
            font-size: 1.1rem;
        }
        .driver-info a {
            color: #17a2b8;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .driver-info a:hover {
            color: #ffca28;
        }
        .driver-info .no-data {
            color: #b0bec5;
            font-style: italic;
        }
        .table {
            background: #2c3e50;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            margin-bottom: 0;
            table-layout: fixed;
        }
        .table thead {
            background: linear-gradient(45deg, #ffca28, #ff8f00);
            color: #1c2526;
        }
        .table th, .table td {
            vertical-align: middle;
            font-size: 1rem;
            padding: 12px;
            text-align: left;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .table th:nth-child(1), .table td:nth-child(1) { width: 25%; }
        .table th:nth-child(2), .table td:nth-child(2) { width: 20%; }
        .table th:nth-child(3), .table td:nth-child(3) { width: 20%; }
        .table th:nth-child(4), .table td:nth-child(4) { width: 20%; }
        .table th:nth-child(5), .table td:nth-child(5) { width: 15%; }
        .table tbody tr {
            border-bottom: 1px solid #37474f;
            transition: background 0.3s ease;
            color: #eceff1;
        }
        .table tbody tr:hover {
            background: #37474f;
        }
        .status-select {
            background: #37474f;
            color: #eceff1;
            border: 1px solid #ffca28;
            border-radius: 5px;
            padding: 2px 5px;
            font-size: 0.9rem;
            width: 100%;
        }
        .status-select:focus {
            outline: none;
            border-color: #17a2b8;
        }
        .status-car-idle { background-color: #007bff; color: #fff; padding: 5px 10px; border-radius: 5px; }
        .status-car-arrived { background-color: #28a745; color: #fff; padding: 5px 10px; border-radius: 5px; }
        .status-car-departed { background-color: #ffc107; color: #fff; padding: 5px 10px; border-radius: 5px; }
        .status-car-destination { background-color: #28a745; color: #fff; padding: 5px 10px; border-radius: 5px; }
        .status-car-closed { background-color: #343a40; color: #fff; padding: 5px 10px; border-radius: 5px; }
        .text-muted {
            color: #b0bec5 !important;
        }
        .btn-details {
            color: #17a2b8;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .btn-details i {
            font-size: 1rem;
        }
        .btn-details:hover {
            color: #138496;
        }
        .modal-content {
            background: #263238;
            color: #eceff1;
            border-radius: 15px;
            border: none;
        }
        .modal-header {
            border-bottom: 1px solid #37474f;
        }
        .modal-header h5 {
            color: #ffca28;
            font-weight: 600;
        }
        .modal-body p {
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        .modal-body strong {
            color: #ffca28;
            font-weight: 600;
        }
        .modal-footer {
            border-top: 1px solid #37474f;
        }
        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        .profile-image {
            display: block;
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            margin: 0 auto 15px;
            border: 2px solid #ffca28;
        }
        .no-image {
            text-align: center;
            color: #b0bec5;
            margin-bottom: 15px;
        }
        .search-toggle {
            background: #ffca28;
            color: #1c2526;
            border: none;
            border-radius: 5px;
            padding: 8px 20px;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
            transition: background 0.3s ease;
            margin-left: auto;
        }
        .search-toggle:hover {
            background: #ffb300;
        }
        .filter-section {
            background: #2c3e50;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        .filter-section.active {
            display: flex;
        }
        .filter-section label {
            color: #ffca28;
            font-weight: 600;
            margin-right: 10px;
        }
        .filter-section input, .filter-section select {
            background: #37474f;
            color: #eceff1;
            border: 1px solid #ffca28;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 0.9rem;
        }
        .filter-section input[type="date"] {
            width: 150px;
        }
        .filter-section input:focus, .filter-section select:focus {
            outline: none;
            border-color: #17a2b8;
        }
        .filter-section .btn-reset {
            background: #dc3545;
            color: #eceff1;
            border: none;
            border-radius: 5px;
            padding: 5px 15px;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }
        .filter-section .btn-reset:hover {
            background: #c82333;
        }
        .no-queue-message {
            background: #2c3e50;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        .no-queue-message i {
            color: #ffca28;
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .no-queue-message p {
            font-size: 1.2rem;
            margin: 0;
        }
        .calendar-filter {
            margin-bottom: 20px;
            text-align: center;
        }
        .calendar-filter label {
            color: #ffca28;
            font-size: 1rem;
            margin-right: 10px;
        }
        .calendar-filter input {
            background: #37474f;
            color: #eceff1;
            border: 1px solid #ffca28;
            border-radius: 5px;
            padding: 8px 12px;
            font-size: 0.9rem;
            width: 200px;
        }
        .calendar-filter input:focus {
            outline: none;
            border-color: #17a2b8;
        }
        .flatpickr-calendar {
            background: #263238;
            color: #eceff1;
            z-index: 9999 !important;
            width: 100% !important;
            max-width: 300px !important;
            margin: 0 auto !important;
        }
        .flatpickr-day {
            color: #eceff1;
        }
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
            background: #ffca28;
            border-color: #ffca28;
            color: #1c2526;
        }
        .flatpickr-day.today {
            border-color: #17a2b8;
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
        .flatpickr-month, .flatpickr-current-month span.cur-month, .flatpickr-weekdays, .flatpickr-weekday {
            color: #ffca28 !important;
        }
        .flatpickr-prev-month, .flatpickr-next-month {
            color: #ffca28 !important;
        }
        .flatpickr-prev-month:hover, .flatpickr-next-month:hover {
            color: #ffb300 !important;
        }
        .clear-filter {
            margin-left: 10px;
            padding: 8px 12px;
            background-color: #dc3545;
            color: #eceff1;
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
                padding-top: 10px;
                padding-bottom: 40px;
            }
            .queue-card {
                padding: 15px;
                margin-bottom: 30px;
            }
            .queue-header h4 {
                font-size: 1.3rem;
            }
            .queue-header i {
                font-size: 1.2rem;
            }
            .info-section p {
                font-size: 1rem;
            }
            .driver-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .driver-info p {
                font-size: 0.95rem;
            }
            .driver-info i {
                font-size: 1rem;
            }
            .table th, .table td {
                font-size: 0.85rem;
                padding: 8px;
            }
            .table th:nth-child(2), .table td:nth-child(2) {
                display: none;
            }
            .table th:nth-child(1), .table td:nth-child(1) {
                width: 35%;
            }
            .table th:nth-child(3), .table td:nth-child(3) {
                width: 25%;
            }
            .table th:nth-child(4), .table td:nth-child(4) {
                width: 25%;
            }
            .table th:nth-child(5), .table td:nth-child(5) {
                width: 15%;
            }
            .status-select {
                font-size: 0.75rem;
                padding: 2px 4px;
            }
            .btn-details i {
                font-size: 0.9rem;
            }
            .modal-body p {
                font-size: 1rem;
            }
            .search-toggle {
                font-size: 0.9rem;
                padding: 6px 15px;
            }
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-section label {
                margin-bottom: 5px;
            }
            .filter-section input, .filter-section select {
                width: 100%;
            }
            .filter-section input[type="date"] {
                width: 100%;
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
                width: 180px;
                font-size: 0.85rem;
            }
            .clear-filter {
                padding: 6px 10px;
                font-size: 0.85rem;
            }
            .profile-image {
                width: 120px;
                height: 120px;
            }
            .no-queue-message i {
                font-size: 2.5rem;
            }
            .no-queue-message p {
                font-size: 1rem;
            }
        }
        @media (max-width: 576px) {
            h2 {
                font-size: 1.5rem;
            }
            .queue-header h4 {
                font-size: 1.2rem;
            }
            .queue-header i {
                font-size: 1rem;
            }
            .info-section p {
                font-size: 0.9rem;
            }
            .driver-info p {
                font-size: 0.9rem;
            }
            .driver-info i {
                font-size: 0.9rem;
            }
            .table th, .table td {
                font-size: 0.75rem;
                padding: 6px;
            }
            .status-select {
                font-size: 0.65rem;
                padding: 1px 3px;
            }
            .btn-details i {
                font-size: 0.8rem;
            }
            .modal-body p {
                font-size: 0.9rem;
            }
            .search-toggle {
                font-size: 0.85rem;
                padding: 5px 12px;
            }
            .calendar-filter input {
                width: 100%;
                max-width: 200px;
                font-size: 0.8rem;
            }
            .clear-filter {
                padding: 5px 8px;
                font-size: 0.8rem;
            }
            .flatpickr-calendar {
                left: 50% !important;
                transform: translateX(-50%) !important;
            }
            .profile-image {
                width: 100px;
                height: 100px;
            }
            .no-queue-message i {
                font-size: 2rem;
            }
            .no-queue-message p {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>ตรวจสอบตารางรถ (คุณมีทั้งหมด <?php echo $total_queues ?? 0; ?> คิว)</h2>

    <?php if (!empty($grouped_data)): ?>
        <div class="calendar-filter">
            <label for="date-filter">เลือกวันที่เพื่อกรองคิวงาน:</label>
            <input type="text" id="date-filter" placeholder="เลือกวันที่">
            <button class="clear-filter" id="clear-filter">ล้างตัวกรอง</button>
        </div>

        <button class="search-toggle" onclick="toggleFilterSection()">
            <i class="fas fa-search"></i> ค้นหา
        </button>

        <div class="filter-section" id="filter-section">
            <div>
                <label for="search-name">ค้นหาชื่อ:</label>
                <input type="text" id="search-name" placeholder="พิมพ์ชื่อหรือนามสกุล">
            </div>
            <div>
                <label for="filter-faculty">คณะ:</label>
                <select id="filter-faculty">
                    <option value="">ทั้งหมด</option>
                    <?php foreach ($faculties as $faculty): ?>
                        <option value="<?php echo htmlspecialchars($faculty); ?>">
                            <?php echo htmlspecialchars($faculty); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter-status">สถานะ:</label>
                <select id="filter-status">
                    <option value="">ทั้งหมด</option>
                    <option value="">-</option>
                    <option value="ขึ้นรถแล้ว">ขึ้นรถแล้ว</option>
                    <option value="ลา">ลา</option>
                    <option value="สาย">สาย</option>
                    <option value="เลิกเรียนแล้ว">เลิกเรียนแล้ว</option>
                </select>
            </div>
            <button class="btn-reset" onclick="resetFilters()">รีเซ็ต</button>
        </div>

        <div id="queue-list">
            <?php foreach ($grouped_data as $queue_id => $rows): ?>
                <?php $header_data = $rows[0]; ?>
                <div class="queue-card" 
                     data-date="<?php echo htmlspecialchars($header_data['queue_date'] ?? ''); ?>" 
                     data-queue-id="<?php echo htmlspecialchars($queue_id); ?>"
                     style="<?php echo $header_data['queue_date'] == $nearest_date ? '' : 'display: none;'; ?>">
                    <div class="queue-header">
                        <i class="fas fa-bus"></i>
                        <h4>คิวรถ ID: <?php echo htmlspecialchars($queue_id); ?></h4>
                    </div>
                    <div class="info-section" data-queue-id="<?php echo htmlspecialchars($queue_id); ?>">
                        <p><strong>ประจำวันที่:</strong> 
                            <?php 
                            if ($header_data['queue_date']) {
                                $date = new DateTime($header_data['queue_date']);
                                $day = $date->format('j');
                                $month = getMonthName($date->format('n'));
                                $year = $date->format('Y') + 543;
                                echo "$day $month $year";
                            } else {
                                echo 'ไม่ระบุ';
                            }
                            ?>
                        </p>
                        <p><strong>ตารางการเดินทาง:</strong> <?php echo htmlspecialchars($header_data['location']); ?></p>
                        <p><strong>จังหวัด:</strong> <?php echo htmlspecialchars($header_data['PROVINCE_NAME']); ?></p>
                        <div class="driver-info">
                            <p><strong>รถตู้ทะเบียน:</strong> <?php echo htmlspecialchars($header_data['car_license'] ?? 'ไม่ระบุ'); ?></p>
                            <p>
                                <i class="fas fa-user"></i>
                                <strong>คนขับ:</strong> 
                                <?php 
                                if (!empty($header_data['driver_name']) && !empty($header_data['driver_lastname'])) {
                                    echo htmlspecialchars($header_data['driver_name'] . ' ' . $header_data['driver_lastname']);
                                } else {
                                    echo '<span class="no-data">ไม่ระบุ</span>';
                                }
                                ?>
                            </p>
                            <p>
                                <i class="fas fa-phone"></i>
                                <strong>เบอร์โทร:</strong> 
                                <?php 
                                if (!empty($header_data['driver_tel'])) {
                                    echo '<a href="tel:' . htmlspecialchars($header_data['driver_tel']) . '">' . htmlspecialchars($header_data['driver_tel']) . '</a>';
                                } else {
                                    echo '<span class="no-data">ไม่ระบุ</span>';
                                }
                                ?>
                            </p>
                        </div>
                        <p><strong>สถานะรถ:</strong> 
                            <span class="status-car" data-status="<?php echo htmlspecialchars($header_data['status_car'] ?: 'ว่าง'); ?>">
                                <?php echo htmlspecialchars($header_data['status_car'] ?: 'ว่าง'); ?>
                            </span>
                        </p>
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>ชื่อ</th>
                                <th>เบอร์โทร</th>
                                <th>คณะ</th>
                                <th>สถานะ</th>
                                <th>เพิ่มเติม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr data-name="<?php echo htmlspecialchars($row['stu_name'] . ' ' . $row['stu_lastname']); ?>"
                                    data-faculty="<?php echo htmlspecialchars($row['stu_faculty'] ?? 'ไม่ระบุ'); ?>"
                                    data-status="<?php echo htmlspecialchars($row['stu_status'] ?? ''); ?>">
                                    <td title="<?php echo htmlspecialchars($row['stu_name'] . ' ' . $row['stu_lastname']); ?>">
                                        <span class="name-full"><?php echo htmlspecialchars($row['stu_name'] . ' ' . $row['stu_lastname']); ?></span>
                                        <span class="name-first"><?php echo htmlspecialchars($row['stu_name']); ?></span>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($row['stu_tel'] ?? 'ไม่ระบุ'); ?>">
                                        <?php echo htmlspecialchars($row['stu_tel'] ?? 'ไม่ระบุ'); ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($row['stu_faculty'] ?? 'ไม่ระบุ'); ?>">
                                        <?php echo htmlspecialchars($row['stu_faculty'] ?? 'ไม่ระบุ'); ?>
                                    </td>
                                    <td>
                                        <select class="status-select" 
                                                data-stud-id="<?php echo htmlspecialchars($row['stu_ID']); ?>" 
                                                data-queue-id="<?php echo htmlspecialchars($queue_id); ?>">
                                            <option value="" <?php echo $row['stu_status'] == '' ? 'selected' : ''; ?>>-</option>
                                            <option value="ขึ้นรถแล้ว" <?php echo $row['stu_status'] == 'ขึ้นรถแล้ว' ? 'selected' : ''; ?>>ขึ้นรถแล้ว</option>
                                            <option value="ลา" <?php echo $row['stu_status'] == 'ลา' ? 'selected' : ''; ?>>ลา</option>
                                            <option value="สาย" <?php echo $row['stu_status'] == 'สาย' ? 'selected' : ''; ?>>สาย</option>
                                            <option value="เลิกเรียนแล้ว" <?php echo $row['stu_status'] == 'เลิกเรียนแล้ว' ? 'selected' : ''; ?>>เลิกเรียนแล้ว</option>
                                        </select>
                                    </td>
                                    <td>
                                        <span class="btn-details" data-bs-toggle="modal" data-bs-target="#studentModal" 
                                              data-stu-id="<?php echo htmlspecialchars($row['stu_ID'] ?? ''); ?>">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-queue-message text-muted">
            <i class="fas fa-bus"></i>
            <p>ขณะนี้คุณยังไม่มีตารางรถ</p>
            <p>กรุณาติดต่อผู้ดูแลระบบหากมีข้อสงสัย</p>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentModalLabel">รายละเอียดนักเรียน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <img id="modal-stu-img" class="profile-image" style="display: none;" alt="Profile Image">
                    <p id="modal-no-image" class="no-image" style="display: none;">ไม่มีรูปภาพ</p>
                </div>
                <p><strong>รหัสนักศึกษา:</strong> <span id="modal-stu-license"></span></p>
                <p><strong>ชื่อ:</strong> <span id="modal-stu-name"></span></p>
                <p><strong>เบอร์โทร:</strong> <span id="modal-stu-tel"></span></p>
                <p><strong>คณะ:</strong> <span id="modal-stu-faculty"></span></p>
                <p><strong>สาขา:</strong> <span id="modal-stu-major"></span></p>
                <p><strong>ชื่อผู้ใช้:</strong> <span id="modal-stu-username"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const studentModal = document.getElementById('studentModal');
    studentModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const stuId = button.getAttribute('data-stu-id');

        fetch(`get_student_details.php?stu_id=${stuId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: data.error,
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }
                const profileImage = document.getElementById('modal-stu-img');
                const noImage = document.getElementById('modal-no-image');
                if (data.stu_img && data.stu_img !== '') {
                    profileImage.src = '../Uploads/' + data.stu_img;
                    profileImage.style.display = 'block';
                    noImage.style.display = 'none';
                } else {
                    profileImage.style.display = 'none';
                    noImage.style.display = 'block';
                }
                document.getElementById('modal-stu-license').textContent = data.stu_license || 'ไม่ระบุ';
                document.getElementById('modal-stu-name').textContent = (data.stu_name || '') + ' ' + (data.stu_lastname || '');
                document.getElementById('modal-stu-tel').textContent = data.stu_tel || 'ไม่ระบุ';
                document.getElementById('modal-stu-major').textContent = data.stu_major || 'ไม่ระบุ';
                document.getElementById('modal-stu-faculty').textContent = data.stu_faculty || 'ไม่ระบุ';
                document.getElementById('modal-stu-username').textContent = data.stu_username || 'ไม่ระบุ';
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถดึงข้อมูลได้',
                    confirmButtonText: 'ตกลง'
                });
            });
    });

    function toggleNameDisplay() {
        const fullNames = document.querySelectorAll('.name-full');
        const firstNames = document.querySelectorAll('.name-first');
        if (window.innerWidth <= 768) {
            fullNames.forEach(name => name.style.display = 'none');
            firstNames.forEach(name => name.style.display = 'inline');
        } else {
            fullNames.forEach(name => name.style.display = 'inline');
            firstNames.forEach(name => name.style.display = 'none');
        }
    }

    toggleNameDisplay();
    window.addEventListener('resize', toggleNameDisplay);

    const allowedStatuses = ['', 'ขึ้นรถแล้ว', 'ลา', 'สาย', 'เลิกเรียนแล้ว'];

    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function () {
            const stuId = this.getAttribute('data-stud-id');
            const queueId = this.getAttribute('data-queue-id');
            const newStatus = this.value;

            if (!allowedStatuses.includes(newStatus)) {
                Swal.fire({
                    icon: 'error',
                    title: 'สถานะไม่ถูกต้อง',
                    text: 'กรุณาเลือกสถานะที่อนุญาต',
                    confirmButtonText: 'ตกลง'
                });
                this.value = this.getAttribute('data-original-status') || '';
                return;
            }

            Swal.fire({
                icon: 'question',
                title: 'ยืนยันการเปลี่ยนสถานะ',
                text: `คุณต้องการเปลี่ยนสถานะเป็น "${newStatus || '-'}" สำหรับคิว ${queueId} หรือไม่?`,
                showCancelButton: true,
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('update_student_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `stu_id=${stuId}&queue_id=${queueId}&stu_status=${encodeURIComponent(newStatus)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ',
                                text: data.message,
                                confirmButtonText: 'ตกลง'
                            });
                            const row = this.closest('tr');
                            row.setAttribute('data-status', newStatus);
                            this.setAttribute('data-original-status', newStatus);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: data.error || 'ไม่สามารถอัปเดตสถานะได้',
                                confirmButtonText: 'ตกลง'
                            });
                            this.value = this.getAttribute('data-original-status') || '';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้',
                            confirmButtonText: 'ตกลง'
                        });
                        this.value = this.getAttribute('data-original-status') || '';
                    });
                } else {
                    this.value = this.getAttribute('data-original-status') || '';
                }
            });
        });

        select.setAttribute('data-original-status', select.value);
    });

    window.toggleFilterSection = function() {
        const filterSection = document.getElementById('filter-section');
        filterSection.classList.toggle('active');
    };

    const queueDates = <?php echo json_encode($queue_dates); ?>;
    const allQueues = document.querySelectorAll('.queue-card');
    const queueList = document.getElementById('queue-list');
    const totalQueues = <?php echo $total_queues ?? 0; ?>;

    if (typeof flatpickr !== 'undefined') {
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
                filterTable(dateStr);
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
                filterTable('');
            }
        });
    }

    function filterTable(dateFilter = '') {
        const filterDate = dateFilter || document.getElementById('filter-date')?.value;
        const searchName = document.getElementById('search-name')?.value.toLowerCase() || '';
        const filterFaculty = document.getElementById('filter-faculty')?.value || '';
        const filterStatus = document.getElementById('filter-status')?.value || '';

        const queueCards = document.querySelectorAll('.queue-card');
        let nearestDate = null;
        queueCards.forEach(card => {
            const cardDate = card.getAttribute('data-date');
            if (!nearestDate || (cardDate >= '<?php echo $current_date; ?>' && cardDate < nearestDate)) {
                nearestDate = cardDate;
            }
        });

        let visibleQueueCount = 0;
        queueCards.forEach(card => {
            const cardDate = card.getAttribute('data-date');
            let matchesDate = true;
            if (filterDate) {
                matchesDate = cardDate === filterDate;
            } else {
                matchesDate = cardDate === nearestDate;
            }

            if (matchesDate) {
                card.style.display = '';
                const rows = card.querySelectorAll('.table tbody tr');
                rows.forEach(row => {
                    const name = row.getAttribute('data-name').toLowerCase();
                    const faculty = row.getAttribute('data-faculty');
                    const status = row.getAttribute('data-status');

                    const matchesName = name.includes(searchName);
                    const matchesFaculty = !filterFaculty || faculty === filterFaculty;
                    const matchesStatus = !filterStatus || status === filterStatus;

                    if (matchesName && matchesFaculty && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });

                const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
                if (visibleRows.length === 0) {
                    card.style.display = 'none';
                } else {
                    visibleQueueCount++;
                }
            } else {
                card.style.display = 'none';
            }
        });

        const header = document.querySelector('h2');
        header.textContent = `ตรวจสอบตารางรถ (คุณมีทั้งหมด ${visibleQueueCount} คิว)`;

        if (visibleQueueCount === 0) {
            queueList.innerHTML = `
                <div class="no-queue-message text-muted">
                    <i class="fas fa-bus"></i>
                    <p>ไม่มีคิวงานในวันที่เลือก</p>
                    <p>กรุณาติดต่อผู้ดูแลระบบหากมีข้อสงสัย</p>
                </div>
            `;
        } else {
            queueList.innerHTML = '';
            queueCards.forEach(card => {
                if (card.style.display !== 'none') {
                    queueList.appendChild(card);
                }
            });
        }
    }

    document.getElementById('filter-date')?.addEventListener('change', () => filterTable());
    document.getElementById('search-name')?.addEventListener('input', () => filterTable());
    document.getElementById('filter-faculty')?.addEventListener('change', () => filterTable());
    document.getElementById('filter-status')?.addEventListener('change', () => filterTable());

    window.resetFilters = function() {
        document.getElementById('filter-date').value = '';
        document.getElementById('search-name').value = '';
        document.getElementById('filter-faculty').value = '';
        document.getElementById('filter-status').value = '';
        const flatpickrInstance = document.getElementById('date-filter')._flatpickr;
        if (flatpickrInstance) {
            flatpickrInstance.clear();
        }
        filterTable('');
    };

    let deletedQueueIds = new Set();

    function updateCarStatus() {
        const queueCards = document.querySelectorAll('.queue-card');
        const queueIds = Array.from(queueCards)
            .map(card => card.querySelector('.info-section').getAttribute('data-queue-id'))
            .filter(queueId => !deletedQueueIds.has(queueId));

        if (queueIds.length === 0) return;

        Promise.all(queueIds.map(queueId =>
            fetch(`check_queue_status.php?queue_id=${queueId}`)
                .then(response => response.json())
                .then(data => ({ queueId, data }))
                .catch(error => {
                    console.error(`Error fetching status for queue ${queueId}:`, error);
                    return { queueId, data: null };
                })
        )).then(results => {
            results.forEach(({ queueId, data }) => {
                if (!data || data.success === false) {
                    if (data && data.error === 'Queue not found') {
                        deletedQueueIds.add(queueId);
                        const card = document.querySelector(`.info-section[data-queue-id="${queueId}"]`)?.closest('.queue-card');
                        if (card) {
                            card.remove();
                            Swal.fire({
                                icon: 'info',
                                title: `คิวรถ ID: ${queueId}`,
                                text: 'คิวนี้ถูกปิดงานและถูกลบออกจากตารางแล้ว',
                                confirmButtonText: 'ตกลง',
                                timer: 3000,
                                timerProgressBar: true
                            });
                        }
                    }
                    return;
                }

                const card = document.querySelector(`.info-section[data-queue-id="${queueId}"]`)?.closest('.queue-card');
                if (!card) return;

                const statusSpan = card.querySelector('.status-car');
                const currentStatus = statusSpan.getAttribute('data-status');

                if (data.status_car === 'ปิดงาน') {
                    deletedQueueIds.add(queueId);

                    // รีเซ็ตสถานะนักเรียนในตาราง students
                    fetch(`reset_student_status.php?queue_id=${queueId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        }
                    })
                    .then(response => response.json())
                    .then(resetData => {
                        if (resetData.success) {
                            // อัปเดต UI: รีเซ็ตสถานะในตาราง
                            const rows = card.querySelectorAll('.table tbody tr');
                            rows.forEach(row => {
                                const select = row.querySelector('.status-select');
                                if (select) {
                                    select.value = '';
                                    row.setAttribute('data-status', '');
                                    select.setAttribute('data-original-status', '');
                                }
                            });

                            // ลบการ์ดคิวออกจากหน้า
                            card.remove();
                            Swal.fire({
                                icon: 'info',
                                title: `คิวรถ ID: ${queueId}`,
                                text: 'คิวนี้ถูกปิดงานแล้ว สถานะนักเรียนถูกรีเซ็ตเรียบร้อย',
                                confirmButtonText: 'ตกลง',
                                timer: 3000,
                                timerProgressBar: true
                            });
                        } else {
                            console.error(`Error resetting student status for queue ${queueId}:`, resetData.error);
                        }
                    })
                    .catch(error => {
                        console.error(`Error resetting student status for queue ${queueId}:`, error);
                    });
                    return;
                }

                if (data.status_car !== currentStatus) {
                    statusSpan.textContent = data.status_car || 'ว่าง';
                    statusSpan.setAttribute('data-status', data.status_car || 'ว่าง');
                    statusSpan.className = 'status-car';
                    switch (data.status_car) {
                        case 'ว่าง': statusSpan.classList.add('status-car-idle'); break;
                        case 'ถึงจุดรับ': statusSpan.classList.add('status-car-arrived'); break;
                        case 'ออกเดินทาง': statusSpan.classList.add('status-car-departed'); break;
                        case 'ถึงที่หมาย': statusSpan.classList.add('status-car-destination'); break;
                        case 'ปิดงาน': statusSpan.classList.add('status-car-closed'); break;
                        default: statusSpan.classList.add('status-car-idle');
                    }
                    Swal.fire({
                        icon: data.status_car === 'ถึงจุดรับ' ? 'success' : 'info',
                        title: `คิวรถ ID: ${queueId}`,
                        text: `สถานะรถอัปเดตเป็น: ${data.status_car || 'ว่าง'}`,
                        confirmButtonText: 'ตกลง',
                        timer: 3000,
                        timerProgressBar: true
                    });
                }
            });

            if (!document.querySelector('.queue-card')) {
                const container = document.querySelector('.container');
                container.querySelector('.no-queue-message')?.remove();
                container.insertAdjacentHTML('beforeend', `
                    <div class="no-queue-message text-muted">
                        <i class="fas fa-bus"></i>
                        <p>ขณะนี้คุณยังไม่มีตารางรถ</p>
                        <p>กรุณาติดต่อผู้ดูแลระบบหากมีข้อสงสัย</p>
                    </div>
                `);
            }

            // อัปเดตจำนวนคิวหลังจากลบคิว
            const visibleCards = document.querySelectorAll('.queue-card:not([style*="display: none"])');
            const header = document.querySelector('h2');
            header.textContent = `ตรวจสอบตารางรถ (คุณมีทั้งหมด ${visibleCards.length} คิว)`;
        });
    }

    function refreshQueueData() {
        fetch('get_queue_data.php')
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Error refreshing queue data:', data.error);
                    return;
                }

                const container = document.querySelector('.container');
                const currentCards = container.querySelectorAll('.queue-card');
                const currentQueueIds = new Set(
                    Array.from(currentCards).map(card => 
                        card.querySelector('.info-section').getAttribute('data-queue-id')
                    )
                );

                currentCards.forEach(card => {
                    const queueId = card.querySelector('.info-section').getAttribute('data-queue-id');
                    if (!data.queues.some(queue => queue.queue_id == queueId)) {
                        card.remove();
                    }
                });

                container.querySelector('.no-queue-message')?.remove();

                if (!data.queues || data.queues.length === 0) {
                    container.insertAdjacentHTML('beforeend', `
                        <div class="no-queue-message text-muted">
                            <i class="fas fa-bus"></i>
                            <p>ขณะนี้คุณยังไม่มีตารางรถ</p>
                            <p>กรุณาติดต่อผู้ดูแลระบบหากมีข้อสงสัย</p>
                        </div>
                    `);
                    const header = document.querySelector('h2');
                    header.textContent = `ตรวจสอบตารางรถ (คุณมีทั้งหมด 0 คิว)`;
                } else {
                    const currentDate = '<?php echo $current_date; ?>';
                    let nearestDate = null;
                    data.queues.forEach(queue => {
                        if (queue.queue_date < currentDate) {
                            return;
                        }
                        if (!nearestDate || queue.queue_date < nearestDate) {
                            nearestDate = queue.queue_date;
                        }
                    });

                    data.queues.forEach(queue => {
                        if (queue.queue_date < currentDate) {
                            return;
                        }
                        if (currentQueueIds.has(String(queue.queue_id))) {
                            const card = document.querySelector(`.info-section[data-queue-id="${queue.queue_id}"]`)?.closest('.queue-card');
                            if (card) {
                                const statusSpan = card.querySelector('.status-car');
                                if (statusSpan.getAttribute('data-status') !== queue.status_car) {
                                    statusSpan.textContent = queue.status_car || 'ว่าง';
                                    statusSpan.setAttribute('data-status', queue.status_car || 'ว่าง');
                                    statusSpan.className = 'status-car';
                                    switch (queue.status_car) {
                                        case 'ว่าง': statusSpan.classList.add('status-car-idle'); break;
                                        case 'ถึงจุดรับ': statusSpan.classList.add('status-car-arrived'); break;
                                        case 'ออกเดินทาง': statusSpan.classList.add('status-car-departed'); break;
                                        case 'ถึงที่หมาย': statusSpan.classList.add('status-car-destination'); break;
                                        case 'ปิดงาน': statusSpan.classList.add('status-car-closed'); break;
                                        default: statusSpan.classList.add('status-car-idle');
                                    }
                                }
                            }
                            return;
                        }

                        const isNearest = queue.queue_date === nearestDate;
                        const queueHtml = `
                            <div class="queue-card" 
                                 data-date="${queue.queue_date || ''}" 
                                 data-queue-id="${queue.queue_id}"
                                 style="${isNearest ? '' : 'display: none;'}">
                                <div class="queue-header">
                                    <i class="fas fa-bus"></i>
                                    <h4>คิวรถ ID: ${queue.queue_id}</h4>
                                </div>
                                <div class="info-section" data-queue-id="${queue.queue_id}">
                                    <p><strong>ประจำวันที่:</strong> ${queue.formatted_date || 'ไม่ระบุ'}</p>
                                    <p><strong>ตารางการเดินทาง:</strong> ${queue.location || 'ไม่ระบุ'}</p>
                                    <p><strong>จังหวัด:</strong> ${queue.PROVINCE_NAME || 'ไม่ระบุ'}</p>
                                    <div class="driver-info">
                                        <p><strong>รถตู้ทะเบียน:</strong> ${queue.car_license || 'ไม่ระบุ'}</p>
                                        <p><i class="fas fa-user"></i><strong>คนขับ:</strong> ${queue.driver_name && queue.driver_lastname ? queue.driver_name + ' ' + queue.driver_lastname : '<span class="no-data">ไม่ระบุ</span>'}</p>
                                        <p><i class="fas fa-phone"></i><strong>เบอร์โทร:</strong> ${queue.driver_tel ? `<a href="tel:${queue.driver_tel}">${queue.driver_tel}</a>` : '<span class="no-data">ไม่ระบุ</span>'}</p>
                                    </div>
                                    <p><strong>สถานะรถ:</strong> 
                                        <span class="status-car" data-status="${queue.status_car || 'ว่าง'}">${queue.status_car || 'ว่าง'}</span>
                                    </p>
                                </div>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ชื่อ</th>
                                            <th>เบอร์โทร</th>
                                            <th>คณะ</th>
                                            <th>สถานะ</th>
                                            <th>เพิ่มเติม</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${queue.students.map(student => `
                                            <tr data-name="${student.stu_name} ${student.stu_lastname}"
                                                data-faculty="${student.stu_faculty || 'ไม่ระบุ'}"
                                                data-status="${student.stu_status || ''}">
                                                <td title="${student.stu_name} ${student.stu_lastname}">
                                                    <span class="name-full">${student.stu_name} ${student.stu_lastname}</span>
                                                    <span class="name-first">${student.stu_name}</span>
                                                </td>
                                                <td title="${student.stu_tel || 'ไม่ระบุ'}">${student.stu_tel || 'ไม่ระบุ'}</td>
                                                <td title="${student.stu_faculty || 'ไม่ระบุ'}">${student.stu_faculty || 'ไม่ระบุ'}</td>
                                                <td>
                                                    <select class="status-select" 
                                                            data-stud-id="${student.stu_ID}" 
                                                            data-queue-id="${queue.queue_id}">
                                                        <option value="" ${student.stu_status == '' ? 'selected' : ''}>-</option>
                                                        <option value="ขึ้นรถแล้ว" ${student.stu_status == 'ขึ้นรถแล้ว' ? 'selected' : ''}>ขึ้นรถแล้ว</option>
                                                        <option value="ลา" ${student.stu_status == 'ลา' ? 'selected' : ''}>ลา</option>
                                                        <option value="สาย" ${student.stu_status == 'สาย' ? 'selected' : ''}>สาย</option>
                                                        <option value="เลิกเรียนแล้ว" ${student.stu_status == 'เลิกเรียนแล้ว' ? 'selected' : ''}>เลิกเรียนแล้ว</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <span class="btn-details" data-bs-toggle="modal" data-bs-target="#studentModal" data-stu-id="${student.stu_ID}">
                                                        <i class="fas fa-eye"></i>
                                                    </span>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                        container.insertAdjacentHTML('beforeend', queueHtml);
                    });

                    // อัปเดต Flatpickr ด้วยวันที่ใหม่
                    const newQueueDates = data.queues
                        .filter(queue => queue.queue_date >= '<?php echo $current_date; ?>')
                        .map(queue => queue.queue_date);
                    queueDates.length = 0;
                    queueDates.push(...new Set(newQueueDates));
                    const flatpickrInstance = document.getElementById('date-filter')._flatpickr;
                    if (flatpickrInstance) {
                        flatpickrInstance.redraw();
                    }
                }

                document.querySelectorAll('.status-select').forEach(select => {
                    select.removeEventListener('change', select.changeHandler);
                    select.changeHandler = function () {
                        const stuId = this.getAttribute('data-stud-id');
                        const queueId = this.getAttribute('data-queue-id');
                        const newStatus = this.value;

                        if (!allowedStatuses.includes(newStatus)) {
                            Swal.fire({
                                icon: 'error',
                                title: 'สถานะไม่ถูกต้อง',
                                text: 'กรุณาเลือกสถานะที่อนุญาต',
                                confirmButtonText: 'ตกลง'
                            });
                            this.value = this.getAttribute('data-original-status') || '';
                            return;
                        }

                        Swal.fire({
                            icon: 'question',
                            title: 'ยืนยันการเปลี่ยนสถานะ',
                            text: `คุณต้องการเปลี่ยนสถานะเป็น "${newStatus || '-'}" สำหรับคิว ${queueId} หรือไม่?`,
                            showCancelButton: true,
                            confirmButtonText: 'ยืนยัน',
                            cancelButtonText: 'ยกเลิก'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                fetch('update_student_status.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: `stu_id=${stuId}&queue_id=${queueId}&stu_status=${encodeURIComponent(newStatus)}`
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'สำเร็จ',
                                            text: data.message,
                                            confirmButtonText: 'ตกลง'
                                        });
                                        const row = this.closest('tr');
                                        row.setAttribute('data-status', newStatus);
                                        this.setAttribute('data-original-status', newStatus);
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'เกิดข้อผิดพลาด',
                                            text: data.error || 'ไม่สามารถอัปเดตสถานะได้',
                                            confirmButtonText: 'ตกลง'
                                        });
                                        this.value = this.getAttribute('data-original-status') || '';
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'เกิดข้อผิดพลาด',
                                        text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้',
                                        confirmButtonText: 'ตกลง'
                                    });
                                    this.value = this.getAttribute('data-original-status') || '';
                                });
                            } else {
                                this.value = this.getAttribute('data-original-status') || '';
                            }
                        });
                    };
                    select.addEventListener('change', select.changeHandler);
                    select.setAttribute('data-original-status', select.value);
                });

                toggleNameDisplay();
                filterTable();
                updateCarStatus();

                // อัปเดตจำนวนคิวหลังจากรีเฟรช
                const visibleCards = document.querySelectorAll('.queue-card:not([style*="display: none"])');
                const header = document.querySelector('h2');
                header.textContent = `ตรวจสอบตารางรถ (คุณมีทั้งหมด ${visibleCards.length} คิว)`;
            })
            .catch(error => {
                console.error('Error refreshing queue data:', error);
            });
    }

    filterTable();
    updateCarStatus();
    setInterval(updateCarStatus, 15000);
    setInterval(refreshQueueData, 60000);
});
</script>
</body>
</html>