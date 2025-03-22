<?php
    include 'config/condb.php';
    session_start();

    // ตรวจสอบการลบข้อมูล
    if (isset($_GET['delete_id']) && ! empty($_GET['delete_id'])) {
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
    if (isset($_POST['search']) && ! empty($_POST['search'])) {
        $search      = $_POST['search'];
        $searchQuery = "WHERE r.location LIKE :search OR p.PROVINCE_NAME LIKE :search OR a.AMPHUR_NAME LIKE :search";
    }

    if (isset($_POST['province_filter']) && ! empty($_POST['province_filter'])) {
        $province_filter = $_POST['province_filter'];
        $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " r.province = :province_filter";
    }

    if (isset($_POST['amphur_filter']) && ! empty($_POST['amphur_filter'])) {
        $amphur_filter = $_POST['amphur_filter'];
        $searchQuery .= ($searchQuery ? " AND" : " WHERE") . " r.amphur = :amphur_filter";
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

    // ดึงข้อมูลเส้นทางจากฐานข้อมูลตามการค้นหา
    try {
        $stmt = $conn->prepare("SELECT r.*, p.PROVINCE_NAME, a.AMPHUR_NAME
                            FROM routes r
                            LEFT JOIN province p ON r.province = p.PROVINCE_ID
                            LEFT JOIN amphur a ON r.amphur = a.AMPHUR_ID
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
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<!-- Modal สำหรับแสดงภาพขนาดใหญ่ -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> <!-- เพิ่ม modal-xl -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">รูปภาพประกอบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid" alt="Route Image">
            </div>
        </div>
    </div>
</div>

<body class="body_route">
    <?php include 'sidebar.php'; ?>
    <div class="content" id="content">
        <div class="container mt-5">
            <h3 class="mb-4">รายการเส้นทาง</h3>
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
                        <input type="text" name="search" class="form-control" placeholder="ค้นหาเส้นทาง" value="<?php echo isset($search) ? $search : ''; ?>">
                        <button class="btn btn-outline-secondary" type="submit">ค้นหา</button>
                    </div>
                </form>
            </div>
</form>
            </div>

            <div class="container mt-5  mb-3 d-flex justify-content-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRouteModal">
                    เพิ่มเส้นทาง
                </button>
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
                                        <?php foreach ($provinces as $province) {?>
                                            <option value="<?php echo $province['PROVINCE_ID']; ?>">
                                                <?php echo $province['PROVINCE_NAME']; ?>
                                            </option>
                                        <?php }?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="amphur" class="form-label">อำเภอ:</label>
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
                                    <input type="text" id="price" name="price" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="route_image" class="form-label">อัปโหลดรูปภาพ:</label>
                                    <input type="file" id="route_image" name="route_image" class="form-control" accept="image/*" onchange="previewImage(this, 'preview_add')">
                                    <img id="preview_add" src="" alt="Preview Image" style="max-width: 100px; display: none; margin-top: 10px;">
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary">เพิ่มเส้นทาง</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- แสดงรายการเส้นทาง -->
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>NO.</th>
                        <th>Province</th>
                        <th>Amphur</th>
                        <th>Location</th>
                        <th>Price</th>
                        <th>รูปภาพ</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $index = 1;
                    foreach ($routes as $route) {?>
                        <tr>
                            <td><?php echo $index++; ?></td>
                            <td><?php echo $route['PROVINCE_NAME']; ?></td>
                            <td><?php echo $route['AMPHUR_NAME']; ?></td>
                            <td><?php echo $route['location']; ?></td>
                            <td><?php echo $route['price']; ?></td>
                            <td>
                                <?php if (! empty($route['route_image'])) {?>
                                    <img src="<?php echo $route['route_image']; ?>" alt="Route Image" width="100" class="previewable" style="cursor:pointer;">
                                <?php } else {?>
                                    ไม่มีรูปภาพ
                                <?php }?>
                            </td>

                            <td>
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editRouteModal"
                                        data-route_id="<?php echo $route['route_ID']; ?>"
                                        data-province="<?php echo $route['province']; ?>"
                                        data-amphur="<?php echo $route['amphur']; ?>"
                                        data-location="<?php echo $route['location']; ?>"

                                        data-price="<?php echo $route['price']; ?>">แก้ไข</button>
                                <a href="route_delete.php?delete_id=<?php echo $route['route_ID']; ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('คุณต้องการลบข้อมูลนี้จริงๆ หรือไม่?');">ลบ</a>
                            </td>
                        </tr>
                    <?php }?>
                </tbody>
            </table>
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
                            <?php foreach ($provinces as $province) {?>
                                <option value="<?php echo $province['PROVINCE_ID']; ?>">
                                    <?php echo $province['PROVINCE_NAME']; ?>
                                </option>
                            <?php }?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="amphur_edit" class="form-label">อำเภอ:</label>
                        <select id="amphur_edit" name="amphur_id" class="form-control" required>
                            <option value="">เลือกอำเภอ</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">สถานที่:</label>
                        <input type="text" id="location" name="location" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">ราคา:</label>
                        <input type="text" id="price" name="price" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="route_image_edit" class="form-label">เปลี่ยนรูปภาพ:</label>
                        <input type="file" id="route_image_edit" name="route_image" class="form-control" accept="image/*" onchange="previewImage(event)">
                        <input type="hidden" name="old_route_image" value="<?php echo $route['route_image']; ?>">
                    </div>
                    <!-- แสดงตัวอย่างรูปภาพ -->
                    <div class="mb-3" id="imagePreviewContainer" style="display: none;">
                        <label for="imagePreview" class="form-label">ตัวอย่างรูปภาพ:</label>
                        <img id="imagePreview" src="" class="img-fluid" alt="Image Preview">
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                </form>
            </div>
        </div>
    </div>
</div>

    <script>
        // ฟังก์ชั่นดึงข้อมูลอำเภอ
        function fetchAmphurs(province_id, mode = 'add') {
            var amphur_select = (mode === 'add') ? '#amphur_add' : (mode === 'edit' ? '#amphur_edit' : '#amphur_filter');

            if (province_id) {
                $.ajax({
                    url: 'get_amphur.php',
                    type: 'POST',
                    data: { province_id: province_id },
                    success: function(data) {
                        $(amphur_select).empty();
                        if (data.trim()) {
                            $(amphur_select).html(data);
                        } else {
                            $(amphur_select).html('<option value="">ไม่มีข้อมูลอำเภอ</option>');
                        }
                    },
                    error: function() {
                        alert('เกิดข้อผิดพลาดในการดึงข้อมูลอำเภอ');
                    }
                });
            } else {
                $(amphur_select).html('<option value="">เลือกอำเภอ</option>');
            }
        }

    </script>
    <script>
            $('#editRouteModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget); // ปุ่มที่กด
                var routeID = button.data('route_id');
                var province = button.data('province');
                var amphur = button.data('amphur');
                var location = button.data('location');
                var price = button.data('price');

                var modal = $(this);
                modal.find('#route_ID').val(routeID);
                modal.find('#province_edit').val(province);
                modal.find('#amphur_edit').val(amphur);
                modal.find('#location').val(location);
                modal.find('#price').val(price);

                // ดึงข้อมูลอำเภอใหม่ตามจังหวัด
                fetchAmphurs(province, 'edit');
});
    </script>
    <script>
            // ซ่อนข้อความสำเร็จ/ข้อผิดพลาดหลังจาก 3 วินาที
    $(document).ready(function() {
        // สำหรับข้อความสำเร็จ
        if ($('#successMessage').length) {
            setTimeout(function() {
                $('#successMessage').fadeOut('slow');
            }, 1000); // ซ่อนหลังจาก 3 วินาที
        }

        // สำหรับข้อความข้อผิดพลาด
        if ($('#errorMessage').length) {
            setTimeout(function() {
                $('#errorMessage').fadeOut('slow');
            }, 1000); // ซ่อนหลังจาก 1 วินาที
        }

        // ฟังก์ชันปิด Sidebar เมื่อคลิกปุ่ม X
        $('.close-btn').on('click', function() {
            $('.sidebar').addClass('closed');
            $('.content').addClass('closed');
        });

        // ฟังก์ชันเปิด Sidebar เมื่อคลิกปุ่มเปิด Sidebar
        $('.open-btn').on('click', function() {
            $('.sidebar').removeClass('closed');
            $('.content').removeClass('closed');
        });
    });
        // เมื่อคลิกที่รูป ให้เปิด modal และแสดงรูปที่เลือก
    $(document).ready(function () {
        $('img.previewable').click(function () {
            var imgSrc = $(this).attr('src');  // ดึง URL ของรูปที่คลิก
            $('#modalImage').attr('src', imgSrc); // แสดงรูปใน modal
            $('#imageModal').modal('show'); // เปิด modal
        });
    });

    // ฟังก์ชันสำหรับแสดงตัวอย่างรูปภาพ
    function previewImage(event) {
        var reader = new FileReader();
        var imagePreview = document.getElementById('imagePreview');
        var imagePreviewContainer = document.getElementById('imagePreviewContainer');

        reader.onload = function() {
            imagePreview.src = reader.result; // ตั้งค่า src ของตัวอย่างภาพ
            imagePreviewContainer.style.display = 'block'; // แสดง container ของตัวอย่างภาพ
        }

        if (event.target.files.length > 0) {
            reader.readAsDataURL(event.target.files[0]); // อ่านไฟล์ที่เลือก
        }
    }


    </script>
    <button class="open-btn" id="open-btn">&#9776; </button>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
