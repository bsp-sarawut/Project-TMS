<?php
session_start();
require_once("config/condb.php");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['driver_user'])) {
    echo "<script>
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
    </script>";
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
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'ข้อผิดพลาด',
                text: 'ไม่พบข้อมูลคนขับ กรุณาติดต่อผู้ดูแลระบบ',
                confirmButtonText: 'ตกลง'
            });
        });
    </script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_car'])) {
    try {
        $status_car = $_POST['status_car'];
        $valid_statuses = ['ว่าง', 'ถึงจุดรับ', 'ออกเดินทาง', 'ถึงที่หมาย', 'ปิดงาน'];
        if (!in_array($status_car, $valid_statuses)) {
            throw new Exception('สถานะรถไม่ถูกต้อง');
        }

        $stmt = $conn->prepare("
            UPDATE queue q
            JOIN car c ON q.car_id = c.car_id
            SET q.status_car = :status_car
            WHERE c.driver_id = :driver_id
        ");
        $stmt->bindParam(':status_car', $status_car, PDO::PARAM_STR);
        $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
        $stmt->execute();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'อัพเดตสถานะรถสำเร็จ']);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

$queues = [];
$current_status = 'ว่าง';
function getMonthName($monthNumber) {
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
    ];
    return $months[$monthNumber] ?? 'ไม่ทราบเดือน';
}
try {
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
        ORDER BY q.queue_date DESC, q.queue_id DESC
    ");
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->execute();
    $queues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($queues) && !empty($queues[0]['status_car'])) {
        $current_status = $queues[0]['status_car'];
    }
} catch (Exception $e) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถดึงข้อมูลคิวงานได้: " . htmlspecialchars($e->getMessage()) . "',
                confirmButtonText: 'ตกลง'
            });
        });
    </script>";
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            .queue-details {
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
        }
        @media (max-width: 576px) {
            .container {
                padding: 8px;
            }
            .card-title {
                font-size: 1.2rem;
            }
            .queue-details {
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
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>

    <div class="container">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">คิวงานของฉัน</h5>
                <div class="current-status">
                    <span>สถานะรถปัจจุบัน:</span>
                    <select id="status-select" class="status-select">
                        <option value="ว่าง" <?php echo $current_status === 'ว่าง' ? 'selected' : ''; ?>>ว่าง</option>
                        <option value="ถึงจุดรับ" <?php echo $current_status === 'ถึงจุดรับ' ? 'selected' : ''; ?>>ถึงจุดรับ</option>
                        <option value="ออกเดินทาง" <?php echo $current_status === 'ออกเดินทาง' ? 'selected' : ''; ?>>ออกเดินทาง</option>
                        <option value="ถึงที่หมาย" <?php echo $current_status === 'ถึงที่หมาย' ? 'selected' : ''; ?>>ถึงที่หมาย</option>
                        <option value="ปิดงาน" <?php echo $current_status === 'ปิดงาน' ? 'selected' : ''; ?>>ปิดงาน</option>
                    </select>
                </div>
                <div class="status-buttons">
                    <button class="status-btn status-vacant <?php echo $current_status === 'ว่าง' ? 'active' : ''; ?>" data-status="ว่าง">ว่าง</button>
                    <button class="status-btn status-arrived <?php echo $current_status === 'ถึงจุดรับ' ? 'active' : ''; ?>" data-status="ถึงจุดรับ">ถึงจุดรับ</button>
                    <button class="status-btn status-departed <?php echo $current_status === 'ออกเดินทาง' ? 'active' : ''; ?>" data-status="ออกเดินทาง">ออกเดินทาง</button>
                    <button class="status-btn status-destination <?php echo $current_status === 'ถึงที่หมาย' ? 'active' : ''; ?>" data-status="ถึงที่หมาย">ถึงที่หมาย</button>
                    <button class="status-btn status-closed <?php echo $current_status === 'ปิดงาน' ? 'active' : ''; ?>" data-status="ปิดงาน">ปิดงาน</button>
                </div>
                <?php if (empty($queues)): ?>
                    <div class="no-data">ไม่มีคิวงานในขณะนี้</div>
                <?php else: ?>
                    <?php
                    $grouped_queues = [];
                    foreach ($queues as $queue) {
                        $grouped_queues[$queue['queue_id']][] = $queue;
                    }
                    ?>
                    <?php foreach ($grouped_queues as $queue_id => $queue_rows): ?>
                        <div class="queue-details">
                            <p><strong>รหัสคิว:</strong> <?php echo htmlspecialchars($queue_rows[0]['queue_id']); ?></p>
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
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const statusButtons = document.querySelectorAll('.status-btn');
            const statusSelect = document.getElementById('status-select');
            const currentStatus = '<?php echo addslashes($current_status); ?>';

            function updateStatus(status) {
                Swal.fire({
                    icon: 'question',
                    title: 'ยืนยันการเปลี่ยนสถานะ',
                    text: `คุณต้องการเปลี่ยนสถานะรถเป็น "${status}" จาก "${currentStatus}" หรือไม่?`,
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
                            body: `status_car=${encodeURIComponent(status)}`
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
                                    text: data.error || 'ไม่สามารถอัพเดตสถานะรถได้',
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

            statusButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const status = this.getAttribute('data-status');
                    updateStatus(status);
                });
            });

            statusSelect.addEventListener('change', function () {
                const status = this.value;
                if (status !== currentStatus) {
                    updateStatus(status);
                }
            });

            // ฟังก์ชันจัดการการแจ้งเตือนเมื่อทุกคนเลิกเรียน
            function checkAllStudentsFinished() {
                const queueTables = document.querySelectorAll('.table-responsive');
                let notifiedQueues = JSON.parse(localStorage.getItem('notified_queues') || '[]');

                queueTables.forEach(table => {
                    const queueId = table.getAttribute('data-queue-id');
                    const rows = table.querySelectorAll('tbody tr');
                    if (rows.length === 0) {
                        console.log(`Queue ${queueId}: No students`);
                        return;
                    }

                    const allFinished = Array.from(rows).every(row => {
                        const status = row.getAttribute('data-stu-status');
                        return status === 'เลิกเรียนแล้ว';
                    });

                    console.log(`Queue ${queueId}: allFinished=${allFinished}, notified=${notifiedQueues.includes(queueId)}`);

                    // แจ้งเตือนเมื่อทุกคนเลิกเรียนและคิวนี้ยังไม่ถูกแจ้ง
                    if (allFinished && !notifiedQueues.includes(queueId) && currentStatus !== 'ปิดงาน') {
                        Swal.fire({
                            icon: 'success',
                            title: `ทุกคนเลิกเรียนแล้ว (คิว ${queueId})`,
                            text: 'ทุกคนในคิวนี้เลิกเรียนแล้ว สามารถเตรียมรถได้',
                            confirmButtonText: 'ตกลง',
                            confirmButtonColor: '#28a745'
                        });
                        notifiedQueues.push(queueId);
                        localStorage.setItem('notified_queues', JSON.stringify(notifiedQueues));
                        table.classList.add('notified');
                    }
                });
            }

            // ฟังก์ชันอัปเดตสถานะนักเรียนแบบเรียลไทม์
            function fetchStudentStatuses() {
                const queueTables = document.querySelectorAll('.table-responsive');
                queueTables.forEach(table => {
                    const queueId = table.getAttribute('data-queue-id');
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
                                checkAllStudentsFinished();
                            } else {
                                console.error(`Queue ${queueId}: Error fetching statuses - ${data.error}`);
                            }
                        })
                        .catch(error => {
                            console.error(`Queue ${queueId}: Fetch error - ${error.message}`);
                        });
                });
            }

            // ล้าง notified_queues สำหรับคิวที่เก่ากว่า 1 วัน
            const today = new Date().toDateString();
            const lastCleared = localStorage.getItem('last_cleared');
            if (lastCleared !== today) {
                localStorage.removeItem('notified_queues');
                localStorage.setItem('last_cleared', today);
            }

            // เรียกฟังก์ชันเมื่อโหลดหน้าและทุก 10 วินาที
            checkAllStudentsFinished();
            fetchStudentStatuses();
            setInterval(fetchStudentStatuses, 3000);
        });
    </script>
</body>
</html>