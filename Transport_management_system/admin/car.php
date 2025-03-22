<?php
    include('config/condb.php');
    session_start();
    
    // ตรวจสอบการลบข้อมูล
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

    $searchQuery = "";
    $params = [];

    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit'])) {
        if (!empty($_POST['search'])) {
            $search = $_POST['search'];
            $searchQuery = " WHERE (car_license LIKE :search OR car_brand LIKE :search OR car_color LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
    }

    // ตรวจสอบว่า WHERE ถูกใช้หรือยัง
    $whereClause = (!empty($searchQuery) ? $searchQuery : " WHERE 1=1 ");

    // ดึงข้อมูลจากฐานข้อมูล
    try {
        $sql = "SELECT * FROM car $whereClause";
        $stmt = $conn->prepare($sql);

        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
        }

        $stmt->execute();
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการข้อมูลรถยนต์</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?> <!-- เรียกใช้ sidebar.php -->
    
    <!-- Content -->
    <div class="content" id="content">
        <div class="container mt-5">
            <!-- แสดงข้อความสำเร็จหรือข้อผิดพลาด -->
            <?php if (isset($_SESSION['success'])) { ?>
                <div id="successMessage" class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php } ?>
            <?php if (isset($_SESSION['error'])) { ?>
                <div id="errorMessage" class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php } ?>

            <h3 class="mb-4">รายการรถยนต์</h3>
            <hr>
            <!-- ค้นหา -->
            <form method="POST" action="">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหารถยนต์" value="<?php echo isset($_POST['search']) ? $_POST['search'] : ''; ?>">
                    <button class="btn btn-outline-secondary" type="submit" name="submit">ค้นหา</button>
                </div>
            </form>

            <!-- ปุ่มเปิด Modal -->
            <div class="container mt-5 d-flex justify-content-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCarModal">
                    เพิ่มรถยนต์
                </button>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="addCarModal" tabindex="-1" aria-labelledby="addCarModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addCarModalLabel">เพิ่มรถยนต์</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="car_insert.php" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="car_license" class="form-label">หมายเลขทะเบียนรถ:</label>
                                    <input type="text" id="car_license" name="car_license" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="car_brand" class="form-label">ยี่ห้อรถ:</label>
                                    <input type="text" id="car_brand" name="car_brand" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="car_color" class="form-label">สีรถ:</label>
                                    <input type="text" id="car_color" name="car_color" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="car_seat" class="form-label">จำนวนที่นั่ง:</label>
                                    <input type="number" id="car_seat" name="car_seat" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="car_status" class="form-label">สถานะ:</label>
                                    <select id="car_status" name="car_status" class="form-control">
                                        <option value="available">พร้อมใช้งาน</option>
                                        <option value="unavailable">ไม่พร้อมใช้งาน</option>
                                    </select>
                                </div>

                                <!-- ฟิลด์สำหรับอัพโหลดรูปภาพ -->
                                <div class="mb-3">
                                    <label for="car_image" class="form-label">รูปภาพรถ:</label>
                                    <input type="file" id="car_image" name="car_image" class="form-control" accept="image/*">
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary">เพิ่มรถยนต์</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
                 
                
                <!-- แสดงรายการรถยนต์ -->
