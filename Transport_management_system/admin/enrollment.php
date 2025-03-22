<?php
require_once 'config/condb.php';

$sql = "
    SELECT tr.*, r.location, p.PROVINCE_NAME, a.AMPHUR_NAME, s.stu_name, s.stu_lastname, ts.num_of_days AS schedule_num_of_days, ts.available_dates
    FROM transport_registration tr
    LEFT JOIN routes r ON tr.route_id = r.route_ID
    LEFT JOIN province p ON r.province = p.PROVINCE_ID
    LEFT JOIN amphur a ON r.amphur = a.AMPHUR_ID
    LEFT JOIN students s ON tr.stu_username = s.stu_username
    LEFT JOIN transport_schedule ts ON tr.transport_schedule_id = ts.id
    WHERE 1=1
";

if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search = "%" . $_GET['search'] . "%"; 
    $sql .= " AND (s.stu_name LIKE :search 
                OR s.stu_lastname LIKE :search 
                OR r.location LIKE :search
                OR p.PROVINCE_NAME LIKE :search
                OR a.AMPHUR_NAME LIKE :search
                OR tr.created_at LIKE :search
                OR ts.num_of_days LIKE :search
                OR tr.payment_status LIKE :search)";
}

if (isset($_GET['payment_status']) && $_GET['payment_status'] !== '') {
    $sql .= " AND tr.payment_status = :payment_status";
}
if (isset($_GET['province']) && $_GET['province'] !== '') {
    $sql .= " AND r.province = :province";
}
if (isset($_GET['amphur']) && $_GET['amphur'] !== '') {
    $sql .= " AND r.amphur = :amphur";
}
if (isset($_GET['location']) && $_GET['location'] !== '') {
    $sql .= " AND r.location = :location";
}

$sql .= " ORDER BY tr.created_at DESC";  // เรียงจากวันที่เพิ่มเข้ามาล่าสุด
$stmt = $conn->prepare($sql);

// การ bind ข้อมูล
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $stmt->bindParam(':search', $search);
}
if (isset($_GET['payment_status']) && $_GET['payment_status'] !== '') {
    $stmt->bindParam(':payment_status', $_GET['payment_status']);
}
if (isset($_GET['province']) && $_GET['province'] !== '') {
    $stmt->bindParam(':province', $_GET['province']);
}
if (isset($_GET['amphur']) && $_GET['amphur'] !== '') {
    $stmt->bindParam(':amphur', $_GET['amphur']);
}
if (isset($_GET['location']) && $_GET['location'] !== '') {
    $stmt->bindParam(':location', $_GET['location']);
}

