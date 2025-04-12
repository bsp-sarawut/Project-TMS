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

// ดึงจำนวนผู้ใช้ที่ Active
$active_users = fetchSingleResult(
    $conn,
    "SELECT COUNT(*) AS active_users FROM students 
     WHERE TIMESTAMPDIFF(SECOND, last_login, NOW()) < 1800 
     AND stu_status = 'active'"
)['active_users'] ?? 0;

// ดึงจำนวนผู้ใช้ทั้งหมด
$total_users = fetchSingleResult(
    $conn,
    "SELECT COUNT(*) AS total_users FROM students"
)['total_users'] ?? 0;

// ดึงจำนวนการลงทะเบียนขึ้นรถที่ชำระเงินแล้วในวันนี้
$registered_paid_today = fetchSingleResult(
    $conn,
    "SELECT COUNT(*) AS registered_paid_today 
     FROM transport_registration 
     WHERE DATE(created_at) = CURDATE() 
     AND payment_status = 'paid'"
)['registered_paid_today'] ?? 0;

// ดึงรายการผู้ใช้ทั้งหมด
try {
    $stmt_users = $conn->prepare("SELECT stu_username, login_status, last_login, stu_name, stu_lastname 
                                   FROM students ORDER BY stu_ID DESC");
    $stmt_users->execute();
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            color: #212529;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        .content {
            margin-left: 250px; /* ปรับตามขนาดของ sidebar */
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        .content.collapsed {
            margin-left: 60px;
        }
        .open-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #0d6efd;
            color: #ffffff;
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
        .card {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background: #0d6efd;
            color: #ffffff;
            font-weight: 500;
            padding: 12px;
            border-bottom: none;
            border-radius: 8px 8px 0 0;
        }
        .card-body {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .card-body i {
            font-size: 1.8rem;
            color: #0d6efd;
        }
        .card-body h5 {
            margin: 0;
            font-size: 1.1rem;
            color: #212529;
        }
        .card-body p {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 500;
            color: #0d6efd;
        }
        .card-body a {
            color: #0d6efd;
            text-decoration: none;
        }
        .card-body a:hover {
            color: #0056b3;
        }
        .chart-container {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            height: 100%;
        }
        .chart-container h3 {
            color: #212529;
            font-weight: 500;
            margin-bottom: 20px;
            font-size: 1.25rem;
        }
        .table-container {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .table-container h3 {
            color: #212529;
            font-weight: 500;
            margin-bottom: 20px;
            font-size: 1.25rem;
        }
        .table {
            background: #ffffff;
            color: #212529;
            border-radius: 8px;
            overflow: hidden;
        }
        .table thead {
            background: #0d6efd;
            color: #ffffff;
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
        .status-active {
            color: #28a745;
            font-weight: 500;
        }
        .status-offline {
            color: #6c757d;
            font-weight: 500;
        }
        @media (max-width: 992px) {
            .content {
                margin-left: 60px;
            }
            .content.collapsed {
                margin-left: 60px;
            }
            .open-btn {
                left: 70px;
            }
            .card-body h5 {
                font-size: 1rem;
            }
            .card-body p {
                font-size: 1.2rem;
            }
            .card-body i {
                font-size: 1.5rem;
            }
            .table th, .table td {
                font-size: 0.9rem;
                padding: 8px;
            }
        }
        @media (max-width: 576px) {
            .content {
                margin-left: 0;
                padding: 10px;
            }
            .open-btn {
                left: 10px;
            }
            .open-btn.collapsed {
                left: 10px;
            }
            .card-body {
                flex-direction: column;
                text-align: center;
            }
            .card-body i {
                margin-bottom: 10px;
            }
            .table th, .table td {
                font-size: 0.8rem;
                padding: 6px;
            }
            .table th:nth-child(2), .table td:nth-child(2) {
                display: none; /* ซ่อน Full Name ในหน้าจอเล็ก */
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
            <!-- Summary Cards and Chart -->
            <div class="row">
                <!-- Active Users Card -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-users me-2"></i> Active Users
                        </div>
                        <div class="card-body">
                            <i class="fas fa-user-check"></i>
                            <div>
                                <h5>Active Users</h5>
                                <p><?php echo $active_users; ?> Users</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Users Card -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-users-cog me-2"></i> Total Users
                        </div>
                        <div class="card-body">
                            <i class="fas fa-users"></i>
                            <div>
                                <h5>Total Users</h5>
                                <p><?php echo $total_users; ?> Users</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Registered Students Paid Today Card -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-user-plus me-2"></i> New Registrations (Paid)
                        </div>
                        <div class="card-body">
                            <i class="fas fa-money-check-alt"></i>
                            <div>
                                <h5>New Registered Students (Paid)</h5>
                                <p><a href="enrollment.php"><?php echo $registered_paid_today; ?> Students</a></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="chart-container">
                        <h3>User Activity Overview</h3>
                        <canvas id="userChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Table of Users -->
            <div class="row">
                <div class="col-12">
                    <div class="table-container">
                        <h3>User List</h3>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th scope="col">Username</th>
                                    <th scope="col">Full Name</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Last Login</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($users as $user) {
                                    $last_login = $user['last_login'] ? date('d M Y H:i', strtotime($user['last_login'])) : 'Never';
                                    $status_class = $user['login_status'] === 'active' ? 'status-active' : 'status-offline';
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($user['stu_username']) . "</td>";
                                    echo "<td>" . htmlspecialchars($user['stu_name'] . " " . $user['stu_lastname']) . "</td>";
                                    echo "<td><span class='$status_class'>" . htmlspecialchars($user['login_status']) . "</span></td>";
                                    echo "<td>" . $last_login . "</td>";
                                    echo "</tr>";
                                }
                                ?>
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
                content.classList.add('collapsed');
                openBtn.classList.add('collapsed');
                document.querySelector('.sidebar').classList.add('collapsed'); // สมมติว่า sidebar มี class นี้
            } else {
                content.classList.remove('collapsed');
                openBtn.classList.remove('collapsed');
                document.querySelector('.sidebar').classList.remove('collapsed');
            }
        });

        // Chart.js - User Activity Overview
        const ctx = document.getElementById('userChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active Users', 'Inactive Users'],
                datasets: [{
                    data: [<?php echo $active_users; ?>, <?php echo $total_users - $active_users; ?>],
                    backgroundColor: ['#0d6efd', '#6c757d'],
                    borderColor: ['#ffffff', '#ffffff'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#212529',
                            font: {
                                size: 12,
                                family: "'Roboto', sans-serif"
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>