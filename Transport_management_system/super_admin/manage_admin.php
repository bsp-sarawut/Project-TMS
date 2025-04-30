<?php
session_start();
include 'config/condb.php';

// ตรวจสอบว่า Super Admin ล็อกอินอยู่หรือไม่
if (!isset($_SESSION['super_admin_id'])) {
    $_SESSION['error'] = 'กรุณาล็อกอินก่อนใช้งาน!';
    session_write_close();
    header("Location: login.php");
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    $_SESSION['error'] = 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้';
    session_write_close();
    header("Location: login.php");
    exit;
}

// เพิ่ม Admin พร้อมการตรวจสอบเงื่อนไข
if (isset($_POST['add_admin'])) {
    $username = trim($_POST['admin_username']);
    $password = trim($_POST['admin_password']);
    $name = trim($_POST['admin_name']);
    $lastname = trim($_POST['admin_lastname']);

    // ตรวจสอบเงื่อนไข
    if (empty($username) || strlen($username) < 4) {
        $_SESSION['error'] = 'ชื่อผู้ใช้ต้องมีความยาวอย่างน้อย 4 ตัวอักษร';
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    }

    if (empty($password) || strlen($password) < 6) {
        $_SESSION['error'] = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    }

    if (empty($name)) {
        $_SESSION['error'] = 'กรุณากรอกชื่อ';
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    }

    if (empty($lastname)) {
        $_SESSION['error'] = 'กรุณากรอกนามสกุล';
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    }

    try {
        // ตรวจสอบชื่อผู้ใช้ซ้ำ
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin WHERE admin_username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว';
            session_write_close();
            header("Location: manage_admin.php");
            exit();
        }

        // เข้ารหัสรหัสผ่านและบันทึกข้อมูล
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin (admin_username, admin_password, admin_name, admin_lastname) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $name, $lastname]);

        $_SESSION['success'] = 'เพิ่ม Admin สำเร็จ!';
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    }
}

// แก้ไข Admin
if (isset($_POST['edit_admin'])) {
    $admin_id = $_POST['admin_ID'];
    $username = trim($_POST['admin_username']);
    $name = trim($_POST['admin_name']);
    $lastname = trim($_POST['admin_lastname']);
    $password = !empty($_POST['admin_password']) ? trim($_POST['admin_password']) : null;

    // ตรวจสอบเงื่อนไข
    if (empty($username) || strlen($username) < 4) {
        $_SESSION['error'] = 'ชื่อผู้ใช้ต้องมีความยาวอย่างน้อย 4 ตัวอักษร';
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    }

    if (!empty($password) && strlen($password) < 6) {
        $_SESSION['error'] = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    }

    if (empty($name)) {
        $_SESSION['error'] = 'กรุณากรอกชื่อ';
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    }

    if (empty($lastname)) {
        $_SESSION['error'] = 'กรุณากรอกนามสกุล';
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT admin_username FROM admin WHERE admin_ID = ?");
        $stmt->execute([$admin_id]);
        $current_username = $stmt->fetchColumn();

        if ($username !== $current_username) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM admin WHERE admin_username = ? AND admin_ID != ?");
            $stmt->execute([$username, $admin_id]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = 'ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว';
                session_write_close();
                header("Location: manage_admin.php");
                exit();
            }
        }

        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin SET admin_username = ?, admin_password = ?, admin_name = ?, admin_lastname = ? WHERE admin_ID = ?");
            $stmt->execute([$username, $hashed_password, $name, $lastname, $admin_id]);
        } else {
            $stmt = $conn->prepare("UPDATE admin SET admin_username = ?, admin_name = ?, admin_lastname = ? WHERE admin_ID = ?");
            $stmt->execute([$username, $name, $lastname, $admin_id]);
        }

        $_SESSION['success'] = 'แก้ไข Admin สำเร็จ!';
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    }
}