$stmt->execute();
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการการลงทะเบียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.0/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
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
            flex-grow: 1;
            padding: 20px;
            overflow-x: auto;
            transition: margin-left 0.3s ease-in-out;
        }
        .content.expanded {
            margin-left: 0;
        }
        #search {
            width: 100%;  /* ให้ช่องค้นหาขยายเต็มความกว้าง */
        }

    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <h2 class="text-center mb-4">จัดการการลงทะเบียน</h2>
        <form method="get" action="">
    <div class="row mb-3">
        <div class="col-12 mb-3">
            <label for="search" class="form-label">ค้นหา</label>
            <input type="text" name="search" id="search" class="form-control" 
                value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                placeholder="กรอกข้อมูลเพื่อค้นหา">
        </div>
        <div class="col-md-3">
            <label for="payment_status" class="form-label">สถานะการชำระเงิน</label>
            <select name="payment_status" id="payment_status" class="form-select">
                <option value="">ทั้งหมด</option>
                <option value="Pending Confirmation" <?= isset($_GET['payment_status']) && $_GET['payment_status'] == 'Pending Confirmation' ? 'selected' : '' ?>>Pending Confirmation</option>
                <option value="Paid" <?= isset($_GET['payment_status']) && $_GET['payment_status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="province" class="form-label">จังหวัด</label>
            <select name="province" id="province" class="form-select" onchange="loadAmphur()">
                <option value="">ทั้งหมด</option>
                <?php
                $provinceQuery = $conn->query("SELECT * FROM province");
                while ($province = $provinceQuery->fetch(PDO::FETCH_ASSOC)): ?>
                    <option value="<?= $province['PROVINCE_ID'] ?>"><?= htmlspecialchars($province['PROVINCE_NAME']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="amphur" class="form-label">อำเภอ</label>
            <select name="amphur" id="amphur" class="form-select" onchange="loadLocation()">
                <option value="">ทั้งหมด</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="location" class="form-label">จุดขึ้นรถ</label>
            <select name="location" id="location" class="form-select">
                <option value="">ทั้งหมด</option>
            </select>
        </div>
        <!-- เพิ่มช่องค้นหาแบบเต็มหน้า -->
       
        <div class="col-12 mt-3">
            <button type="submit" class="btn btn-primary ">ค้นหา</button>
        </div>
    </div>
</form>

        <div class="table-container">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>ชื่อ นามสกุล</th>
                        <th>วันที่ลงทะเบียน</th>
                        <th>จำนวนวัน</th>
                        <th>ราคารวม</th>
                        <th>จังหวัด</th>
                        <th>อำเภอ</th>
                        <th>สถานที่</th>
                        <th>ใบเสร็จ</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $row): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['stu_name']) . ' ' . htmlspecialchars($row['stu_lastname']) ?></td>
                            <td><?= date("d/m/Y", strtotime($row['created_at'])) ?></td>
                            <td><?= htmlspecialchars($row['schedule_num_of_days']) ?> วัน (วันที่: <?= htmlspecialchars($row['available_dates']) ?>)</td>
                            <td><?= number_format(htmlspecialchars($row['total_price']), 2) ?> ฿</td>
                            <td><?= htmlspecialchars($row['PROVINCE_NAME']) ?></td>
                            <td><?= htmlspecialchars($row['AMPHUR_NAME']) ?></td>
                            <td><?= htmlspecialchars($row['location']) ?></td>
                            <td>
                                <?php if (!empty($row['payment_receipt_image'])): ?>
                                    <img src="../Student/booking/<?= htmlspecialchars($row['payment_receipt_image']) ?>" width="50" class="img-thumbnail" onclick="openImagePreview('../Student/booking/<?= htmlspecialchars($row['payment_receipt_image']) ?>')">
                                <?php else: ?>
                                    <span class="text-muted">ไม่มี</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="enrollment_update.php" class="d-flex align-items-center">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <select name="payment_status" class="form-select form-select-sm me-2">
                                        <option value="Pending Confirmation" <?= $row['payment_status'] == 'Pending Confirmation' ? 'selected' : '' ?>>Pending Confirmation</option>
                                        <option value="Paid" <?= $row['payment_status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                                    </select>
                                    <button type="submit" class="btn btn-success btn-sm">✔</button>
                                </form>
                            </td>
                            <td>
                                <form method="post" action="enrollment_delete.php" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm" onclick="return confirm('ยืนยันการลบ?')">🗑 ลบ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imagePreviewModalLabel">Preview รูปภาพ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="previewImage" src="" alt="Preview Image" style="max-width: 100%; height: auto;">
                </div>
            </div>
        </div>
    </div>

    <button class="open-btn" id="open-btn">&#9776; </button>
    <script>
        function openImagePreview(imagePath) {
            document.getElementById('previewImage').src = imagePath;
            const imagePreviewModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
            imagePreviewModal.show();
        }

        document.getElementById('close-btn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('closed');
            document.querySelector('.content').classList.add('expanded');
        });

        document.getElementById('open-btn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('closed');
            document.querySelector('.content').classList.remove('expanded');
        });

        document.querySelectorAll('.sidebar a').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.sidebar a').forEach(link => link.classList.remove('active'));
                this.classList.add('active');
            });
        });

        const logoutBtn = document.getElementById('logout');
        logoutBtn.addEventListener('click', (event) => {
            event.preventDefault();
            window.location.href = 'logout.php';
        });

        document.getElementById('province').addEventListener('change', function() {
            var provinceID = this.value;
            if (provinceID) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'get_amphur.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        document.getElementById('amphur').innerHTML = xhr.responseText;
                    }
                };
                xhr.send('province_id=' + provinceID);
            } else {
                document.getElementById('amphur').innerHTML = '<option value="">กรุณาเลือกจังหวัดก่อน</option>';
            }
        });

        function loadLocation() {
            var provinceID = document.getElementById('province').value;
            var amphurID = document.getElementById('amphur').value;
            if (provinceID && amphurID) {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'get_location.php?province_id=' + provinceID + '&amphur_id=' + amphurID, true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        document.getElementById('location').innerHTML = xhr.responseText;
                    }
                };
                xhr.send();
            } else {
                document.getElementById('location').innerHTML = '<option value="">กรุณาเลือกจังหวัดและอำเภอก่อน</option>';
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>