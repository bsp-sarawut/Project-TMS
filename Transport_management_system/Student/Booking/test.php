<?php
require_once 'config/condb.php';
session_start();

// ตรวจสอบว่านักศึกษาเข้าสู่ระบบแล้ว
if (!isset($_SESSION['stu_username'])) {
    header("Location: login.php");
    exit();
}

$stu_username = $_SESSION['stu_username'];

// จำนวนรายการต่อหน้า
$records_per_page = 5;

// รับหน้าปัจจุบันจาก URL (ถ้าไม่มีให้เริ่มที่หน้า 1)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($page - 1) * $records_per_page;

// ดึงข้อมูลการลงทะเบียนของนักศึกษาคนนั้น
$sql = "
    SELECT tr.*, r.location, p.PROVINCE_NAME, a.AMPHUR_NAME, s.stu_name, s.stu_lastname, ts.num_of_days AS schedule_num_of_days, ts.available_dates
    FROM transport_registration tr
    LEFT JOIN routes r ON tr.route_id = r.route_ID
    LEFT JOIN province p ON r.province = p.PROVINCE_ID
    LEFT JOIN amphur a ON r.amphur = a.AMPHUR_ID
    LEFT JOIN students s ON tr.stu_username = s.stu_username
    LEFT JOIN transport_schedule ts ON tr.transport_schedule_id = ts.id
    WHERE tr.stu_username = :stu_username
    ORDER BY tr.created_at DESC
    LIMIT :start_from, :records_per_page
";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':stu_username', $stu_username);
$stmt->bindParam(':start_from', $start_from, PDO::PARAM_INT);
$stmt->bindParam(':records_per_page', $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// นับจำนวนรายการทั้งหมด
$sql_count = "
    SELECT COUNT(*) as total
    FROM transport_registration tr
    WHERE tr.stu_username = :stu_username
";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bindParam(':stu_username', $stu_username);
$stmt_count->execute();
$total_records = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

// คำนวณจำนวนหน้าทั้งหมด
$total_pages = ceil($total_records / $records_per_page);

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตารางการขึ้นรถของฉัน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { 
            font-family: 'Kanit', sans-serif; 
            background: #f5f6f5; 
            min-height: 100vh; 
            margin: 0;
            display: flex;
        }
        .sidebar { 
            width: 250px; 
            transition: width 0.3s ease-in-out; 
        }
        .sidebar.closed { 
            width: 0; 
            overflow: hidden; 
        }
        .content { 
            margin-left: 250px; 
            padding: 20px; 
            flex-grow: 1; 
            transition: margin-left 0.3s ease-in-out; 
        }
        .content.expanded { 
            margin-left: 0; 
        }
        .card { 
            border-radius: 10px; 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); 
            background: #fff; 
            padding: 15px; 
            margin-bottom: 20px; 
        }
        .card h3 { 
            color: #333; 
            font-weight: 600; 
            border-bottom: 1px solid #e0e0e0; 
            padding-bottom: 5px; 
            margin-bottom: 15px; 
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
            text-align: center; 
        }
        .pagination { 
            justify-content: center; 
            margin-top: 20px; 
        }
        .pagination .page-link { 
            color: #007bff; 
            border-radius: 5px; 
            margin: 0 5px; 
        }
        .pagination .page-item.active .page-link { 
            background-color: #007bff; 
            border-color: #007bff; 
            color: #fff; 
        }
        .pagination .page-item.disabled .page-link { 
            color: #6c757d; 
        }
        .open-btn { 
            position: fixed; 
            top: 10px; 
            left: 10px; 
            z-index: 1000; 
            background: #007bff; 
            color: #fff; 
            border: none; 
            border-radius: 5px; 
            padding: 6px 12px; 
            cursor: pointer; 
            display: none; 
        }
        @media (max-width: 768px) {
            .content { 
                margin-left: 0; 
                padding: 15px; 
            }
            .sidebar { 
                position: fixed; 
                z-index: 1000; 
                height: 100%; 
            }
            .open-btn { 
                display: block; 
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <div class="container mt-4">
            <h2 class="text-center mb-4" style="color: #333; font-weight: 600;">ตารางการขึ้นรถของฉัน</h2>

            <div class="card mb-4">
                <h3 class="mb-3">ข้อมูลการลงทะเบียน - (รหัสนักศึกษา: <?php echo htmlspecialchars($stu_username); ?>)</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>รหัสการจอง</th>
                                <th>วันที่จอง</th>
                                <th>สถานะ</th>
                                <th>รายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registrations)): ?>
                                <tr><td colspan="4" class="text-center text-muted">ไม่มีข้อมูลการลงทะเบียน</td></tr>
                            <?php else: ?>
                                <?php foreach ($registrations as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo date("d/m/Y", strtotime($row['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detailModal" 
                                                    data-province="<?php echo htmlspecialchars($row['PROVINCE_NAME']); ?>" 
                                                    data-amphur="<?php echo htmlspecialchars($row['AMPHUR_NAME']); ?>" 
                                                    data-location="<?php echo htmlspecialchars($row['location']); ?>" 
                                                    data-days="<?php echo htmlspecialchars($row['schedule_num_of_days']); ?>" 
                                                    data-dates="<?php echo htmlspecialchars($row['available_dates']); ?>" 
                                                    data-price="<?php echo number_format($row['total_price'], 2); ?>">
                                                ดูรายละเอียด
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal รายละเอียด -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">รายละเอียดการจอง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>จังหวัด:</strong> <span id="modal-province"></span></p>
                    <p><strong>อำเภอ:</strong> <span id="modal-amphur"></span></p>
                    <p><strong>จุดขึ้นรถ:</strong> <span id="modal-location"></span></p>
                    <p><strong>จำนวนวัน:</strong> <span id="modal-days"></span> วัน</p>
                    <p><strong>วันที่ขึ้นรถ:</strong> <span id="modal-dates"></span></p>
                    <p><strong>ราคา:</strong> <span id="modal-price"></span> ฿</p>
                </div>
            </div>
        </div>
    </div>

    <button class="open-btn" id="open-btn">☰</button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('close-btn')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('closed');
            document.querySelector('.content').classList.add('expanded');
            document.getElementById('open-btn').style.display = 'block';
        });

        document.getElementById('open-btn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('closed');
            document.querySelector('.content').classList.remove('expanded');
            this.style.display = 'none';
        });

        // กรอกข้อมูลใน Modal รายละเอียด
        const detailModal = document.getElementById('detailModal');
        detailModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('modal-province').textContent = button.getAttribute('data-province') || '-';
            document.getElementById('modal-amphur').textContent = button.getAttribute('data-amphur') || '-';
            document.getElementById('modal-location').textContent = button.getAttribute('data-location') || '-';
            document.getElementById('modal-days').textContent = button.getAttribute('data-days');
            document.getElementById('modal-dates').textContent = button.getAttribute('data-dates');
            document.getElementById('modal-price').textContent = button.getAttribute('data-price');
        });
    </script>
</body>
</html>