<?php
session_start();
require_once("config/condb.php"); // คงพาธเดิมตามที่ระบุ

// ตรวจสอบว่าแอดมินล็อกอินหรือไม่
if (!isset($_SESSION['admin_id'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'ไม่ได้รับอนุญาต',
                text: 'กรุณาเข้าสู่ระบบแอดมินก่อน',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.location.href = 'index.php';
            });
        });
    </script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_user = trim($_POST['driver_user']);
    $driver_password = trim($_POST['driver_password']);
    $driver_name = trim($_POST['driver_name']);
    $driver_lastname = trim($_POST['driver_lastname']);
    $driver_tel = trim($_POST['driver_tel']);
    $driver_province = trim($_POST['driver_province']);
    $driver_amphur = trim($_POST['driver_amphur']);

    // ตรวจสอบข้อมูล
    $errors = [];
    if (empty($driver_user) || strlen($driver_user) < 4) {
        $errors[] = "ชื่อผู้ใช้ต้องมีอย่างน้อย 4 ตัวอักษร";
    }
    if (empty($driver_password) || strlen($driver_password) < 6) {
        $errors[] = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
    }
    if (empty($driver_name) || empty($driver_lastname)) {
        $errors[] = "กรุณากรอกชื่อและนามสกุล";
    }
    if (!preg_match('/^[0-9]{10}$/', $driver_tel)) {
        $errors[] = "เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก";
    }

    // ตรวจสอบว่า driver_user ซ้ำหรือไม่
    $stmt = $conn->prepare("SELECT COUNT(*) FROM driver WHERE driver_user = :driver_user");
    $stmt->bindParam(':driver_user', $driver_user);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "ชื่อผู้ใช้นี้มีอยู่แล้ว";
    }

    // ตรวจสอบ province และ amphur
    $stmt = $conn->prepare("SELECT COUNT(*) FROM province WHERE province_id = :province_id");
    $stmt->bindParam(':province_id', $driver_province);
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $errors[] = "จังหวัดไม่ถูกต้อง";
    }
    $stmt = $conn->prepare("SELECT COUNT(*) FROM amphur WHERE amphur_id = :amphur_id AND province_id = :province_id");
    $stmt->bindParam(':amphur_id', $driver_amphur);
    $stmt->bindParam(':province_id', $driver_province);
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $errors[] = "อำเภอไม่ถูกต้อง";
    }

    if (!empty($errors)) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    html: '" . implode("<br>", array_map('htmlspecialchars', $errors)) . "',
                    confirmButtonText: 'ตกลง'
                });
            });
        </script>";
    } else {
        try {
            // แฮชรหัสผ่าน
            $hashed_password = password_hash($driver_password, PASSWORD_DEFAULT);

            // เพิ่มข้อมูลคนขับ
            $stmt = $conn->prepare("INSERT INTO driver (driver_user, driver_password, driver_name, driver_lastname, driver_tel, driver_province, driver_amphur) 
                                    VALUES (:driver_user, :driver_password, :driver_name, :driver_lastname, :driver_tel, :driver_province, :driver_amphur)");
            $stmt->bindParam(':driver_user', $driver_user);
            $stmt->bindParam(':driver_password', $hashed_password);
            $stmt->bindParam(':driver_name', $driver_name);
            $stmt->bindParam(':driver_lastname', $driver_lastname);
            $stmt->bindParam(':driver_tel', $driver_tel);
            $stmt->bindParam(':driver_province', $driver_province);
            $stmt->bindParam(':driver_amphur', $driver_amphur);
            $stmt->execute();

            // ดึง driver_id ที่เพิ่งเพิ่ม
            $driver_id = $conn->lastInsertId();

            // ตรวจสอบและอัปโหลดรูปภาพ
            $driver_image = null;
            if (isset($_FILES['driver_image']) && $_FILES['driver_image']['error'] === UPLOAD_ERR_OK) {
                $allowed_ext = ['jpg', 'jpeg', 'png'];
                $image_tmp_name = $_FILES['driver_image']['tmp_name'];
                $image_name = $_FILES['driver_image']['name'];
                $image_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                $image_size = $_FILES['driver_image']['size'];
                $image_new_name = 'driver_' . $driver_id . '.' . $image_ext;
                $image_upload_path = 'Uploads/drivers/' . $image_new_name;

                if (!in_array($image_ext, $allowed_ext)) {
                    throw new Exception("ไฟล์รูปภาพต้องเป็น jpg, jpeg, หรือ png เท่านั้น");
                }
                if ($image_size > 2 * 1024 * 1024) { // จำกัดขนาด 2MB
                    throw new Exception("ไฟล์รูปภาพต้องมีขนาดไม่เกิน 2MB");
                }

                if (!is_dir('Uploads/drivers/')) {
                    mkdir('Uploads/drivers/', 0777, true);
                }

                if (!move_uploaded_file($image_tmp_name, $image_upload_path)) {
                    // ลบข้อมูลคนขับถ้าอัปโหลดล้มเหลว
                    $delete_stmt = $conn->prepare("DELETE FROM driver WHERE driver_id = :driver_id");
                    $delete_stmt->bindParam(':driver_id', $driver_id);
                    $delete_stmt->execute();
                    throw new Exception("ไม่สามารถอัปโหลดรูปภาพได้");
                }

                $driver_image = $image_new_name;
                // อัปเดต driver_image ในฐานข้อมูล
                $update_stmt = $conn->prepare("UPDATE driver SET driver_image = :driver_image WHERE driver_id = :driver_id");
                $update_stmt->bindParam(':driver_image', $driver_image);
                $update_stmt->bindParam(':driver_id', $driver_id);
                $update_stmt->execute();
            }

            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'เพิ่มข้อมูลสำเร็จ',
                        text: 'ข้อมูลคนขับถูกบันทึกเรียบร้อยแล้ว',
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        window.location.href = 'driver.php';
                    });
                });
            </script>";
        } catch (Exception $e) {
            // ลบข้อมูลคนขับถ้ามีข้อผิดพลาด
            if (isset($driver_id)) {
                $delete_stmt = $conn->prepare("DELETE FROM driver WHERE driver_id = :driver_id");
                $delete_stmt->bindParam(':driver_id', $driver_id);
                $delete_stmt->execute();
            }
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: '" . htmlspecialchars($e->getMessage()) . "',
                        confirmButtonText: 'ตกลง'
                    });
                });
            </script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มข้อมูลคนขับ</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: #f5f7fa;
            font-family: 'IBM Plex Sans Thai', sans-serif;
            min-height: 100vh;
            padding-top: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
        }
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .card-body {
            padding: 30px;
        }
        .card-title {
            font-size: 1.5rem;
            color: #1a1a1a;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-label {
            font-size: 1rem;
            color: #4a4a4a;
        }
        .form-control {
            border-radius: 8px;
            font-size: 1rem;
        }
        .btn-primary {
            background-color: #003087;
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-weight: 500;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #0044cc;
        }
        .alert {
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        @media (max-width: 576px) {
            .container {
                padding: 15px;
            }
            .card-body {
                padding: 20px;
            }
            .card-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">เพิ่มข้อมูลคนขับ</h5>

                <form action="add_driver.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="driver_user" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="driver_user" name="driver_user" placeholder="กรอกชื่อผู้ใช้" required>
                    </div>
                    <div class="mb-3">
                        <label for="driver_password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="driver_password" name="driver_password" placeholder="กรอกรหัสผ่าน" required>
                    </div>
                    <div class="mb-3">
                        <label for="driver_name" class="form-label">ชื่อ</label>
                        <input type="text" class="form-control" id="driver_name" name="driver_name" placeholder="กรอกชื่อ" required>
                    </div>
                    <div class="mb-3">
                        <label for="driver_lastname" class="form-label">นามสกุล</label>
                        <input type="text" class="form-control" id="driver_lastname" name="driver_lastname" placeholder="กรอกนามสกุล" required>
                    </div>
                    <div class="mb-3">
                        <label for="driver_tel" class="form-label">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" id="driver_tel" name="driver_tel" placeholder="กรอกเบอร์โทรศัพท์" required>
                    </div>
                    <div class="mb-3">
                        <label for="driver_province" class="form-label">จังหวัด</label>
                        <select class="form-control" id="driver_province" name="driver_province" required>
                            <option value="">เลือกจังหวัด</option>
                            <?php
                            $stmt = $conn->query("SELECT * FROM province ORDER BY province_name");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$row['province_id']}'>{$row['province_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="driver_amphur" class="form-label">อำเภอ</label>
                        <select class="form-control" id="driver_amphur" name="driver_amphur" disabled required>
                            <option value="">เลือกอำเภอ</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="driver_image" class="form-label">รูปภาพ</label>
                        <input type="file" class="form-control" id="driver_image" name="driver_image" accept="image/jpeg,image/png">
                    </div>
                    <button type="submit" class="btn btn-primary">เพิ่มคนขับ</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS, Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script>
        // AJAX สำหรับโหลดอำเภอเมื่อเลือกจังหวัด
        $(document).ready(function() {
            $('#driver_province').change(function() {
                var province_id = $(this).val();
                if (province_id) {
                    $.ajax({
                        type: "POST",
                        url: "get_amphur.php",
                        data: { province_id: province_id },
                        success: function(response) {
                            $('#driver_amphur').html(response);
                            $('#driver_amphur').prop('disabled', false);
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: 'ไม่สามารถโหลดข้อมูลอำเภอได้',
                                confirmButtonText: 'ตกลง'
                            });
                        }
                    });
                } else {
                    $('#driver_amphur').html('<option value="">เลือกอำเภอ</option>');
                    $('#driver_amphur').prop('disabled', true);
                }
            });
        });
    </script>
</body>
</html>