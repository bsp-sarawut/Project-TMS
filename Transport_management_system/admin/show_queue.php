<?php
session_start();
require_once 'config/condb.php';

// เปิดการแสดงข้อผิดพลาดเพื่อ debug (สามารถปิดได้เมื่อใช้งานจริง)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ดึงข้อมูลตัวเลือกสำหรับ dropdown จังหวัด
$provinces = $conn->query("SELECT DISTINCT PROVINCE_ID, PROVINCE_NAME FROM province ORDER BY PROVINCE_NAME")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการคิวรถ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(to bottom, #f5f6f5, #e9ecef);
            min-height: 100vh;
            display: flex;
            margin: 0;
            overflow-x: hidden;
        }
        .sidebar {
            width: 250px;
            transition: transform 0.3s ease-in-out;
            position: fixed;
            height: 100%;
            overflow-y: auto;
            color: #fff;
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
        /* ปุ่มทั้งหมด */
        .btn {
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #007bff;
            border: none;
        }
        .btn-primary:hover {
            background: #0056b3;
            transform: scale(1.05);
        }
        .btn-success {
            background: #28a745;
            border: none;
        }
        .btn-success:hover {
            background: #218838;
            transform: scale(1.05);
        }
        .btn-danger {
            background: #e74c3c;
            border: none;
        }
        .btn-danger:hover {
            background: #c0392b;
            transform: scale(1.05);
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: scale(1.05);
        }
        .btn-info {
            background: #17a2b8;
            border: none;
        }
        .btn-info:hover {
            background: #138496;
            transform: scale(1.05);
        }
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            width: 100%;
            min-width: 710px; /* ปรับให้แคบลง */
        }
        .table thead th {
            background: #003087;
            color: #fff;
            text-align: center;
            padding: 15px 20px;
            font-weight: 500;
            font-size: 0.95rem;
            white-space: nowrap;
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
            padding: 12px 20px;
            font-size: 0.9rem;
            word-break: break-word;
        }
        .table th:nth-child(1), .table td:nth-child(1) { /* รหัสคิว */
            min-width: 60px; /* ลดความกว้าง */
        }
        .table th:nth-child(2), .table td:nth-child(2) { /* วันที่ */
            min-width: 100px;
        }
        .table th:nth-child(3), .table td:nth-child(3) { /* จุดขึ้นรถ */
            min-width: 120px;
        }
        .table th:nth-child(4), .table td:nth-child(4) { /* ยานพาหนะ */
            min-width: 150px;
        }
        .table th:nth-child(5), .table td:nth-child(5) { /* สถานะรถ */
            min-width: 80px; /* ลดความกว้าง */
        }
        .table th:nth-child(6), .table td:nth-child(6) { /* รายชื่อนักเรียน */
            min-width: 120px;
        }
        .table th:nth-child(7), .table td:nth-child(7) { /* จัดการ */
            min-width: 80px; /* ลดความกว้าง */
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
        .modal-dialog {
            max-width: 600px;
            margin: 1.75rem auto;
        }
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            background: #007bff;
            color: #fff;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 15px;
        }
        .modal-title {
            font-weight: 600;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            border-top: none;
            padding: 10px 20px;
        }
        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .student-table th, .student-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            font-size: 0.9rem;
        }
        .student-table th {
            background: #e9ecef;
            font-weight: 500;
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
        .badge.btn-secondary {
            background: #dc3545; /* สีแดงสำหรับสถานะปิดงาน */
            color: #fff;
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
            .table-container {
                margin-left: -15px;
                margin-right: -15px;
            }
            .table thead th {
                padding: 10px 15px;
                font-size: 0.85rem;
            }
            .table td {
                padding: 8px 15px;
                font-size: 0.85rem;
            }
            .modal-dialog {
                max-width: 90%;
                margin: 1rem auto;
            }
            .modal-content {
                max-height: 90vh;
            }
            .btn {
                padding: 6px 12px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="header-title">ระบบจัดการคิวรถ</h2>

            <!-- แสดงข้อความแจ้งเตือน -->
            <?php if (isset($_SESSION['delete_queue_success']) && $_SESSION['delete_queue_success']) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: 'คิวถูกลบออกเรียบร้อยแล้ว',
                            confirmButtonText: 'ตกลง',
                            allowOutsideClick: false,
                            focusConfirm: false
                        });
                    });
                </script>
                <?php unset($_SESSION['delete_queue_success']); ?>
            <?php } elseif (isset($_SESSION['delete_queue_error'])) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'ข้อผิดพลาด',
                            text: '<?php echo $_SESSION['delete_queue_error']; ?>',
                            confirmButtonText: 'ตกลง',
                            allowOutsideClick: false,
                            focusConfirm: false
                        });
                    });
                </script>
                <?php unset($_SESSION['delete_queue_error']); ?>
            <?php } elseif (isset($_SESSION['delete_student_success']) && $_SESSION['delete_student_success']) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: 'นักเรียนถูกลบออกจากคิวเรียบร้อยแล้ว',
                            confirmButtonText: 'ตกลง',
                            allowOutsideClick: false,
                            focusConfirm: false
                        });
                    });
                </script>
                <?php unset($_SESSION['delete_student_success']); ?>
            <?php } elseif (isset($_SESSION['delete_student_error'])) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'ข้อผิดพลาด',
                            text: '<?php echo $_SESSION['delete_student_error']; ?>',
                            confirmButtonText: 'ตกลง',
                            allowOutsideClick: false,
                            focusConfirm: false
                        });
                    });
                </script>
                <?php unset($_SESSION['delete_student_error']); ?>
            <?php } elseif (isset($_SESSION['add_success']) && $_SESSION['add_success']) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: 'นักเรียนถูกเพิ่มเข้าในคิวเรียบร้อยแล้ว',
                            confirmButtonText: 'ตกลง',
                            allowOutsideClick: false,
                            focusConfirm: false
                        });
                    });
                </script>
                <?php unset($_SESSION['add_success']); ?>
            <?php } elseif (isset($_SESSION['add_error'])) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'ข้อผิดพลาด',
                            text: '<?php echo $_SESSION['add_error']; ?>',
                            confirmButtonText: 'ตกลง',
                            allowOutsideClick: false,
                            focusConfirm: false
                        });
                    });
                </script>
                <?php unset($_SESSION['add_error']); ?>
            <?php } elseif (isset($_SESSION['edit_success']) && $_SESSION['edit_success']) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: 'ข้อมูลนักเรียนถูกแก้ไขเรียบร้อยแล้ว',
                            confirmButtonText: 'ตกลง',
                            allowOutsideClick: false,
                            focusConfirm: false
                        });
                    });
                </script>
                <?php unset($_SESSION['edit_success']); ?>
            <?php } elseif (isset($_SESSION['edit_error'])) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'ข้อผิดพลาด',
                            text: '<?php echo $_SESSION['edit_error']; ?>',
                            confirmButtonText: 'ตกลง',
                            allowOutsideClick: false,
                            focusConfirm: false
                        });
                    });
                </script>
                <?php unset($_SESSION['edit_error']); ?>
            <?php } ?>

            <!-- ส่วนที่ 1: ฟอร์มค้นหา -->
            <div class="card mb-4 search-section">
                <h3 class="mb-3">ค้นหาข้อมูลคิวรถ</h3>
                <div class="search-container">
                    <label for="search_input" class="form-label">ค้นหาคิวรถ</label>
                    <div class="search-input-group">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" id="search_input" class="form-control" placeholder="ค้นหา: รถ, ชื่อนักเรียน">
                    </div>
                </div>
                <div class="filter-row">
                    <div class="col-md-3 col-12">
                        <label for="queue_date_filter" class="form-label">วันที่</label>
                        <input type="text" id="queue_date_filter" class="form-control" placeholder="-- แสดงทั้งหมด --">
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="province_filter" class="form-label">จังหวัด</label>
                        <select id="province_filter" class="form-select" onchange="loadAmphur()">
                            <option value="">-- แสดงทั้งหมด --</option>
                            <?php foreach ($provinces as $province): ?>
                                <option value="<?php echo $province['PROVINCE_ID']; ?>">
                                    <?php echo htmlspecialchars($province['PROVINCE_NAME']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="amphur_filter" class="form-label">อำเภอ</label>
                        <select id="amphur_filter" class="form-select" onchange="loadLocation()">
                            <option value="">-- แสดงทั้งหมด --</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="location_filter" class="form-label">จุดขึ้นรถ</label>
                        <select id="location_filter" class="form-select">
                            <option value="">-- แสดงทั้งหมด --</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="status_filter" class="form-label">สถานะรถ</label>
                        <select id="status_filter" class="form-select">
                            <option value="">-- แสดงทั้งหมด --</option>
                            <option value="ว่าง">ว่าง</option>
                            <option value="ไม่ว่าง">ไม่ว่าง</option>
                            <option value="ปิดงาน">ปิดงาน</option>
                        </select>
                    </div>
                </div>
                <div class="action-buttons">
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-undo me-2"></i>เคลียร์ฟิลเตอร์
                    </button>
                </div>
            </div>

            <!-- ส่วนที่ 2: รายการคิวรถ -->
            <div class="card mb-4">
                <h3 class="mb-3">ข้อมูลคิวรถทั้งหมด</h3>
                <div class="total-count">จำนวนคิวรถทั้งหมด: <span id="totalItems">0</span> คิว</div>
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>รหัสคิว</th>
                                <th>วันที่</th>
                                <th>จุดขึ้นรถ</th>
                                <th>ข้อมูลรถ</th>
                                <th>สถานะรถ</th>
                                <th>รายชื่อนักเรียน</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="queueTable">
                            <tr><td colspan="7" class="text-center text-muted">กำลังโหลดข้อมูล...</td></tr>
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

    <!-- Modal รายชื่อนักเรียน -->
    <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalLabel">รายชื่อนักเรียนในคิว</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="current_queue_id">
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th>ไอดี</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody">
                            <!-- โหลดข้อมูลนักเรียนด้วย AJAX -->
                        </tbody>
                    </table>
                    <button class="btn btn-success btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#addStudentModal" 
                            onclick="setAddStudentFromModal()">
                        <i class="bi bi-plus-circle me-1"></i> เพิ่มนักเรียน
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มนักเรียน -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="queue_add_student.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addStudentModalLabel">เพิ่มนักเรียน</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="queue_id" id="add_queue_id">
                        <div class="mb-3">
                            <label for="student_id" class="form-label">เลือกนักเรียน</label>
                            <select name="student_id" id="student_id" class="form-select" required>
                                <option value="">-- เลือกนักเรียน --</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="add_student" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขนักเรียน -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="queue_edit_student.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editStudentModalLabel">แก้ไขนักเรียน</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="queue_id" id="edit_queue_id">
                        <input type="hidden" name="old_student_id" id="old_student_id">
                        <div class="mb-3">
                            <label for="current_student" class="form-label">นักเรียนปัจจุบัน</label>
                            <input type="text" id="current_student" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="new_student_id" class="form-label">เลือกนักเรียนใหม่</label>
                            <select name="new_student_id" id="new_student_id" class="form-select" required>
                                <option value="">-- เลือกนักเรียน --</option>
                                <?php $all_students = $conn->query("SELECT stu_ID, stu_name, stu_lastname FROM students ORDER BY stu_name")->fetchAll(PDO::FETCH_ASSOC); ?>
                                <?php foreach ($all_students as $student): ?>
                                    <option value="<?= $student['stu_ID'] ?>"><?= $student['stu_name'] . ' ' . $student['stu_lastname'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="edit_student" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
    <script>
        let searchTimeout;
        let lastScrollPosition = 0;
        let currentPage = 1;

        // ฟังก์ชันเก็บตำแหน่ง scroll
        function saveScrollPosition() {
            lastScrollPosition = window.scrollY || window.pageYOffset;
        }

        // ฟังก์ชันเลื่อนกลับไปยังตำแหน่งเดิม
        function restoreScrollPosition() {
            window.scrollTo({
                top: lastScrollPosition,
                behavior: 'instant'
            });
        }

        // โหลดข้อมูลคิวรถ
        function loadQueues(page = 1) {
            currentPage = page;
            saveScrollPosition();

            const search = $('#search_input').val();
            const queue_date = $('#queue_date_filter').val();
            const province = $('#province_filter').val();
            const amphur = $('#amphur_filter').val();
            const location = $('#location_filter').val();
            const status = $('#status_filter').val();

            $.ajax({
                url: 'fetch_queues.php',
                type: 'POST',
                data: {
                    search: search,
                    queue_date: queue_date,
                    province_id: province,
                    amphur_id: amphur,
                    location: location,
                    status: status,
                    page: page
                },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: data.error,
                            confirmButtonText: 'ตกลง',
                            allowOutsideClick: false,
                            focusConfirm: false,
                            willClose: () => {
                                restoreScrollPosition();
                            }
                        });
                        $('#queueTable').html('<tr><td colspan="7" class="text-center text-muted">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
                        $('#totalItems').text(0);
                        $('#pagination').html('');
                        restoreScrollPosition();
                        return;
                    }

                    const queues = data.queues;
                    const totalRows = data.totalRows;
                    const totalPages = data.totalPages;
                    let html = '';

                    if (queues.length > 0) {
                        queues.forEach(queue => {
                            let statusClass;
                            if (queue.status_car === 'ปิดงาน') {
                                statusClass = 'btn-secondary';
                            } else if (queue.status_car === 'ว่าง') {
                                statusClass = 'btn-success';
                            } else {
                                statusClass = 'btn-warning';
                            }
                            html += `
                                <tr>
                                    <td>${queue.queue_id}</td>
                                    <td>${queue.queue_date ? new Date(queue.queue_date).toLocaleDateString('th-TH', { day: '2-digit', month: '2-digit', year: 'numeric' }) : 'ไม่ระบุ'}</td>
                                    <td>${queue.location || '-'}</td>
                                    <td>${queue.car_license} - ${queue.car_brand}</td>
                                    <td><span class="badge ${statusClass}">${queue.status_car}</span></td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm view-students-btn" 
                                                data-queue-id="${queue.queue_id}">
                                            <i class="bi bi-eye"></i> ดู
                                        </button>
                                    </td>
                                    <td>
                                        <form method="POST" action="queue_delete.php" id="deleteQueueForm_${queue.queue_id}" style="display:inline;">
                                            <input type="hidden" name="queue_id" value="${queue.queue_id}">
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="confirmDeleteQueue(${queue.queue_id})">
                                                <i class="bi bi-trash"></i> ลบ
                                            </button>
                                        </form>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        html = '<tr><td colspan="7" class="text-center text-muted">ไม่มีข้อมูลคิวรถ</td></tr>';
                    }

                    $('#queueTable').html(html);
                    $('#totalItems').text(totalRows);

                    // สร้าง pagination
                    let paginationHtml = '';
                    paginationHtml += `
                        <li class="page-item ${page <= 1 ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="loadQueues(${page - 1})" aria-label="Previous">
                                <span aria-hidden="true">«</span>
                            </a>
                        </li>`;
                    for (let i = 1; i <= totalPages; i++) {
                        paginationHtml += `
                            <li class="page-item ${page == i ? 'active' : ''}">
                                <a class="page-link" href="javascript:void(0)" onclick="loadQueues(${i})">${i}</a>
                            </li>`;
                    }
                    paginationHtml += `
                        <li class="page-item ${page >= totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="loadQueues(${page + 1})" aria-label="Next">
                                <span aria-hidden="true">»</span>
                            </a>
                        </li>`;
                    $('#pagination').html(paginationHtml);

                    setTimeout(() => {
                        restoreScrollPosition();
                    }, 100);
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถโหลดข้อมูลได้: ' + error,
                        confirmButtonText: 'ตกลง',
                        allowOutsideClick: false,
                        focusConfirm: false,
                        willClose: () => {
                            restoreScrollPosition();
                        }
                    });
                    $('#queueTable').html('<tr><td colspan="7" class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</td></tr>');
                    $('#totalItems').text(0);
                    $('#pagination').html('');
                    restoreScrollPosition();
                }
            });
        }

        // ฟังก์ชันเคลียร์ฟิลเตอร์
        function clearFilters() {
            saveScrollPosition();
            $('#search_input').val('');
            $('#queue_date_filter').val('');
            $('#province_filter').val('');
            $('#amphur_filter').val('');
            $('#location_filter').val('');
            $('#status_filter').val('');
            loadAmphur();
            loadLocation();
            loadQueues(1);
        }

        // โหลดข้อมูลอำเภอ
        function loadAmphur() {
            const provinceID = $('#province_filter').val();
            if (provinceID) {
                $.ajax({
                    url: 'get_amphur.php',
                    type: 'POST',
                    data: { province_id: provinceID },
                    success: function(data) {
                        $('#amphur_filter').html(data);
                        loadLocation();
                    }
                });
            } else {
                $('#amphur_filter').html('<option value="">-- แสดงทั้งหมด --</option>');
                $('#location_filter').html('<option value="">-- แสดงทั้งหมด --</option>');
            }
        }

        // โหลดข้อมูลจุดขึ้นรถ
        function loadLocation() {
            const provinceID = $('#province_filter').val();
            const amphurID = $('#amphur_filter').val();
            if (provinceID && amphurID) {
                $.ajax({
                    url: 'get_location.php',
                    type: 'GET',
                    data: { province_id: provinceID, hur_id: amphurID },
                    success: function(data) {
                        $('#location_filter').html(data);
                    }
                });
            } else {
                $('#location_filter').html('<option value="">-- แสดงทั้งหมด --</option>');
            }
        }

        // แสดงรายชื่อนักเรียนใน Modal
        $(document).on('click', '.view-students-btn', function() {
            saveScrollPosition();
            const queueId = $(this).data('queue-id');
            document.getElementById('current_queue_id').value = queueId;

            $.ajax({
                url: 'get_students.php',
                type: 'GET',
                data: { queue_id: queueId },
                success: function(data) {
                    let rows = '';
                    const students = $(data).filter('tr');
                    if (students.length > 0) {
                        students.each(function() {
                            const studentId = $(this).find('td:eq(0)').text().trim();
                            const studentName = $(this).find('td:eq(1)').text().trim();
                            rows += `
                                <tr>
                                    <td>${studentId}</td>
                                    <td>${studentName}</td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm me-2" 
                                                onclick="openEditStudentModal('${queueId}', '${studentId}', '${studentName}')">
                                            <i class="bi bi-pencil"></i> แก้ไข
                                        </button>
                                        <form method="POST" action="queue_delete_student.php" style="display:inline;">
                                            <input type="hidden" name="queue_id" value="${queueId}">
                                            <input type="hidden" name="student_id" value="${studentId}">
                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบนักเรียนนี้ออกจากคิว?')">
                                                <i class="bi bi-trash"></i> ลบ
                                            </button>
                                        </form>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        rows = '<tr><td colspan="3" class="text-center text-muted">ไม่มีนักเรียนในคิวนี้</td></tr>';
                    }
                    $('#studentTableBody').html(rows);
                    $('#studentModal').modal('show');
                    restoreScrollPosition();
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        text: 'ไม่สามารถโหลดรายชื่อนักเรียนได้',
                        confirmButtonText: 'ตกลง',
                        allowOutsideClick: false,
                        focusConfirm: false,
                        willClose: () => {
                            restoreScrollPosition();
                        }
                    });
                    restoreScrollPosition();
                }
            });
        });

        // ฟังก์ชันตั้งค่า Modal เพิ่มนักเรียน
        function setAddStudentFromModal() {
            const queueId = document.getElementById('current_queue_id').value;
            document.getElementById('add_queue_id').value = queueId;

            $.ajax({
                url: 'get_available_students.php',
                type: 'GET',
                data: { queue_id: queueId },
                success: function(data) {
                    $('#student_id').html('<option value="">-- เลือกนักเรียน --</option>' + data);
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        text: 'ไม่สามารถโหลดรายชื่อนักเรียนได้',
                        confirmButtonText: 'ตกลง',
                        allowOutsideClick: false,
                        focusConfirm: false
                    });
                }
            });
        }

        // ฟังก์ชันเปิด Modal แก้ไขนักเรียน
        function openEditStudentModal(queueId, studentId, studentName) {
            document.getElementById('edit_queue_id').value = queueId;
            document.getElementById('old_student_id').value = studentId;
            document.getElementById('current_student').value = studentName;
            $('#editStudentModal').modal('show');
        }

        // ฟังก์ชันยืนยันการลบคิว
        function confirmDeleteQueue(queueId) {
            Swal.fire({
                title: 'คุณแน่ใจหรือไม่?',
                text: 'การลบคิวนี้จะลบรายชื่อนักเรียนทั้งหมดในคิวด้วย',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`deleteQueueForm_${queueId}`).submit();
                }
            });
        }

        // เริ่มต้นหน้า
        $(document).ready(function() {
            // Sidebar Toggle with localStorage
            const sidebarState = localStorage.getItem('sidebarState');
            if (sidebarState === 'closed') {
                $('.sidebar').addClass('closed');
                $('.content').addClass('closed');
                $('#open-btn').show();
            }

            $('#close-btn').on('click', function() {
                $('.sidebar').addClass('closed');
                $('.content').addClass('closed');
                $('#open-btn').show();
                localStorage.setItem('sidebarState', 'closed');
            });

            $('#open-btn').on('click', function() {
                $('.sidebar').removeClass('closed');
                $('.content').removeClass('closed');
                $('#open-btn').hide();
                localStorage.setItem('sidebarState', 'open');
            });

            // ตั้งค่า Flatpickr
            flatpickr("#queue_date_filter", {
                dateFormat: "Y-m-d",
                locale: "th",
                onClose: function() {
                    loadQueues(1);
                }
            });

            // โหลดข้อมูลเริ่มต้น
            loadQueues();

            // ค้นหาแบบเรียลไทม์เมื่อพิมพ์
            $('#search_input').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadQueues(1);
                }, 300);
            });

            // ค้นหาแบบเรียลไทม์เมื่อเปลี่ยนตัวกรอง
            $('#province_filter, #amphur_filter, #location_filter, #status_filter').on('change', function() {
                loadQueues(1);
            });
        });
    </script>
</body>
</html>