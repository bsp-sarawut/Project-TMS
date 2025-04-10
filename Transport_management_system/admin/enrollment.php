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

$sql .= " ORDER BY tr.created_at DESC";
$stmt = $conn->prepare($sql);

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
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.0/dist/sweetalert2.all.min.js"></script>
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
            background: #007bff; 
            border: none; 
            border-radius: 8px; 
            padding: 8px 20px; 
            transition: background 0.3s ease; 
        }
        .btn-primary:hover { 
            background: #0056b3; 
        }
        .btn-success { 
            background: #28a745; 
            border: none; 
            border-radius: 5px; 
            padding: 5px 15px; 
            color: #fff; 
        }
        .btn-danger { 
            background: #e74c3c; 
            border: none; 
            border-radius: 5px; 
            padding: 5px 15px; 
            color: #fff; 
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
        .total-count { 
            font-size: 1.1rem; 
            color: #333; 
            margin-bottom: 10px; 
        }
        .img-thumbnail { 
            max-width: 100px; 
            max-height: 100px; 
            object-fit: cover; 
            cursor: pointer; 
        }
        .modal-content { 
            border-radius: 10px; 
        }
        .modal-header { 
            background: #007bff; 
            color: #fff; 
            border-radius: 10px 10px 0 0; 
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
            <h2 class="text-center mb-4" style="color: #333; font-weight: 600;">จัดการการลงทะเบียน</h2>

            <div class="card mb-4">
                <h3 class="mb-3">ค้นหาการลงทะเบียน</h3>
                <form method="get" action="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">ค้นหา</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                                placeholder="กรอกข้อมูลเพื่อค้นหา">
                        </div>
                        <div class="col-md-4">
                            <label for="payment_status" class="form-label">สถานะการชำระเงิน</label>
                            <select name="payment_status" id="payment_status" class="form-select">
                                <option value="">ทั้งหมด</option>
                                <option value="Pending Confirmation" <?= isset($_GET['payment_status']) && $_GET['payment_status'] == 'Pending Confirmation' ? 'selected' : '' ?>>Pending Confirmation</option>
                                <option value="Paid" <?= isset($_GET['payment_status']) && $_GET['payment_status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="province" class="form-label">จังหวัด</label>
                            <select name="province" id="province" class="form-select" onchange="loadAmphur()">
                                <option value="">ทั้งหมด</option>
                                <?php
                                $provinceQuery = $conn->query("SELECT * FROM province");
                                while ($province = $provinceQuery->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?= $province['PROVINCE_ID'] ?>" <?= isset($_GET['province']) && $_GET['province'] == $province['PROVINCE_ID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($province['PROVINCE_NAME']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="amphur" class="form-label">อำเภอ</label>
                            <select name="amphur" id="amphur" class="form-select" onchange="loadLocation()">
                                <option value="">ทั้งหมด</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="location" class="form-label">จุดขึ้นรถ</label>
                            <select name="location" id="location" class="form-select">
                                <option value="">ทั้งหมด</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>ค้นหา</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card mb-4">
                <h3 class="mb-3">รายการการลงทะเบียน</h3>
                <div class="table-responsive">
                    <div class="total-count">จำนวนการลงทะเบียนทั้งหมด: <?php echo count($registrations); ?> รายการ</div>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ชื่อ-นามสกุล</th>
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
                                    <td><?= htmlspecialchars($row['stu_name'] . ' ' . $row['stu_lastname']) ?></td>
                                    <td><?= date("d/m/Y", strtotime($row['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($row['schedule_num_of_days']) ?> วัน<br><small>(<?= htmlspecialchars($row['available_dates']) ?>)</small></td>
                                    <td><?= number_format($row['total_price'], 2) ?> ฿</td>
                                    <td><?= htmlspecialchars($row['PROVINCE_NAME']) ?: '-' ?></td>
                                    <td><?= htmlspecialchars($row['AMPHUR_NAME']) ?: '-' ?></td>
                                    <td><?= htmlspecialchars($row['location']) ?: '-' ?></td>
                                    <td>
                                        <?php if (!empty($row['payment_receipt_image'])): ?>
                                            <img src="../Student/booking/<?= htmlspecialchars($row['payment_receipt_image']) ?>" 
                                                 class="img-thumbnail" 
                                                 onclick="openImagePreview('../Student/booking/<?= htmlspecialchars($row['payment_receipt_image']) ?>')">
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
                                            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check"></i></button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="<?= $row['id'] ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imagePreviewModalLabel">ดูตัวอย่างใบเสร็จ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="previewImage" src="" alt="Preview Image" style="max-width: 100%; max-height: 70vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <button class="open-btn" id="open-btn">☰</button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openImagePreview(imagePath) {
            document.getElementById('previewImage').src = imagePath;
            const imagePreviewModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
            imagePreviewModal.show();
        }

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
                document.getElementById('amphur').innerHTML = '<option value="">ทั้งหมด</option>';
            }
        });

        function loadLocation() {
            var provinceID = document.getElementById('province').value;
            var amphurID = document.getElementById('amphur').value;
            if (provinceID && amphurID) {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'get_location.php?province_id=' + provinceID + '&hur_id=' + amphurID, true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        document.getElementById('location').innerHTML = xhr.responseText;
                    }
                };
                xhr.send();
            } else {
                document.getElementById('location').innerHTML = '<option value="">ทั้งหมด</option>';
            }
        }

        // SweetAlert2 สำหรับการลบ
        $(document).ready(function() {
            $('.delete-btn').on('click', function() {
                var deleteId = $(this).data('id');
                Swal.fire({
                    title: 'คุณแน่ใจหรือไม่?',
                    text: 'คุณต้องการลบข้อมูลการลงทะเบียนนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'enrollment_delete.php',
                            type: 'POST',
                            data: { id: deleteId, delete: true },
                            success: function(response) {
                                Swal.fire('สำเร็จ!', 'ลบข้อมูลการลงทะเบียนเรียบร้อยแล้ว', 'success').then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function(xhr) {
                                Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถลบข้อมูลได้: ' + xhr.responseText, 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>