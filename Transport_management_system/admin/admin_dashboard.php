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

// การแจ้งเตือน
$pending_confirmations = fetchSingleResult(
    $conn,
    "SELECT COUNT(*) AS pending_confirmations FROM transport_registration WHERE payment_status = 'Pending Confirmation'"
)['pending_confirmations'] ?? 0;

// ข้อมูลสำหรับกราฟแนวโน้มการลงทะเบียน 7 วัน
$registration_data = [];
$labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = fetchSingleResult(
        $conn,
        "SELECT COUNT(*) AS count FROM transport_registration WHERE DATE(created_at) = :date",
        ['date' => $date]
    )['count'] ?? 0;
    $registration_data[] = $count;
    $labels[] = date('d M', strtotime($date));
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f3f4;
            color: #212529;
            margin: 0;
            padding: 0;
        }
        .content {
            margin-left: 250px;
            padding: 30px;
        }
        .open-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #1a73e8;
            color: #ffffff;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 1000;
            transition: left 0.3s ease;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 20px;
        }
        .card {
            background: #ffffff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .table-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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
        }
        .alert-card i {
            font-size: 1.5rem;
            color: #dc3545;
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Content -->
    <div class="content" id="content">
        <div class="container mt-4">
            <!-- System Overview -->
            <div class="section-title">ภาพรวมระบบ</div>
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-users"></i> นักเรียนทั้งหมด
                        </div>
                        <div class="card-body">
                            <i class="fas fa-users"></i>
                            <div>
                                <h5>นักเรียนทั้งหมด</h5>
                                <p><?php echo $total_students; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-user-check"></i> นักเรียนที่ Active
                        </div>
                        <div class="card-body">
                            <i class="fas fa-user-check"></i>
                            <div>
                                <h5>นักเรียนที่ Active</h5>
                                <p><?php echo $active_students; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-ticket-alt"></i> การลงทะเบียนวันนี้
                        </div>
                        <div class="card-body">
                            <i class="fas fa-ticket-alt"></i>
                            <div>
                                <h5>การลงทะเบียนวันนี้</h5>
                                <p><?php echo $registrations_today; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Car and Queue Overview -->
            <div class="section-title">ภาพรวมรถและคิว</div>
            <div class="row">
                <div class="col-lg-3 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-bus"></i> รถทั้งหมด
                        </div>
                        <div class="card-body">
                            <i class="fas fa-bus"></i>
                            <div>
                                <h5>รถทั้งหมด</h5>
                                <p><?php echo $total_cars; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-check-circle"></i> รถที่พร้อมใช้งาน
                        </div>
                        <div class="card-body">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <h5>รถที่พร้อมใช้งาน</h5>
                                <p><?php echo $available_cars; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-hourglass-half"></i> คิวที่รอดำเนินการ
                        </div>
                        <div class="card-body">
                            <i class="fas fa-hourglass-half"></i>
                            <div>
                                <h5>คิวที่รอดำเนินการ</h5>
                                <p><?php echo $pending_queues; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 mb-4">
                    <div class="chart-container">
                        <canvas id="registrationChart" height="150"></canvas>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <div class="section-title">การแจ้งเตือน</div>
            <div class="row">
                <div class="col-12">
                    <?php if ($pending_confirmations > 0): ?>
                        <div class="alert-card">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>คุณมี <strong><?php echo $pending_confirmations; ?></strong> การลงทะเบียนที่รอการยืนยัน <a href="pending_registrations.php">ดูรายละเอียด</a></p>
                        </div>
                    <?php else: ?>
                        <div class="alert-card" style="border-left-color: #28a745;">
                            <i class="fas fa-check-circle" style="color: #28a745;"></i>
                            <p>ไม่มีรายการที่ต้องดำเนินการในขณะนี้</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <!-- Recent Queues -->
                <div class="col-lg-6 mb-4">
                    <div class="table-container">
                        <div class="section-title">คิวล่าสุด</div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>รหัสคิว</th>
                                    <th>สถานที่</th>
                                    <th>จังหวัด</th>
                                    <th>อำเภอ</th>
                                    <th>รถ</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_queues as $queue): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($queue['queue_id']); ?></td>
                                        <td><?php echo htmlspecialchars($queue['location']); ?></td>
                                        <td><?php echo htmlspecialchars($queue['PROVINCE_NAME']); ?></td>
                                        <td><?php echo htmlspecialchars($queue['AMPHUR_NAME']); ?></td>
                                        <td><?php echo htmlspecialchars($queue['car_license']); ?></td>
                                        <td>
                                            <span class="status-car-<?php echo htmlspecialchars($queue['status_car']); ?>">
                                                <?php echo htmlspecialchars($queue['status_car']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Registrations -->
                <div class="col-lg-6 mb-4">
                    <div class="table-container">
                        <div class="section-title">การลงทะเบียนล่าสุด</div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>รหัส</th>
                                    <th>นักเรียน</th>
                                    <th>สถานที่</th>
                                    <th>วันที่ลงทะเบียน</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_registrations as $reg): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reg['id']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['stu_name'] . ' ' . $reg['stu_lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['location']); ?></td>
                                        <td><?php echo date('d M Y H:i', strtotime($reg['created_at'])); ?></td>
                                        <td>
                                            <span class="status-<?php echo $reg['payment_status'] == 'Paid' ? 'paid' : 'pending'; ?>">
                                                <?php echo htmlspecialchars($reg['payment_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Open Sidebar Button -->
    <button class="open-btn" id="open-btn">☰</button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle Functionality
        const openBtn = document.getElementById('open-btn');
        const content = document.getElementById('content');
        let isCollapsed = false;

        openBtn.addEventListener('click', () => {
            isCollapsed = !isCollapsed;
            if (isCollapsed) {
                content.style.marginLeft = '60px';
                openBtn.style.left = '70px';
                document.querySelector('.sidebar').classList.add('collapsed');
            } else {
                content.style.marginLeft = '250px';
                openBtn.style.left = '20px';
                document.querySelector('.sidebar').classList.remove('collapsed');
            }
        });

        // Registration Trend Chart
        const registrationChartCtx = document.getElementById('registrationChart').getContext('2d');
        new Chart(registrationChartCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'การลงทะเบียน',
                    data: <?php echo json_encode($registration_data); ?>,
                    borderColor: '#1a73e8',
                    backgroundColor: 'rgba(26, 115, 232, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#6c757d',
                            font: {
                                family: "'Inter', sans-serif"
                            }
                        }
                    },
                    x: {
                        ticks: {
                            color: '#6c757d',
                            font: {
                                family: "'Inter', sans-serif"
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>