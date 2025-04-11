<?php
    require_once 'condb.php';
    include 'navbar.php';

    // ตรวจสอบว่ามี session ของผู้ใช้หรือไม่
    if (!isset($_SESSION['user_name'])) {
        header("Location: login.php");
        exit();
    }

    $stu_username = $_SESSION['user_name']; // ดึงค่าผู้ใช้ที่ล็อกอินอยู่

    // ฟังก์ชันแปลงหมายเลขเดือนเป็นชื่อเดือนภาษาไทย
    function getMonthName($monthNumber) {
        $months = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
        ];
        return $months[$monthNumber] ?? 'ไม่ทราบเดือน';
    }

    // ขั้นตอนที่ 1: หาคิวที่ผู้ใช้ที่ล็อกอินอยู่มีส่วนเกี่ยวข้อง
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

        if (empty($queue_ids)) {
            $queue_data = [];
        } else {
            // ขั้นตอนที่ 2: ดึงข้อมูลคิวและนักเรียน
            $queue_ids_str = implode(',', array_map('intval', $queue_ids));
            $sql = "SELECT q.queue_id, q.location, q.queue_date,
                           p.PROVINCE_NAME,
                           c.car_license,
                           s.stu_ID, s.stu_name, s.stu_lastname, s.stu_tel, s.stu_faculty, s.stu_status
                    FROM queue q
                    INNER JOIN queue_student qs ON q.queue_id = qs.queue_id
                    INNER JOIN students s ON qs.student_id = s.stu_ID
                    INNER JOIN province p ON q.province_id = p.PROVINCE_ID
                    INNER JOIN car c ON q.car_id = c.car_id
                    WHERE q.queue_id IN ($queue_ids_str)
                    ORDER BY q.queue_id, s.stu_ID";

            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $queue_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // จัดกลุ่มข้อมูลตาม queue_id
            $grouped_data = [];
            foreach ($queue_data as $row) {
                $grouped_data[$row['queue_id']][] = $row;
            }

            // ดึงรายการคณะที่ไม่ซ้ำกันสำหรับฟิลเตอร์
            $sql_faculties = "SELECT DISTINCT stu_faculty FROM students WHERE stu_faculty IS NOT NULL AND stu_faculty != '' ORDER BY stu_faculty";
            $stmt_faculties = $conn->prepare($sql_faculties);
            $stmt_faculties->execute();
            $faculties = $stmt_faculties->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
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
        }
        .info-section strong {
            color: #ffca28;
            font-weight: 600;
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
        .table th:nth-child(1), .table td:nth-child(1) { /* ชื่อ */
            width: 25%;
        }
        .table th:nth-child(2), .table td:nth-child(2) { /* เบอร์โทร */
            width: 20%;
        }
        .table th:nth-child(3), .table td:nth-child(3) { /* คณะ */
            width: 20%;
        }
        .table th:nth-child(4), .table td:nth-child(4) { /* สถานะ */
            width: 20%;
        }
        .table th:nth-child(5), .table td:nth-child(5) { /* เพิ่มเติม */
            width: 15%;
        }
        .table tbody tr {
            border-bottom: 1px solid #37474f;
            transition: background 0.3s ease;
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
        /* Modal Styles */
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
        /* Profile Image Styles */
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
        /* Search Button Styles */
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
        /* Filter Styles */
        .filter-section {
            background: #2c3e50;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: none; /* ซ่อนเริ่มต้น */
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
        /* Responsive Design for Mobile */
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
            .table th, .table td {
                font-size: 0.85rem;
                padding: 8px;
            }
            .table th:nth-child(2), .table td:nth-child(2) {
                display: none;
            }
            .table th:nth-child(1), .table td:nth-child(1) { /* ชื่อ */
                width: 35%;
            }
            .table th:nth-child(3), .table td:nth-child(3) { /* คณะ */
                width: 25%;
            }
            .table th:nth-child(4), .table td:nth-child(4) { /* สถานะ */
                width: 25%;
            }
            .table th:nth-child(5), .table td:nth-child(5) { /* เพิ่มเติม */
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
            .profile-image {
                width: 120px;
                height: 120px;
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
            .profile-image {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>ตรวจสอบตารางรถ</h2>

    <!-- ปุ่มค้นหา -->
    <?php if (!empty($grouped_data)): ?>
        <button class="search-toggle" onclick="toggleFilterSection()">
            <i class="fas fa-search"></i> ค้นหา
        </button>

        <!-- ฟิลเตอร์การค้นหา -->
        <div class="filter-section" id="filter-section">
            <div>
                <label for="filter-date">วันที่:</label>
                <input type="date" id="filter-date">
            </div>
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
                </select>
            </div>
            <button class="btn-reset" onclick="resetFilters()">รีเซ็ต</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($grouped_data)): ?>
        <?php foreach ($grouped_data as $queue_id => $rows): ?>
            <?php $header_data = $rows[0]; // ใช้ข้อมูลแถวแรกของคิวนี้เพื่อแสดงหัวข้อ ?>
            <!-- การ์ดของแต่ละคิว -->
            <div class="queue-card" data-date="<?php echo htmlspecialchars($header_data['queue_date'] ?? ''); ?>">
                <div class="queue-header">
                    <i class="fas fa-bus"></i>
                    <h4>คิวรถ ID: <?php echo htmlspecialchars($queue_id); ?></h4>
                </div>
                <div class="info-section">
                    <p><strong>ตารางการเดินทาง:</strong> <?php echo htmlspecialchars($header_data['location']); ?></p>
                    <p><strong>ประจำวันที่:</strong> 
                        <?php 
                            if ($header_data['queue_date']) {
                                $date = new DateTime($header_data['queue_date']);
                                $day = $date->format('j');
                                $month = getMonthName($date->format('n'));
                                $year = $date->format('Y');
                                echo "$day $month $year";
                            } else {
                                echo 'ไม่ระบุ';
                            }
                        ?>
                    </p>
                    <p><strong>รถตู้ทะเบียน:</strong> <?php echo htmlspecialchars($header_data['car_license'] ?? 'ไม่ระบุ'); ?></p>
                    <p><strong>จังหวัด:</strong> <?php echo htmlspecialchars($header_data['PROVINCE_NAME']); ?></p>
                </div>

                <!-- ตารางของผู้โดยสารในคิวนี้ -->
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
                                    <select class="status-select" data-stu-id="<?php echo htmlspecialchars($row['stu_ID']); ?>">
                                        <option value="" <?php echo $row['stu_status'] == '' ? 'selected' : ''; ?>>-</option>
                                        <option value="ขึ้นรถแล้ว" <?php echo $row['stu_status'] == 'ขึ้นรถแล้ว' ? 'selected' : ''; ?>>ขึ้นรถแล้ว</option>
                                        <option value="ลา" <?php echo $row['stu_status'] == 'ลา' ? 'selected' : ''; ?>>ลา</option>
                                        <option value="สาย" <?php echo $row['stu_status'] == 'สาย' ? 'selected' : ''; ?>>สาย</option>
                                    </select>
                                </td>
                                <td>
                                    <span class="btn-details" data-bs-toggle="modal" data-bs-target="#studentModal" data-stu-id="<?php echo htmlspecialchars($row['stu_ID'] ?? ''); ?>">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center text-muted">ไม่พบข้อมูลตารางรถ</p>
    <?php endif; ?>
</div>

<!-- Modal -->
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
// JavaScript สำหรับจัดการ Modal, ฟิลเตอร์, และ AJAX
document.addEventListener('DOMContentLoaded', function () {
    const studentModal = document.getElementById('studentModal');
    studentModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const stuId = button.getAttribute('data-stu-id');

        fetch(`get_student_details.php?stu_id=${stuId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('เกิดข้อผิดพลาด: ' + data.error);
                    return;
                }

                // แสดงรูปภาพ
                const profileImage = document.getElementById('modal-stu-img');
                const noImage = document.getElementById('modal-no-image');
                if (data.stu_img && data.stu_img !== '') {
                    profileImage.src = '../uploads/' + data.stu_img;
                    profileImage.style.display = 'block';
                    noImage.style.display = 'none';
                } else {
                    profileImage.style.display = 'none';
                    noImage.style.display = 'block';
                }

                // แสดงข้อมูลอื่นๆ
                document.getElementById('modal-stu-license').textContent = data.stu_license;
                document.getElementById('modal-stu-name').textContent = data.stu_name + ' ' + data.stu_lastname;
                document.getElementById('modal-stu-tel').textContent = data.stu_tel || 'ไม่ระบุ';
                document.getElementById('modal-stu-major').textContent = data.stu_major || 'ไม่ระบุ';
                document.getElementById('modal-stu-faculty').textContent = data.stu_faculty || 'ไม่ระบุ';
                document.getElementById('modal-stu-username').textContent = data.stu_username;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการดึงข้อมูล');
            });
    });

    // ซ่อน/แสดงชื่อ-นามสกุลตามขนาดหน้าจอ
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

    // จัดการการเปลี่ยนสถานะนักเรียน
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function () {
            const stuId = this.getAttribute('data-stu-id');
            const newStatus = this.value;

            fetch('update_student_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `stu_id=${stuId}&stu_status=${encodeURIComponent(newStatus)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // อัปเดต data-status ในแถว
                    const row = this.closest('tr');
                    row.setAttribute('data-status', newStatus);
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.error);
                    this.value = this.getAttribute('data-original-status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการอัปเดตสถานะ');
                this.value = this.getAttribute('data-original-status');
            });

            this.setAttribute('data-original-status', newStatus);
        });
    });

    // ฟังก์ชันสำหรับแสดง/ซ่อนส่วนฟิลเตอร์
    window.toggleFilterSection = function() {
        const filterSection = document.getElementById('filter-section');
        filterSection.classList.toggle('active');
    };

    // ฟังก์ชันสำหรับกรองตาราง
    function filterTable() {
        const filterDate = document.getElementById('filter-date').value;
        const searchName = document.getElementById('search-name').value.toLowerCase();
        const filterFaculty = document.getElementById('filter-faculty').value;
        const filterStatus = document.getElementById('filter-status').value;

        const queueCards = document.querySelectorAll('.queue-card');

        queueCards.forEach(card => {
            const cardDate = card.getAttribute('data-date');
            const matchesDate = !filterDate || cardDate === filterDate;

            if (matchesDate) {
                card.style.display = '';
                // กรองแถวในตารางของการ์ดนี้
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

                // ซ่อนการ์ดถ้าไม่มีแถวที่แสดง
                const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
                if (visibleRows.length === 0) {
                    card.style.display = 'none';
                }
            } else {
                card.style.display = 'none';
            }
        });
    }

    // เพิ่ม Event Listener สำหรับฟิลเตอร์
    document.getElementById('filter-date')?.addEventListener('change', filterTable);
    document.getElementById('search-name')?.addEventListener('input', filterTable);
    document.getElementById('filter-faculty')?.addEventListener('change', filterTable);
    document.getElementById('filter-status')?.addEventListener('change', filterTable);

    // ฟังก์ชันรีเซ็ตฟิลเตอร์
    window.resetFilters = function() {
        document.getElementById('filter-date').value = '';
        document.getElementById('search-name').value = '';
        document.getElementById('filter-faculty').value = '';
        document.getElementById('filter-status').value = '';
        filterTable();
    };
});
</script>
</body>
</html>