<?php
include 'config/condb.php';
session_start();

// ดึงข้อมูลคณะทั้งหมด
try {
    $stmt = $conn->prepare("SELECT * FROM faculties ORDER BY faculty_name");
    $stmt->execute();
    $faculties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลคณะ: " . $e->getMessage();
    $faculties = [];
}

// ดึงข้อมูลสาขาทั้งหมดพร้อมชื่อคณะ
try {
    $stmt = $conn->prepare("SELECT m.*, f.faculty_name 
                           FROM majors m 
                           JOIN faculties f ON m.faculty_id = f.faculty_id 
                           ORDER BY m.major_name");
    $stmt->execute();
    $majors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลสาขา: " . $e->getMessage();
    $majors = [];
}

// จัดการเพิ่มคณะ
if (isset($_POST['add_faculty'])) {
    $faculty_name = trim($_POST['faculty_name']);
    try {
        $stmt = $conn->prepare("INSERT INTO faculties (faculty_name) VALUES (:faculty_name)");
        $stmt->bindParam(':faculty_name', $faculty_name, PDO::PARAM_STR);
        $stmt->execute();
        $_SESSION['success'] = "เพิ่มคณะเรียบร้อยแล้ว";
        header("Location: manage_faculties_majors.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มคณะ: " . $e->getMessage();
    }
}

// จัดการแก้ไขคณะ
if (isset($_POST['edit_faculty'])) {
    $faculty_id = $_POST['faculty_id'];
    $faculty_name = trim($_POST['faculty_name']);
    try {
        $stmt = $conn->prepare("UPDATE faculties SET faculty_name = :faculty_name WHERE faculty_id = :faculty_id");
        $stmt->bindParam(':faculty_name', $faculty_name, PDO::PARAM_STR);
        $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['success'] = "แก้ไขคณะเรียบร้อยแล้ว";
        header("Location: manage_faculties_majors.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการแก้ไขคณะ: " . $e->getMessage();
    }
}

// จัดการลบคณะ
if (isset($_GET['delete_faculty_id'])) {
    $faculty_id = $_GET['delete_faculty_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM faculties WHERE faculty_id = :faculty_id");
        $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['success'] = "ลบคณะเรียบร้อยแล้ว";
        header("Location: manage_faculties_majors.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบคณะ: " . $e->getMessage();
    }
}

// จัดการเพิ่มสาขา
if (isset($_POST['add_major'])) {
    $major_name = trim($_POST['major_name']);
    $faculty_id = $_POST['faculty_id'];
    try {
        $stmt = $conn->prepare("INSERT INTO majors (major_name, faculty_id) VALUES (:major_name, :faculty_id)");
        $stmt->bindParam(':major_name', $major_name, PDO::PARAM_STR);
        $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['success'] = "เพิ่มสาขาเรียบร้อยแล้ว";
        header("Location: manage_faculties_majors.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มสาขา: " . $e->getMessage();
    }
}

// จัดการแก้ไขสาขา
if (isset($_POST['edit_major'])) {
    $major_id = $_POST['major_id'];
    $major_name = trim($_POST['major_name']);
    $faculty_id = $_POST['faculty_id'];
    try {
        $stmt = $conn->prepare("UPDATE majors SET major_name = :major_name, faculty_id = :faculty_id WHERE major_id = :major_id");
        $stmt->bindParam(':major_name', $major_name, PDO::PARAM_STR);
        $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
        $stmt->bindParam(':major_id', $major_id, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['success'] = "แก้ไขสาขาเรียบร้อยแล้ว";
        header("Location: manage_faculties_majors.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการแก้ไขสาขา: " . $e->getMessage();
    }
}

// จัดการลบสาขา
if (isset($_GET['delete_major_id'])) {
    $major_id = $_GET['delete_major_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM majors WHERE major_id = :major_id");
        $stmt->bindParam(':major_id', $major_id, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['success'] = "ลบสาขาเรียบร้อยแล้ว";
        header("Location: manage_faculties_majors.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบสาขา: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการคณะและสาขา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        .btn-warning {
            background: #f39c12;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            color: #fff;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            width: 80px;
            text-align: center;
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
            font-size: 0.9rem;
            width: 80px;
            text-align: center;
        }
        .btn-danger:hover {
            background: #c0392b;
            transform: scale(1.05);
        }
        .action-buttons-column {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
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
            .action-buttons-column {
                flex-direction: column;
                gap: 5px;
            }
            .btn-warning, .btn-danger {
                width: 100%;
            }
            .header-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="header-title">ระบบจัดการคณะและสาขา</h2>

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

            <!-- ส่วนที่ 1: จัดการคณะ -->
            <div class="card mb-4">
                <h3 class="mb-3">จัดการคณะ</h3>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                        <i class="fas fa-plus me-2"></i>เพิ่มคณะ
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ลำดับ</th>
                                <th>ชื่อคณะ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($faculties)) { ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">ไม่พบข้อมูลคณะ</td>
                                </tr>
                            <?php } else { ?>
                                <?php $index = 1; foreach ($faculties as $faculty) { ?>
                                    <tr>
                                        <td><?php echo $index++; ?></td>
                                        <td><?php echo htmlspecialchars($faculty['faculty_name']); ?></td>
                                        <td>
                                            <div class="action-buttons-column">
                                                <button type="button" class="btn btn-warning btn-sm edit-faculty-btn"
                                                    data-bs-toggle="modal" data-bs-target="#editFacultyModal"
                                                    data-faculty-id="<?php echo $faculty['faculty_id']; ?>"
                                                    data-faculty-name="<?php echo htmlspecialchars($faculty['faculty_name']); ?>">
                                                    <i class="fas fa-edit"></i> แก้ไข
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm delete-faculty-btn"
                                                    data-faculty-id="<?php echo $faculty['faculty_id']; ?>">
                                                    <i class="fas fa-trash-alt"></i> ลบ
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ส่วนที่ 2: จัดการสาขา -->
            <div class="card mb-4">
                <h3 class="mb-3">จัดการสาขา</h3>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMajorModal">
                        <i class="fas fa-plus me-2"></i>เพิ่มสาขา
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ลำดับ</th>
                                <th>ชื่อสาขา</th>
                                <th>คณะ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($majors)) { ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">ไม่พบข้อมูลสาขา</td>
                                </tr>
                            <?php } else { ?>
                                <?php $index = 1; foreach ($majors as $major) { ?>
                                    <tr>
                                        <td><?php echo $index++; ?></td>
                                        <td><?php echo htmlspecialchars($major['major_name']); ?></td>
                                        <td><?php echo htmlspecialchars($major['faculty_name']); ?></td>
                                        <td>
                                            <div class="action-buttons-column">
                                                <button type="button" class="btn btn-warning btn-sm edit-major-btn"
                                                    data-bs-toggle="modal" data-bs-target="#editMajorModal"
                                                    data-major-id="<?php echo $major['major_id']; ?>"
                                                    data-major-name="<?php echo htmlspecialchars($major['major_name']); ?>"
                                                    data-faculty-id="<?php echo $major['faculty_id']; ?>">
                                                    <i class="fas fa-edit"></i> แก้ไข
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm delete-major-btn"
                                                    data-major-id="<?php echo $major['major_id']; ?>">
                                                    <i class="fas fa-trash-alt"></i> ลบ
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มคณะ -->
    <div class="modal fade" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addFacultyModalLabel">เพิ่มคณะ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addFacultyForm" method="POST" action="">
                        <div class="mb-3">
                            <label for="faculty_name" class="form-label">ชื่อคณะ:</label>
                            <input type="text" id="faculty_name" name="faculty_name" class="form-control" required>
                        </div>
                        <input type="hidden" name="add_faculty" value="1">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>เพิ่มคณะ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขคณะ -->
    <div class="modal fade" id="editFacultyModal" tabindex="-1" aria-labelledby="editFacultyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editFacultyModalLabel">แก้ไขคณะ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editFacultyForm" method="POST" action="">
                        <input type="hidden" id="edit_faculty_id" name="faculty_id">
                        <div class="mb-3">
                            <label for="edit_faculty_name" class="form-label">ชื่อคณะ:</label>
                            <input type="text" id="edit_faculty_name" name="faculty_name" class="form-control" required>
                        </div>
                        <input type="hidden" name="edit_faculty" value="1">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>บันทึก</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มสาขา -->
    <div class="modal fade" id="addMajorModal" tabindex="-1" aria-labelledby="addMajorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMajorModalLabel">เพิ่มสาขา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addMajorForm" method="POST" action="">
                        <div class="mb-3">
                            <label for="major_name" class="form-label">ชื่อสาขา:</label>
                            <input type="text" id="major_name" name="major_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="major_faculty" class="form-label">คณะ:</label>
                            <select id="major_faculty" name="faculty_id" class="form-select" required>
                                <option value="">-- เลือกคณะ --</option>
                                <?php foreach ($faculties as $faculty) { ?>
                                    <option value="<?php echo $faculty['faculty_id']; ?>">
                                        <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <input type="hidden" name="add_major" value="1">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>เพิ่มสาขา</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขสาขา -->
    <div class="modal fade" id="editMajorModal" tabindex="-1" aria-labelledby="editMajorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMajorModalLabel">แก้ไขสาขา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editMajorForm" method="POST" action="">
                        <input type="hidden" id="edit_major_id" name="major_id">
                        <div class="mb-3">
                            <label for="edit_major_name" class="form-label">ชื่อสาขา:</label>
                            <input type="text" id="edit_major_name" name="major_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_major_faculty" class="form-label">คณะ:</label>
                            <select id="edit_major_faculty" name="faculty_id" class="form-select" required>
                                <option value="">-- เลือกคณะ --</option>
                                <?php foreach ($faculties as $faculty) { ?>
                                    <option value="<?php echo $faculty['faculty_id']; ?>">
                                        <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <input type="hidden" name="edit_major" value="1">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>บันทึก</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

            // ตั้งค่า Modal แก้ไขคณะ
            $('.edit-faculty-btn').on('click', function() {
                var facultyId = $(this).data('faculty-id');
                var facultyName = $(this).data('faculty-name');

                $('#edit_faculty_id').val(facultyId);
                $('#edit_faculty_name').val(facultyName);
            });

            // ตั้งค่า Modal แก้ไขสาขา
            $('.edit-major-btn').on('click', function() {
                var majorId = $(this).data('major-id');
                var majorName = $(this).data('major-name');
                var facultyId = $(this).data('faculty-id');

                $('#edit_major_id').val(majorId);
                $('#edit_major_name').val(majorName);
                $('#edit_major_faculty').val(facultyId);
            });

            // SweetAlert สำหรับยืนยันการลบคณะ
            $('.delete-faculty-btn').on('click', function() {
                var facultyId = $(this).data('faculty-id');
                Swal.fire({
                    title: 'คุณแน่ใจหรือไม่?',
                    text: 'การลบคณะจะลบสาขาที่เกี่ยวข้องทั้งหมดด้วย! การกระทำนี้ไม่สามารถย้อนกลับได้!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'manage_faculties_majors.php?delete_faculty_id=' + facultyId;
                    }
                });
            });

            // SweetAlert สำหรับยืนยันการลบสาขา
            $('.delete-major-btn').on('click', function() {
                var majorId = $(this).data('major-id');
                Swal.fire({
                    title: 'คุณแน่ใจหรือไม่?',
                    text: 'คุณต้องการลบสาขานี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'manage_faculties_majors.php?delete_major_id=' + majorId;
                    }
                });
            });

            // ตรวจสอบฟอร์มเพิ่มคณะ
            $('#addFacultyForm').on('submit', function(e) {
                e.preventDefault();
                let errors = [];

                const facultyName = $('#faculty_name').val().trim();

                if (!facultyName) {
                    errors.push('กรุณากรอกชื่อคณะ');
                }

                if (errors.length > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        html: errors.join('<br>'),
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }

                Swal.fire({
                    title: 'ยืนยันการเพิ่มคณะ?',
                    text: 'คุณต้องการเพิ่มคณะนี้หรือไม่?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });

            // ตรวจสอบฟอร์มแก้ไขคณะ
            $('#editFacultyForm').on('submit', function(e) {
                e.preventDefault();
                let errors = [];

                const facultyName = $('#edit_faculty_name').val().trim();

                if (!facultyName) {
                    errors.push('กรุณากรอกชื่อคณะ');
                }

                if (errors.length > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        html: errors.join('<br>'),
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }

                Swal.fire({
                    title: 'ยืนยันการแก้ไขคณะ?',
                    text: 'คุณต้องการบันทึกการแก้ไขคณะนี้หรือไม่?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });

            // ตรวจสอบฟอร์มเพิ่มสาขา
            $('#addMajorForm').on('submit', function(e) {
                e.preventDefault();
                let errors = [];

                const majorName = $('#major_name').val().trim();
                const facultyId = $('#major_faculty').val();

                if (!majorName) {
                    errors.push('กรุณากรอกชื่อสาขา');
                }
                if (!facultyId) {
                    errors.push('กรุณาเลือกคณะ');
                }

                if (errors.length > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        html: errors.join('<br>'),
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }

                Swal.fire({
                    title: 'ยืนยันการเพิ่มสาขา?',
                    text: 'คุณต้องการเพิ่มสาขานี้หรือไม่?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });

            // ตรวจสอบฟอร์มแก้ไขสาขา
            $('#editMajorForm').on('submit', function(e) {
                e.preventDefault();
                let errors = [];

                const majorName = $('#edit_major_name').val().trim();
                const facultyId = $('#edit_major_faculty').val();

                if (!majorName) {
                    errors.push('กรุณากรอกชื่อสาขา');
                }
                if (!facultyId) {
                    errors.push('กรุณาเลือกคณะ');
                }

                if (errors.length > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        html: errors.join('<br>'),
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }

                Swal.fire({
                    title: 'ยืนยันการแก้ไขสาขา?',
                    text: 'คุณต้องการบันทึกการแก้ไขสาขานี้หรือไม่?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>