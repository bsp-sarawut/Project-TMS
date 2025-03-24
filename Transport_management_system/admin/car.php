<?php
include('config/condb.php');
session_start();

if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM car WHERE car_id = :car_id");
        $stmt->bindParam(':car_id', $delete_id);
        $stmt->execute();
        $_SESSION['success'] = "ลบข้อมูลรถยนต์เรียบร้อยแล้ว";
        header("Location: car.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
        header("Location: car.php");
        exit();
    }
}

$totalRows = 0;
$totalPages = 0;
$cars = [];
$searchQuery = "";
$params = [];

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit'])) {
    if (!empty($_POST['search'])) {
        $search = $_POST['search'];
        $searchQuery .= " WHERE (c.car_license LIKE :search OR c.car_brand LIKE :search OR c.car_color LIKE :search OR CONCAT(d.driver_name, ' ', d.driver_lastname) LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    if (!empty($_POST['queue_filter'])) {
        $queue_filter = $_POST['queue_filter'];
        if ($queue_filter == 'มีคิวรถ') {
            $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " q.queue_id IS NOT NULL";
        } elseif ($queue_filter == 'ไม่มีคิวรถ') {
            $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " q.queue_id IS NULL";
        }
    }
    if (!empty($_POST['status_filter'])) {
        $status_filter = $_POST['status_filter'];
        $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " c.car_status = :status_filter";
        $params[':status_filter'] = $status_filter;
    }
}

try {
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM car c 
                                 LEFT JOIN driver d ON c.driver_id = d.driver_id 
                                 LEFT JOIN queue q ON c.car_id = q.car_id 
                                 $searchQuery");
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการนับข้อมูล: " . $e->getMessage();
}

try {
    $sql = "SELECT c.*, 
            CONCAT(d.driver_name, ' ', d.driver_lastname) AS driver_fullname,
            q.queue_id AS queue_status
            FROM car c 
            LEFT JOIN driver d ON c.driver_id = d.driver_id 
            LEFT JOIN queue q ON c.car_id = q.car_id 
            $searchQuery
            LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}

