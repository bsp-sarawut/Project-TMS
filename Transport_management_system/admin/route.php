<?php
include 'config/condb.php';
session_start();

// ตรวจสอบการลบข้อมูล
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM routes WHERE route_ID = :route_ID");
        $stmt->bindParam(':route_ID', $delete_id);
        $stmt->execute();
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'ลบข้อมูลสำเร็จ',
                    text: 'ลบข้อมูลเส้นทางเรียบร้อยแล้ว',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = 'route.php';
                });
            });
        </script>";
    } catch (PDOException $e) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage() . "',
                    confirmButtonText: 'ตกลง'
                });
            });
        </script>";
    }
}

// ค้นหาข้อมูลตามเงื่อนไข
$searchQuery = "";
$params = [];
if (isset($_POST['search']) && !empty($_POST['search'])) {
    $search = $_POST['search'];
    $searchQuery = "WHERE r.location LIKE :search OR p.PROVINCE_NAME LIKE :search OR a.AMPHUR_NAME LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

if (isset($_POST['province_filter']) && !empty($_POST['province_filter'])) {
    $province_filter = $_POST['province_filter'];
    $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " r.province = :province_filter";
    $params[':province_filter'] = $province_filter;
}

if (isset($_POST['amphur_filter']) && !empty($_POST['amphur_filter'])) {
    $amphur_filter = $_POST['amphur_filter'];
    $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " r.amphur = :amphur_filter";
    $params[':amphur_filter'] = $amphur_filter;
}

// การแบ่งหน้า (Pagination)
$limit = 10; // จำนวนแถวต่อหน้า
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// นับจำนวนข้อมูลทั้งหมด
try {
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM routes r
                                LEFT JOIN province p ON r.province = p.PROVINCE_ID
                                LEFT JOIN amphur a ON r.amphur = a.AMPHUR_ID
                                $searchQuery");
    $countStmt->execute($params);
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);
} catch (PDOException $e) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'เกิดข้อผิดพลาดในการนับข้อมูล: " . $e->getMessage() . "',
                confirmButtonText: 'ตกลง'
            });
        });
    </script>";
}

// ดึงข้อมูลจังหวัด
try {
    $stmt = $conn->prepare("SELECT * FROM province");
    $stmt->execute();
    $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'เกิดข้อผิดพลาดในการดึงข้อมูลจังหวัด: " . $e->getMessage() . "',
                confirmButtonText: 'ตกลง'
            });
        });
    </script>";
}

