<?php
require_once 'config/condb.php';

// ดึงข้อมูลจากตาราง queue และ join กับตารางที่เกี่ยวข้อง
$query = $conn->query("
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
    GROUP BY q.queue_id
    ORDER BY q.queue_date DESC, q.created_at DESC
");
$queues = $query->fetchAll(PDO::FETCH_ASSOC);
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
        .badge-danger {
            background: #dc3545;
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
</body>
</html>