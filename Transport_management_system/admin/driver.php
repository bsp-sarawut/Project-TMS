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
        header("Location: driver.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
}

$searchQuery = "";
$params = [];
if (isset($_POST['search']) && !empty($_POST['search'])) {
    $search = $_POST['search'];
    $searchQuery = "WHERE d.driver_name LIKE :search OR d.driver_lastname LIKE :search OR p.PROVINCE_NAME LIKE :search OR a.AMPHUR_NAME LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

if (isset($_POST['province_filter']) && !empty($_POST['province_filter'])) {
    $province_filter = $_POST['province_filter'];
    $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " d.driver_province = :province_filter";
    $params[':province_filter'] = $province_filter;
}

if (isset($_POST['amphur_filter']) && !empty($_POST['amphur_filter'])) {
    $amphur_filter = $_POST['amphur_filter'];
    $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " d.driver_amphur = :amphur_filter";
    $params[':amphur_filter'] = $amphur_filter;
}

try {
    $stmt = $conn->prepare("SELECT * FROM province");
    $stmt->execute();
    $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลจังหวัด: " . $e->getMessage();
    $provinces = [];
}

try {
    $stmt = $conn->prepare("SELECT d.*, p.PROVINCE_NAME, a.AMPHUR_NAME
                            FROM driver d
                            LEFT JOIN province p ON d.driver_province = p.PROVINCE_ID
                            LEFT JOIN amphur a ON d.driver_amphur = a.AMPHUR_ID
                            $searchQuery");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
    $drivers = [];
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
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f5f6f5; font-family: 'Kanit', sans-serif; min-height: 100vh; }
        .content { margin-left: 250px; padding: 20px; transition: margin-left 0.3s ease; }
        .card { border-radius: 10px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); background: #fff; padding: 15px; margin-bottom: 20px; }
        .card h3 { color: #333; font-weight: 600; border-bottom: 1px solid #e0e0e0; padding-bottom: 5px; margin-bottom: 15px; }
        .form-label { font-weight: 500; color: #444; }
        .form-select, .form-control { border-radius: 5px; border: 1px solid #ccc; padding: 8px; }
        .form-select:focus, .form-control:focus { border-color: #007bff; box-shadow: 0 0 3px rgba(0, 123, 255, 0.3); }
        .btn-primary { border-radius: 8px; padding: 8px 20px; background: #007bff; border: none; transition: background 0.3s ease; }
        .btn-primary:hover { background: #0056b3; }
        .btn-secondary { border-radius: 8px; padding: 8px 20px; background: #6c757d; border: none; transition: background 0.3s ease; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-warning { background: #f39c12; border: none; border-radius: 5px; padding: 5px 15px; color: #fff; }
        .btn-danger { background: #e74c3c; border: none; border-radius: 5px; padding: 5px 15px; color: #fff; }
        .table { border-radius: 5px; overflow: hidden; background: #fff; }
        .table thead th { background: #003087; color: #fff; text-align: center; padding: 12px; }
        .table tbody tr:hover { background: #f9f9f9; }
        .table td { vertical-align: middle; text-align: center; }
        .img-thumbnail { border-radius: 5px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); cursor: pointer; transition: transform 0.2s ease; }
        .img-thumbnail:hover { transform: scale(1.05); }
        .modal-content { border-radius: 10px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); }
        .modal-header { background: #003087; color: #fff; border-bottom: none; border-radius: 10px 10px 0 0; }
        .modal-body { padding: 20px; }
        @media (max-width: 768px) {
            .content { margin-left: 0; padding: 15px; }
            .btn-group { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="text-center mb-4" style="color: #333; font-weight: 600;">ระบบจัดการคนขับ</h2>

            <div class="card mb-4">
                <h3 class="mb-3">ค้นหาคนขับ</h3>
                <form method="POST" action="" id="searchForm">
                    <div class="row g-3">
                        <div class="col-md-4 col-12">
                            <label for="province_filter" class="form-label">จังหวัด</label>
                            <select name="province_filter" id="province_filter" class="form-select" onchange="fetchAmphurs(this.value, 'filter')">
                                <option value="">เลือกจังหวัด</option>
                                <?php foreach ($provinces as $province) { ?>
                                    <option value="<?php echo $province['PROVINCE_ID']; ?>" <?php echo (isset($province_filter) && $province_filter == $province['PROVINCE_ID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($province['PROVINCE_NAME']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4 col-12">
                            <label for="amphur_filter" class="form-label">อำเภอ</label>
                            <select name="amphur_filter" id="amphur_filter" class="form-select">
                                <option value="">เลือกอำเภอ</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-12">
                            <label for="search_input" class="form-label">ค้นหาคนขับ</label>
                            <input type="text" name="search" id="search_input" class="form-control" placeholder="ค้นหาคนขับ" value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
                        </div>
                    </div>
                    <div class="btn-group d-flex justify-content-start mt-3">
                        <button type="submit" class="btn btn-primary me-2">ค้นหา</button>
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">เคลียร์ฟิลเตอร์</button>
                    </div>
                </form>
            </div>

            <div class="card mb-4">
                <h3 class="mb-3">รายชื่อคนขับ</h3>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                        เพิ่มคนขับ
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>NO.</th>
                                <th>Username</th>
                                <!-- <th>Password</th> -->
                                <th>Full Name</th>
                                <th>Phone</th>
                                <th>Province</th>
                                <th>Amphur</th>
                                <th>รูปภาพ</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $index = 1;
                            foreach ($drivers as $driver) {
                            ?>
                                <tr>
                                    <td><?php echo $index++; ?></td>
                                    <td><?php echo htmlspecialchars($driver['driver_user']); ?></td>
                                    <!-- <td><?php echo htmlspecialchars($driver['driver_password']); ?></td> -->
                                    <td><?php echo htmlspecialchars($driver['driver_name'] . " " . $driver['driver_lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($driver['driver_tel']); ?></td>
                                    <td><?php echo htmlspecialchars($driver['PROVINCE_NAME'] ?? 'ไม่ระบุ'); ?></td>
                                    <td><?php echo htmlspecialchars($driver['AMPHUR_NAME'] ?? 'ไม่ระบุ'); ?></td>
                                    <td>
                                        <?php 
                                        // ปรับเส้นทางให้ถูกต้องสำหรับ admin/
                                        $imagePath = !empty($driver['driver_image']) ? "uploads/drivers/" . $driver['driver_image'] : "../images/default_driver.jpg";
                                        if (!empty($driver['driver_image']) && file_exists("uploads/drivers/" . $driver['driver_image'])) { ?>
                                            <img src="<?php echo $imagePath; ?>" 
                                                 alt="รูปภาพคนขับ" 
                                                 class="img-thumbnail previewable" 
                                                 style="max-width: 100px; max-height: 100px; object-fit: cover; cursor: pointer;"
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#imageModal" 
                                                 data-image="<?php echo $imagePath; ?>">
                                        <?php } else { ?>
                                            ไม่มีรูปภาพ
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editDriverModal"
                                                data-driver_id="<?php echo $driver['driver_id']; ?>"
                                                data-driver_user="<?php echo htmlspecialchars($driver['driver_user']); ?>"
                                                data-driver_password="<?php echo htmlspecialchars($driver['driver_password']); ?>"
                                                data-driver_name="<?php echo htmlspecialchars($driver['driver_name']); ?>"
                                                data-driver_lastname="<?php echo htmlspecialchars($driver['driver_lastname']); ?>"
                                                data-driver_tel="<?php echo htmlspecialchars($driver['driver_tel']); ?>"
                                                data-driver_province="<?php echo $driver['driver_province']; ?>"
                                                data-driver_amphur="<?php echo $driver['driver_amphur']; ?>">
                                            แก้ไข
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                                data-delete-id="<?php echo $driver['driver_id']; ?>">
                                            ลบ
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

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
                            <label for="driver_amphur" class="form-label">อำเภอ:</label>
                            <select id="amphur_add" name="driver_amphur" class="form-control" required>
                                <option value="">เลือกอำเภอ</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="driver_image" class="form-label">รูปภาพ:</label>
                            <input type="file" id="driver_image" name="driver_image" class="form-control" onchange="previewImage(this, 'preview_add')">
                            <img id="preview_add" src="" alt="Preview Image" style="max-width: 100px; display: none; margin-top: 10px;">
                        </div>
                        <button type="submit" class="btn btn-primary">เพิ่มคนขับ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
                            <label for="edit_driver_amphur" class="form-label">อำเภอ:</label>
                            <select id="edit_amphur" name="driver_amphur" class="form-control" required>
                                <option value="">เลือกอำเภอ</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_driver_image" class="form-label">รูปภาพ:</label>
                            <input type="file" id="edit_driver_image" name="driver_image" class="form-control" onchange="previewImage(this, 'preview_edit')">
                            <img id="preview_edit" src="" alt="Preview Image" style="max-width: 100px; display: none; margin-top: 10px;">
                        </div>
                        <button type="submit" class="btn btn-primary">แก้ไขข้อมูลคนขับ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">รูปภาพประกอบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" alt="Driver Image">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
        });

        $(document).ready(function() {
            $('img.previewable').click(function() {
                $('#modalImage').attr('src', $(this).attr('src'));
                $('#imageModal').modal('show');
            });

            $('.close-btn').on('click', function() {
                $('.sidebar').addClass('closed');
                $('.content').addClass('closed');
            });

            $('.open-btn').on('click', function() {
                $('.sidebar').removeClass('closed');
                $('.content').removeClass('closed');
            });

            $('.delete-btn').on('click', function() {
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

            <?php if (isset($_SESSION['success'])) { ?>
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: '<?php echo $_SESSION['success']; ?>',
                    confirmButtonText: 'ตกลง'
                });
                <?php unset($_SESSION['success']); ?>
            <?php } ?>

            <?php if (isset($_SESSION['error'])) { ?>
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: '<?php echo $_SESSION['error']; ?>',
                    confirmButtonText: 'ตกลง'
                });
                <?php unset($_SESSION['error']); ?>
            <?php } ?>
        });

        function previewImage(input, previewId) {
            var preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function clearFilters() {
            $('#province_filter').val('');
            $('#amphur_filter').html('<option value="">เลือกอำเภอ</option>');
            $('#search_input').val('');
            $('#searchForm').submit();
        }
    </script>
</body>
</html>