try {
    $driverStmt = $conn->query("SELECT driver_id, CONCAT(driver_name, ' ', driver_lastname) AS driver_fullname 
                                FROM driver 
                                WHERE driver_id NOT IN (SELECT driver_id FROM car WHERE driver_id IS NOT NULL)");
    $availableDrivers = $driverStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $availableDrivers = [];
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลคนขับ: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการข้อมูลรถยนต์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Kanit', sans-serif; background: #f5f6f5; min-height: 100vh; }
        .content { margin-left: 250px; padding: 20px; transition: margin-left 0.3s ease; }
        .card { border-radius: 10px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); background: #fff; padding: 15px; margin-bottom: 20px; }
        .card h3 { color: #333; font-weight: 600; border-bottom: 1px solid #e0e0e0; padding-bottom: 5px; margin-bottom: 15px; }
        .form-label { font-weight: 500; color: #444; }
        .form-select, .form-control { border-radius: 5px; border: 1px solid #ccc; padding: 8px; }
        .form-select:focus, .form-control:focus { border-color: #007bff; box-shadow: 0 0 3px rgba(0, 123, 255, 0.3); }
        .btn-primary { background: #007bff; border: none; border-radius: 8px; padding: 8px 20px; transition: background 0.3s ease; }
        .btn-primary:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; transition: background 0.3s ease; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-warning { background: #f39c12; border: none; border-radius: 5px; padding: 5px 15px; color: #fff; }
        .btn-danger { background: #e74c3c; border: none; border-radius: 5px; padding: 5px 15px; color: #fff; }
        .table { border-radius: 5px; overflow: hidden; background: #fff; }
        .table thead th { background: #003087; color: #fff; text-align: center; padding: 12px; }
        .table tbody tr:hover { background: #f9f9f9; }
        .table td { vertical-align: middle; text-align: center; }
        .pagination { justify-content: center; margin-top: 20px; }
        .total-count { font-size: 1.1rem; color: #333; margin-bottom: 10px; }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>
    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="text-center mb-4" style="color: #333; font-weight: 600;">ระบบจัดการข้อมูลรถยนต์</h2>

            <div class="card mb-4">
                <h3 class="mb-3">ค้นหารถยนต์</h3>
                <form method="POST" action="" id="searchForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">ค้นหา</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="ทะเบียน, ยี่ห้อ, สี, ชื่อคนขับ" value="<?php echo isset($_POST['search']) ? $_POST['search'] : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="queue_filter" class="form-label">สถานะคิวรถ</label>
                            <select name="queue_filter" id="queue_filter" class="form-select">
                                <option value="">ทั้งหมด</option>
                                <option value="มีคิวรถ" <?php echo (isset($_POST['queue_filter']) && $_POST['queue_filter'] == 'มีคิวรถ') ? 'selected' : ''; ?>>มีคิวรถ</option>
                                <option value="ไม่มีคิวรถ" <?php echo (isset($_POST['queue_filter']) && $_POST['queue_filter'] == 'ไม่มีคิวรถ') ? 'selected' : ''; ?>>ไม่มีคิวรถ</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status_filter" class="form-label">สถานะ</label>
                            <select name="status_filter" id="status_filter" class="form-select">
                                <option value="">ทั้งหมด</option>
                                <option value="available" <?php echo (isset($_POST['status_filter']) && $_POST['status_filter'] == 'available') ? 'selected' : ''; ?>>พร้อมใช้งาน</option>
                                <option value="unavailable" <?php echo (isset($_POST['status_filter']) && $_POST['status_filter'] == 'unavailable') ? 'selected' : ''; ?>>ไม่พร้อมใช้งาน</option>
                            </select>
                        </div>
                    </div>
                    <div class="btn-group d-flex justify-content-start mt-3">
                        <button type="submit" name="submit" class="btn btn-primary me-2">ค้นหา</button>
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">เคลียร์ฟิลเตอร์</button>
                    </div>
                </form>
            </div>

            <div class="card mb-4">
                <h3 class="mb-3">รายการรถยนต์</h3>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCarModal">เพิ่มรถยนต์</button>
                </div>
                <div class="total-count">จำนวนรถยนต์ทั้งหมด: <?php echo $totalRows; ?> คัน</div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>NO.</th>
                                <th>หมายเลขทะเบียน</th>
                                <th>ยี่ห้อ</th>
                                <th>สี</th>
                                <th>จำนวนที่นั่ง</th>
                                <th>ชื่อคนขับ</th>
                                <th>สถานะ</th>
                                <th>รหัสคิว</th>
                                <th>รูปภาพ</th>
                                <th>การกระทำ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $index = ($page - 1) * $limit + 1; 
                            foreach ($cars as $car) { ?>
                                <tr>
                                    <td><?php echo $index++; ?></td>
                                    <td><?php echo $car['car_license']; ?></td>
                                    <td><?php echo $car['car_brand']; ?></td>
                                    <td><?php echo $car['car_color']; ?></td>
                                    <td><?php echo $car['car_seat']; ?></td>
                                    <td><?php echo $car['driver_fullname'] ?: 'ไม่ระบุ'; ?></td>
                                    <td><?php echo $car['car_status'] == 'available' ? 'พร้อมใช้งาน' : 'ไม่พร้อมใช้งาน'; ?></td>
                                    <td><?php echo $car['queue_status'] ? 'Q-' . $car['queue_status'] : 'ไม่มีคิวรถ'; ?></td>
                                    <td>
                                        <?php if (!empty($car['car_image']) && file_exists("uploads/cars/" . $car['car_image'])) { ?>
                                            <img src="uploads/cars/<?php echo $car['car_image']; ?>" 
                                                 alt="Car Image" 
                                                 class="car-image clickable" 
                                                 style="max-width: 100px; max-height: 100px; object-fit: cover; cursor: pointer;"
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#imageModal" 
                                                 data-image="uploads/cars/<?php echo $car['car_image']; ?>">
                                        <?php } else { ?>
                                            ไม่มีรูปภาพ
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm edit-btn" 
                                                data-car_id="<?php echo $car['car_id']; ?>"
                                                data-car_license="<?php echo $car['car_license']; ?>"
                                                data-car_brand="<?php echo $car['car_brand']; ?>"
                                                data-car_color="<?php echo $car['car_color']; ?>"
                                                data-car_seat="<?php echo $car['car_seat']; ?>"
                                                data-car_status="<?php echo $car['car_status']; ?>"
                                                data-driver_id="<?php echo $car['driver_id']; ?>"
                                                data-car_image="<?php echo $car['car_image']; ?>">แก้ไข</button>
                                        <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                                data-delete_id="<?php echo $car['car_id']; ?>">ลบ</button>
                                    </td>
                                </tr>
                            <?php } ?>
                            <?php if (empty($cars)) { ?>
                                <tr><td colspan="10" class="text-center">ไม่มีข้อมูลรถยนต์</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1) { ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">«</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php } ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">»</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addCarModal" tabindex="-1" aria-labelledby="addCarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCarModalLabel">เพิ่มรถยนต์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCarForm" method="POST" action="car_insert.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="car_license_add" class="form-label">หมายเลขทะเบียนรถ:</label>
                            <input type="text" id="car_license_add" name="car_license" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="car_brand_add" class="form-label">ยี่ห้อรถ:</label>
                            <input type="text" id="car_brand_add" name="car_brand" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="car_color_add" class="form-label">สีรถ:</label>
                            <input type="text" id="car_color_add" name="car_color" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="car_seat_add" class="form-label">จำนวนที่นั่ง:</label>
                            <input type="number" id="car_seat_add" name="car_seat" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="driver_id_add" class="form-label">ชื่อคนขับ:</label>
                            <select id="driver_id_add" name="driver_id" class="form-control">
                                <option value="">ไม่ระบุ</option>
                                <?php foreach ($availableDrivers as $driver) { ?>
                                    <option value="<?php echo $driver['driver_id']; ?>"><?php echo $driver['driver_fullname']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="car_status_add" class="form-label">สถานะ:</label>
                            <select id="car_status_add" name="car_status" class="form-control">
                                <option value="available">พร้อมใช้งาน</option>
                                <option value="unavailable">ไม่พร้อมใช้งาน</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="car_image_add" class="form-label">รูปภาพรถยนต์:</label>
                            <input type="file" id="car_image_add" name="car_image" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">เพิ่มรถยนต์</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCarModal" tabindex="-1" aria-labelledby="editCarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCarModalLabel">แก้ไขข้อมูลรถยนต์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editCarForm" method="POST" action="car_update.php" enctype="multipart/form-data">
                        <input type="hidden" id="car_id_edit" name="car_id">
                        <div class="mb-3">
                            <label for="car_license_edit" class="form-label">หมายเลขทะเบียนรถ:</label>
                            <input type="text" id="car_license_edit" name="car_license" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="car_brand_edit" class="form-label">ยี่ห้อรถ:</label>
                            <input type="text" id="car_brand_edit" name="car_brand" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="car_color_edit" class="form-label">สีรถ:</label>
                            <input type="text" id="car_color_edit" name="car_color" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="car_seat_edit" class="form-label">จำนวนที่นั่ง:</label>
                            <input type="number" id="car_seat_edit" name="car_seat" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="driver_id_edit" class="form-label">ชื่อคนขับ:</label>
                            <select id="driver_id_edit" name="driver_id" class="form-control">
                                <option value="">ไม่ระบุ</option>
                                <?php foreach ($availableDrivers as $driver) { ?>
                                    <option value="<?php echo $driver['driver_id']; ?>"><?php echo $driver['driver_fullname']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="car_status_edit" class="form-label">สถานะ:</label>
                            <select id="car_status_edit" name="car_status" class="form-control">
                                <option value="available">พร้อมใช้งาน</option>
                                <option value="unavailable">ไม่พร้อมใช้งาน</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="car_image_edit" class="form-label">รูปภาพรถยนต์:</label>
                            <input type="file" id="car_image_edit" name="car_image" class="form-control">
                            <img id="current_car_image" src="" alt="Current Image" style="max-width: 100px; max-height: 100px; object-fit: cover; margin-top: 10px; display: none;">
                        </div>
                        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">รูปภาพรถยนต์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Car Image" style="max-width: 100%; max-height: 70vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#addCarForm').on('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'ยืนยันการเพิ่มรถยนต์?',
                    text: 'คุณต้องการเพิ่มข้อมูลรถยนต์นี้หรือไม่?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var formData = new FormData(this);
                        $.ajax({
                            url: 'car_insert.php',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function(response) {
                                if (response === "success") {
                                    Swal.fire('สำเร็จ!', 'เพิ่มข้อมูลรถยนต์เรียบร้อยแล้ว', 'success').then(() => {
                                        window.location.href = 'car.php';
                                    });
                                } else {
                                    Swal.fire('เกิดข้อผิดพลาด!', response, 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถเพิ่มข้อมูลได้: ' + xhr.responseText, 'error');
                            }
                        });
                    }
                });
            });

            $('.edit-btn').on('click', function() {
                var car_id = $(this).data('car_id');
                var car_license = $(this).data('car_license');
                var car_brand = $(this).data('car_brand');
                var car_color = $(this).data('car_color');
                var car_seat = $(this).data('car_seat');
                var car_status = $(this).data('car_status');
                var driver_id = $(this).data('driver_id') || '';
                var car_image = $(this).data('car_image') || '';

                console.log('Driver ID:', driver_id);
                console.log('Car Image:', car_image);

                $('#car_id_edit').val(car_id);
                $('#car_license_edit').val(car_license);
                $('#car_brand_edit').val(car_brand);
                $('#car_color_edit').val(car_color);
                $('#car_seat_edit').val(car_seat);
                $('#car_status_edit').val(car_status);
                $('#driver_id_edit').val(driver_id);

                if (car_image && car_image !== '') {
                    $('#current_car_image').attr('src', 'uploads/cars/' + car_image).show();
                } else {
                    $('#current_car_image').hide();
                }

                $('#editCarModal').modal('show');
            });

            $('#editCarForm').on('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'ยืนยันการแก้ไข?',
                    text: 'คุณต้องการบันทึกการแก้ไขข้อมูลรถยนต์นี้หรือไม่?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var formData = new FormData(this);
                        $.ajax({
                            url: 'car_update.php',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function(response) {
                                console.log('Update Response:', response);
                                if (response === "success") {
                                    Swal.fire('สำเร็จ!', 'แก้ไขข้อมูลรถยนต์เรียบร้อยแล้ว', 'success').then(() => {
                                        window.location.href = 'car.php';
                                    });
                                } else {
                                    Swal.fire('เกิดข้อผิดพลาด!', response, 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถแก้ไขข้อมูลได้: ' + xhr.responseText, 'error');
                            }
                        });
                    }
                });
            });

            $('.delete-btn').on('click', function() {
                var delete_id = $(this).data('delete_id');
                console.log('Delete ID:', delete_id);
                Swal.fire({
                    title: 'คุณแน่ใจหรือไม่?',
                    text: 'คุณต้องการลบข้อมูลรถยนต์นี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'car.php',
                            type: 'GET',
                            data: { delete_id: delete_id },
                            success: function(response) {
                                Swal.fire('สำเร็จ!', 'ลบข้อมูลรถยนต์เรียบร้อยแล้ว', 'success').then(() => {
                                    window.location.href = 'car.php';
                                });
                            },
                            error: function(xhr) {
                                Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถลบข้อมูลได้: ' + xhr.responseText, 'error');
                            }
                        });
                    }
                });
            });

            $('.car-image').on('click', function() {
                var imageSrc = $(this).data('image');
                $('#modalImage').attr('src', imageSrc);
            });

            <?php if (isset($_SESSION['success'])) { ?>
                Swal.fire('สำเร็จ!', '<?php echo $_SESSION['success']; ?>', 'success');
                <?php unset($_SESSION['success']); ?>
            <?php } ?>
            <?php if (isset($_SESSION['error'])) { ?>
                Swal.fire('เกิดข้อผิดพลาด!', '<?php echo $_SESSION['error']; ?>', 'error');
                <?php unset($_SESSION['error']); ?>
            <?php } ?>

            $('.close-btn').on('click', function() {
                $('.sidebar').addClass('closed');
                $('.content').addClass('closed');
            });
            $('.open-btn').on('click', function() {
                $('.sidebar').removeClass('closed');
                $('.content').removeClass('closed');
            });
        });

        function clearFilters() {
            $('#search').val('');
            $('#queue_filter').val('');
            $('#status_filter').val('');
            $.ajax({
                url: 'car.php',
                type: 'POST',
                data: { submit: true, search: '', queue_filter: '', status_filter: '' },
                success: function(response) {
                    var newContent = $(response).find('.table-responsive').html();
                    $('.table-responsive').html(newContent);
                    var newTotal = $(response).find('. warfarin-count').text();
                    $('.total-count').text(newTotal);
                    var newPagination = $(response).find('.pagination').html();
                    $('.pagination').html(newPagination);
                },
                error: function(xhr) {
                    Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถรีเซ็ตข้อมูลได้: ' + xhr.responseText, 'error');
                }
            });
        }
    </script>
</body>
</html>