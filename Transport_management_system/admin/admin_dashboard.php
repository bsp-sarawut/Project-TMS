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
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Content -->
    <div class="content" id="content">
        <div class="container mt-4">
            <div class="row">
                <!-- Active Users Card -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Active Users</h5>
                            <p class="card-text"><?php echo $active_users; ?> Users</p>
                        </div>
                    </div>
                </div>

                <!-- Total Users Card -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Users</h5>
                            <p class="card-text"><?php echo $total_users; ?> Users</p>
                        </div>
                    </div>
                </div>

                <!-- New Registered Students Paid Today Card -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">New Registered Students (Paid)</h5>
                            <a href="enrollment.php" class="card-text text-white">
                                <?php echo $registered_paid_today; ?> Students
                            </a>
                        </div>
                    </div>
                </div>


            <!-- Table of Users -->
            <div class="row">
                <div class="col-12">
                    <h3 class="mb-4">User List</h3>
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
                                echo "<tr>";
                                echo "<td>" . $user['stu_username'] . "</td>";
                                echo "<td>" . $user['stu_name'] . " " . $user['stu_lastname'] . "</td>";
                                echo "<td>" . $user['login_status'] . "</td>";
                                echo "<td>" . ($user['last_login'] ? $user['last_login'] : 'Never') . "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Open Sidebar Button -->
    <button class="open-btn" id="open-btn">&#9776; </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar and menu functionality...
    </script>
</body>
</html>
