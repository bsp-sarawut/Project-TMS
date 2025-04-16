<?php
include('config/condb.php');
session_start();

if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM car WHERE car_id = :car_id");
        $stmt->bindParam(':car_id', $delete_id, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['success'] = "ลบข้อมูลรถยนต์เรียบร้อยแล้ว";
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
    header("Location: car.php");
    exit;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(to bottom, #f5f6f5, #e9ecef);
            min-height: 100vh;
            display: flex;
            margin: 0;
        }
        .sidebar {
            width: 250px;
            transition: transform 0.3s ease-in-out;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }
        .sidebar.closed {
            transform: translateX(-250px);
        }
        .content {
            margin-left: 250px;
            padding: 30px;
            flex-grow: 1;
            transition: margin-left 0.3s ease-in-out;
        }
        .content.closed {
            margin-left: 0;
        }
        .header-title {
            font-size: 2rem;
            color: #2c3e50;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background: #fff;
            padding: 20px;
            margin-bottom: 25px;
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card h3 {
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #007bff;
            padding-bottom: 8px;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        .form-label {
            font-weight: 500;
            color: #444;
            font-size: 0.95rem;
        }
        .form-select, .form-control {
            border-radius: 10px;
            border: 1px solid #ced4da;
            padding: 10px;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }
        .form-select:focus, .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
            background: #fff;
        }
        .btn-primary {
            background: #007bff;
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: #0056b3;
            transform: scale(1.05);
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: scale(1.05);
        }
        .btn-warning {
            background: #f39c12;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            color: #fff;
            transition: all 0.3s ease;
        }
        .btn-warning:hover {
            background: #d35400;
            transform: scale(1.05);
        }
        .btn-danger {
            background: #e74c3c;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            color: #fff;
            transition: all 0.3s ease;
        }
        .btn-danger:hover {
            background: #c0392b;
            transform: scale(1.05);
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }
        .table thead th {
            background: #003087;
            color: #fff;
            text-align: center;
            padding: 15px;
            font-weight: 500;
        }
        .table tbody tr {
            transition: background 0.2s ease;
        }
        .table tbody tr:hover {
            background: #f1f8ff;
        }
        .table td {
            vertical-align: middle;
            text-align: center;
            padding: 12px;
        }
        .pagination {
            justify-content: center;
            margin-top: 25px;
        }
        .pagination .page-item .page-link {
            border-radius: 8px;
            margin: 0 5px;
            color: #007bff;
            transition: all 0.3s ease;
        }
        .pagination .page-item.active .page-link {
            background: #007bff;
            border-color: #007bff;
            color: #fff;
        }
        .pagination .page-item .page-link:hover {
            background: #e9ecef;
        }
        .total-count {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .search-section {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .search-section:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        .search-container {
            margin-bottom: 20px;
        }
        .search-container .form-control {
            border-radius: 25px;
            padding: 12px 20px 12px 45px;
            border: 1px solid #ced4da;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        .search-container .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }
        .search-input-group {
            position: relative;
        }
        .search-input-group .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
            font-size: 1.1rem;
        }
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filter-row .col-md-3 {
            flex: 1;
            min-width: 200px;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .car-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        .car-image:hover {
            transform: scale(1.1);
        }
        .image-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #ddd;
            margin-top: 10px;
            display: none;
        }
        .image-preview-container {
            text-align: center;
            margin-top: 15px;
        }
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .modal-header {
            background: #007bff;
            color: #fff;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 20px;
        }
        .modal-title {
            font-weight: 600;
        }
        .modal-body {
            padding: 25px;
        }
        .modal-footer {
            border-top: none;
            padding: 15px 25px;
        }
        .modal-image {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 10px;
            margin: 0 auto;
            display: block;
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
            .filter-row {
                flex-direction: column;
            }
            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }
            .action-buttons .btn {
                width: 100%;
            }
            .header-title {
                font-size: 1.5rem;
            }
            .car-image {
                width: 60px;
                height: 60px;
            }
            .image-preview {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body class="body_car">
    <?php include('sidebar.php'); ?>
    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="header-title">ระบบจัดการข้อมูลรถยนต์</h2>

            <!-- แสดงข้อความแจ้งเตือน -->
            <?php if (isset($_SESSION['success'])) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: '<?php echo $_SESSION['success']; ?>',
                            confirmButtonText: 'ตกลง'
                        });
                    });
                </script>
                <?php unset($_SESSION['success']); ?>
            <?php } elseif (isset($_SESSION['error'])) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'ข้อผิดพลาด',
                            text: '<?php echo $_SESSION['error']; ?>',
                            confirmButtonText: 'ตกลง'
                        });
                    });
                </script>
                <?php unset($_SESSION['error']); ?>
            <?php } ?>

            <!-- ส่วนที่ 1: ฟอร์มค้นหารถยนต์ -->
            <div class="card mb-4 search-section">
                <h3 class="mb-3">ค้นหาข้อมูลรถยนต์</h3>
                <div class="search-container">
                    <label for="search" class="form-label">ค้นหา</label>
                    <div class="search-input-group">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" id="search" class="form-control" placeholder="ค้นหา: ทะเบียน, ยี่ห้อ, สี, ชื่อคนขับ">
                    </div>
                </div>
                <div class="filter-row">
                    <div class="col-md-3 col-12">
                        <label for="queue_filter" class="form-label">สถานะคิวรถ</label>
                        <select name="queue_filter" id="queue_filter" class="form-select">
                            <option value="">-- แสดงทั้งหมด --</option>
                            <option value="มีคิวรถ">มีคิวรถ</option>
                            <option value="ไม่มีคิวรถ">ไม่มีคิวรถ</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="status_filter" class="form-label">สถานะ</label>
                        <select name="status_filter" id="status_filter" class="form-select">
                            <option value="">-- แสดงทั้งหมด --</option>
                            <option value="available">พร้อมใช้งาน</option>
                            <option value="unavailable">ไม่พร้อมใช้งาน</option>
                        </select>
                    </div>
                </div>
                <div class="action-buttons">
                    <!-- <button type="button" class="btn btn-primary" onclick="loadCars()"><i class="fas fa-search me-2"></i>ค้นหา</button> -->
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()"><i class="fas fa-undo me-2"></i>เคลียร์ฟิลเตอร์</button>
                </div>
            </div>

            <!-- ส่วนที่ 2: รายการรถยนต์ -->
            <div class="card mb-4">
                <h3 class="mb-3">ข้อมูลรถยนต์ทั้งหมด</h3>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCarModal">
                        <i class="fas fa-plus me-2"></i>เพิ่มรถยนต์
                    </button>
                </div>
                <div class="total-count">จำนวนรถยนต์ทั้งหมด: <span id="totalRows">0</span> คัน</div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ลำดับ</th>
                                <th>หมายเลขทะเบียน</th>
                                <th>ยี่ห้อ</th>
                                <th>สี</th>
                                <th>จำนวนที่นั่ง</th>
                                <th>ชื่อคนขับ</th>
                                <th>สถานะ</th>
                                <th>รหัสคิว</th>
                                <th>รูปภาพ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="carTableBody">
                            <tr><td colspan="10" class="text-center text-muted">กำลังโหลดข้อมูล...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation">
                    <ul class="pagination" id="pagination">
                        <!-- Pagination จะถูกโหลดด้วย AJAX -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มรถยนต์ -->
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
                            <input type="file" id="car_image_add" name="car_image" class="form-control" accept="image/*">
                            <div class="image-preview-container">
                                <img id="addImagePreview" class="image-preview" alt="ตัวอย่างรูปภาพ">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>เพิ่มรถยนต์</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขข้อมูลรถยนต์ -->
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
                        <input type="hidden" id="old_car_image" name="old_car_image">
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
                            <input type="file" id="car_image_edit" name="car_image" class="form-control" accept="image/*">
                            <div class="image-preview-container">
                                <img id="editImagePreview" class="image-preview" alt="ตัวอย่างรูปภาพ">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>บันทึกการแก้ไข</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แสดงรูปภาพ -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">รูปภาพรถยนต์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" class="modal-image" alt="รูปภาพรถยนต์">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let currentPage = 1;
    let searchTimeout;

    // โหลดข้อมูลรถยนต์
    function loadCars(page = 1) {
        currentPage = page;
        const search = $('#search').val();
        const queue_filter = $('#queue_filter').val();
        const status_filter = $('#status_filter').val();

        $.ajax({
            url: 'fetch_cars.php',
            type: 'POST',
            data: {
                page: page,
                search: search,
                queue_filter: queue_filter,
                status_filter: status_filter
            },
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    Swal.fire('เกิดข้อผิดพลาด', data.error, 'error');
                    $('#carTableBody').html('<tr><td colspan="10" class="text-center text-muted">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
                    $('#totalRows').text(0);
                    $('#pagination').html('');
                    return;
                }

                const cars = data.cars;
                const totalRows = data.totalRows;
                const totalPages = data.totalPages;
                let index = (page - 1) * 10 + 1;
                let html = '';

                if (cars.length > 0) {
                    cars.forEach(car => {
                        const imageSrc = car.car_image ? `uploads/cars/${car.car_image}` : 'path/to/default-image.jpg';
                        html += `
                            <tr>
                                <td>${index++}</td>
                                <td>${car.car_license || 'ไม่ระบุ'}</td>
                                <td>${car.car_brand || 'ไม่ระบุ'}</td>
                                <td>${car.car_color || 'ไม่ระบุ'}</td>
                                <td>${car.car_seat || 'ไม่ระบุ'}</td>
                                <td>${car.driver_fullname || 'ไม่ระบุ'}</td>
                                <td>${car.car_status == 'available' ? 'พร้อมใช้งาน' : 'ไม่พร้อมใช้งาน'}</td>
                                <td>${car.queue_status ? 'Q-' + car.queue_status : 'ไม่มีคิวรถ'}</td>
                                <td>
                                    ${car.car_image ? 
                                        `<img src="${imageSrc}" class="car-image" alt="รูปภาพรถยนต์" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="${imageSrc}">` : 
                                        'ไม่มีรูปภาพ'}
                                </td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editCarModal"
                                        data-car_id="${car.car_id}"
                                        data-car_license="${car.car_license}"
                                        data-car_brand="${car.car_brand}"
                                        data-car_color="${car.car_color}"
                                        data-car_seat="${car.car_seat}"
                                        data-car_status="${car.car_status}"
                                        data-driver_id="${car.driver_id || ''}"
                                        data-car_image="${imageSrc}"><i class="fas fa-edit"></i> แก้ไข</button>
                                    <button type="button" class="btn btn-danger btn-sm delete-btn"
                                        data-delete-id="${car.car_id}"><i class="fas fa-trash-alt"></i> ลบ</button>
                                </td>
                            </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="10" class="text-center text-muted">ไม่พบข้อมูลรถยนต์</td></tr>';
                }

                $('#carTableBody').html(html);
                $('#totalRows').text(totalRows);

                let paginationHtml = '';
                paginationHtml += `
                    <li class="page-item ${page <= 1 ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0)" onclick="loadCars(${page - 1})" aria-label="Previous">
                            <span aria-hidden="true">«</span>
                        </a>
                    </li>`;
                for (let i = 1; i <= totalPages; i++) {
                    paginationHtml += `
                        <li class="page-item ${page == i ? 'active' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="loadCars(${i})">${i}</a>
                        </li>`;
                }
                paginationHtml += `
                    <li class="page-item ${page >= totalPages ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0)" onclick="loadCars(${page + 1})" aria-label="Next">
                            <span aria-hidden="true">»</span>
                        </a>
                    </li>`;
                $('#pagination').html(paginationHtml);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                console.log("Response:", xhr.responseText);
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้: ' + error, 'error');
                $('#carTableBody').html('<tr><td colspan="10" class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</td></tr>');
                $('#totalRows').text(0);
                $('#pagination').html('');
            }
        });
    }

    // ล้างฟิลเตอร์
    function clearFilters() {
        $('#search').val('');
        $('#queue_filter').val('');
        $('#status_filter').val('');
        loadCars();
    }

    $(document).ready(function() {
        // Sidebar Toggle
        $('.close-btn').on('click', function() {
            $('.sidebar').addClass('closed');
            $('.content').addClass('closed');
        });

        $('.open-btn').on('click', function() {
            $('.sidebar').removeClass('closed');
            $('.content').removeClass('closed');
        });

        // โหลดข้อมูลเริ่มต้น
        loadCars();

        // ค้นหาแบบเรียลไทม์
        $('#search').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadCars();
            }, 300);
        });

        // เปลี่ยนตัวกรอง
        $('#queue_filter, #status_filter').on('change', function() {
            loadCars();
        });

        // SweetAlert สำหรับยืนยันการลบ
        $(document).on('click', '.delete-btn', function() {
            var deleteId = $(this).data('delete-id');
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
                    window.location.href = 'car.php?delete_id=' + deleteId;
                }
            });
        });

        // ตั้งค่า Modal แก้ไข
        $('#editCarModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var car_id = button.data('car_id');
            var car_license = button.data('car_license');
            var car_brand = button.data('car_brand');
            var car_color = button.data('car_color');
            var car_seat = button.data('car_seat');
            var car_status = button.data('car_status');
            var driver_id = button.data('driver_id');
            var car_image = button.data('car_image');

            var modal = $(this);
            modal.find('#car_id_edit').val(car_id);
            modal.find('#car_license_edit').val(car_license);
            modal.find('#car_brand_edit').val(car_brand);
            modal.find('#car_color_edit').val(car_color);
            modal.find('#car_seat_edit').val(car_seat);
            modal.find('#car_status_edit').val(car_status);
            modal.find('#driver_id_edit').val(driver_id);
            modal.find('#old_car_image').val(car_image);

            if (car_image) {
                $('#editImagePreview').attr('src', car_image).show();
            } else {
                $('#editImagePreview').hide();
            }
        });

        // Preview รูปภาพก่อนอัปโหลด (เพิ่มรถยนต์)
        $('#car_image_add').on('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#addImagePreview').attr('src', e.target.result).show();
                };
                reader.readAsDataURL(file);
            } else {
                $('#addImagePreview').hide();
            }
        });

        // Preview รูปภาพก่อนอัปโหลด (แก้ไขรถยนต์)
        $('#car_image_edit').on('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#editImagePreview').attr('src', e.target.result).show();
                };
                reader.readAsDataURL(file);
            }
        });

        // แสดงรูปภาพใน Modal เมื่อคลิก
        $(document).on('click', '.car-image', function() {
            const imageSrc = $(this).data('image');
            $('#modalImage').attr('src', imageSrc);
        });

        // SweetAlert สำหรับเพิ่มรถยนต์
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
                        dataType: 'json', // ระบุว่า response เป็น JSON
                        success: function(response) {
                            if (response.status === "success") {
                                Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                                    $('#addCarModal').modal('hide');
                                    loadCars(); // รีโหลดข้อมูลหลังเพิ่ม
                                });
                            } else {
                                Swal.fire('เกิดข้อผิดพลาด!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถเพิ่มข้อมูลได้: ' + (xhr.responseJSON?.message || xhr.responseText), 'error');
                        }
                    });
                }
            });
        });

        // SweetAlert สำหรับแก้ไขรถยนต์
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
                        dataType: 'json', // ระบุว่า response เป็น JSON
                        success: function(response) {
                            if (response.status === "success") {
                                Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                                    $('#editCarModal').modal('hide');
                                    loadCars(); // รีโหลดข้อมูลหลังแก้ไข
                                });
                            } else {
                                Swal.fire('เกิดข้อผิดพลาด!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถแก้ไขข้อมูลได้: ' + (xhr.responseJSON?.message || xhr.responseText), 'error');
                        }
                    });
                }
            });
        });
    });
</script>
</body>
</html>