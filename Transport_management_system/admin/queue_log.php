<?php
session_start();
require_once 'config/condb.php';

// ฟังก์ชันแปลงวันที่เป็นรูปแบบภาษาไทย
function getFormattedThaiDate($date) {
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
    return "$day $month $year";
}

// ดึงข้อมูลคิวที่ปิดงานแล้ว
try {
    $stmt_queues = $conn->prepare(
        "SELECT q.queue_id, q.province_id, q.amphur_id, q.location, q.car_id, q.created_at, q.year, q.status_car, q.queue_date,
                p.PROVINCE_NAME, a.AMPHUR_NAME, c.car_license, c.car_brand, c.car_color, c.car_seat
         FROM queue q
         JOIN province p ON q.province_id = p.PROVINCE_ID
         JOIN amphur a ON q.amphur_id = a.AMPHUR_ID
         JOIN car c ON q.car_id = c.car_id
         WHERE q.status_car = 'ปิดงาน'
         ORDER BY q.created_at DESC"
    );
    $stmt_queues->execute();
    $closed_queues = $stmt_queues->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}

// ดึงข้อมูลนักเรียนในแต่ละคิวและสถานะจาก student_status_log
$queue_students = [];
try {
    // ดึง student_id และข้อมูลนักเรียน
    $stmt_students = $conn->prepare(
        "SELECT qs.queue_id, qs.student_id, s.stu_name, s.stu_lastname
         FROM queue_student qs
         JOIN students s ON qs.student_id = s.stu_ID
         WHERE qs.queue_id IN (
             SELECT queue_id FROM queue WHERE status_car = 'ปิดงาน'
         )"
    );
    $stmt_students->execute();
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // ดึงสถานะจาก student_status_log
    $stmt_status = $conn->prepare(
        "SELECT queue_id, student_id, stu_status
         FROM student_status_log
         WHERE queue_id IN (
             SELECT queue_id FROM queue WHERE status_car = 'ปิดงาน'
         )"
    );
    $stmt_status->execute();
    $status_log = [];
    while ($row = $stmt_status->fetch(PDO::FETCH_ASSOC)) {
        $status_log[$row['queue_id']][$row['student_id']] = $row['stu_status'];
    }

    // รวมข้อมูลนักเรียนและสถานะ
    foreach ($students as $student) {
        $queue_id = $student['queue_id'];
        $student_id = $student['student_id'];
        $student['status'] = isset($status_log[$queue_id][$student_id]) ? $status_log[$queue_id][$student_id] : 'ขาด';
        $queue_students[$queue_id][] = $student;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}

// ดึงจังหวัดทั้งหมดสำหรับตัวกรอง
try {
    $stmt_provinces = $conn->prepare("SELECT DISTINCT PROVINCE_NAME FROM province ORDER BY PROVINCE_NAME");
    $stmt_provinces->execute();
    $provinces = $stmt_provinces->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $provinces = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log คิวรถที่ปิดงานแล้ว</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: #f5f6f5;
            color: #333;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        .open-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 1000;
            transition: left 0.3s ease;
        }
        .open-btn.collapsed {
            left: 70px;
        }
        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .filter-section {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .filter-section label {
            font-weight: 500;
            color: #444;
            margin-right: 10px;
        }
        .filter-section input, .filter-section select {
            background: #fff;
            color: #333;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 8px;
            font-size: 0.9rem;
        }
        .filter-section input:focus, .filter-section select:focus {
            border-color: #007bff;
            box-shadow: 0 0 3px rgba(0, 123, 255, 0.3);
            outline: none;
        }
        .filter-section .btn-reset {
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }
        .filter-section .btn-reset:hover {
            background: #c82333;
        }
        .table-container {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .table {
            background: #fff;
            border-radius: 5px;
            overflow: hidden;
            width: 100%;
            margin-bottom: 0;
            table-layout: fixed;
        }
        .table thead {
            background: #003087;
            color: #fff;
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
        .table th:nth-child(1), .table td:nth-child(1) { width: 10%; }
        .table th:nth-child(2), .table td:nth-child(2) { width: 15%; }
        .table th:nth-child(3), .table td:nth-child(3) { width: 15%; }
        .table th:nth-child(4), .table td:nth-child(4) { width: 15%; }
        .table th:nth-child(5), .table td:nth-child(5) { width: 20%; }
        .table th:nth-child(6), .table td:nth-child(6) { width: 15%; }
        .table th:nth-child(7), .table td:nth-child(7) { width: 10%; }
        .table th:nth-child(8), .table td:nth-child(8) { width: 10%; }
        .table tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.3s ease;
        }
        .table tbody tr:hover {
            background: #f9f9f9;
        }
        .status-closed {
            background-color: #dc3545;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 500;
        }
        .btn-view-details {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            font-size: 1.2rem;
        }
        .btn-view-details:hover {
            color: #0056b3;
        }
        .modal-content {
            border-radius: 10px;
        }
        .modal-header {
            background: #003087;
            color: #fff;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .modal-title {
            font-weight: 600;
        }
        .modal-body {
            background: #fff;
            color: #333;
        }
        .student-list {
            margin-top: 10px;
            padding-left: 0;
            max-height: 200px;
            overflow-y: auto;
        }
        .student-list li {
            font-size: 0.95rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
        }
        .student-status {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #fff;
        }
        .status-absent {
            background-color: #dc3545;
        }
        .status-late {
            background-color: #fd7e14;
        }
        .status-normal {
            background-color: #28a745;
        }
        .queue-details {
            margin-bottom: 20px;
        }
        .queue-details p {
            margin: 5px 0;
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
            background: #fff;
            color: #333;
        }
        .flatpickr-day {
            color: #333;
        }
        .flatpickr-day.selected {
            background: #007bff;
            border-color: #007bff;
            color: #fff;
        }
        .flatpickr-month, .flatpickr-current-month span.cur-month, .flatpickr-weekdays, .flatpickr-weekday {
            color: #333 !important;
        }
        .flatpickr-prev-month, .flatpickr-next-month {
            color: #007bff !important;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Content -->
    <div class="content" id="content">
        <div class="container mt-4">
            <div class="section-title">Log คิวรถที่ปิดงานแล้ว</div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div>
                    <label for="filter-province">จังหวัด:</label>
                    <select id="filter-province">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($provinces as $province): ?>
                            <option value="<?php echo htmlspecialchars($province); ?>">
                                <?php echo htmlspecialchars($province); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter-date">วันที่คิว:</label>
                    <input type="text" id="filter-date" placeholder="เลือกวันที่">
                </div>
                <div>
                    <label for="search-location">ค้นหาสถานที่:</label>
                    <input type="text" id="search-location" placeholder="พิมพ์สถานที่">
                </div>
                <button class="btn-reset" onclick="resetFilters()">รีเซ็ต</button>
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <?php if (count($closed_queues) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>รหัสคิว</th>
                                <th>วันที่คิว</th>
                                <th>จังหวัด</th>
                                <th>อำเภอ</th>
                                <th>สถานที่</th>
                                <th>รถ</th>
                                <th>สถานะ</th>
                                <th>ดูข้อมูลเพิ่มเติม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($closed_queues as $queue): ?>
                                <tr data-province="<?php echo htmlspecialchars($queue['PROVINCE_NAME']); ?>"
                                    data-date="<?php echo htmlspecialchars($queue['queue_date']); ?>"
                                    data-location="<?php echo htmlspecialchars($queue['location']); ?>">
                                    <td><?php echo htmlspecialchars($queue['queue_id']); ?></td>
                                    <td><?php echo getFormattedThaiDate($queue['queue_date']); ?></td>
                                    <td><?php echo htmlspecialchars($queue['PROVINCE_NAME']); ?></td>
                                    <td><?php echo htmlspecialchars($queue['AMPHUR_NAME']); ?></td>
                                    <td><?php echo htmlspecialchars($queue['location']); ?></td>
                                    <td><?php echo htmlspecialchars($queue['car_brand'] . " (" . $queue['car_license'] . ", " . $queue['car_color'] . ")"); ?></td>
                                    <td><span class="status-closed"><?php echo htmlspecialchars($queue['status_car']); ?></span></td>
                                    <td>
                                        <button class="btn-view-details" data-bs-toggle="modal" data-bs-target="#detailsModal-<?php echo $queue['queue_id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal สำหรับแสดงข้อมูลเพิ่มเติม -->
                                <div class="modal fade" id="detailsModal-<?php echo $queue['queue_id']; ?>" tabindex="-1" aria-labelledby="detailsModalLabel-<?php echo $queue['queue_id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="detailsModalLabel-<?php echo $queue['queue_id']; ?>">ข้อมูลเพิ่มเติม: คิว #<?php echo $queue['queue_id']; ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <!-- ข้อมูลจาก queue_log -->
                                                <div class="queue-details">
                                                    <h6>ข้อมูลคิว (queue_log)</h6>
                                                    <p><strong>รหัสคิว:</strong> <?php echo htmlspecialchars($queue['queue_id']); ?></p>
                                                    <p><strong>วันที่สร้างคิว:</strong> <?php echo getFormattedThaiDate($queue['created_at']); ?></p>
                                                    <p><strong>จำนวนที่นั่ง:</strong> <?php echo htmlspecialchars($queue['car_seat']); ?></p>
                                                    <p><strong>สถานะ:</strong> <span class="status-closed"><?php echo htmlspecialchars($queue['status_car']); ?></span></p>
                                                </div>

                                                <!-- ข้อมูลจาก student_status_log -->
                                                <div class="student-details">
                                                    <h6>รายชื่อนักเรียน (student_status_log)</h6>
                                                    <?php if (isset($queue_students[$queue['queue_id']]) && count($queue_students[$queue['queue_id']]) > 0): ?>
                                                        <ul class="student-list">
                                                            <?php foreach ($queue_students[$queue['queue_id']] as $student): ?>
                                                                <?php
                                                                $status_class = 'status-normal';
                                                                if ($student['status'] === 'ขาด') {
                                                                    $status_class = 'status-absent';
                                                                } elseif ($student['status'] === 'สาย') {
                                                                    $status_class = 'status-late';
                                                                }
                                                                ?>
                                                                <li>
                                                                    <?php echo htmlspecialchars($student['stu_name'] . " " . $student['stu_lastname']); ?>
                                                                    <span class="student-status <?php echo $status_class; ?>">
                                                                        <?php echo htmlspecialchars($student['status']); ?>
                                                                    </span>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php else: ?>
                                                        <p>ไม่มีนักเรียนในคิวนี้</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-bus"></i><br>
                        ไม่มีข้อมูลคิวรถที่ปิดงานแล้วในขณะนี้
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Open Sidebar Button -->
    <button class="open-btn" id="open-btn">☰</button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle Functionality
        const openBtn = document.getElementById('open-btn');
        const content = document.getElementById('content');
        let isCollapsed = false;

        openBtn.addEventListener('click', () => {
            isCollapsed = !isCollapsed;
            if (isCollapsed) {
                content.classList.add('collapsed');
                openBtn.classList.add('collapsed');
                document.querySelector('.sidebar').classList.add('collapsed');
            } else {
                content.classList.remove('collapsed');
                openBtn.classList.remove('collapsed');
                document.querySelector('.sidebar').classList.remove('collapsed');
            }
        });

        // Filter Functionality
        document.addEventListener('DOMContentLoaded', function () {
            const filterProvince = document.getElementById('filter-province');
            const filterDate = document.getElementById('filter-date');
            const searchLocation = document.getElementById('search-location');

            flatpickr("#filter-date", {
                locale: "th",
                dateFormat: "Y-m-d",
                onChange: function(selectedDates, dateStr) {
                    filterTable();
                }
            });

            function filterTable() {
                const province = filterProvince.value;
                const date = filterDate.value;
                const location = searchLocation.value.toLowerCase();

                const rows = document.querySelectorAll('.table tbody tr');
                let visibleRows = 0;

                rows.forEach(row => {
                    const rowProvince = row.getAttribute('data-province');
                    const rowDate = row.getAttribute('data-date');
                    const rowLocation = row.getAttribute('data-location').toLowerCase();

                    const matchesProvince = !province || rowProvince === province;
                    const matchesDate = !date || rowDate === date;
                    const matchesLocation = !location || rowLocation.includes(location);

                    if (matchesProvince && matchesDate && matchesLocation) {
                        row.style.display = '';
                        visibleRows++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                const tableContainer = document.querySelector('.table-container');
                if (visibleRows === 0) {
                    tableContainer.innerHTML = `
                        <div class="no-data">
                            <i class="fas fa-bus"></i><br>
                            ไม่มีข้อมูลคิวรถที่ปิดงานแล้วตามตัวกรองนี้
                        </div>
                    `;
                } else {
                    const table = document.querySelector('.table');
                    if (!table) {
                        location.reload(); // รีโหลดหน้าเพื่อแสดงตารางใหม่
                    }
                }
            }

            filterProvince.addEventListener('change', filterTable);
            searchLocation.addEventListener('input', filterTable);

            window.resetFilters = function() {
                filterProvince.value = '';
                filterDate.value = '';
                searchLocation.value = '';
                const flatpickrInstance = filterDate._flatpickr;
                if (flatpickrInstance) {
                    flatpickrInstance.clear();
                }
                filterTable();
            };
        });
    </script>
</body>
</html>