// ลบ Admin
if (isset($_POST['delete_admin'])) {
    $admin_id = $_POST['admin_ID'];
    // ดีบัก: ตรวจสอบค่า admin_ID
    echo "<!-- Debug: admin_ID = " . htmlspecialchars($admin_id) . " -->";
    if (empty($admin_id)) {
        $_SESSION['error'] = 'ไม่พบ ID ของ Admin ที่ต้องการลบ';
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    }
    try {
        $stmt = $conn->prepare("DELETE FROM admin WHERE admin_ID = ?");
        $stmt->execute([$admin_id]);
        $_SESSION['success'] = 'ลบ Admin สำเร็จ!';
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        session_write_close();
        header("Location: manage_admin.php");
        exit();
    }
}

// ดึงข้อมูล Admin ทั้งหมด
try {
    $stmt = $conn->prepare("SELECT * FROM admin ORDER BY created_at DESC");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<!-- ดึงข้อมูลได้ " . count($admins) . " แถว -->";
    echo "<!-- ข้อมูลดิบ: " . print_r($admins, true) . " -->";
} catch (PDOException $e) {
    $admins = [];
    $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    echo "<!-- ข้อผิดพลาด SQL: " . $e->getMessage() . " -->";
}

// เก็บข้อความแจ้งเตือนไว้ในตัวแปรเพื่อใช้ใน JavaScript
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : null;
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : null;
// ล้าง Session หลังจากเก็บข้อความแล้ว
if (isset($_SESSION['success'])) unset($_SESSION['success']);
if (isset($_SESSION['error'])) unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการ Admin - ระบบจัดการการขนส่ง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(to bottom, #e0f7fa, #ffffff);
            min-height: 100vh;
            margin: 0;
        }
        .navbar {
            background-color: #003087;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        .navbar-brand img {
            height: 50px;
        }
        .container {
            padding: 30px;
        }
        .header-title {
            font-size: 2.5rem;
            color: #2c3e50;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background: #fff;
            padding: 20px;
            margin-bottom: 25px;
        }
        .card h3 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .btn-primary {
            background: #007bff;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-warning, .btn-danger {
            border-radius: 8px;
            padding: 8px 15px;
            color: #fff;
        }
        .btn-warning {
            background: #f39c12;
        }
        .btn-warning:hover {
            background: #d35400;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .table thead th {
            background: #003087;
            color: #fff;
            text-align: center;
        }
        .table td {
            text-align: center;
            vertical-align: middle;
        }
        .modal-content {
            border-radius: 15px;
        }
        .modal-header {
            background: #007bff;
            color: #fff;
        }
        @media (max-width: 768px) {
            .header-title {
                font-size: 1.8rem;
            }
            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><img src="../Logo/logo_index.png" alt="โลโก้ระบบจัดการการขนส่ง"></a>
            <div class="ms-auto">
                <a href="logout.php" class="btn btn-light">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="header-title">จัดการ Admin</h1>

        <!-- ส่วนจัดการ Admin -->
        <div class="card">
            <h3>รายการ Admin</h3>
            <div class="text-end mb-3">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    <i class="fas fa-plus me-2"></i>เพิ่ม Admin
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>ชื่อ</th>
                            <th>นามสกุล</th>
                            <th>วันที่สร้าง</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($admins) && is_array($admins)): ?>
                            <?php foreach ($admins as $index => $admin): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($admin['admin_username'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($admin['admin_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($admin['admin_lastname'] ?? ''); ?></td>
                                    <td>
                                        <?php
                                        if (isset($admin['created_at']) && !empty($admin['created_at'])) {
                                            echo date('d/m/Y H:i', strtotime($admin['created_at']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm edit-btn me-2"
                                            data-id="<?php echo htmlspecialchars($admin['admin_ID'] ?? ''); ?>"
                                            data-username="<?php echo htmlspecialchars($admin['admin_username'] ?? ''); ?>"
                                            data-name="<?php echo htmlspecialchars($admin['admin_name'] ?? ''); ?>"
                                            data-lastname="<?php echo htmlspecialchars($admin['admin_lastname'] ?? ''); ?>">
                                            <i class="fas fa-edit"></i> แก้ไข
                                        </button>
                                        <button class="btn btn-danger btn-sm delete-btn"
                                            data-id="<?php echo htmlspecialchars($admin['admin_ID'] ?? ''); ?>">
                                            <i class="fas fa-trash-alt"></i> ลบ
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">ไม่มีข้อมูล Admin</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่ม Admin -->
    <div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAdminModalLabel">เพิ่ม Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="add_admin" value="1">
                        <div class="mb-3">
                            <label for="add_admin_username" class="form-label">ชื่อผู้ใช้:</label>
                            <input type="text" class="form-control" id="add_admin_username" name="admin_username" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_admin_password" class="form-label">รหัสผ่าน:</label>
                            <input type="password" class="form-control" id="add_admin_password" name="admin_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_admin_name" class="form-label">ชื่อ:</label>
                            <input type="text" class="form-control" id="add_admin_name" name="admin_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_admin_lastname" class="form-label">นามสกุล:</label>
                            <input type="text" class="form-control" id="add_admin_lastname" name="admin_lastname" required>
                        </div>
                        <button type="submit" class="btn btn-primary">เพิ่ม Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไข Admin -->
    <div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAdminModalLabel">แก้ไข Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="edit_admin" value="1">
                        <input type="hidden" id="edit_admin_id" name="admin_ID">
                        <div class="mb-3">
                            <label for="edit_admin_username" class="form-label">ชื่อผู้ใช้:</label>
                            <input type="text" class="form-control" id="edit_admin_username" name="admin_username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_admin_password" class="form-label">รหัสผ่านใหม่ (เว้นว่างหากไม่ต้องการเปลี่ยน):</label>
                            <input type="password" class="form-control" id="edit_admin_password" name="admin_password">
                        </div>
                        <div class="mb-3">
                            <label for="edit_admin_name" class="form-label">ชื่อ:</label>
                            <input type="text" class="form-control" id="edit_admin_name" name="admin_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_admin_lastname" class="form-label">นามสกุล:</label>
                            <input type="text" class="form-control" id="edit_admin_lastname" name="admin_lastname" required>
                        </div>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- โหลด JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        // ตรวจสอบว่า SweetAlert2 โหลดสำเร็จหรือไม่
        if (typeof Swal === 'undefined') {
            console.error('SweetAlert2 ไม่โหลดสำเร็จ');
        } else {
            console.log('SweetAlert2 โหลดสำเร็จ');

            // แสดง SweetAlert2 สำหรับ success
            <?php if ($success_message): ?>
                console.log('SESSION success: <?php echo addslashes($success_message); ?>');
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: '<?php echo addslashes($success_message); ?>',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#28a745'
                });
            <?php endif; ?>

            // แสดง SweetAlert2 สำหรับ error
            <?php if ($error_message): ?>
                console.log('SESSION error: <?php echo addslashes($error_message); ?>');
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาด',
                    text: '<?php echo addslashes($error_message); ?>',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#dc3545'
                });
            <?php endif; ?>
        }

        // ตั้งค่า Modal แก้ไข Admin
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const username = this.getAttribute('data-username');
                const name = this.getAttribute('data-name');
                const lastname = this.getAttribute('data-lastname');

                document.getElementById('edit_admin_id').value = id;
                document.getElementById('edit_admin_username').value = username;
                document.getElementById('edit_admin_name').value = name;
                document.getElementById('edit_admin_lastname').value = lastname;

                const modal = new bootstrap.Modal(document.getElementById('editAdminModal'));
                modal.show();
            });
        });

        // ยืนยันการลบ Admin
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                console.log('Delete button clicked, admin_ID:', id); // ดีบัก
                if (!id) {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        text: 'ไม่พบ ID ของ Admin ที่ต้องการลบ',
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#dc3545'
                    });
                    return;
                }
                Swal.fire({
                    title: 'ยืนยันการลบ?',
                    text: 'คุณต้องการลบ Admin นี้หรือไม่?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.style.display = 'none';
                        form.innerHTML = `
                            <input type="hidden" name="delete_admin" value="1">
                            <input type="hidden" name="admin_ID" value="${id}">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>