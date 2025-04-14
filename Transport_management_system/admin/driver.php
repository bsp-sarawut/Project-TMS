<?php
include 'config/condb.php';
session_start();

if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM driver WHERE driver_id = :driver_id");
        $stmt->bindParam(':driver_id', $delete_id);
        $stmt->execute();
        $_SESSION['success'] = "ลบข้อมูลคนขับเรียบร้อยแล้ว";
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
    header("Location: driver.php");
    exit();
}

try {
    $stmt = $conn->prepare("SELECT * FROM province");
    $stmt->execute();
    $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลจังหวัด: " . $e->getMessage();
    $provinces = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการคนขับ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
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
        .driver-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        .driver-image:hover {
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
            .driver-image {
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
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="header-title">ระบบจัดการคนขับ</h2>

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

            <!-- ส่วนที่ 1: ฟอร์มค้นหาคนขับ -->
            <div class="card mb-4 search-section">
                <h3 class="mb-3">ตัวกรองข้อมูลคนขับ</h3>
                <div class="search-container">
                    <label for="search_input" class="form-label">ค้นหาคนขับ</label>
                    <div class="search-input-group">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" id="search_input" class="form-control" placeholder="ค้นหา: ชื่อ, นามสกุล, จังหวัด, อำเภอ">
                    </div>
                </div>
                <div class="filter-row">
                    <div class="col-md-3 col-12">
                        <label for="province_filter" class="form-label">จังหวัด</label>
                        <select name="province_filter" id="province_filter" class="form-select" onchange="fetchAmphurs(this.value, 'filter')">
                            <option value="">-- แสดงทั้งหมด --</option>
                            <?php foreach ($provinces as $province) { ?>
                                <option value="<?php echo $province['PROVINCE_ID']; ?>">
                                    <?php echo htmlspecialchars($province['PROVINCE_NAME']); ?>
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
                </div>
                <div class="action-buttons">
                    <button type="button" class="btn btn-primary" onclick="loadDrivers()"><i class="fas fa-search me-2"></i>ค้นหา</button>
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()"><i class="fas fa-undo me-2"></i>เคลียร์ฟิลเตอร์</button>
                </div>
            </div>

            <!-- ส่วนที่ 2: รายชื่อคนขับ -->
            <div class="card mb-4">
                <h3 class="mb-3">ข้อมูลคนขับทั้งหมด</h3>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                        <i class="fas fa-plus me-2"></i>เพิ่มคนขับ
                    </button>
                </div>
                <div class="total-count">จำนวนคนขับทั้งหมด: <span id="totalRows">0</span> คน</div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>NO.</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Phone</th>
                                <th>Province</th>
                                <th>Amphur</th>
                                <th>รูปภาพ</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="driverTableBody">
                            <tr><td colspan="8" class="text-center text-muted">กำลังโหลดข้อมูล...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มคนขับ -->
    <div class="modal fade" id="addDriverModal" tabindex="-1" aria-labelledby="addDriverModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDriverModalLabel">เพิ่มข้อมูลคนขับ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addDriverForm" method="POST" action="driver_insert.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="driver_user" class="form-label">Username:</label>
                            <input type="text" id="driver_user" name="driver_user" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="driver_password" class="form-label">Password:</label>
                            <input type="password" id="driver_password" name="driver_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="driver_name" class="form-label">ชื่อ:</label>
                            <input type="text" id="driver_name" name="driver_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="driver_lastname" class="form-label">นามสกุล:</label>
                            <input type="text" id="driver_lastname" name="driver_lastname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="driver_tel" class="form-label">เบอร์โทรศัพท์:</label>
                            <input type="text" id="driver_tel" name="driver_tel" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="driver_province" class="form-label">จังหวัด:</label>
                            <select id="driver_province" name="driver_province" class="form-control" onchange="fetchAmphurs(this.value, 'add')" required>
                                <option value="">เลือกจังหวัด</option>
                                <?php foreach ($provinces as $province) { ?>
                                    <option value="<?php echo $province['PROVINCE_ID']; ?>"><?php echo htmlspecialchars($province['PROVINCE_NAME']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="amphur_add" class="form-label">อำเภอ:</label>
                            <select id="amphur_add" name="driver_amphur" class="form-control" required>
                                <option value="">เลือกอำเภอ</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="driver_image" class="form-label">รูปภาพ:</label>
                            <input type="file" id="driver_image" name="driver_image" class="form-control" accept="image/*" onchange="previewImage(this, 'preview_add')">
                            <div class="image-preview-container">
                                <img id="preview_add" class="image-preview" alt="ตัวอย่างรูปภาพ">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>เพิ่มคนขับ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขข้อมูลคนขับ -->
    <div class="modal fade" id="editDriverModal" tabindex="-1" aria-labelledby="editDriverModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDriverModalLabel">แก้ไขข้อมูลคนขับ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editDriverForm" method="POST" action="driver_update.php" enctype="multipart/form-data">
                        <input type="hidden" id="edit_driver_id" name="driver_id">
                        <div class="mb-3">
                            <label for="edit_driver_user" class="form-label">Username:</label>
                            <input type="text" id="edit_driver_user" name="driver_user" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_driver_password" class="form-label">Password:</label>
                            <input type="password" id="edit_driver_password" name="driver_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_driver_name" class="form-label">ชื่อ:</label>
                            <input type="text" id="edit_driver_name" name="driver_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_driver_lastname" class="form-label">นามสกุล:</label>
                            <input type="text" id="edit_driver_lastname" name="driver_lastname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_driver_tel" class="form-label">เบอร์โทรศัพท์:</label>
                            <input type="text" id="edit_driver_tel" name="driver_tel" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_driver_province" class="form-label">จังหวัด:</label>
                            <select id="edit_driver_province" name="driver_province" class="form-control" onchange="fetchAmphurs(this.value, 'edit')" required>
                                <option value="">เลือกจังหวัด</option>
                                <?php foreach ($provinces as $province) { ?>
                                    <option value="<?php echo $province['PROVINCE_ID']; ?>"><?php echo htmlspecialchars($province['PROVINCE_NAME']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_amphur" class="form-label">อำเภอ:</label>
                            <select id="edit_amphur" name="driver_amphur" class="form-control" required>
                                <option value="">เลือกอำเภอ</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_driver_image" class="form-label">รูปภาพ:</label>
                            <input type="file" id="edit_driver_image" name="driver_image" class="form-control" accept="image/*" onchange="previewImage(this, 'preview_edit')">
                            <div class="image-preview-container">
                                <img id="preview_edit" class="image-preview" alt="ตัวอย่างรูปภาพ">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>แก้ไขข้อมูลคนขับ</button>
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
                    <h5 class="modal-title" id="imageModalLabel">รูปภาพคนขับ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" class="modal-image" alt="รูปภาพคนขับ">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let searchTimeout;

        // โหลดข้อมูลคนขับ
        function loadDrivers() {
            const search = $('#search_input').val();
            const province_filter = $('#province_filter').val();
            const amphur_filter = $('#amphur_filter').val();

            $.ajax({
                url: 'fetch_drivers.php',
                type: 'POST',
                data: {
                    search: search,
                    province_filter: province_filter,
                    amphur_filter: amphur_filter
                },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        Swal.fire('เกิดข้อผิดพลาด', data.error, 'error');
                        $('#driverTableBody').html('<tr><td colspan="8" class="text-center text-muted">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
                        $('#totalRows').text(0);
                        return;
                    }

                    const drivers = data.drivers;
                    const totalRows = data.totalRows;
                    let index = 1;
                    let html = '';

                    if (drivers.length > 0) {
                        drivers.forEach(driver => {
                            const imageSrc = driver.driver_image ? `uploads/drivers/${driver.driver_image}` : 'images/default_driver.jpg';
                            html += `
                                <tr>
                                    <td>${index++}</td>
                                    <td>${driver.driver_user || 'ไม่ระบุ'}</td>
                                    <td>${driver.driver_name} ${driver.driver_lastname}</td>
                                    <td>${driver.driver_tel || 'ไม่ระบุ'}</td>
                                    <td>${driver.PROVINCE_NAME || 'ไม่ระบุ'}</td>
                                    <td>${driver.AMPHUR_NAME || 'ไม่ระบุ'}</td>
                                    <td>
                                        ${driver.driver_image && driver.driver_image !== '' ? 
                                            `<img src="${imageSrc}" class="driver-image" alt="รูปภาพคนขับ" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="${imageSrc}">` : 
                                            'ไม่มีรูปภาพ'}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editDriverModal"
                                            data-driver_id="${driver.driver_id}"
                                            data-driver_user="${driver.driver_user}"
                                            data-driver_password="${driver.driver_password}"
                                            data-driver_name="${driver.driver_name}"
                                            data-driver_lastname="${driver.driver_lastname}"
                                            data-driver_tel="${driver.driver_tel}"
                                            data-driver_province="${driver.driver_province}"
                                            data-driver_amphur="${driver.driver_amphur}"
                                            data-driver_image="${imageSrc}"><i class="fas fa-edit"></i> แก้ไข</button>
                                        <button type="button" class="btn btn-danger btn-sm delete-btn"
                                            data-delete-id="${driver.driver_id}"><i class="fas fa-trash-alt"></i> ลบ</button>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        html = '<tr><td colspan="8" class="text-center text-muted">ไม่พบข้อมูลคนขับ</td></tr>';
                    }

                    $('#driverTableBody').html(html);
                    $('#totalRows').text(totalRows);
                },
                error: function(xhr, status, error) {
                    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้: ' + error, 'error');
                    $('#driverTableBody').html('<tr><td colspan="8" class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</td></tr>');
                    $('#totalRows').text(0);
                }
            });
        }

        // ล้างฟิลเตอร์
        function clearFilters() {
            $('#search_input').val('');
            $('#province_filter').val('');
            $('#amphur_filter').html('<option value="">เลือกอำเภอ</option>');
            loadDrivers();
        }

        // ดึงข้อมูลอำเภอเมื่อเลือกจังหวัด
        function fetchAmphurs(province_id, mode = 'add', callback = null) {
            var amphur_select = (mode === 'add') ? '#amphur_add' : (mode === 'edit' ? '#edit_amphur' : '#amphur_filter');
            if (province_id) {
                $.ajax({
                    url: 'fetch_amphurs.php',
                    method: 'GET',
                    data: { province_id: province_id },
                    success: function(response) {
                        $(amphur_select).empty().html('<option value="">เลือกอำเภอ</option>');
                        const amphurs = JSON.parse(response);
                        amphurs.forEach(function(amphur) {
                            $(amphur_select).append(`<option value="${amphur.AMPHUR_ID}">${amphur.AMPHUR_NAME}</option>`);
                        });
                        if (callback) callback();
                    },
                    error: function() {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลอำเภอได้', 'error');
                    }
                });
            } else {
                $(amphur_select).html('<option value="">เลือกอำเภอ</option>');
            }
        }

        // Preview รูปภาพก่อนอัปโหลด
        function previewImage(input, previewId) {
            var preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
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
            loadDrivers();

            // ค้นหาแบบเรียลไทม์
            $('#search_input').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadDrivers();
                }, 300);
            });

            // เปลี่ยนตัวกรอง
            $('#province_filter, #amphur_filter').on('change', function() {
                loadDrivers();
            });

            // SweetAlert สำหรับยืนยันการลบ
            $(document).on('click', '.delete-btn', function() {
                var deleteId = $(this).data('delete-id');
                Swal.fire({
                    title: 'คุณแน่ใจหรือไม่?',
                    text: 'คุณต้องการลบข้อมูลคนขับนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'driver.php?delete_id=' + deleteId;
                    }
                });
            });

            // ตั้งค่า Modal แก้ไข
            $('#editDriverModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var driverId = button.data('driver_id');
                var driverUser = button.data('driver_user');
                var driverPassword = button.data('driver_password');
                var driverName = button.data('driver_name');
                var driverLastname = button.data('driver_lastname');
                var driverTel = button.data('driver_tel');
                var driverProvince = button.data('driver_province');
                var driverAmphur = button.data('driver_amphur');
                var driverImage = button.data('driver_image');

                var modal = $(this);
                modal.find('#edit_driver_id').val(driverId);
                modal.find('#edit_driver_user').val(driverUser);
                modal.find('#edit_driver_password').val(driverPassword);
                modal.find('#edit_driver_name').val(driverName);
                modal.find('#edit_driver_lastname').val(driverLastname);
                modal.find('#edit_driver_tel').val(driverTel);
                modal.find('#edit_driver_province').val(driverProvince);

                fetchAmphurs(driverProvince, 'edit', function() {
                    modal.find('#edit_amphur').val(driverAmphur);
                });

                if (driverImage) {
                    $('#preview_edit').attr('src', driverImage).show();
                } else {
                    $('#preview_edit').hide();
                }
            });

            // SweetAlert สำหรับเพิ่มคนขับ
            $('#addDriverForm').on('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'ยืนยันการเพิ่มคนขับ?',
                    text: 'คุณต้องการเพิ่มข้อมูลคนขับนี้หรือไม่?',
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
                            url: 'driver_insert.php',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === "success") {
                                    Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                                        $('#addDriverModal').modal('hide');
                                        loadDrivers();
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

            // SweetAlert สำหรับแก้ไขคนขับ
            $('#editDriverForm').on('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'ยืนยันการแก้ไข?',
                    text: 'คุณต้องการบันทึกการแก้ไขข้อมูลคนขับนี้หรือไม่?',
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
                            url: 'driver_update.php',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === "success") {
                                    Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                                        $('#editDriverModal').modal('hide');
                                        loadDrivers();
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

            // แสดงรูปภาพใน Modal เมื่อคลิก
            $(document).on('click', '.driver-image', function() {
                const imageSrc = $(this).data('image');
                $('#modalImage').attr('src', imageSrc);
            });
        });
    </script>
</body>
</html>