<?php
session_start();
require_once('config/condb.php'); // เชื่อมต่อฐานข้อมูล

/**
 * ฟังก์ชันสำหรับรันคำสั่ง SQL และดึงผลลัพธ์
 */
function fetchSingleResult($conn, $query, $params = []) {
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("location: index.php");
        exit();
    }
}

// ภาพรวมตัวชี้วัดหลัก
$total_students = fetchSingleResult($conn, "SELECT COUNT(*) AS total_students FROM students")['total_students'] ?? 0;

$active_students = fetchSingleResult(
    $conn,
    "SELECT COUNT(*) AS active_students FROM students 
     WHERE login_status = 'active' 
     AND TIMESTAMPDIFF(SECOND, last_login, NOW()) < 1800"
)['active_students'] ?? 0;

$total_cars = fetchSingleResult($conn, "SELECT COUNT(*) AS total_cars FROM car")['total_cars'] ?? 0;

$available_cars = fetchSingleResult(
    $conn,
    "SELECT COUNT(*) AS available_cars FROM car WHERE car_status = 'available'"
)['available_cars'] ?? 0;

$pending_queues = fetchSingleResult(
    $conn,
    "SELECT COUNT(*) AS pending_queues FROM queue WHERE status_car IN ('ว่าง', 'ถึงจุดรับ')"
)['pending_queues'] ?? 0;

$registrations_today = fetchSingleResult(
    $conn,
    "SELECT COUNT(*) AS registrations_today FROM transport_registration WHERE DATE(created_at) = CURDATE()"
)['registrations_today'] ?? 0;

// การแจ้งเตือนสำหรับ Pending Confirmations
$pending_confirmations = fetchSingleResult(
    $conn,
    "SELECT COUNT(*) AS pending_confirmations FROM transport_registration WHERE payment_status = 'Pending Confirmation'"
)['pending_confirmations'] ?? 0;

// ตรวจสอบการลงทะเบียนใหม่
$latest_registration = fetchSingleResult(
    $conn,
    "SELECT MAX(created_at) AS latest_timestamp FROM transport_registration"
)['latest_timestamp'] ?? '0000-00-00 00:00:00';

$new_registrations_count = 0;
if (!isset($_SESSION['last_checked_timestamp'])) {
    // ถ้ายังไม่เคยตรวจสอบมาก่อน ให้ตั้งค่าเริ่มต้น
    $_SESSION['last_checked_timestamp'] = $latest_registration;
} else {
    // เปรียบเทียบ timestamp ล่าสุดกับที่เก็บไว้
    if ($latest_registration > $_SESSION['last_checked_timestamp']) {
        $new_registrations_count = fetchSingleResult(
            $conn,
            "SELECT COUNT(*) AS new_count FROM transport_registration WHERE created_at > ?",
            [$_SESSION['last_checked_timestamp']]
        )['new_count'] ?? 0;
        // อัปเดต timestamp ล่าสุดที่ตรวจสอบ
        $_SESSION['last_checked_timestamp'] = $latest_registration;
    }
}

