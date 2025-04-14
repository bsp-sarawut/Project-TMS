<?php
include 'config/condb.php';
session_start();

// ตรวจสอบการลบข้อมูล
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM routes WHERE route_ID = :route_ID");
        $stmt->bindParam(':route_ID', $delete_id, PDO::PARAM_INT);
        $stmt->execute();
        // การลบรูปภาพอยู่ใน route_delete.php
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
    header("Location: route.php");
    exit;
}

// ดึงข้อมูลจังหวัด
try {
    $stmt = $conn->prepare("SELECT * FROM province");
    $stmt->execute();
    $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลจังหวัด: " . $e->getMessage();
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
        .route-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        .route-image:hover {
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
        /* เพิ่มสไตล์สำหรับรูปภาพใน Modal */
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
            .route-image {
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
<body class="body_route">
    <?php include 'sidebar.php'; ?>
    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="header-title">ระบบจัดการเส้นทาง</h2>

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

            <!-- ส่วนที่ 1: ฟอร์มค้นหาเส้นทาง -->
            <div class="card mb-4 search-section">
                <h3 class="mb-3">ตัวกรองข้อมูลเส้นทาง</h3>
                <div class="search-container">
                    <label for="search" class="form-label">ค้นหา</label>
                    <div class="search-input-group">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" id="search" class="form-control" placeholder="ค้นหา: ชื่อจังหวัด, อำเภอ, สถานที่">
                    </div>
                </div>
                <div class="filter-row">
                    <div class="col-md-3 col-12">
                        <label for="province_filter" class="form-label">จังหวัด</label>
                        <select name="province_filter" id="province_filter" class="form-select">
                            <option value="">-- แสดงทั้งหมด --</option>
                            <?php foreach ($provinces as $province) { ?>
                                <option value="<?php echo $province['PROVINCE_ID']; ?>">
                                    <?php echo $province['PROVINCE_NAME']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="amphur_filter" class="form-label">อำเภอ</label>
                        <select name="amphur_filter" id="amphur_filter" class="form-select">
                            <option value="">-- แสดงทั้งหมด --</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="location_filter" class="form-label">สถานที่</label>
                        <select name="location_filter" id="location_filter" class="form-select">
                            <option value="">-- แสดงทั้งหมด --</option>
                        </select>
                    </div>
                </div>
                <div class="action-buttons">
                    <button type="button" class="btn btn-primary" onclick="loadRoutes()"><i class="fas fa-search me-2"></i>ค้นหา</button>
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()"><i class="fas fa-undo me-2"></i>เคลียร์ฟิลเตอร์</button>
                </div>
            </div>

            <!-- ส่วนที่ 2: รายการเส้นทาง -->
            <div class="card mb-4">
                <h3 class="mb-3">ข้อมูลเส้นทางทั้งหมด</h3>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRouteModal">
                        <i class="fas fa-plus me-2"></i>เพิ่มเส้นทาง
                    </button>
                </div>
                <div class="total-count">จำนวนเส้นทางทั้งหมด: <span id="totalRows">0</span> เส้นทาง</div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>NO.</th>
                                <th>จังหวัด</th>
                                <th>อำเภอ</th>
                                <th>สถานที่</th>
                                <th>ราคา</th>
                                <th>รูปภาพ</th>
                                <th>การกระทำ</th>
                            </tr>
                        </thead>
                        <tbody id="routeTableBody">
                            <tr><td colspan="7" class="text-center text-muted">กำลังโหลดข้อมูล...</td></tr>
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
                        <div class="mb-3">
                            <label for="route_image" class="form-label">รูปภาพเส้นทาง:</label>
                            <input type="file" id="route_image" name="route_image" class="form-control" accept="image/*">
                            <div class="image-preview-container">
                                <img id="addImagePreview" class="image-preview" alt="ตัวอย่างรูปภาพ">
                            </div>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>เพิ่มเส้นทาง</button>
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
                        <input type="hidden" id="old_route_image" name="old_route_image">
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
                        <div class="mb-3">
                            <label for="route_image_edit" class="form-label">รูปภาพเส้นทาง:</label>
                            <input type="file" id="route_image_edit" name="route_image" class="form-control" accept="image/*">
                            <div class="image-preview-container">
                                <img id="editImagePreview" class="image-preview" alt="ตัวอย่างรูปภาพ">
                            </div>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>บันทึกการแก้ไข</button>
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
                    <h5 class="modal-title" id="imageModalLabel">รูปภาพเส้นทาง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" class="modal-image" alt="รูปภาพเส้นทาง">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentPage = 1;
        let searchTimeout;

        // โหลดข้อมูลเส้นทาง
        function loadRoutes(page = 1) {
            currentPage = page;
            const search = $('#search').val();
            const province_filter = $('#province_filter').val();
            const amphur_filter = $('#amphur_filter').val();
            const location_filter = $('#location_filter').val();

            $.ajax({
                url: 'fetch_routes.php',
                type: 'POST',
                data: {
                    page: page,
                    search: search,
                    province_filter: province_filter,
                    amphur_filter: amphur_filter,
                    location_filter: location_filter
                },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        Swal.fire('เกิดข้อผิดพลาด', data.error, 'error');
                        $('#routeTableBody').html('<tr><td colspan="7" class="text-center text-muted">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
                        $('#totalRows').text(0);
                        $('#pagination').html('');
                        return;
                    }

                    const routes = data.routes;
                    const totalRows = data.totalRows;
                    const totalPages = data.totalPages;
                    let index = (page - 1) * 10 + 1;
                    let html = '';

                    if (routes.length > 0) {
                        routes.forEach(route => {
                            const imageSrc = route.route_image ? route.route_image : 'path/to/default-image.jpg';
                            html += `
                                <tr>
                                    <td>${index++}</td>
                                    <td>${route.PROVINCE_NAME || 'ไม่ระบุ'}</td>
                                    <td>${route.AMPHUR_NAME || 'ไม่ระบุ'}</td>
                                    <td>${route.location || 'ไม่ระบุ'}</td>
                                    <td>${parseFloat(route.price || 0).toFixed(2)}</td>
                                    <td><img src="${imageSrc}" class="route-image" alt="รูปภาพเส้นทาง" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="${imageSrc}"></td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editRouteModal"
                                            data-route_id="${route.route_ID}"
                                            data-province="${route.province}"
                                            data-amphur="${route.amphur}"
                                            data-location="${route.location}"
                                            data-price="${route.price}"
                                            data-image="${imageSrc}"><i class="fas fa-edit"></i> แก้ไข</button>
                                        <button type="button" class="btn btn-danger btn-sm delete-btn"
                                            data-delete-id="${route.route_ID}"><i class="fas fa-trash-alt"></i> ลบ</button>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        html = '<tr><td colspan="7" class="text-center text-muted">ไม่พบข้อมูลเส้นทาง</td></tr>';
                    }

                    $('#routeTableBody').html(html);
                    $('#totalRows').text(totalRows);

                    let paginationHtml = '';
                    paginationHtml += `
                        <li class="page-item ${page <= 1 ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="loadRoutes(${page - 1})" aria-label="Previous">
                                <span aria-hidden="true">«</span>
                            </a>
                        </li>`;
                    for (let i = 1; i <= totalPages; i++) {
                        paginationHtml += `
                            <li class="page-item ${page == i ? 'active' : ''}">
                                <a class="page-link" href="javascript:void(0)" onclick="loadRoutes(${i})">${i}</a>
                            </li>`;
                    }
                    paginationHtml += `
                        <li class="page-item ${page >= totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="loadRoutes(${page + 1})" aria-label="Next">
                                <span aria-hidden="true">»</span>
                            </a>
                        </li>`;
                    $('#pagination').html(paginationHtml);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    console.log("Response:", xhr.responseText);
                    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้: ' + error, 'error');
                    $('#routeTableBody').html('<tr><td colspan="7" class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</td></tr>');
                    $('#totalRows').text(0);
                    $('#pagination').html('');
                }
            });
        }

        // ดึงข้อมูลอำเภอ
        function fetchAmphurs(province_id, mode = 'add') {
            var amphur_select = (mode === 'add') ? '#amphur_add' : (mode === 'edit' ? '#amphur_edit' : '#amphur_filter');
            if (province_id) {
                $.ajax({
                    url: 'get_amphur.php',
                    type: 'POST',
                    data: { province_id: province_id },
                    success: function(data) {
                        $(amphur_select).empty().html(data || '<option value="">ไม่มีข้อมูลอำเภอ</option>');
                        if (mode === 'filter') {
                            $('#location_filter').html('<option value="">ทุกสถานที่</option>');
                            loadRoutes();
                        }
                    },
                    error: function() {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลอำเภอได้', 'error');
                    }
                });
            } else {
                $(amphur_select).html('<option value="">ทุกอำเภอ</option>');
                if (mode === 'filter') {
                    $('#location_filter').html('<option value="">ทุกสถานที่</option>');
                    loadRoutes();
                }
            }
        }

        // ดึงข้อมูลสถานที่
        function fetchLocations(province_id, amphur_id) {
            if (province_id && amphur_id) {
                $.ajax({
                    url: 'get_location.php',
                    type: 'GET',
                    data: { province_id: province_id, hur_id: amphur_id },
                    success: function(data) {
                        $('#location_filter').empty().html(data);
                        loadRoutes();
                    },
                    error: function() {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลสถานที่ได้', 'error');
                    }
                });
            } else {
                $('#location_filter').html('<option value="">ทุกสถานที่</option>');
                loadRoutes();
            }
        }

        // ล้างฟิลเตอร์
        function clearFilters() {
            $('#search').val('');
            $('#province_filter').val('');
            $('#amphur_filter').html('<option value="">ทุกอำเภอ</option>');
            $('#location_filter').html('<option value="">ทุกสถานที่</option>');
            loadRoutes();
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
            loadRoutes();

            // ค้นหาแบบเรียลไทม์
            $('#search').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadRoutes();
                }, 300);
            });

            $('#province_filter').on('change', function() {
                fetchAmphurs(this.value, 'filter');
            });

            $('#amphur_filter').on('change', function() {
                const province_id = $('#province_filter').val();
                const amphur_id = $(this).val();
                fetchLocations(province_id, amphur_id);
            });

            $('#location_filter').on('change', function() {
                loadRoutes();
            });

            // SweetAlert สำหรับยืนยันการลบ
            $(document).on('click', '.delete-btn', function() {
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
                        window.location.href = 'route_delete.php?delete_id=' + deleteId;
                    }
                });
            });

            // ตั้งค่า Modal แก้ไข
            $('#editRouteModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var routeID = button.data('route_id');
                var province = button.data('province');
                var amphur = button.data('amphur');
                var location = button.data('location');
                var price = button.data('price');
                var image = button.data('image');

                var modal = $(this);
                modal.find('#route_ID').val(routeID);
                modal.find('#province_edit').val(province);
                modal.find('#amphur_edit').val(amphur);
                modal.find('#location_edit').val(location);
                modal.find('#price_edit').val(price);
                modal.find('#old_route_image').val(image);

                if (image) {
                    $('#editImagePreview').attr('src', image).show();
                } else {
                    $('#editImagePreview').hide();
                }

                fetchAmphurs(province, 'edit');
                setTimeout(() => modal.find('#amphur_edit').val(amphur), 500);
            });

            // Preview รูปภาพก่อนอัปโหลด (เพิ่มเส้นทาง)
            $('#route_image').on('change', function(event) {
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

            // Preview รูปภาพก่อนอัปโหลด (แก้ไขเส้นทาง)
            $('#route_image_edit').on('change', function(event) {
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
            $(document).on('click', '.route-image', function() {
                const imageSrc = $(this).data('image');
                $('#modalImage').attr('src', imageSrc);
            });
        });
    </script>
</body>
</html>