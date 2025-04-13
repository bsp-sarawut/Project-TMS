<?php
session_start();
require_once 'config/condb.php';

// ฟังก์ชันแปลงวันที่เป็นรูปแบบภาษาไทย (มีตัวเลือกแสดงเวลา)
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
            fputcsv($output, ["ชื่อ", "สถานะ", "วันที่และเวลา"]);

            foreach ($student_logs[$queue_id_current] as $student_log) {
                $student_name = (isset($student_log['stu_name']) && isset($student_log['stu_lastname'])) 
                    ? htmlspecialchars($student_log['stu_name'] . ' ' . $student_log['stu_lastname']) 
                    : 'ไม่พบชื่อนักเรียน (ID: ' . htmlspecialchars($student_log['student_id']) . ')';
                
                $student_row = [
                    $student_name,
                    htmlspecialchars($student_log['stu_status']),
                    getFormattedThaiDate($student_log['log_timestamp'], true)
                ];

                $student_row = array_map(function($value) {
                    return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }, $student_row);

                fputcsv($output, $student_row);
            }
        }
        fputcsv($output, []);
    }

    fclose($output);
    exit();
}

// ดึงวันที่ที่มีคิวปิดงาน (สำหรับปฏิทิน)
try {
    $stmt_dates = $conn->prepare("SELECT DISTINCT queue_date FROM queue WHERE status_car = 'ปิดงาน'");
    $stmt_dates->execute();
    $closed_dates = $stmt_dates->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}

// ดึงข้อมูลจังหวัดทั้งหมดสำหรับ dropdown
try {
    $stmt_provinces = $conn->prepare("SELECT PROVINCE_ID, PROVINCE_NAME FROM province ORDER BY PROVINCE_NAME");
    $stmt_provinces->execute();
    $provinces = $stmt_provinces->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}

// ดึงข้อมูลอำเภอทั้งหมด
try {
    $stmt_amphurs = $conn->prepare("SELECT AMPHUR_ID, AMPHUR_NAME, PROVINCE_ID FROM amphur ORDER BY AMPHUR_NAME");
    $stmt_amphurs->execute();
    $amphurs = $stmt_amphurs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}

// รับค่าจากฟอร์มค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_date = isset($_GET['filter_date']) ? trim($_GET['filter_date']) : '';
$filter_province = isset($_GET['filter_province']) ? trim($_GET['filter_province']) : '';
$filter_amphur = isset($_GET['filter_amphur']) ? trim($_GET['filter_amphur']) : '';

// ดึงข้อมูลคิวที่ปิดงานแล้วทั้งหมด (ไม่มี Pagination)
try {
    $sql = "SELECT q.queue_id, q.created_at, q.queue_date, p.PROVINCE_NAME, a.AMPHUR_NAME, 
                   c.car_license, c.car_brand, c.car_color
            FROM queue q
            JOIN province p ON q.province_id = p.PROVINCE_ID
            JOIN amphur a ON q.amphur_id = a.AMPHUR_ID
            JOIN car c ON q.car_id = c.car_id
            WHERE q.status_car = 'ปิดงาน'";

    $params = [];
    if (!empty($search)) {
        $sql .= " AND (q.queue_id LIKE :search 
                OR q.queue_date LIKE :search 
                OR c.car_brand LIKE :search 
                OR c.car_license LIKE :search 
                OR c.car_color LIKE :search 
                OR p.PROVINCE_NAME LIKE :search 
                OR a.AMPHUR_NAME LIKE :search)";
        $params[':search'] = "%$search%";
    }
    if (!empty($filter_date)) {
        $sql .= " AND q.queue_date = :filter_date";
        $params[':filter_date'] = $filter_date;
    }
    if (!empty($filter_province)) {
        $sql .= " AND p.PROVINCE_ID = :filter_province";
        $params[':filter_province'] = $filter_province;
    }
    if (!empty($filter_amphur)) {
        $sql .= " AND a.AMPHUR_ID = :filter_amphur";
        $params[':filter_amphur'] = $filter_amphur;
    }

    $sql .= " ORDER BY q.created_at DESC";

    $stmt_queues = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt_queues->bindValue($key, $value);
    }
    $stmt_queues->execute();
    $closed_queues = $stmt_queues->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}

