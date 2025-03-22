<?php
    include 'config/condb.php';
    session_start();

    // ตรวจสอบการลบข้อมูล
    if (isset($_GET['delete_id']) && ! empty($_GET['delete_id'])) {
        $delete_id = $_GET['delete_id'];

        try {
            $stmt = $conn->prepare("DELETE FROM driver WHERE driver_id = :driver_id");
            $stmt->bindParam(':driver_id', $delete_id);
            $stmt->execute();

            echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'ลบข้อมูลสำเร็จ',
                    text: 'ลบข้อมูลคนขับเรียบร้อยแล้ว',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = 'driver.php';
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
    if (isset($_POST['search']) && ! empty($_POST['search'])) {
        $search      = $_POST['search'];
        $searchQuery = "WHERE d.driver_name LIKE :search OR d.driver_lastname LIKE :search OR p.PROVINCE_NAME LIKE :search OR a.AMPHUR_NAME LIKE :search";
    }

    if (isset($_POST['province_filter']) && ! empty($_POST['province_filter'])) {
        $province_filter = $_POST['province_filter'];
        $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " d.driver_province = :province_filter";
    }

    if (isset($_POST['amphur_filter']) && ! empty($_POST['amphur_filter'])) {
        $amphur_filter = $_POST['amphur_filter'];
        $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " d.driver_amphur = :amphur_filter";
    }

    // ดึงข้อมูลจังหวัดจากฐานข้อมูล
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

    // ดึงข้อมูลคนขับจากฐานข้อมูลตามการค้นหา
    try {
        $stmt = $conn->prepare("SELECT d.*, p.PROVINCE_NAME, a.AMPHUR_NAME
                            FROM driver d
                            LEFT JOIN province p ON d.driver_province = p.PROVINCE_ID
                            LEFT JOIN amphur a ON d.driver_amphur = a.AMPHUR_ID
                            $searchQuery");

        if ($searchQuery) {
            if (isset($search)) {
                $stmt->bindValue(':search', '%' . $search . '%');
            }
            if (isset($province_filter)) {
                $stmt->bindValue(':province_filter', $province_filter);
            }
            if (isset($amphur_filter)) {
                $stmt->bindValue(':amphur_filter', $amphur_filter);
            }
        }
        $stmt->execute();
        $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>ระบบจัดการคนขับ</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content" id="content">
        <div class="container mt-5">
            <h3 class="mb-4">รายชื่อคนขับ</h3>
            <hr>
            <div class="mt-4">
                <form method="POST" action="">
                    <div class="input-group mt-3">
                        <select name="province_filter" class="form-control" onchange="fetchAmphurs(this.value, 'filter')">
                            <option value="">เลือกจังหวัด</option>
                            <?php foreach ($provinces as $province) {?>
                                <option value="<?php echo $province['PROVINCE_ID']; ?>"<?php echo(isset($province_filter) && $province_filter == $province['PROVINCE_ID']) ? 'selected' : ''; ?>>
                                    <?php echo $province['PROVINCE_NAME']; ?>
                                </option>
                            <?php }?>
                        </select>
                    </div>
                    <div class="input-group mt-3">
                        <select name="amphur_filter" id="amphur_filter" class="form-control">
                            <option value="">เลือกอำเภอ</option>
                        </select>
                    </div>
                    <div class="input-group mt-3">
                        <input type="text" name="search" class="form-control" placeholder="ค้นหาคนขับ" value="<?php echo isset($search) ? $search : ''; ?>">
                        <button class="btn btn-outline-secondary" type="submit">ค้นหา</button>
                    </div>
                </form>
            </div>

            <div class="container mt-5 mb-3 d-flex justify-content-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                เพิ่มคนขับ
            </button>

            </div>

                <!-- Modal เพิ่มข้อมูลคนขับ -->
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
                                            <?php foreach ($provinces as $province) {?>
                                                <option value="<?php echo $province['PROVINCE_ID']; ?>"><?php echo $province['PROVINCE_NAME']; ?></option>
                                            <?php }?>
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
                                        <input type="file" id="driver_image" name="driver_image" class="form-control">
                                    </div>
                                    <button type="submit" class="btn btn-primary">เพิ่มคนขับ</button>
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
                                    <?php foreach ($provinces as $province) {?>
                                        <option value="<?php echo $province['PROVINCE_ID']; ?>"><?php echo $province['PROVINCE_NAME']; ?></option>
                                    <?php }?>
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
                                <input type="file" id="edit_driver_image" name="driver_image" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary">แก้ไขข้อมูลคนขับ</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal สำหรับ Preview รูปภาพ -->
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

          <!-- แสดงรายการคนขับ -->
<table class="table table-striped">
    <thead>
        <tr>
            <th>NO.</th>
            <th>Username</th>
            <th>Password</th>
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
                $imagePath = !empty($driver['driver_image']) ? 'uploads/' . $driver['driver_image'] : 'path/to/default/image.jpg';
        ?>
            <tr>
                <td><?php echo $index++; ?></td>
                <td><?php echo $driver['driver_user']; ?></td>
                <td><?php echo $driver['driver_password']; ?></td>
                <td><?php echo $driver['driver_name'] . " " . $driver['driver_lastname']; ?></td>
                <td><?php echo $driver['driver_tel']; ?></td>
                <td><?php echo $driver['PROVINCE_NAME']; ?></td>
                <td><?php echo $driver['AMPHUR_NAME']; ?></td>
                <td>
                    <img src="<?php echo $imagePath; ?>" 
                         alt="รูปภาพคนขับ" 
                         style="width: 100px; height: auto; cursor: pointer;" 
                         class="img-thumbnail" 
                         onclick="openImagePreview('<?php echo $imagePath; ?>')">
                </td>
                <td>
                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editDriverModal"
                            data-driver_id="<?php echo $driver['driver_id']; ?>"
                            data-driver_user="<?php echo $driver['driver_user']; ?>"
                            data-driver_password="<?php echo $driver['driver_password']; ?>"
                            data-driver_name="<?php echo $driver['driver_name']; ?>"
                            data-driver_lastname="<?php echo $driver['driver_lastname']; ?>"
                            data-driver_tel="<?php echo $driver['driver_tel']; ?>"
                            data-driver_province="<?php echo $driver['driver_province']; ?>"
                            data-driver_amphur="<?php echo $driver['driver_amphur']; ?>">
                        แก้ไข
                    </button>
                    <a href="driver_delete.php?delete_id=<?php echo $driver['driver_id']; ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('คุณต้องการลบข้อมูลนี้จริงๆ หรือไม่?');">ลบ</a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>
        </div>
    </div>
<script>
// เปิด modal แก้ไขและใส่ข้อมูลคนขับลงในฟอร์ม
$('#editDriverModal').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget); // ปุ่มที่เปิด modal
    var driverId = button.data('driver_id'); // ได้ driver_id ที่ถูกต้อง
    var driverUser = button.data('driver_user');
    var driverPassword = button.data('driver_password');
    var driverName = button.data('driver_name');
    var driverLastname = button.data('driver_lastname');
    var driverTel = button.data('driver_tel');
    var driverProvince = button.data('driver_province');
    var driverAmphur = button.data('driver_amphur');

    // ใส่ข้อมูลในฟอร์ม
    $('#edit_driver_user').val(driverUser);
    $('#edit_driver_password').val(driverPassword);
    $('#edit_driver_name').val(driverName);
    $('#edit_driver_lastname').val(driverLastname);
    $('#edit_driver_tel').val(driverTel);
    $('#edit_driver_province').val(driverProvince);

    // เรียกฟังก์ชัน fetchAmphurs เพื่อดึงข้อมูลอำเภอ
    fetchAmphurs(driverProvince, 'edit'); // เรียกฟังก์ชัน fetchAmphurs

    // ตั้งค่าอำเภอที่มีอยู่ในฟอร์ม
    $('#edit_amphur').val(driverAmphur); // ตั้งค่าอำเภอที่มีอยู่
    $('#edit_driver_id').val(driverId); // ตั้งค่า ID คนขับ
});

// ฟังก์ชั่นดึงข้อมูลอำเภอ
function fetchAmphurs(province_id, mode = 'add') {
    var amphur_select = (mode === 'add') ? '#amphur_add' :
                        (mode === 'edit' ? '#edit_amphur' : '#amphur_filter');

    if (province_id === "") {
        $(amphur_select).html("<option value=''>เลือกอำเภอ</option>");
        return;
    }

    $.ajax({
        url: "fetch_amphurs.php",
        method: "GET",
        data: { province_id: province_id },
        success: function(response) {
            $(amphur_select).html("<option value=''>เลือกอำเภอ</option>");
            const amphurs = JSON.parse(response);
            amphurs.forEach(function(amphur) {
                const option = document.createElement("option");
                option.value = amphur.AMPHUR_ID;
                option.text = amphur.AMPHUR_NAME;
                $(amphur_select).append(option);
            });

            // ถ้าเป็นโหมด 'edit', ตั้งค่าอำเภอที่เลือก
            if (mode === 'edit') {
                $(amphur_select).val($('#edit_driver_amphur').val());
            }
        }
    });
}


</script>
<script>
// ฟังก์ชันสำหรับเปิด Modal และแสดงรูปภาพ
function openImagePreview(imagePath) {
    // ตั้งค่า src ของรูปภาพใน Modal
    document.getElementById('previewImage').src = imagePath;
    // เปิด Modal
    const imagePreviewModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
    imagePreviewModal.show();
}
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>