// ดึงข้อมูลเส้นทางตามหน้า
try {
    $stmt = $conn->prepare("SELECT r.*, p.PROVINCE_NAME, a.AMPHUR_NAME
                            FROM routes r
                            LEFT JOIN province p ON r.province = p.PROVINCE_ID
                            LEFT JOIN amphur a ON r.amphur = a.AMPHUR_ID
                            $searchQuery
                            LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage() . "',
                confirmButtonText: 'ตกลง'
            });
        });
    </script>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการเส้นทาง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: #f5f6f5;
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
        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
            transition: background 0.3s ease;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-warning {
            background: #f39c12;
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
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        .total-count {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="body_route">
    <?php include 'sidebar.php'; ?>
    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="text-center mb-4" style="color: #333; font-weight: 600;">ระบบจัดการเส้นทาง</h2>

            <!-- ส่วนที่ 1: ฟอร์มค้นหาเส้นทาง -->
            <div class="card mb-4">
                <h3 class="mb-3">ค้นหาเส้นทาง</h3>
                <form method="POST" action="" id="searchForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="province_filter" class="form-label">จังหวัด</label>
                            <select name="province_filter" id="province_filter" class="form-select" onchange="fetchAmphurs(this.value, 'filter')">
                                <option value="">เลือกจังหวัด</option>
                                <?php foreach ($provinces as $province) { ?>
                                    <option value="<?php echo $province['PROVINCE_ID']; ?>" <?php echo (isset($province_filter) && $province_filter == $province['PROVINCE_ID']) ? 'selected' : ''; ?>>
                                        <?php echo $province['PROVINCE_NAME']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="amphur_filter" class="form-label">อำเภอ</label>
                            <select name="amphur_filter" id="amphur_filter" class="form-select">
                                <option value="">เลือกอำเภอ</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">ค้นหาเส้นทาง</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="ค้นหาเส้นทาง" value="<?php echo isset($search) ? $search : ''; ?>">
                        </div>
                    </div>
                    <div class="btn-group d-flex justify-content-start mt-3">
                        <button type="submit" class="btn btn-primary me-2">ค้นหา</button>
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">เคลียร์ฟิลเตอร์</button>
                    </div>
                </form>
            </div>

            <!-- ส่วนที่ 2: รายการเส้นทาง -->
            <div class="card mb-4">
                <h3 class="mb-3">รายการเส้นทาง</h3>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRouteModal">
                        เพิ่มเส้นทาง
                    </button>
                </div>
                <div class="total-count">จำนวนเส้นทางทั้งหมด: <?php echo $totalRows; ?> เส้นทาง</div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>NO.</th>
                                <th>จังหวัด</th>
                                <th>อำเภอ</th>
                                <th>สถานที่</th>
                                <th>ราคา</th>
                                <th>การกระทำ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $index = ($page - 1) * $limit + 1; 
                            foreach ($routes as $route) { ?>
                                <tr>
                                    <td><?php echo $index++; ?></td>
                                    <td><?php echo $route['PROVINCE_NAME']; ?></td>
                                    <td><?php echo $route['AMPHUR_NAME']; ?></td>
                                    <td><?php echo $route['location']; ?></td>
                                    <td><?php echo number_format($route['price'], 2); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editRouteModal"
                                                data-route_id="<?php echo $route['route_ID']; ?>"
                                                data-province="<?php echo $route['province']; ?>"
                                                data-amphur="<?php echo $route['amphur']; ?>"
                                                data-location="<?php echo $route['location']; ?>"
                                                data-price="<?php echo $route['price']; ?>">แก้ไข</button>
                                        <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                                data-delete-id="<?php echo $route['route_ID']; ?>">ลบ</button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1) { ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php } ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มเส้นทาง -->
    <div class="modal fade" id="addRouteModal" tabindex="-1" aria-labelledby="addRouteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRouteModalLabel">เพิ่มเส้นทาง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="route_insert.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="province" class="form-label">จังหวัด:</label>
                            <select id="province" name="PROVINCE_ID" class="form-control" onchange="fetchAmphurs(this.value, 'add')" required>
                                <option value="">เลือกจังหวัด</option>
                                <?php foreach ($provinces as $province) { ?>
                                    <option value="<?php echo $province['PROVINCE_ID']; ?>">
                                        <?php echo $province['PROVINCE_NAME']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="amphur_add" class="form-label">อำเภอ:</label>
                            <select id="amphur_add" name="amphur_id" class="form-control" required>
                                <option value="">เลือกอำเภอ</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">สถานที่:</label>
                            <input type="text" id="location" name="location" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">ราคา:</label>
                            <input type="number" step="0.01" id="price" name="price" class="form-control" required>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary">เพิ่มเส้นทาง</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขข้อมูลเส้นทาง -->
    <div class="modal fade" id="editRouteModal" tabindex="-1" aria-labelledby="editRouteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRouteModalLabel">แก้ไขเส้นทาง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="route_update.php" enctype="multipart/form-data">
                        <input type="hidden" id="route_ID" name="route_ID">
                        <div class="mb-3">
                            <label for="province_edit" class="form-label">จังหวัด:</label>
                            <select id="province_edit" name="PROVINCE_ID" class="form-control" onchange="fetchAmphurs(this.value, 'edit')" required>
                                <option value="">เลือกจังหวัด</option>
                                <?php foreach ($provinces as $province) { ?>
                                    <option value="<?php echo $province['PROVINCE_ID']; ?>">
                                        <?php echo $province['PROVINCE_NAME']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="amphur_edit" class="form-label">อำเภอ:</label>
                            <select id="amphur_edit" name="amphur_id" class="form-control" required>
                                <option value="">เลือกอำเภอ</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="location_edit" class="form-label">สถานที่:</label>
                            <input type="text" id="location_edit" name="location" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="price_edit" class="form-label">ราคา:</label>
                            <input type="number" step="0.01" id="price_edit" name="price" class="form-control" required>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function fetchAmphurs(province_id, mode = 'add') {
            var amphur_select = (mode === 'add') ? '#amphur_add' : (mode === 'edit' ? '#amphur_edit' : '#amphur_filter');
            if (province_id) {
                $.ajax({
                    url: 'get_amphur.php',
                    type: 'POST',
                    data: { province_id: province_id },
                    success: function(data) {
                        $(amphur_select).empty().html(data || '<option value="">ไม่มีข้อมูลอำเภอ</option>');
                    },
                    error: function() {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลอำเภอได้', 'error');
                    }
                });
            } else {
                $(amphur_select).html('<option value="">เลือกอำเภอ</option>');
            }
        }

        $('#editRouteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var routeID = button.data('route_id');
            var province = button.data('province');
            var amphur = button.data('amphur');
            var location = button.data('location');
            var price = button.data('price');

            var modal = $(this);
            modal.find('#route_ID').val(routeID);
            modal.find('#province_edit').val(province);
            modal.find('#amphur_edit').val(amphur);
            modal.find('#location_edit').val(location);
            modal.find('#price_edit').val(price);

            fetchAmphurs(province, 'edit');
            setTimeout(() => modal.find('#amphur_edit').val(amphur), 500);
        });

        $(document).ready(function() {
            $('.close-btn').on('click', function() {
                $('.sidebar').addClass('closed');
                $('.content').addClass('closed');
            });

            $('.open-btn').on('click', function() {
                $('.sidebar').removeClass('closed');
                $('.content').removeClass('closed');
            });

            // SweetAlert สำหรับยืนยันการลบ
            $('.delete-btn').on('click', function() {
                var deleteId = $(this).data('delete-id');
                Swal.fire({
                    title: 'คุณแน่ใจหรือไม่?',
                    text: 'คุณต้องการลบข้อมูลเส้นทางนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'route.php?delete_id=' + deleteId;
                    }
                });
            });
        });

        function clearFilters() {
            $('#province_filter').val('');
            $('#amphur_filter').html('<option value="">เลือกอำเภอ</option>');
            $('#search').val('');
            $('#searchForm').submit();
        }
    </script>
</body>
</html>