// ดึงข้อมูลจาก queue_log และกลุ่มตาม queue_id
$queue_logs = [];
try {
    $stmt_queue_logs = $conn->prepare(
        "SELECT queue_id, status_car, log_timestamp
         FROM queue_log
         WHERE queue_id IN (
             SELECT queue_id FROM queue WHERE status_car = 'ปิดงาน'
         )
         ORDER BY log_timestamp ASC"
    );
    $stmt_queue_logs->execute();
    $logs = $stmt_queue_logs->fetchAll(PDO::FETCH_ASSOC);

    foreach ($logs as $log) {
        $queue_logs[$log['queue_id']][] = $log;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
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
        WHERE `ssl`.`queue_id` IN (
            SELECT queue_id FROM queue WHERE status_car = 'ปิดงาน'
        )
        ORDER BY `ssl`.`log_timestamp` ASC"
    );
    $stmt_student_logs->execute();
    $student_log_data = $stmt_student_logs->fetchAll(PDO::FETCH_ASSOC);

    foreach ($student_log_data as $log) {
        $student_logs[$log['queue_id']][] = $log;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}

// รวมข้อมูลคิวและ logs เพื่อใช้ใน export
$export_data = [];
foreach ($closed_queues as $queue) {
    $queue_id = $queue['queue_id'];
    if (!isset($queue_logs[$queue_id]) || empty($queue_logs[$queue_id])) continue;
    $queue['logs'] = $queue_logs[$queue_id];
    $export_data[] = $queue;
}

// ตรวจสอบการกดปุ่ม Export ทั้งหมด
if (isset($_POST['export_all'])) {
    exportToCSV($export_data, "closed_queues_" . date('Ymd') . ".csv", $student_logs);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log คิวรถที่ปิดงานแล้ว</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #f5f6f5;
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
        }
        .content {
            padding: 20px;
            max-height: 100vh;
            overflow-y: auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: #fff;
            padding: 15px;
            margin-bottom: 20px;
        }
        .queue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .queue-details-table {
            margin-bottom: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            overflow: hidden;
        }
        .queue-details-table th {
            background: #e9ecef;
            color: #333;
            font-weight: 500;
            padding: 10px;
            width: 150px;
            vertical-align: middle;
        }
        .queue-details-table td {
            padding: 10px;
            vertical-align: middle;
        }
        .form-label {
            font-weight: 500;
            color: #444;
        }
        .form-select, .form-control {
            border-radius: 5px;
            border: 1px solid #ccc;
            padding: 8px;
        }
        .form-select:focus, .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 3px rgba(0, 123, 255, 0.3);
        }
        .btn-success {
            border-radius: 8px;
            padding: 10px 10px;
            font-size: 15px;
            background: #28a745;
            border: none;
            transition: background 0.3s ease;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-primary {
            border-radius: 8px;
            padding: 8px 15px;
            font-size: 14px;
        }
        .table {
            border-radius: 5px;
            overflow: hidden;
            background: #fff;
        }
        .table thead th {
            background: #003087;
            color: #fff;
            text-align: center;
            padding: 12px;
        }
        .table tbody tr {
            background: transparent;
        }
        .table tbody tr.phase-row {
            background: #f1f1f1;
        }
        .table td {
            vertical-align: middle;
        }
        .status-closed {
            background-color: #ff6666;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 500;
            display: inline-block;
            width: 100px;
            text-align: center;
        }
        .status-arrived {
            background-color: #66cc99;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 500;
            display: inline-block;
            width: 100px;
            text-align: center;
        }
        .status-departed {
            background-color: #ff9966;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 500;
            display: inline-block;
            width: 100px;
            text-align: center;
        }
        .status-pickup {
            background-color: #6699ff;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 500;
            display: inline-block;
            width: 100px;
            text-align: center;
        }
        .status-default {
            background-color: #b3c6e6;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 500;
            display: inline-block;
            width: 100px;
            text-align: center;
        }
        .no-data {
            text-align: center;
            color: #555;
            font-size: 1.2rem;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .no-data i {
            font-size: 2.5rem;
            color: #007bff;
            margin-bottom: 15px;
        }
        .flatpickr-calendar {
            font-family: 'Kanit', sans-serif;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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
            background-color: #ff0000;
            border-radius: 50%;
        }
        .flatpickr-day.selected {
            background-color: #007bff;
            border-color: #007bff;
        }
        .flatpickr-day.today {
            border-color: #007bff;
        }
        @media (max-width: 768px) {
            .content {
                padding: 15px;
            }
            .table th, .table td {
                font-size: 0.9rem;
                padding: 8px;
            }
            .form-select, .form-control {
                width: 100%;
            }
            .status-closed,
            .status-arrived,
            .status-departed,
            .status-pickup,
            .status-default {
                width: 80px;
            }
            .queue-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .queue-header form {
                margin-top: 10px;
            }
            .queue-details-table th {
                width: 100px;
                font-size: 0.9rem;
            }
            .queue-details-table td {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Content -->
    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="text-center mb-4" style="color: #333; font-weight: 600;">Log คิวรถที่ปิดงานแล้ว</h2>

            <!-- ฟอร์มค้นหา -->
            <div class="card mb-4">
                <h3 class="mb-3">ค้นหาคิวรถที่ปิดงานแล้ว</h3>
                <form method="get" action="" id="searchForm">
                    <div class="row g-3">
                        <div class="col-md-3 col-12">
                            <label for="search" class="form-label">ค้นหาคิว</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="เช่น Queue ID, วันที่, รถ, จังหวัด, อำเภอ" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3 col-12">
                            <label for="datePicker" class="form-label">วันที่</label>
                            <input type="text" name="filter_date" id="datePicker" class="form-control" placeholder="เลือกวันที่" value="<?php echo htmlspecialchars($filter_date); ?>">
                        </div>
                        <div class="col-md-3 col-12">
                            <label for="filterProvince" class="form-label">จังหวัด</label>
                            <select name="filter_province" id="filterProvince" class="form-select">
                                <option value="">เลือกจังหวัด</option>
                                <?php foreach ($provinces as $province): ?>
                                    <option value="<?php echo htmlspecialchars($province['PROVINCE_ID']); ?>" <?php echo $filter_province == $province['PROVINCE_ID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($province['PROVINCE_NAME']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 col-12">
                            <label for="filterAmphur" class="form-label">อำเภอ</label>
                            <select name="filter_amphur" id="filterAmphur" class="form-select">
                                <option value="">เลือกอำเภอ</option>
                                <?php if (!empty($filter_province)): ?>
                                    <?php foreach ($amphurs as $amphur): ?>
                                        <?php if ($amphur['PROVINCE_ID'] == $filter_province): ?>
                                            <option value="<?php echo htmlspecialchars($amphur['AMPHUR_ID']); ?>" <?php echo $filter_amphur == $amphur['AMPHUR_ID'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($amphur['AMPHUR_NAME']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Queue Sections -->
            <?php if (count($closed_queues) > 0): ?>
                <?php foreach ($closed_queues as $queue): ?>
                    <?php 
                    $queue_id = $queue['queue_id'];
                    if (!isset($queue_logs[$queue_id]) || empty($queue_logs[$queue_id])) continue;
                    ?>
                    <div class="card mb-4">
                        <div class="queue-header">
                            <h3 class="mb-3">คิว #<?php echo htmlspecialchars($queue_id); ?></h3>
                            <form method="post" style="display: inline;">
                                <button type="submit" name="export_all" class="btn btn-success">
                                    <i class="fas fa-file-csv"></i> Export ไฟล์(CSV)
                                </button>
                            </form>
                        </div>
                        <table class="table queue-details-table">
                            <tbody>
                                <tr>
                                    <th>คิว</th>
                                    <td>#<?php echo htmlspecialchars($queue_id); ?></td>
                                </tr>
                                <tr>
                                    <th>รถ</th>
                                    <td><?php echo htmlspecialchars($queue['car_brand'] . " (" . $queue['car_license'] . ", " . $queue['car_color'] . ")"); ?></td>
                                </tr>
                                <tr>
                                    <th>วันที่คิว</th>
                                    <td><?php echo getFormattedThaiDate($queue['queue_date'], false); ?></td>
                                </tr>
                                <tr>
                                    <th>จังหวัด</th>
                                    <td><?php echo htmlspecialchars($queue['PROVINCE_NAME']); ?></td>
                                </tr>
                                <tr>
                                    <th>อำเภอ</th>
                                    <td><?php echo htmlspecialchars($queue['AMPHUR_NAME']); ?></td>
                                </tr>
                                <tr>
                                    <th>รายละเอียด</th>
                                    <td>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#studentModal<?php echo $queue_id; ?>">
                                            รายละเอียดนักเรียนในคิว
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>สถานะ</th>
                                        <th>วันที่และเวลา</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $is_first_phase = true;
                                    $is_second_phase = false;
                                    $arrived_count = 0;
                                    $first_phase_displayed = false;
                                    $second_phase_displayed = false;

                                    foreach ($queue_logs[$queue_id] as $log):
                                        if ($log['status_car'] === 'ถึงที่หมาย') {
                                            $arrived_count++;
                                        }

                                        $row_class = '';
                                        if ($arrived_count === 1 && $log['status_car'] === 'ถึงที่หมาย') {
                                            $is_first_phase = false;
                                        } elseif ($arrived_count === 1 && !$is_first_phase && $log['status_car'] === 'ถึงจุดรับ') {
                                            $is_second_phase = true;
                                        }

                                        if ($is_first_phase && !$first_phase_displayed) {
                                            echo '<tr class="phase-row"><td colspan="2" class="text-center">ขาไปมหาวิทยาลัย</td></tr>';
                                            $first_phase_displayed = true;
                                        } elseif ($is_second_phase && !$second_phase_displayed) {
                                            echo '<tr class="phase-row"><td colspan="2" class="text-center">ขากลับจากมหาวิทยาลัย</td></tr>';
                                            $second_phase_displayed = true;
                                        }

                                        $row_class = $is_first_phase ? 'row-first-phase' : 'row-second-phase';
                                        if ($log['status_car'] === 'ปิดงาน') {
                                            $row_class = 'row-closed';
                                        }

                                        $status_class = 'status-default';
                                        if ($log['status_car'] === 'ปิดงาน') {
                                            $status_class = 'status-closed';
                                        } elseif ($log['status_car'] === 'ถึงที่หมาย') {
                                            $status_class = 'status-arrived';
                                        } elseif ($log['status_car'] === 'ออกเดินทาง') {
                                            $status_class = 'status-departed';
                                        } elseif ($log['status_car'] === 'ถึงจุดรับ') {
                                            $status_class = 'status-pickup';
                                        }
                                    ?>
                                        <tr class="<?php echo $row_class; ?>">
                                            <td>
                                                <span class="<?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($log['status_car']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo getFormattedThaiDate($log['log_timestamp'], true); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Modal สำหรับแสดงรายละเอียดนักเรียน -->
                    <div class="modal fade" id="studentModal<?php echo $queue_id; ?>" tabindex="-1" aria-labelledby="studentModalLabel<?php echo $queue_id; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="studentModalLabel<?php echo $queue_id; ?>">รายละเอียดนักเรียนในคิว #<?php echo htmlspecialchars($queue_id); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php if (isset($student_logs[$queue_id]) && !empty($student_logs[$queue_id])): ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>ชื่อ</th>
                                                        <th>สถานะ</th>
                                                        <th>วันที่และเวลา</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($student_logs[$queue_id] as $student_log): ?>
                                                        <tr>
                                                            <td>
                                                                <?php 
                                                                echo (isset($student_log['stu_name']) && isset($student_log['stu_lastname'])) 
                                                                    ? htmlspecialchars($student_log['stu_name'] . ' ' . $student_log['stu_lastname']) 
                                                                    : 'ไม่พบชื่อนักเรียน (ID: ' . htmlspecialchars($student_log['student_id']) . ')'; 
                                                                ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($student_log['stu_status']); ?></td>
                                                            <td><?php echo getFormattedThaiDate($student_log['log_timestamp'], true); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-center">ไม่มีข้อมูลนักเรียนในคิวนี้</p>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-bus"></i><br>
                    ไม่มีข้อมูลคิวรถที่ปิดงานแล้วในขณะนี้
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
    <script>
        // Flatpickr Initialization
        const closedDates = <?php echo json_encode($closed_dates); ?>;
        flatpickr("#datePicker", {
            dateFormat: "Y-m-d",
            locale: "th",
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const date = dayElem.dateObj;
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const dateStr = `${year}-${month}-${day}`;
                
                if (closedDates.includes(dateStr)) {
                    dayElem.classList.add('has-queue');
                }
            },
            onChange: function(selectedDates, dateStr, instance) {
                document.getElementById('searchForm').submit();
            }
        });

        // ตัวเลือกจังหวัดและอำเภอ
        const amphurs = <?php echo json_encode($amphurs); ?>;
        const provinceSelect = document.getElementById('filterProvince');
        const amphurSelect = document.getElementById('filterAmphur');

        provinceSelect.addEventListener('change', function() {
            const provinceId = this.value;
            amphurSelect.innerHTML = '<option value="">เลือกอำเภอ</option>';

            if (provinceId) {
                const filteredAmphurs = amphurs.filter(amphur => amphur.PROVINCE_ID == provinceId);
                filteredAmphurs.forEach(amphur => {
                    const option = document.createElement('option');
                    option.value = amphur.AMPHUR_ID;
                    option.textContent = amphur.AMPHUR_NAME;
                    amphurSelect.appendChild(option);
                });
            }
            document.getElementById('searchForm').submit();
        });

        amphurSelect.addEventListener('change', function() {
            document.getElementById('searchForm').submit();
        });

        // ค้นหาด้วยข้อความ
        document.querySelector('input[name="search"]').addEventListener('input', function() {
            setTimeout(() => {
                document.getElementById('searchForm').submit();
            }, 500);
        });
    </script>
</body>
</html>