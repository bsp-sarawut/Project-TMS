<?php
require_once 'condb.php';

// ตรวจสอบว่าเซสชันยังไม่เริ่มต้น
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบการออกจากระบบก่อนการส่ง output ใด ๆ
if (isset($_GET['logout'])) {
    session_destroy(); // ล้าง session ทั้งหมด
    header("Location: ../index.php"); // กลับไปหน้า login
    exit();
}

// ตรวจสอบว่ามี session ของผู้ใช้หรือไม่
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

$stu_username = $_SESSION['user_name'];
$user = null;

// สร้างโฟลเดอร์ uploads ถ้ายังไม่มี
$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        $_SESSION['error'] = "ไม่สามารถสร้างโฟลเดอร์ uploads ได้";
        header("Location: setting.php");
        exit();
    }
}

// ตรวจสอบว่าโฟลเดอร์สามารถเขียนได้หรือไม่
if (!is_writable($upload_dir)) {
    $_SESSION['error'] = "โฟลเดอร์ uploads ไม่สามารถเขียนได้ กรุณาตรวจสอบสิทธิ์";
    header("Location: setting.php");
    exit();
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน
try {
    $sql = "SELECT stu_ID, stu_name, stu_lastname, stu_tel, stu_faculty, stu_major, stu_username, stu_img
            FROM students
            WHERE stu_username = :stu_username";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':stu_username', $stu_username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "ไม่พบข้อมูลผู้ใช้";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

// ดึงข้อมูลคณะ
try {
    $stmt = $conn->prepare("SELECT faculty_id, faculty_name FROM faculties ORDER BY faculty_name");
    $stmt->execute();
    $faculties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลคณะ: " . $e->getMessage();
    $faculties = [];
}

// ตรวจสอบว่าคณะของผู้ใช้มีอยู่ใน $faculties หรือไม่
$faculty_exists = false;
if ($user && !empty($user['stu_faculty'])) {
    foreach ($faculties as $faculty) {
        if ($faculty['faculty_name'] === $user['stu_faculty']) {
            $faculty_exists = true;
            break;
        }
    }
    if (!$faculty_exists) {
        $_SESSION['error'] = "คณะของผู้ใช้ (" . htmlspecialchars($user['stu_faculty']) . ") ไม่มีอยู่ในระบบ กรุณาติดต่อแอดมิน";
    }
}

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $stu_name = trim($_POST['stu_name'] ?? '');
    $stu_lastname = trim($_POST['stu_lastname'] ?? '');
    $stu_tel = trim($_POST['stu_tel'] ?? '');
    $stu_faculty = trim($_POST['stu_faculty'] ?? '');
    $stu_major = trim($_POST['stu_major'] ?? '');
    $new_username = trim($_POST['stu_username'] ?? '');
    $stu_password = trim($_POST['stu_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    $errors = [];

    if (empty($stu_name)) {
        $errors[] = "กรุณากรอกชื่อ";
    }

    if (empty($stu_lastname)) {
        $errors[] = "กรุณากรอกนามสกุล";
    }

    if (empty($stu_tel) || !preg_match('/^[0-9]{10}$/', $stu_tel)) {
        $errors[] = "เบอร์โทรต้องเป็นตัวเลข 10 หลัก";
    }

    if (empty($stu_faculty)) {
        $errors[] = "กรุณาเลือกคณะ";
    }

    if (empty($stu_major)) {
        $errors[] = "กรุณาเลือกสาขา";
    }

    if (empty($new_username)) {
        $errors[] = "กรุณากรอกชื่อผู้ใช้";
    } else {
        if ($new_username !== $stu_username) {
            $sql_check = "SELECT COUNT(*) FROM students WHERE stu_username = :new_username";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bindParam(':new_username', $new_username, PDO::PARAM_STR);
            $stmt_check->execute();
            if ($stmt_check->fetchColumn() > 0) {
                $errors[] = "ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว";
            }
        }
    }

    if (!empty($stu_password)) {
        if (strlen($stu_password) < 6) {
            $errors[] = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
        } elseif ($stu_password !== $confirm_password) {
            $errors[] = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน";
        }
    }

    $stu_img = $user['stu_img'] ?? null;
    if (isset($_FILES['stu_img']) && $_FILES['stu_img']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['stu_img'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_error = $file['error'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png'];

        // ตรวจสอบข้อผิดพลาดจากการอัปโหลด
        switch ($file_error) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = "ไฟล์รูปภาพมีขนาดใหญ่เกินไป";
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = "ไฟล์รูปภาพถูกอัปโหลดเพียงบางส่วน";
                break;
            case UPLOAD_ERR_NO_FILE:
                break; // ไม่มีไฟล์อัปโหลด ไม่ต้องทำอะไร
            case UPLOAD_ERR_NO_TMP_DIR:
                $errors[] = "ไม่พบโฟลเดอร์ชั่วคราวสำหรับอัปโหลด";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errors[] = "ไม่สามารถเขียนไฟล์ลงดิสก์ได้";
                break;
            case UPLOAD_ERR_EXTENSION:
                $errors[] = "ส่วนขยายไฟล์ถูกบล็อกโดย PHP";
                break;
            default:
                $errors[] = "เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุในการอัปโหลด";
                break;
        }

        if ($file_error === UPLOAD_ERR_OK) {
            if (!in_array($file_ext, $allowed_exts)) {
                $errors[] = "ไฟล์รูปภาพต้องเป็น JPG หรือ PNG เท่านั้น";
            }

            if ($file_size > 2 * 1024 * 1024) {
                $errors[] = "ไฟล์รูปภาพต้องมีขนาดไม่เกิน 2MB";
            }

            if (empty($errors)) {
                $new_file_name = "profile_" . $user['stu_ID'] . "." . $file_ext;
                $file_path = $upload_dir . $new_file_name;

                // ลบไฟล์เก่าถ้ามี
                if ($stu_img && file_exists($upload_dir . $stu_img)) {
                    if (!unlink($upload_dir . $stu_img)) {
                        $errors[] = "ไม่สามารถลบรูปภาพเก่าได้";
                    }
                }

                if (empty($errors)) {
                    if (move_uploaded_file($file_tmp, $file_path)) {
                        $stu_img = $new_file_name;
                    } else {
                        $errors[] = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ: ไม่สามารถย้ายไฟล์ได้";
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $sql_update = "UPDATE students SET 
                           stu_name = :stu_name,
                           stu_lastname = :stu_lastname,
                           stu_tel = :stu_tel,
                           stu_faculty = :stu_faculty,
                           stu_major = :stu_major,
                           stu_username = :stu_username,
                           stu_img = :stu_img";
            if (!empty($stu_password)) {
                $sql_update .= ", stu_password = :stu_password";
                $hashed_password = password_hash($stu_password, PASSWORD_DEFAULT);
            }
            $sql_update .= " WHERE stu_username = :old_username";

            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bindParam(':stu_name', $stu_name, PDO::PARAM_STR);
            $stmt_update->bindParam(':stu_lastname', $stu_lastname, PDO::PARAM_STR);
            $stmt_update->bindParam(':stu_tel', $stu_tel, PDO::PARAM_STR);
            $stmt_update->bindParam(':stu_faculty', $stu_faculty, PDO::PARAM_STR);
            $stmt_update->bindParam(':stu_major', $stu_major, PDO::PARAM_STR);
            $stmt_update->bindParam(':stu_username', $new_username, PDO::PARAM_STR);
            $stmt_update->bindParam(':stu_img', $stu_img, PDO::PARAM_STR);
            $stmt_update->bindParam(':old_username', $stu_username, PDO::PARAM_STR);
            if (!empty($stu_password)) {
                $stmt_update->bindParam(':stu_password', $hashed_password, PDO::PARAM_STR);
            }

            if ($stmt_update->execute()) {
                if ($new_username !== $stu_username) {
                    $_SESSION['user_name'] = $new_username;
                }
                $_SESSION['success'] = "บันทึกข้อมูลสำเร็จ";
                header("Location: setting.php");
                exit();
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
                header("Location: setting.php");
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
            header("Location: setting.php");
            exit();
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: setting.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1c2526 0%, #2c3e50 100%);
            font-family: 'Chakra Petch', sans-serif;
            min-height: 100vh;
            margin: 0;
            position: relative;
            overflow-x: hidden;
            padding-top: 80px;
            color: #eceff1;
        }
        .container {
            padding-top: 20px;
            padding-bottom: 50px;
        }
        h2 {
            color: #ffca28;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 40px;
            text-align: center;
        }
        .settings-card {
            background: #263238;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 40px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            border-left: 5px solid #ffca28;
            transition: transform 0.3s ease;
        }
        .settings-card:hover {
            transform: translateY(-5px);
        }
        .form-section {
            background: #2c3e50;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            color: #ffca28;
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }
        .form-group input, .form-group select {
            width: 100%;
            background: #37474f;
            color: #eceff1;
            border: 1px solid #ffca28;
            border-radius: 5px;
            padding: 8px 10px;
            font-size: 1rem;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #17a2b8;
        }
        .form-group input[type="file"] {
            padding: 3px;
        }
        .profile-image {
            display: block;
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            margin: 0 auto 15px;
            border: 2px solid #ffca28;
        }
        .no-image {
            text-align: center;
            color: #b0bec5;
            margin-bottom: 15px;
            font-style: italic;
        }
        .btn-save {
            background: #ffca28;
            color: #1c2526;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.3s ease;
            display: block;
            margin: 20px auto 0;
        }
        .btn-save:hover {
            background: #ffb300;
        }
        .message {
            background: #2c3e50;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1rem;
        }
        .message.success {
            color: #28a745;
        }
        .message.error {
            color: #dc3545;
        }
        .text-muted {
            color: #b0bec5 !important;
        }
        @media (max-width: 768px) {
            .container {
                padding-top: 10px;
                padding-bottom: 40px;
            }
            .settings-card {
                padding: 15px;
                margin-bottom: 30px;
            }
            h2 {
                font-size: 1.8rem;
            }
            .form-group label {
                font-size: 0.95rem;
            }
            .form-group input, .form-group select {
                font-size: 0.9rem;
                padding: 6px 8px;
            }
            .profile-image {
                width: 120px;
                height: 120px;
            }
            .btn-save {
                font-size: 0.9rem;
                padding: 8px 15px;
            }
            .message {
                font-size: 0.9rem;
            }
        }
        @media (max-width: 576px) {
            h2 {
                font-size: 1.5rem;
            }
            .form-group label {
                font-size: 0.9rem;
            }
            .form-group input, .form-group select {
                font-size: 0.85rem;
                padding: 5px 8px;
            }
            .profile-image {
                width: 100px;
                height: 100px;
            }
            .btn-save {
                font-size: 0.85rem;
                padding: 6px 12px;
            }
            .message {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h2>ตั้งค่าโปรไฟล์</h2>

        <?php
        // แสดงข้อความ success เฉพาะเมื่อไม่ใช่ "เข้าสู่ระบบสำเร็จ" หรือ "ล็อกอินสำเร็จ"
        if (isset($_SESSION['success']) && !in_array($_SESSION['success'], ["เข้าสู่ระบบสำเร็จ", "ล็อกอินสำเร็จ"])): ?>
            <div class="message success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: '<?php echo htmlspecialchars($_SESSION['success']); ?>',
                        confirmButtonText: 'ตกลง'
                    });
                });
            </script>
            <?php unset($_SESSION['success']); ?>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="message error"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        text: '<?php echo htmlspecialchars($_SESSION['error']); ?>',
                        confirmButtonText: 'ตกลง'
                    });
                });
            </script>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if ($user): ?>
            <div class="settings-card">
                <div class="form-section">
                    <form method="POST" enctype="multipart/form-data">
                        <!-- รูปภาพ -->
                        <div class="form-group">
                            <label>รูปภาพโปรไฟล์:</label>
                            <?php if (!empty($user['stu_img']) && file_exists('../uploads/' . $user['stu_img'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($user['stu_img']); ?>" alt="Profile Image" class="profile-image">
                            <?php else: ?>
                                <p class="no-image">ไม่มีรูปภาพ</p>
                            <?php endif; ?>
                            <input type="file" id="stu_img" name="stu_img" accept="image/jpeg,image/png">
                        </div>
                        <!-- ชื่อ -->
                        <div class="form-group">
                            <label for="stu_name">ชื่อ:</label>
                            <input type="text" id="stu_name" name="stu_name" value="<?php echo htmlspecialchars($user['stu_name']); ?>" required>
                        </div>
                        <!-- นามสกุล -->
                        <div class="form-group">
                            <label for="stu_lastname">นามสกุล:</label>
                            <input type="text" id="stu_lastname" name="stu_lastname" value="<?php echo htmlspecialchars($user['stu_lastname']); ?>" required>
                        </div>
                        <!-- เบอร์โทร -->
                        <div class="form-group">
                            <label for="stu_tel">เบอร์โทร:</label>
                            <input type="text" id="stu_tel" name="stu_tel" value="<?php echo htmlspecialchars($user['stu_tel']); ?>" required>
                        </div>
                        <!-- คณะ -->
                        <div class="form-group">
                            <label for="stu_faculty">คณะ:</label>
                            <select id="stu_faculty" name="stu_faculty" required>
                                <option value="">เลือกคณะ</option>
                                <?php foreach ($faculties as $faculty): ?>
                                    <option value="<?php echo htmlspecialchars($faculty['faculty_name']); ?>" <?php echo $user['stu_faculty'] === $faculty['faculty_name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- สาขา -->
                        <div class="form-group">
                            <label for="stu_major">สาขา:</label>
                            <select id="stu_major" name="stu_major" required>
                                <option value="">เลือกสาขา</option>
                            </select>
                        </div>
                        <!-- ชื่อผู้ใช้ -->
                        <div class="form-group">
                            <label for="stu_username">ชื่อผู้ใช้:</label>
                            <input type="text" id="stu_username" name="stu_username" value="<?php echo htmlspecialchars($user['stu_username']); ?>" required>
                        </div>
                        <!-- รหัสผ่าน -->
                        <div class="form-group">
                            <label for="stu_password">รหัสผ่านใหม่ (เว้นว่างหากไม่ต้องการเปลี่ยน):</label>
                            <input type="password" id="stu_password" name="stu_password">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">ยืนยันรหัสผ่านใหม่:</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                        <button type="submit" class="btn-save">บันทึก</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="no-queue-message text-muted">
                <i class="fas fa-user"></i>
                <p>ไม่พบข้อมูลผู้ใช้</p>
                <p>กรุณาติดต่อผู้ดูแลระบบหากมีข้อสงสัย</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const facultySelect = document.getElementById('stu_faculty');
            const majorSelect = document.getElementById('stu_major');

            if (facultySelect && majorSelect) {
                function fetchMajors(facultyName) {
                    if (!facultyName) {
                        majorSelect.innerHTML = '<option value="">เลือกสาขา</option>';
                        return;
                    }

                    $.ajax({
                        url: 'fetch_majors.php',
                        type: 'POST',
                        data: { faculty_name: facultyName },
                        dataType: 'json',
                        success: function(data) {
                            majorSelect.innerHTML = '<option value="">เลือกสาขา</option>';
                            if (data.error) {
                                majorSelect.innerHTML = '<option value="">เกิดข้อผิดพลาด: ' + data.error + '</option>';
                                return;
                            }
                            data.forEach(major => {
                                const option = document.createElement('option');
                                option.value = major.major_name;
                                option.textContent = major.major_name;
                                const currentMajor = '<?php echo !empty($user['stu_major']) ? addslashes($user['stu_major']) : ''; ?>';
                                if (major.major_name === currentMajor) {
                                    option.selected = true;
                                }
                                majorSelect.appendChild(option);
                            });
                        },
                        error: function(xhr, status, error) {
                            majorSelect.innerHTML = '<option value="">เกิดข้อผิดพลาดในการดึงข้อมูลสาขา</option>';
                        }
                    });
                }

                if (facultySelect.value) {
                    fetchMajors(facultySelect.value);
                }

                facultySelect.addEventListener('change', function() {
                    fetchMajors(this.value);
                });
            }
        });
    </script>
</body>
</html>