// ดึงข้อมูล 5 อันดับจังหวัดที่มีการลงทะเบียนมากที่สุด
try {
    $stmt_top_provinces = $conn->prepare(
        "SELECT p.PROVINCE_NAME, COUNT(*) as registration_count, 
                GROUP_CONCAT(a.AMPHUR_NAME) as amphurs, 
                GROUP_CONCAT(r.location) as locations
         FROM transport_registration tr
         JOIN routes r ON tr.route_id = r.route_ID
         JOIN province p ON r.province = p.PROVINCE_ID
         JOIN amphur a ON r.amphur = a.AMPHUR_ID
         GROUP BY p.PROVINCE_NAME
         ORDER BY registration_count DESC
         LIMIT 5"
    );
    $stmt_top_provinces->execute();
    $top_provinces = $stmt_top_provinces->fetchAll(PDO::FETCH_ASSOC);

    // เตรียมข้อมูลสำหรับชาร์ต
    $province_labels = [];
    $registration_counts = [];
    $amphur_data = [];
    $location_data = [];

    foreach ($top_provinces as $province) {
        $province_labels[] = $province['PROVINCE_NAME'];
        $registration_counts[] = $province['registration_count'];
        $amphur_data[] = explode(',', $province['amphurs']);
        $location_data[] = explode(',', $province['locations']);
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}

// ดึงคิวล่าสุด 5 รายการ
try {
    $stmt_queues = $conn->prepare(
        "SELECT q.queue_id, q.location, q.status_car, q.queue_date, p.PROVINCE_NAME, a.AMPHUR_NAME, c.car_license 
         FROM queue q 
         JOIN province p ON q.province_id = p.PROVINCE_ID 
         JOIN amphur a ON q.amphur_id = a.AMPHUR_ID 
         JOIN car c ON q.car_id = c.car_id 
         ORDER BY q.created_at DESC 
         LIMIT 5"
    );
    $stmt_queues->execute();
    $recent_queues = $stmt_queues->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}

// ดึงการลงทะเบียนล่าสุด 5 รายการ
try {
    $stmt_registrations = $conn->prepare(
        "SELECT tr.id, tr.payment_status, tr.created_at, s.stu_name, s.stu_lastname, r.location 
         FROM transport_registration tr 
         JOIN students s ON tr.stu_username = s.stu_username 
         JOIN routes r ON tr.route_id = r.route_ID 
         ORDER BY tr.created_at DESC 
         LIMIT 5"
    );
    $stmt_registrations->execute();
    $recent_registrations = $stmt_registrations->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ระบบจัดการการขนส่ง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f1f3f4;
            color: #212529;
            margin: 0;
            padding: 0;
        }
        .content {
            margin-left: 250px;
            padding: 30px;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 20px;
            margin-top: 5px;
        }
        .card {
            background: #ffffff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.3s ease, transform 0.3s ease;
            animation: fadeIn 0.5s ease-in-out;
            cursor: pointer;
        }
        .card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .card-header {
            background: #1a73e8;
            color: #ffffff;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-body {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .card-body i {
            font-size: 1.8rem;
            color: #1a73e8;
        }
        .card-body h5 {
            margin: 0;
            font-size: 1rem;
            color: #6c757d;
            font-weight: 500;
        }
        .card-body p {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #212529;
        }
        .card-body a {
            color: #1a73e8;
            text-decoration: none;
        }
        .card-body a:hover {
            color: #1557b0;
        }
        .chart-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.5s ease-in-out;
            height: 350px;
            margin-bottom: 17px;
        }
        .table-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.5s ease-in-out;
            margin-bottom: 17px;
        }
        .table {
            background: #ffffff;
            color: #212529;
            border-radius: 8px;
            overflow: hidden;
        }
        .table thead {
            background: #f8f9fa;
            color: #212529;
        }
        .table th, .table td {
            vertical-align: middle;
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        .status-pending {
            color: #dc3545;
            font-weight: 500;
        }
        .status-paid {
            color: #28a745;
            font-weight: 500;
        }
        .status-car-ว่าง { color: #6c757d; font-weight: 500; }
        .status-car-ถึงจุดรับ { color: #1a73e8; font-weight: 500; }
        .status-car-ออกเดินทาง { color: #28a745; font-weight: 500; }
        .status-car-ถึงที่หมาย { color: #17a2b8; font-weight: 500; }
        .status-car-ปิดงาน { color: #6c757d; font-weight: 500; }
        .alert-card {
            background: #ffffff;
            border-left: 5px solid #dc3545;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.5s ease-in-out;
        }
        .alert-card.success {
            border-left: 5px solid #28a745;
        }
        .alert-card i {
            font-size: 1.5rem;
            color: #dc3545;
        }
        .alert-card.success i {
            color: #28a745;
        }
        .alert-card p {
            margin: 0;
            font-size: 1rem;
            color: #212529;
        }
        .alert-card a {
            color: #1a73e8;
            text-decoration: none;
            font-weight: 500;
        }
        .alert-card a:hover {
            color: #1557b0;
        }
        .refresh-section {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .refresh-btn {
            background: #1a73e8;
            color: #ffffff;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .refresh-btn:hover {
            background: #1557b0;
        }
        .timer {
            font-size: 0.9rem;
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .content {
                margin-left: 250px;
                padding: 15px;
            }
            .card-body p {
                font-size: 1.2rem;
            }
            .table th, .table td {
                font-size: 0.9rem;
                padding: 8px;
            }
            .chart-container {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Content -->
    <div class="content" id="content">
        <div class="container mt-4">
            <!-- Inline Notifications -->
            <?php if ($pending_confirmations > 0): ?>
                <div class="alert-card">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>คุณมี <strong><?php echo $pending_confirmations; ?></strong> การลงทะเบียนที่รอการยืนยัน 
                        <a href="pending_registrations.php">ดูรายละเอียด</a>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($new_registrations_count > 0): ?>
                <div class="alert-card success">
                    <i class="fas fa-bell"></i>
                    <p>มีการลงทะเบียนใหม่ <strong><?php echo $new_registrations_count; ?></strong> รายการ 
                        <a href="registrations_today.php">ดูรายละเอียด</a>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Refresh Section -->
            <div class="refresh-section">
                <span class="timer" id="refresh-timer">รีเฟรชใน 60 วินาที</span>
                <button class="refresh-btn" id="refresh-btn"><i class="fas fa-sync-alt"></i> รีเฟรชทันที</button>
            </div>

            <!-- System Overview -->
            <div class="section-title">ภาพรวมระบบ</div>
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card" data-bs-toggle="tooltip" data-bs-placement="top" title="ดูรายละเอียดนักเรียนทั้งหมด" onclick="window.location.href='students.php'">
                        <div class="card-header">
                            <i class="fas fa-users"></i> นักเรียนทั้งหมด
                        </div>
                        <div class="card-body">
                            <i class="fas fa-users"></i>
                            <div>
                                <h5>นักเรียนทั้งหมด</h5>
                                <p id="total-students"><?php echo $total_students; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card" data-bs-toggle="tooltip" data-bs-placement="top" title="ดูนักเรียนที่กำลังใช้งาน" onclick="window.location.href='active_students.php'">
                        <div class="card-header">
                            <i class="fas fa-user-check"></i> นักเรียนที่ Active
                        </div>
                        <div class="card-body">
                            <i class="fas fa-user-check"></i>
                            <div>
                                <h5>นักเรียนที่ Active</h5>
                                <p id="active-students"><?php echo $active_students; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card" data-bs-toggle="tooltip" data-bs-placement="top" title="ดูการลงทะเบียนวันนี้" onclick="window.location.href='registrations_today.php'">
                        <div class="card-header">
                            <i class="fas fa-ticket-alt"></i> การลงทะเบียนวันนี้
                        </div>
                        <div class="card-body">
                            <i class="fas fa-ticket-alt"></i>
                            <div>
                                <h5>การลงทะเบียนวันนี้</h5>
                                <p id="registrations-today"><?php echo $registrations_today; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Car and Queue Overview -->
            <div class="section-title">ภาพรวมรถและคิว</div>
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card" data-bs-toggle="tooltip" data-bs-placement="top" title="ดูรายละเอียดรถทั้งหมด" onclick="window.location.href='cars.php'">
                        <div class="card-header">
                            <i class="fas fa-bus"></i> รถทั้งหมด
                        </div>
                        <div class="card-body">
                            <i class="fas fa-bus"></i>
                            <div>
                                <h5>รถทั้งหมด</h5>
                                <p id="total-cars"><?php echo $total_cars; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card" data-bs-toggle="tooltip" data-bs-placement="top" title="ดูรถที่พร้อมใช้งาน" onclick="window.location.href='available_cars.php'">
                        <div class="card-header">
                            <i class="fas fa-check-circle"></i> รถที่พร้อมใช้งาน
                        </div>
                        <div class="card-body">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <h5>รถที่พร้อมใช้งาน</h5>
                                <p id="available-cars"><?php echo $available_cars; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card" data-bs-toggle="tooltip" data-bs-placement="top" title="ดูคิวที่รอดำเนินการ" onclick="window.location.href='pending_queues.php'">
                        <div class="card-header">
                            <i class="fas fa-clock"></i> คิวที่รอดำเนินการ
                        </div>
                        <div class="card-body">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h5>คิวที่รอดำเนินการ</h5>
                                <p id="pending-queues"><?php echo $pending_queues; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Provinces Chart -->
            <div class="section-title">5 อันดับจังหวัดที่มีการลงทะเบียนมากที่สุด</div>
            <div class="chart-container">
                <canvas id="topProvincesChart"></canvas>
            </div>

            <!-- Recent Queues -->
            <div class="section-title">คิวล่าสุด</div>
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>สถานที่</th>
                            <th>จังหวัด</th>
                            <th>อำเภอ</th>
                            <th>ป้ายทะเบียนรถ</th>
                            <th>วันที่</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_queues as $index => $queue): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($queue['location']); ?></td>
                                <td><?php echo htmlspecialchars($queue['PROVINCE_NAME']); ?></td>
                                <td><?php echo htmlspecialchars($queue['AMPHUR_NAME']); ?></td>
                                <td><?php echo htmlspecialchars($queue['car_license']); ?></td>
                                <td><?php echo htmlspecialchars($queue['queue_date']); ?></td>
                                <td class="status-car-<?php echo htmlspecialchars($queue['status_car']); ?>">
                                    <?php echo htmlspecialchars($queue['status_car']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Registrations -->
            <div class="section-title">การลงทะเบียนล่าสุด</div>
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>สถานที่</th>
                            <th>วันที่ลงทะเบียน</th>
                            <th>สถานะการชำระเงิน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_registrations as $index => $registration): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($registration['stu_name'] . ' ' . $registration['stu_lastname']); ?></td>
                                <td><?php echo htmlspecialchars($registration['location']); ?></td>
                                <td><?php echo htmlspecialchars($registration['created_at']); ?></td>
                                <td class="status-<?php echo strtolower($registration['payment_status'] == 'Pending Confirmation' ? 'pending' : 'paid'); ?>">
                                    <?php echo htmlspecialchars($registration['payment_status']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle Functionality with localStorage
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        const closeBtn = document.getElementById('close-btn');
        const openBtn = document.getElementById('open-btn');

        // โหลดสถานะ Sidebar จาก localStorage
        window.addEventListener('load', () => {
            const sidebarState = localStorage.getItem('sidebarState');
            if (sidebarState === 'closed') {
                sidebar.classList.add('closed');
                content.classList.add('closed');
            }
        });

        // ซ่อน Sidebar
        closeBtn.addEventListener('click', () => {
            sidebar.classList.add('closed');
            content.classList.add('closed');
            localStorage.setItem('sidebarState', 'closed');
        });

        // เปิด Sidebar
        openBtn.addEventListener('click', () => {
            sidebar.classList.remove('closed');
            content.classList.remove('closed');
            localStorage.setItem('sidebarState', 'open');
        });

        // Initialize Chart.js
        const ctx = document.getElementById('topProvincesChart').getContext('2d');
        const topProvincesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($province_labels); ?>,
                datasets: [{
                    label: 'จำนวนการลงทะเบียน',
                    data: <?php echo json_encode($registration_counts); ?>,
                    backgroundColor: 'rgba(26, 115, 232, 0.6)',
                    borderColor: 'rgba(26, 115, 232, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'จำนวนการลงทะเบียน'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'จังหวัด'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                const amphurs = <?php echo json_encode($amphur_data); ?>[index];
                                const locations = <?php echo json_encode($location_data); ?>[index];
                                return [
                                    'อำเภอ: ' + amphurs.join(', '),
                                    'สถานที่: ' + locations.join(', ')
                                ];
                            }
                        }
                    }
                }
            }
        });

        // Refresh Timer
        let timer = 60;
        const timerElement = document.getElementById('refresh-timer');
        const refreshBtn = document.getElementById('refresh-btn');

        const countdown = setInterval(() => {
            timer--;
            timerElement.textContent = `รีเฟรชใน ${timer} วินาที`;
            if (timer <= 0) {
                window.location.reload();
            }
        }, 1000);

        refreshBtn.addEventListener('click', () => {
            window.location.reload();
        });

        // Initialize Tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    </script>
</body>
</html>