<table class="table table-striped">
    <thead>
        <tr>
            <th>NO.</th>
            <th>หมายเลขทะเบียน</th>
            <th>ยี่ห้อ</th>
            <th>สี</th>
            <th>จำนวนที่นั่ง</th>
            <th>สถานะ</th>
            <th>รูปภาพ</th>
            <th>การกระทำ</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $index = 1; // ตัวแปรสำหรับหมายเลขลำดับ
        foreach ($cars as $car) { ?>
            <tr>
                <td><?php echo $index++; ?></td>
                <td><?php echo $car['car_license']; ?></td>
                <td><?php echo $car['car_brand']; ?></td>
                <td><?php echo $car['car_color']; ?></td>
                <td><?php echo $car['car_seat']; ?></td>
                <td><?php echo $car['car_status']; ?></td>
                <td>
                    <?php if ($car['car_image']) { ?>
                        <img src="uploads/<?php echo $car['car_image']; ?>" alt="Car Image" class="img-thumbnail" style="width: 100px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#imageModal" data-bs-image="uploads/<?php echo $car['car_image']; ?>">
                    <?php } else { ?>
                        ไม่มีรูปภาพ
                    <?php } ?>
                </td>
                <td>
                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editCarModal"
                            data-car_id="<?php echo $car['car_id']; ?>"
                            data-car_license="<?php echo $car['car_license']; ?>"
                            data-car_brand="<?php echo $car['car_brand']; ?>"
                            data-car_color="<?php echo $car['car_color']; ?>"
                            data-car_seat="<?php echo $car['car_seat']; ?>"
                            data-car_status="<?php echo $car['car_status']; ?>"
                            data-car_image="<?php echo $car['car_image']; ?>">
                        แก้ไข
                    </button>

                    <a href="car.php?delete_id=<?php echo $car['car_id']; ?>" 
                       class="btn btn-danger btn-sm" 
                       onclick="return confirm('คุณต้องการลบข้อมูลรถยนต์นี้หรือไม่?')">
                       ลบ
                    </a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>


<!-- Modal แก้ไขข้อมูลรถยนต์ -->
<div class="modal fade" id="editCarModal" tabindex="-1" aria-labelledby="editCarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCarModalLabel">แก้ไขข้อมูลรถยนต์</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="car_update.php" enctype="multipart/form-data">
                    <input type="hidden" id="car_id" name="car_id">

                    <div class="mb-3">
                        <label for="car_license" class="form-label">หมายเลขทะเบียนรถ:</label>
                        <input type="text" id="car_license" name="car_license" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="car_brand" class="form-label">ยี่ห้อรถ:</label>
                        <input type="text" id="car_brand" name="car_brand" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="car_color" class="form-label">สีรถ:</label>
                        <input type="text" id="car_color" name="car_color" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="car_seat" class="form-label">จำนวนที่นั่ง:</label>
                        <input type="number" id="car_seat" name="car_seat" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="car_status" class="form-label">สถานะ:</label>
                        <select id="car_status" name="car_status" class="form-control">
                            <option value="available">พร้อมใช้งาน</option>
                            <option value="unavailable">ไม่พร้อมใช้งาน</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="car_image" class="form-label">รูปภาพรถ:</label>
                        <input type="file" id="car_image" name="car_image" class="form-control" accept="image/*">
                        <img id="car_image_preview" src="#" alt="ตัวอย่างภาพรถ" style="display:none; width: 100px; margin-top: 10px;">
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                </form>
            </div>
        </div>
    </div>
</div>

        <!-- Modal สำหรับแสดงรูปภาพขนาดใหญ่ -->
        <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="imageModalLabel">รูปภาพของรถยนต์</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <img id="carImage" src="" alt="Car Image" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
        <button class="open-btn" id="open-btn">&#9776; </button>                               
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            const imageModal = document.getElementById('imageModal');
            imageModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const imageUrl = button.getAttribute('data-bs-image');
                
                const modalImage = imageModal.querySelector('#carImage');
                modalImage.src = imageUrl;
            });
        </script>
        <script>
            const editCarModal = document.getElementById('editCarModal');

    editCarModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;

        // ดึงค่าจากปุ่มแก้ไข
        const car_id = button.getAttribute('data-car_id');
        const car_license = button.getAttribute('data-car_license');
        const car_brand = button.getAttribute('data-car_brand');
        const car_color = button.getAttribute('data-car_color');
        const car_seat = button.getAttribute('data-car_seat');
        const car_status = button.getAttribute('data-car_status');
        const car_image = button.getAttribute('data-car_image');

        // เติมข้อมูลใน modal
        const modal = editCarModal.querySelector('form');
        modal.querySelector('#car_id').value = car_id;
        modal.querySelector('#car_license').value = car_license;
        modal.querySelector('#car_brand').value = car_brand;
        modal.querySelector('#car_color').value = car_color;
        modal.querySelector('#car_seat').value = car_seat;
        modal.querySelector('#car_status').value = car_status;

        // แสดงภาพตัวอย่าง (ขนาดเล็ก)
        const imagePreview = modal.querySelector('#car_image_preview');
        if (car_image) {
            imagePreview.src = `uploads/${car_image}`;
            imagePreview.style.display = 'block';
        } else {
            imagePreview.style.display = 'none';
        }
    });
        // ซ่อนข้อความสำเร็จ/ข้อผิดพลาดหลังจาก 3 วินาที
        $(document).ready(function() {
            // สำหรับข้อความสำเร็จ
            if ($('#successMessage').length) {
                setTimeout(function() {
                    $('#successMessage').fadeOut('slow');
                }, 5000); // ซ่อนหลังจาก 3 วินาที
            }

            // สำหรับข้อความข้อผิดพลาด
            if ($('#errorMessage').length) {
                setTimeout(function() {
                    $('#errorMessage').fadeOut('slow');
                }, 5000); // ซ่อนหลังจาก 1 วินาที
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

        </script>
    </body>
    </html>
