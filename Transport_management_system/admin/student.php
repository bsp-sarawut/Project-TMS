<?php
include 'config/condb.php';
session_start();

// กำหนด $current_page ให้เป็น 'student.php'
$current_page = 'student.php';

// ดึงข้อมูลคณะ
try {
    $stmt = $conn->prepare("SELECT faculty_id, faculty_name FROM faculties ORDER BY faculty_name");
    $stmt->execute();
    $faculties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลคณะ: " . $e->getMessage();
    $faculties = [];
}

// ดึงข้อมูลสาขา
try {
    $stmt = $conn->prepare("SELECT major_id, major_name, faculty_id FROM majors ORDER BY major_name");
    $stmt->execute();
    $majors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลสาขา: " . $e->getMessage();
    $majors = [];
}

// กำหนดตัวเลือกสำหรับชั้นปี
$current_year = date("Y") + 543; // ปีพ.ศ.
$years = [];
for ($i = $current_year - 4; $i <= $current_year; $i++) {
    $years[] = $i;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการข้อมูลนักศึกษา</title>
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
        .student-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        .student-image:hover {
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
        .nav-link.active {
    background-color: #007bff; /* สีพื้นหลังเมื่อ active */
    color: #fff !important; /* สีตัวอักษรเมื่อ active */
    border-radius: 5px;
}

.nav-link.active i {
    color: #fff !important; /* สีไอคอนเมื่อ active */
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
            .student-image {
                width: 60px;
                height: 60px;
            }
            .image-preview {
                width: 120px;
                height: 120px;
            }
            .action-buttons-column {
                flex-direction: column;
                gap: 5px;
            }
            .btn-warning, .btn-danger {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="header-title">ระบบจัดการข้อมูลนักศึกษา</h2>

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

            <!-- ส่วนที่ 1: ฟอร์มค้นหานักศึกษา -->
            <div class="card mb-4 search-section">
                <h3 class="mb-3">ตัวกรองข้อมูลนักศึกษา</h3>
                <div class="search-container">
                    <label for="search_input" class="form-label">ค้นหานักศึกษา</label>
                    <div class="search-input-group">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" id="search_input" class="form-control" placeholder="ค้นหา: ชื่อ, นามสกุล, คณะ, สาขา">
                    </div>
                </div>
                <div class="filter-row">
                    <div class="col-md-3 col-12">
                        <label for="faculty_filter" class="form-label">คณะ</label>
                        <select name="faculty_filter" id="faculty_filter" class="form-select" onchange="updateMajorFilter()">
                            <option value="">-- แสดงทั้งหมด --</option>
                            <?php foreach ($faculties as $faculty) { ?>
                                <option value="<?php echo htmlspecialchars($faculty['faculty_name']); ?>">
                                    <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="major_filter" class="form-label">สาขา</label>
                        <select name="major_filter" id="major_filter" class="form-select">
                            <option value="">-- แสดงทั้งหมด --</option>
                        </select>
                    </div>
                </div>
                <div class="action-buttons">
                    <!-- <button type="button" class="btn btn-primary" onclick="loadStudents()"><i class="fas fa-search me-2"></i>ค้นหา</button> -->
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()"><i class="fas fa-undo me-2"></i>เคลียร์ฟิลเตอร์</button>
                </div>
            </div>

            <!-- ส่วนที่ 2: รายชื่อนักศึกษา -->
            <div class="card mb-4">
                <h3 class="mb-3">ข้อมูลนักศึกษาทั้งหมด</h3>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-plus me-2"></i>เพิ่มนักศึกษา
                    </button>
                </div>
                <div class="total-count">จำนวนนักศึกษาทั้งหมด: <span id="totalRows">0</span> คน</div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ลำดับ</th>
                                <th>ชื่อผู้ใช้</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>เบอร์โทร</th>
                                <th>คณะ</th>
                                <th>สาขา</th>
                                <th>รูปภาพ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody">
                            <tr><td colspan="8" class="text-center text-muted">กำลังโหลดข้อมูล...</td></tr>
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

    <!-- Modal เพิ่มนักศึกษา -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">เพิ่มข้อมูลนักศึกษา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addStudentForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="stu_username" class="form-label">ชื่อผู้ใช้:</label>
                            <input type="text" id="stu_username" name="stu_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="stu_password" class="form-label">รหัสผ่าน:</label>
                            <input type="password" id="stu_password" name="stu_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="stu_year" class="form-label">ปีการศึกษา:</label>
                            <select id="stu_year" name="stu_year" class="form-select" required>
                                <option value="">-- เลือกปีการศึกษา --</option>
                                <?php foreach ($years as $year) { ?>
                                    <option value="<?php echo htmlspecialchars($year); ?>">
                                        <?php echo htmlspecialchars($year); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="stu_license" class="form-label">เลขประจำตัวนักศึกษา:</label>
                            <input type="text" id="stu_license" name="stu_license" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="stu_name" class="form-label">ชื่อ:</label>
                            <input type="text" id="stu_name" name="stu_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="stu_lastname" class="form-label">นามสกุล:</label>
                            <input type="text" id="stu_lastname" name="stu_lastname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="stu_tel" class="form-label">เบอร์โทรศัพท์:</label>
                            <input type="text" id="stu_tel" name="stu_tel" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="stu_faculty" class="form-label">คณะ:</label>
                            <select id="stu_faculty" name="stu_faculty" class="form-select" onchange="updateAddMajors()" required>
                                <option value="">-- เลือกคณะ --</option>
                                <?php foreach ($faculties as $faculty) { ?>
                                    <option value="<?php echo htmlspecialchars($faculty['faculty_name']); ?>">
                                        <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="stu_major" class="form-label">สาขา:</label>
                            <select id="stu_major" name="stu_major" class="form-select" required>
                                <option value="">-- เลือกสาขา --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="stu_img" class="form-label">รูปภาพ:</label>
                            <input type="file" id="stu_img" name="stu_img" class="form-control" accept="image/*" onchange="previewImage(this, 'preview_add')">
                            <div class="image-preview-container">
                                <img id="preview_add" class="image-preview" alt="ตัวอย่างรูปภาพ">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>เพิ่มนักศึกษา</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขข้อมูลนักศึกษา -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">แก้ไขข้อมูลนักศึกษา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editStudentForm" enctype="multipart/form-data">
                        <input type="hidden" id="edit_stu_id" name="stu_id">
                        <div class="mb-3">
                            <label for="edit_stu_username" class="form-label">ชื่อผู้ใช้:</label>
                            <input type="text" id="edit_stu_username" name="stu_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_stu_password" class="form-label">รหัสผ่านใหม่ (เว้นว่างหากไม่ต้องการเปลี่ยน):</label>
                            <input type="password" id="edit_stu_password" name="stu_password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="edit_stu_year" class="form-label">ปีการศึกษา:</label>
                            <select id="edit_stu_year" name="stu_year" class="form-select" required>
                                <option value="">-- เลือกปีการศึกษา --</option>
                                <?php foreach ($years as $year) { ?>
                                    <option value="<?php echo htmlspecialchars($year); ?>">
                                        <?php echo htmlspecialchars($year); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_stu_license" class="form-label">เลขประจำตัวนักศึกษา:</label>
                            <input type="text" id="edit_stu_license" name="stu_license" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_stu_name" class="form-label">ชื่อ:</label>
                            <input type="text" id="edit_stu_name" name="stu_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_stu_lastname" class="form-label">นามสกุล:</label>
                            <input type="text" id="edit_stu_lastname" name="stu_lastname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_stu_tel" class="form-label">เบอร์โทรศัพท์:</label>
                            <input type="text" id="edit_stu_tel" name="stu_tel" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_stu_faculty" class="form-label">คณะ:</label>
                            <select id="edit_stu_faculty" name="stu_faculty" class="form-select" onchange="updateEditMajors()" required>
                                <option value="">-- เลือกคณะ --</option>
                                <?php foreach ($faculties as $faculty) { ?>
                                    <option value="<?php echo htmlspecialchars($faculty['faculty_name']); ?>">
                                        <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_stu_major" class="form-label">สาขา:</label>
                            <select id="edit_stu_major" name="stu_major" class="form-select" required>
                                <option value="">-- เลือกสาขา --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_stu_img" class="form-label">รูปภาพ:</label>
                            <input type="file" id="edit_stu_img" name="stu_img" class="form-control" accept="image/*" onchange="previewImage(this, 'preview_edit')">
                            <div class="image-preview-container">
                                <img id="preview_edit" class="image-preview" alt="ตัวอย่างรูปภาพ">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>แก้ไขข้อมูลนักศึกษา</button>
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
                    <h5 class="modal-title" id="imageModalLabel">รูปภาพนักศึกษา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" class="modal-image" alt="รูปภาพนักศึกษา">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let searchTimeout;
        let currentPage = 1;

        // โหลดข้อมูลนักศึกษา
        function loadStudents(page = 1) {
            currentPage = page;
            const search = $('#search_input').val();
            const faculty_filter = $('#faculty_filter').val();
            const major_filter = $('#major_filter').val();

            $.ajax({
                url: 'fetch_students.php',
                type: 'POST',
                data: {
                    search: search,
                    faculty_filter: faculty_filter,
                    major_filter: major_filter,
                    page: page
                },
                dataType: 'json',
                success: function(data) {
                    console.log('Fetched data:', data); // ดีบักข้อมูลที่ได้รับ
                    if (data.error) {
                        Swal.fire('เกิดข้อผิดพลาด', data.error, 'error');
                        $('#studentTableBody').html('<tr><td colspan="8" class="text-center text-muted">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
                        $('#totalRows').text(0);
                        $('#pagination').html('');
                        return;
                    }

                    const students = data.students;
                    const totalRows = data.totalRows;
                    const totalPages = data.totalPages;
                    let index = (page - 1) * 10 + 1;
                    let html = '';

                    if (students.length > 0) {
                        students.forEach(student => {
                            console.log('Student ID:', student.stu_ID); // ดีบัก stu_ID
                            const imageSrc = student.stu_img ? `../Student/uploads/${student.stu_img}` : 'images/default_student.jpg';
                            html += `
                                <tr>
                                    <td>${index++}</td>
                                    <td>${student.stu_username || 'ไม่ระบุ'}</td>
                                    <td>${student.stu_name} ${student.stu_lastname}</td>
                                    <td>${student.stu_tel || 'ไม่ระบุ'}</td>
                                    <td>${student.stu_faculty || 'ไม่ระบุ'}</td>
                                    <td>${student.stu_major || 'ไม่ระบุ'}</td>
                                    <td>
                                        ${student.stu_img && student.stu_img !== '' ? 
                                            `<img src="${imageSrc}" class="student-image" alt="รูปภาพนักศึกษา" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="${imageSrc}">` : 
                                            'ไม่มีรูปภาพ'}
                                    </td>
                                    <td>
                                        <div class="action-buttons-column">
                                            <button type="button" class="btn btn-warning btn-sm edit-btn"
                                                data-stu_id="${student.stu_ID}"
                                                data-stu_username="${student.stu_username}"
                                                data-stu_year="${student.stu_year}"
                                                data-stu_license="${student.stu_license}"
                                                data-stu_name="${student.stu_name}"
                                                data-stu_lastname="${student.stu_lastname}"
                                                data-stu_tel="${student.stu_tel}"
                                                data-stu_faculty="${student.stu_faculty}"
                                                data-stu_major="${student.stu_major}"
                                                data-stu_img="${imageSrc}"><i class="fas fa-edit"></i> แก้ไข</button>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn"
                                                data-stu_id="${student.stu_ID}"><i class="fas fa-trash-alt"></i> ลบ</button>
                                        </div>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        html = '<tr><td colspan="8" class="text-center text-muted">ไม่พบข้อมูลนักศึกษา</td></tr>';
                    }

                    $('#studentTableBody').html(html);
                    $('#totalRows').text(totalRows);

                    // สร้าง pagination
                    let paginationHtml = '';
                    paginationHtml += `
                        <li class="page-item ${page <= 1 ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="loadStudents(${page - 1})" aria-label="Previous">
                                <span aria-hidden="true">«</span>
                            </a>
                        </li>`;
                    for (let i = 1; i <= totalPages; i++) {
                        paginationHtml += `
                            <li class="page-item ${page == i ? 'active' : ''}">
                                <a class="page-link" href="javascript:void(0)" onclick="loadStudents(${i})">${i}</a>
                            </li>`;
                    }
                    paginationHtml += `
                        <li class="page-item ${page >= totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="loadStudents(${page + 1})" aria-label="Next">
                                <span aria-hidden="true">»</span>
                            </a>
                        </li>`;
                    $('#pagination').html(paginationHtml);
                },
                error: function(xhr, status, error) {
                    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้: ' + error, 'error');
                    $('#studentTableBody').html('<tr><td colspan="8" class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</td></tr>');
                    $('#totalRows').text(0);
                    $('#pagination').html('');
                }
            });
        }

        // ล้างฟิลเตอร์
        function clearFilters() {
            $('#search_input').val('');
            $('#faculty_filter').val('');
            $('#major_filter').val('');
            updateMajorFilter();
            loadStudents(1);
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

        // อัปเดตตัวเลือกสาขาในตัวกรอง
        function updateMajorFilter() {
            const facultySelect = document.getElementById("faculty_filter");
            const majorSelect = document.getElementById("major_filter");
            const selectedFaculty = facultySelect.value;

            majorSelect.innerHTML = '<option value="">-- แสดงทั้งหมด --</option>';

            if (selectedFaculty) {
                $.ajax({
                    url: 'fetch_majors.php',
                    type: 'POST',
                    data: { faculty_name: selectedFaculty },
                    dataType: 'json',
                    success: function(data) {
                        if (data.majors && data.majors.length > 0) {
                            data.majors.forEach(major => {
                                const option = document.createElement("option");
                                option.value = major.major_name;
                                option.textContent = major.major_name;
                                majorSelect.appendChild(option);
                            });
                        }
                    },
                    error: function() {
                        console.error('ไม่สามารถดึงข้อมูลสาขาได้');
                    }
                });
            }
        }

        // ฟังก์ชันสำหรับอัปเดตสาขาใน modal เพิ่มข้อมูล
        function updateAddMajors() {
            const facultySelect = document.getElementById("stu_faculty");
            const majorSelect = document.getElementById("stu_major");
            const selectedFaculty = facultySelect.value;

            majorSelect.innerHTML = '<option value="">-- เลือกสาขา --</option>';

            if (selectedFaculty) {
                $.ajax({
                    url: 'fetch_majors.php',
                    type: 'POST',
                    data: { faculty_name: selectedFaculty },
                    dataType: 'json',
                    success: function(data) {
                        if (data.majors && data.majors.length > 0) {
                            data.majors.forEach(major => {
                                const option = document.createElement("option");
                                option.value = major.major_name;
                                option.textContent = major.major_name;
                                majorSelect.appendChild(option);
                            });
                        }
                    },
                    error: function() {
                        console.error('ไม่สามารถดึงข้อมูลสาขาได้');
                    }
                });
            }
        }

        // ฟังก์ชันสำหรับอัปเดตสาขาใน modal แก้ไขข้อมูล
        function updateEditMajors(selectedMajor = null) {
            const facultySelect = document.getElementById("edit_stu_faculty");
            const majorSelect = document.getElementById("edit_stu_major");
            const selectedFaculty = facultySelect.value;

            majorSelect.innerHTML = '<option value="">-- เลือกสาขา --</option>';

            if (selectedFaculty) {
                $.ajax({
                    url: 'fetch_majors.php',
                    type: 'POST',
                    data: { faculty_name: selectedFaculty },
                    dataType: 'json',
                    success: function(data) {
                        if (data.majors && data.majors.length > 0) {
                            data.majors.forEach(major => {
                                const option = document.createElement("option");
                                option.value = major.major_name;
                                option.textContent = major.major_name;
                                majorSelect.appendChild(option);
                            });
                            if (selectedMajor) {
                                majorSelect.value = selectedMajor;
                            }
                        }
                    },
                    error: function() {
                        console.error('ไม่สามารถดึงข้อมูลสาขาได้');
                    }
                });
            }
        }

        // แสดงรูปภาพใน Modal
        $(document).on('click', '.student-image', function() {
            const imageSrc = $(this).data('image');
            $('#modalImage').attr('src', imageSrc);
        });

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
            loadStudents();
            updateMajorFilter();

            // ค้นหาแบบเรียลไทม์
            $('#search_input').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadStudents(1);
                }, 300);
            });

            // เปลี่ยนตัวกรอง
            $('#faculty_filter, #major_filter').on('change', function() {
                loadStudents(1);
            });

            // เพิ่มนักศึกษา
            $('#addStudentForm').on('submit', function(e) {
                e.preventDefault();
                let errors = [];

                const stuUsername = $('#stu_username').val().trim();
                const stuPassword = $('#stu_password').val().trim();
                const stuYear = $('#stu_year').val();
                const stuLicense = $('#stu_license').val().trim();
                const stuName = $('#stu_name').val().trim();
                const stuLastname = $('#stu_lastname').val().trim();
                const stuTel = $('#stu_tel').val().trim();
                const stuFaculty = $('#stu_faculty').val();
                const stuMajor = $('#stu_major').val();
                const stuImg = $('#stu_img')[0].files[0];

                // ตรวจสอบข้อมูล
                if (!stuUsername) errors.push('กรุณากรอกชื่อผู้ใช้');
                if (!stuPassword) {
                    errors.push('กรุณากรอกรหัสผ่าน');
                } else if (stuPassword.length < 6) {
                    errors.push('รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
                }
                if (!stuYear) errors.push('กรุณาเลือกปีการศึกษา');
                if (!stuLicense) errors.push('กรุณากรอกเลขประจำตัวนักศึกษา');
                if (!stuName) errors.push('กรุณากรอกชื่อ');
                if (!stuLastname) errors.push('กรุณากรอกนามสกุล');
                if (!stuTel) {
                    errors.push('กรุณากรอกเบอร์โทรศัพท์');
                } else if (!/^[0-9]{10}$/.test(stuTel)) {
                    errors.push('เบอร์โทรต้องเป็นตัวเลข 10 หลัก');
                }
                if (!stuFaculty) errors.push('กรุณาเลือกคณะ');
                if (!stuMajor) errors.push('กรุณาเลือกสาขา');
                if (stuImg) {
                    const fileExt = stuImg.name.split('.').pop().toLowerCase();
                    const allowedExts = ['jpg', 'jpeg', 'png'];
                    if (!allowedExts.includes(fileExt)) {
                        errors.push('ไฟล์รูปภาพต้องเป็น JPG หรือ PNG เท่านั้น');
                    }
                    if (stuImg.size > 2 * 1024 * 1024) {
                        errors.push('ไฟล์รูปภาพต้องมีขนาดไม่เกิน 2MB');
                    }
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
                    title: 'ยืนยันการเพิ่มนักศึกษา?',
                    text: 'คุณต้องการเพิ่มข้อมูลนักศึกษานี้หรือไม่?',
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
                            url: 'student_insert.php',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === "success") {
                                    Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                                        $('#addStudentModal').modal('hide');
                                        $('#addStudentForm')[0].reset();
                                        $('#preview_add').hide();
                                        loadStudents();
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

            // แก้ไขนักศึกษา
            $(document).on('click', '.edit-btn', function() {
                const stuId = $(this).data('stu_id');
                const stuUsername = $(this).data('stu_username');
                const stuYear = $(this).data('stu_year');
                const stuLicense = $(this).data('stu_license');
                const stuName = $(this).data('stu_name');
                const stuLastname = $(this).data('stu_lastname');
                const stuTel = $(this).data('stu_tel');
                const stuFaculty = $(this).data('stu_faculty');
                const stuMajor = $(this).data('stu_major');
                const stuImg = $(this).data('stu_img');

                console.log('Edit stu_id:', stuId); // ดีบักค่า stu_id

                $('#edit_stu_id').val(stuId);
                $('#edit_stu_username').val(stuUsername);
                $('#edit_stu_password').val('');
                $('#edit_stu_year').val(stuYear);
                $('#edit_stu_license').val(stuLicense);
                $('#edit_stu_name').val(stuName);
                $('#edit_stu_lastname').val(stuLastname);
                $('#edit_stu_tel').val(stuTel);
                $('#edit_stu_faculty').val(stuFaculty);

                if (stuImg && stuImg !== 'images/default_student.jpg') {
                    $('#preview_edit').attr('src', stuImg).show();
                } else {
                    $('#preview_edit').hide();
                }

                updateEditMajors(stuMajor);
                $('#editStudentModal').modal('show');
            });

            $('#editStudentForm').on('submit', function(e) {
                e.preventDefault();
                let errors = [];

                const stuUsername = $('#edit_stu_username').val().trim();
                const stuPassword = $('#edit_stu_password').val().trim();
                const stuYear = $('#edit_stu_year').val();
                const stuLicense = $('#edit_stu_license').val().trim();
                const stuName = $('#edit_stu_name').val().trim();
                const stuLastname = $('#edit_stu_lastname').val().trim();
                const stuTel = $('#edit_stu_tel').val().trim();
                const stuFaculty = $('#edit_stu_faculty').val();
                const stuMajor = $('#edit_stu_major').val();
                const stuImg = $('#edit_stu_img')[0].files[0];

                // ตรวจสอบข้อมูล
                if (!stuUsername) errors.push('กรุณากรอกชื่อผู้ใช้');
                if (stuPassword && stuPassword.length < 6) {
                    errors.push('รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
                }
                if (!stuYear) errors.push('กรุณาเลือกปีการศึกษา');
                if (!stuLicense) errors.push('กรุณากรอกเลขประจำตัวนักศึกษา');
                if (!stuName) errors.push('กรุณากรอกชื่อ');
                if (!stuLastname) errors.push('กรุณากรอกนามสกุล');
                if (!stuTel) {
                    errors.push('กรุณากรอกเบอร์โทรศัพท์');
                } else if (!/^[0-9]{10}$/.test(stuTel)) {
                    errors.push('เบอร์โทรต้องเป็นตัวเลข 10 หลัก');
                }
                if (!stuFaculty) errors.push('กรุณาเลือกคณะ');
                if (!stuMajor) errors.push('กรุณาเลือกสาขา');
                if (stuImg) {
                    const fileExt = stuImg.name.split('.').pop().toLowerCase();
                    const allowedExts = ['jpg', 'jpeg', 'png'];
                    if (!allowedExts.includes(fileExt)) {
                        errors.push('ไฟล์รูปภาพต้องเป็น JPG หรือ PNG เท่านั้น');
                    }
                    if (stuImg.size > 2 * 1024 * 1024) {
                        errors.push('ไฟล์รูปภาพต้องมีขนาดไม่เกิน 2MB');
                    }
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
                    title: 'ยืนยันการแก้ไข?',
                    text: 'คุณต้องการบันทึกการแก้ไขข้อมูลนักศึกษานี้หรือไม่?',
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
                            url: 'student_update.php',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === "success") {
                                    Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                                        $('#editStudentModal').modal('hide');
                                        loadStudents();
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

            // ลบนักศึกษา
            $(document).on('click', '.delete-btn', function() {
                const stuId = $(this).data('stu_id');
                console.log('Delete stu_id:', stuId); // ดีบักค่า stu_id

                Swal.fire({
                    title: 'ยืนยันการลบ?',
                    text: 'คุณต้องการลบข้อมูลนักศึกษานี้หรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ลบ',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'student_delete.php',
                            type: 'POST',
                            data: { stu_id: stuId },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === "success") {
                                    Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                                        loadStudents();
                                    });
                                } else {
                                    Swal.fire('เกิดข้อผิดพลาด!', response.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถลบข้อมูลได้: ' + (xhr.responseJSON?.message || xhr.responseText), 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>