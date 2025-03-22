<?php
require_once 'config/condb.php';

// ดึงข้อมูลตัวเลือกสำหรับ dropdown จังหวัด
$provinces = $conn->query("SELECT DISTINCT PROVINCE_ID, PROVINCE_NAME FROM province ORDER BY PROVINCE_NAME")->fetchAll(PDO::FETCH_ASSOC);

// รับค่าจาก filter
$where = [];
$params = [];
if (!empty($_GET['queue_date'])) {
    $where[] = "q.queue_date = ?";
    $params[] = $_GET['queue_date'];
}
if (!empty($_GET['province_id'])) {
    $where[] = "q.province_id = ?";
    $params[] = $_GET['province_id'];
}
if (!empty($_GET['amphur_id'])) {
    $where[] = "q.amphur_id = ?";
    $params[] = $_GET['amphur_id'];
}
if (!empty($_GET['location'])) {
    $where[] = "q.location = ?";
    $params[] = $_GET['location'];
}
if (!empty($_GET['search'])) {
    $where[] = "(c.car_license LIKE ? OR s.stu_name LIKE ? OR s.stu_lastname LIKE ?)";
    $search_term = "%" . $_GET['search'] . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// สร้าง SQL Query
$sql = "
    SELECT 
        q.queue_id, 
        p.PROVINCE_NAME AS province_name, 
        a.AMPHUR_NAME AS amphur_name, 
        q.location, 
        c.car_license, 
        c.car_brand, 
        q.created_at, 
        q.year, 
        q.status_car, 
        q.queue_date,
        GROUP_CONCAT(CONCAT(s.stu_name, ' ', s.stu_lastname) SEPARATOR ', ') AS students
    FROM queue q
    LEFT JOIN province p ON q.province_id = p.PROVINCE_ID
    LEFT JOIN amphur a ON q.amphur_id = a.AMPHUR_ID
    LEFT JOIN car c ON q.car_id = c.car_id
    LEFT JOIN queue_student qs ON q.queue_id = qs.queue_id
    LEFT JOIN students s ON qs.student_id = s.stu_ID
";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " GROUP BY q.queue_id ORDER BY q.queue_date DESC, q.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$queues = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการคิวรถ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.0/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f5f6f5;
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: #fff;
            padding: 15px;
        }
        .card h3 {
            color: #333;
            font-weight: 600;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 5px;
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
        .btn-primary {
            border-radius: 8px;
            padding: 8px 20px;
            background: #007bff;
            border: none;
            transition: background 0.3s ease;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-secondary {
            border-radius: 8px;
            padding: 8px 20px;
            background: #6c757d;
            border: none;
            transition: background 0.3s ease;
        }
        .btn-secondary:hover {
            background: #5a6268;
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
        .table tbody tr:hover {
            background: #f9f9f9;
        }
        .table td {
            vertical-align: middle;
        }
        .badge-success {
            background: #28a745;
        }
        .badge-warning {
            background: #ffc107;
        }
        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="content" id="content">
    <div class="container mt-4">
        <h2 class="text-center mb-4" style="color: #333; font-weight: 600;">รายการคิวรถ</h2>
        
        <!-- Filter Dropdown และ Search -->
        <div class="card mb-4">
            <h3 class="mb-3">ตัวกรองข้อมูล</h3>
            <form method="GET" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3 col-12">
                        <label for="queue_date" class="form-label">วันที่</label>
                        <input type="text" id="queue_date" name="queue_date" class="form-control" 
                               value="<?= isset($_GET['queue_date']) ? htmlspecialchars($_GET['queue_date']) : '' ?>" 
                               placeholder="เลือกวันที่">
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="province_id" class="form-label">จังหวัด</label>
                        <select name="province_id" id="province_id" class="form-select" onchange="loadAmphur()">
                            <option value="">ทุกจังหวัด</option>
                            <?php foreach ($provinces as $province): ?>
                                <option value="<?= $province['PROVINCE_ID'] ?>" <?= isset($_GET['province_id']) && $_GET['province_id'] == $province['PROVINCE_ID'] ? 'selected' : '' ?>>
                                    <?= $province['PROVINCE_NAME'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="amphur_id" class="form-label">อำเภอ</label>
                        <select name="amphur_id" id="amphur_id" class="form-select" onchange="loadLocation()">
                            <option value="">ทุกอำเภอ</option>
                            <?php if (isset($_GET['province_id']) && !empty($_GET['province_id'])): ?>
                                <?php
                                $stmt = $conn->prepare("SELECT AMPHUR_ID, AMPHUR_NAME FROM amphur WHERE PROVINCE_ID = ? ORDER BY AMPHUR_NAME");
                                $stmt->execute([$_GET['province_id']]);
                                $amphurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($amphurs as $amphur): ?>
                                    <option value="<?= $amphur['AMPHUR_ID'] ?>" <?= isset($_GET['amphur_id']) && $_GET['amphur_id'] == $amphur['AMPHUR_ID'] ? 'selected' : '' ?>>
                                        <?= $amphur['AMPHUR_NAME'] ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="location" class="form-label">จุดขึ้นรถ</label>
                        <select name="location" id="location" class="form-select">
                            <option value="">ทุกจุดขึ้นรถ</option>
                            <?php if (isset($_GET['amphur_id']) && !empty($_GET['amphur_id']) && isset($_GET['province_id']) && !empty($_GET['province_id'])): ?>
                                <?php
                                $stmt = $conn->prepare("SELECT DISTINCT location FROM routes WHERE province = ? AND amphur = ? ORDER BY location");
                                $stmt->execute([$_GET['province_id'], $_GET['amphur_id']]);
                                $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['location'] ?>" <?= isset($_GET['location']) && $_GET['location'] == $loc['location'] ? 'selected' : '' ?>>
                                        <?= $loc['location'] ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="search" class="form-label">ค้นหา</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                               placeholder="ค้นหารถหรือชื่อนักเรียน">
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary me-2">กรองข้อมูล</button>
                    <button type="button" class="btn btn-secondary" onclick="clearFilter()">เคลียร์ฟิลเตอร์</button>
                </div>
            </form>
        </div>

        <!-- ตารางแสดงผล -->
        <div class="card mb-4">
            <h3 class="mb-3">ข้อมูลคิวรถทั้งหมด</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>รหัสคิว</th>
                            <th>วันที่</th>
                            <th>จังหวัด</th>
                            <th>อำเภอ</th>
                            <th>จุดขึ้นรถ</th>
                            <th>ยานพาหนะ</th>
                            <th>สถานะรถ</th>
                            <th>รายชื่อนักเรียน</th>
                            <th>วันที่สร้าง</th>
                            <th>ปี</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($queues) > 0): ?>
                            <?php foreach ($queues as $queue): ?>
                                <tr>
                                    <td><?= $queue['queue_id'] ?></td>
                                    <td><?= $queue['queue_date'] ? date('d/m/Y', strtotime($queue['queue_date'])) : 'ไม่ระบุ' ?></td>
                                    <td><?= $queue['province_name'] ?></td>
                                    <td><?= $queue['amphur_name'] ?></td>
                                    <td><?= $queue['location'] ?></td>
                                    <td><?= $queue['car_license'] . ' - ' . $queue['car_brand'] ?></td>
                                    <td>
                                        <span class="badge <?= $queue['status_car'] === 'ว่าง' ? 'badge-success' : 'badge-warning' ?>">
                                            <?= $queue['status_car'] ?>
                                        </span>
                                    </td>
                                    <td><?= $queue['students'] ?: 'ไม่มีนักเรียน' ?></td>
                                    <td><?= date('d/m/Y H:i:s', strtotime($queue['created_at'])) ?></td>
                                    <td><?= $queue['year'] ?: 'ไม่ระบุ' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">ไม่พบข้อมูลคิวรถ</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ตั้งค่า Flatpickr สำหรับ filter วันที่
    flatpickr("#queue_date", {
        dateFormat: "Y-m-d",
        locale: "th"
    });

    // โหลดอำเภอตามจังหวัดที่เลือก
    function loadAmphur() {
        const provinceID = document.getElementById('province_id').value;
        const amphurSelect = document.getElementById('amphur_id');
        const locationSelect = document.getElementById('location');
        amphurSelect.innerHTML = '<option value="">ทุกอำเภอ</option>';
        locationSelect.innerHTML = '<option value="">ทุกจุดขึ้นรถ</option>';

        if (provinceID) {
            fetch('get_amphur.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'province_id=' + provinceID
            })
            .then(response => response.text())
            .then(data => {
                amphurSelect.innerHTML = '<option value="">ทุกอำเภอ</option>' + data;
            })
            .catch(error => console.error('Error:', error));
        }
    }

    // โหลดจุดขึ้นรถตามจังหวัดและอำเภอที่เลือก
    function loadLocation() {
        const provinceID = document.getElementById('province_id').value;
        const amphurID = document.getElementById('amphur_id').value;
        const locationSelect = document.getElementById('location');
        locationSelect.innerHTML = '<option value="">ทุกจุดขึ้นรถ</option>';

        if (provinceID && amphurID) {
            fetch(`get_location.php?province_id=${provinceID}&hur_id=${amphurID}`, {
                method: 'GET'
            })
            .then(response => response.text())
            .then(data => {
                locationSelect.innerHTML = data;
            })
            .catch(error => console.error('Error:', error));
        }
    }

    // เคลียร์ฟิลเตอร์
    function clearFilter() {
        document.getElementById('queue_date').value = '';
        document.getElementById('province_id').value = '';
        document.getElementById('amphur_id').innerHTML = '<option value="">ทุกอำเภอ</option>';
        document.getElementById('location').innerHTML = '<option value="">ทุกจุดขึ้นรถ</option>';
        document.getElementById('search').value = '';
        document.getElementById('filterForm').submit();
    }

    // โหลดอำเภอและจุดขึ้นรถเริ่มต้นถ้ามี province_id หรือ amphur_id จาก GET
    window.onload = function() {
        <?php if (isset($_GET['province_id']) && !empty($_GET['province_id'])): ?>
            loadAmphur();
            <?php if (isset($_GET['amphur_id']) && !empty($_GET['amphur_id'])): ?>
                setTimeout(loadLocation, 100); // รอให้ loadAmphur เสร็จก่อน
            <?php endif; ?>
        <?php endif; ?>
    };
</script>
</body>
</html>