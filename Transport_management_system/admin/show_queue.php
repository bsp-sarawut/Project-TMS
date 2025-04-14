<?php
session_start();
require_once 'config/condb.php';

// เปิดการแสดงข้อผิดพลาดเพื่อ debug (สามารถปิดได้เมื่อใช้งานจริง)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ดึงข้อมูลตัวเลือกสำหรับ dropdown จังหวัด
$provinces = $conn->query("SELECT DISTINCT PROVINCE_ID, PROVINCE_NAME FROM province ORDER BY PROVINCE_NAME")->fetchAll(PDO::FETCH_ASSOC);

// รับค่าจาก filter
$where = [];
$params = [];
if (!empty($_GET['queue_date'])) {
    $where[] = "q.queue_date = ?";
    $params[] = $_GET['queue_date'];
}
if (!empty($_GET['province_id'])) {
    $where[] = "q.province_id = ?";
    $params[] = $_GET['province_id'];
}
if (!empty($_GET['amphur_id'])) {
    $where[] = "q.amphur_id = ?";
    $params[] = $_GET['amphur_id'];
}
if (!empty($_GET['location'])) {
    $where[] = "q.location = ?";
    $params[] = $_GET['location'];
}
if (!empty($_GET['search'])) {
    $where[] = "(c.car_license LIKE ? OR s.stu_name LIKE ? OR s.stu_lastname LIKE ?)";
    $search_term = "%" . $_GET['search'] . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// สร้าง SQL Query
$sql = "
    SELECT 
        q.queue_id, 
        p.PROVINCE_NAME AS province_name, 
        a.AMPHUR_NAME AS amphur_name, 
        q.location, 
        c.car_license, 
        c.car_brand, 
        q.created_at, 
        q.year, 
        q.status_car, 
        q.queue_date
    FROM queue q
    LEFT JOIN province p ON q.province_id = p.PROVINCE_ID
    LEFT JOIN amphur a ON q.amphur_id = a.AMPHUR_ID
    LEFT JOIN car c ON q.car_id = c.car_id
    LEFT JOIN queue_student qs ON q.queue_id = qs.queue_id
    LEFT JOIN students s ON qs.student_id = s.stu_ID
";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " GROUP BY q.queue_id ORDER BY q.queue_date DESC, q.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$queues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ลบคิว
if (isset($_POST['delete_queue'])) {
    $queue_id = $_POST['queue_id'];
    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("DELETE FROM queue_student WHERE queue_id = ?");
        $stmt->execute([$queue_id]);
        $stmt = $conn->prepare("DELETE FROM queue WHERE queue_id = ?");
        $stmt->execute([$queue_id]);
        $conn->commit();
        $_SESSION['delete_queue_success'] = true;
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['delete_queue_error'] = "เกิดข้อผิดพลาดในการลบคิว: " . $e->getMessage();
    }
    header("Location: show_queue.php?" . $_SERVER['QUERY_STRING']);
    exit;
}

// ลบนักเรียน
if (isset($_POST['delete_student'])) {
    $queue_id = $_POST['queue_id'];
    $student_id = $_POST['student_id'];
    $stmt = $conn->prepare("DELETE FROM queue_student WHERE queue_id = ? AND student_id = ?");
    if ($stmt->execute([$queue_id, $student_id])) {
        $_SESSION['delete_student_success'] = true;
    } else {
        $_SESSION['delete_student_error'] = "เกิดข้อผิดพลาดในการลบนักเรียน";
    }
    header("Location: show_queue.php?" . $_SERVER['QUERY_STRING']);
    exit;
}

// เพ黄เพิ่มนักเรียน
if (isset($_POST['add_student'])) {
    $queue_id = $_POST['queue_id'];
    $student_id = $_POST['student_id'];
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM queue_student WHERE queue_id = ? AND student_id = ?");
    $check_stmt->execute([$queue_id, $student_id]);
    if ($check_stmt->fetchColumn() == 0) {
        $stmt = $conn->prepare("INSERT INTO queue_student (queue_id, student_id) VALUES (?, ?)");
        if ($stmt->execute([$queue_id, $student_id])) {
            $_SESSION['add_success'] = true;
        } else {
            $_SESSION['add_error'] = "เกิดข้อผิดพลาดในการเพิ่มนักเรียน";
        }
    }
    header("Location: show_queue.php?" . $_SERVER['QUERY_STRING']);
    exit;
}

// แก้ไขนักเรียน
if (isset($_POST['edit_student'])) {
    $queue_id = $_POST['queue_id'];
    $old_student_id = $_POST['old_student_id'];
    $new_student_id = $_POST['new_student_id'];
    $stmt = $conn->prepare("UPDATE queue_student SET student_id = ? WHERE queue_id = ? AND student_id = ?");
    if ($stmt->execute([$new_student_id, $queue_id, $old_student_id])) {
        $_SESSION['edit_success'] = true;
    } else {
        $_SESSION['edit_error'] = "เกิดข้อผิดพลาดในการแก้ไขนักเรียน";
    }
    header("Location: show_queue.php?" . $_SERVER['QUERY_STRING']);
    exit;
}

// ดึงรายชื่อนักเรียนทั้งหมดสำหรับ dropdown (กรองนักเรียนที่ยังไม่อยู่ในคิว)
function getAvailableStudents($conn, $queue_id) {
    $stmt = $conn->prepare("
        SELECT s.stu_ID, s.stu_name, s.stu_lastname 
        FROM students s
        WHERE s.stu_ID NOT IN (
            SELECT student_id FROM queue_student WHERE queue_id = ?
        )
        ORDER BY s.stu_name
    ");
    $stmt->execute([$queue_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการคิวรถ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.0/dist/sweetalert2.all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { 
            background: #f5f6f5; 
            font-family: 'Kanit', sans-serif; 
            min-height: 100vh; 
            display: flex;
        }
        .sidebar { 
            width: 250px; 
            transition: transform 0.3s ease-in-out; 
        }
        .sidebar.closed { 
            transform: translateX(-250px); 
            overflow: hidden; 
        }
        .content { 
            margin-left: 250px; 
            padding: 20px; 
            flex-grow: 1; 
            transition: margin-left 0.3s ease-in-out; 
        }
        .content.closed { 
            margin-left: 0; 
        }
        .card { 
            border-radius: 10px; 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); 
            background: #fff; 
            padding: 15px; 
        }
        .card h3 { 
            color: #333; 
            font-weight: 600; 
            border-bottom: 1px solid #e0e0e0; 
            padding-bottom: 5px; 
        }
        .form-label { 
            font-weight: 500; 
            color: #444; 
        }
        .form-select, .form-control { 
            border-radius: 5px; 
            border: 1px solid #ccc; 
            padding: 8px; 
        }
        .form-select:focus, .form-control:focus { 
            border-color: #007bff; 
            box-shadow: 0 0 3px rgba(0, 123, 255, 0.3); 
        }
        .btn-primary { 
            border-radius: 8px; 
            padding: 8px 20px; 
            background: #007bff; 
            border: none; 
        }
        .btn-primary:hover { 
            background: #0056b3; 
        }
        .btn-secondary { 
            border-radius: 8px; 
            padding: 8px 20px; 
            background: #6c757d; 
            border: none; 
        }
        .btn-secondary:hover { 
            background: #5a6268; 
        }
        .table thead th { 
            background: #003087; 
            color: #fff; 
            text-align: center; 
        }
        .table tbody tr:hover { 
            background: #f9f9f9; 
        }
        .badge-success { 
            background: #28a745; 
        }
        .badge-warning { 
            background: #ffc107; 
        }
        .btn-toggle { 
            font-size: 0.9rem; 
            padding: 2px 8px; 
        }
        .student-table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        .student-table th, .student-table td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: center; 
        }
        .student-table th { 
            background: #e9ecef; 
        }
        .btn-sm { 
            margin: 2px; 
        }
        @media (max-width: 768px) { 
            .content { 
                margin-left: 250px; 
                padding: 15px; 
            }
            .sidebar { 
                position: fixed; 
                z-index: 1000; 
                height: 100%; 
            }
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="content" id="content">
    <div class="container mt-4">
        <h2 class="text-center mb-4" style="color: #333; font-weight: 600;">รายการคิวรถ</h2>

        <!-- Filter Dropdown และ Search -->
        <div class="card mb-4">
            <h3 class="mb-3">ตัวกรองข้อมูล</h3>
            <form method="GET" id="filterForm">
            <div class="col-md-12 col-12">
                        <label for="search" class="form-label">ค้นหา</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                               placeholder="ค้นหารถหรือชื่อนักเรียน"><br>
                    </div>
                <div class="row g-3">
                    <div class="col-md-3 col-12">
                        <label for="queue_date" class="form-label">วันที่</label>
                        <input type="text" id="queue_date" name="queue_date" class="form-control" 
                               value="<?= isset($_GET['queue_date']) ? htmlspecialchars($_GET['queue_date']) : '' ?>" 
                               placeholder="เลือกวันที่">
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="province_id" class="form-label">จังหวัด</label>
                        <select name="province_id" id="province_id" class="form-select" onchange="loadAmphur()">
                            <option value="">ทุกจังหวัด</option>
                            <?php foreach ($provinces as $province): ?>
                                <option value="<?= $province['PROVINCE_ID'] ?>" <?= isset($_GET['province_id']) && $_GET['province_id'] == $province['PROVINCE_ID'] ? 'selected' : '' ?>>
                                    <?= $province['PROVINCE_NAME'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="amphur_id" class="form-label">อำเภอ</label>
                        <select name="amphur_id" id="amphur_id" class="form-select" onchange="loadLocation()">
                            <option value="">ทุกอำเภอ</option>
                            <?php if (isset($_GET['province_id']) && !empty($_GET['province_id'])): ?>
                                <?php
                                $stmt = $conn->prepare("SELECT AMPHUR_ID, AMPHUR_NAME FROM amphur WHERE PROVINCE_ID = ? ORDER BY AMPHUR_NAME");
                                $stmt->execute([$_GET['province_id']]);
                                $amphurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($amphurs as $amphur): ?>
                                    <option value="<?= $amphur['AMPHUR_ID'] ?>" <?= isset($_GET['amphur_id']) && $_GET['amphur_id'] == $amphur['AMPHUR_ID'] ? 'selected' : '' ?>>
                                        <?= $amphur['AMPHUR_NAME'] ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="location" class="form-label">จุดขึ้นรถ</label>
                        <select name="location" id="location" class="form-select">
                            <option value="">ทุกจุดขึ้นรถ</option>
                            <?php if (isset($_GET['amphur_id']) && !empty($_GET['amphur_id']) && isset($_GET['province_id']) && !empty($_GET['province_id'])): ?>
                                <?php
                                $stmt = $conn->prepare("SELECT DISTINCT location FROM routes WHERE province = ? AND amphur = ? ORDER BY location");
                                $stmt->execute([$_GET['province_id'], $_GET['amphur_id']]);
                                $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['location'] ?>" <?= isset($_GET['location']) && $_GET['location'] == $loc['location'] ? 'selected' : '' ?>>
                                        <?= $loc['location'] ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary me-2">กรองข้อมูล</button>
                    <button type="button" class="btn btn-secondary" onclick="clearFilter()">เคลียร์ฟิลเตอร์</button>
                </div>
            </form>
        </div>

        <!-- ตารางแสดงผล -->
        <div class="card mb-4">
            <h3 class="mb-3">ข้อมูลคิวรถทั้งหมด</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>รหัสคิว</th>
                            <th>วันที่</th>
                            <th>จังหวัด</th>
                            <th>อำเภอ</th>
                            <th>จุดขึ้นรถ</th>
                            <th>ยานพาหนะ</th>
                            <th>สถานะรถ</th>
                            <th>รายชื่อนักเรียน</th>
                            <th>วันที่สร้าง</th>
                            <th>ปี</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($queues) > 0): ?>
                            <?php foreach ($queues as $index => $queue): ?>
                                <tr>
                                    <td><?= $queue['queue_id'] ?></td>
                                    <td><?= $queue['queue_date'] ? date('d/m/Y', strtotime($queue['queue_date'])) : 'ไม่ระบุ' ?></td>
                                    <td><?= $queue['province_name'] ?></td>
                                    <td><?= $queue['amphur_name'] ?></td>
                                    <td><?= $queue['location'] ?></td>
                                    <td><?= $queue['car_license'] . ' - ' . $queue['car_brand'] ?></td>
                                    <td><span class="badge <?= $queue['status_car'] === 'ว่าง' ? 'badge-success' : 'badge-warning' ?>"><?= $queue['status_car'] ?></span></td>
                                    <td>
                                        <button class="btn btn-primary btn-toggle" data-bs-toggle="modal" data-bs-target="#studentModal" 
                                                onclick="showStudents(<?= $queue['queue_id'] ?>)">ดูรายชื่อ</button>
                                    </td>
                                    <td><?= date('d/m/Y H:i:s', strtotime($queue['created_at'])) ?></td>
                                    <td><?= $queue['year'] ?: 'ไม่ระบุ' ?></td>
                                    <td>
                                        <form method="POST" id="deleteQueueForm_<?= $queue['queue_id'] ?>" style="display:inline;">
                                            <input type="hidden" name="queue_id" value="<?= $queue['queue_id'] ?>">
                                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteQueue(<?= $queue['queue_id'] ?>)">ลบคิว</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="11" class="text-center text-muted">ไม่พบข้อมูลคิวรถ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal รายชื่อนักเรียน -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
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
                            <th>รหัสนักเรียน</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        <!-- โหลดข้อมูลนักเรียนด้วย AJAX -->
                    </tbody>
                </table>
                <button class="btn btn-success btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#addStudentModal" 
                        onclick="setAddStudentFromModal()">เพิ่มนักเรียน</button>
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
            <form method="POST">
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
                            <!-- โหลดนักเรียนที่ยังไม่อยู่ในคิวนี้ผ่าน JavaScript -->
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
            <form method="POST">
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

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar Toggle with localStorage
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const closeBtn = document.getElementById('close-btn');
    const openBtn = document.getElementById('open-btn');

    // โหลดสถานะ Sidebar จาก localStorage
    window.addEventListener('load', () => {
        const sidebarState = localStorage.getItem('sidebarState');
        if (sidebarState === 'closed') {
            sidebar.classList.add('closed');
            content.classList.add('closed');
            openBtn.style.display = 'block';
        }
    });

    // ซ่อน Sidebar
    closeBtn.addEventListener('click', () => {
        sidebar.classList.add('closed');
        content.classList.add('closed');
        openBtn.style.display = 'block';
        localStorage.setItem('sidebarState', 'closed');
    });

    // เปิด Sidebar
    openBtn.addEventListener('click', () => {
        sidebar.classList.remove('closed');
        content.classList.remove('closed');
        openBtn.style.display = 'none';
        localStorage.setItem('sidebarState', 'open');
    });

    // ตั้งค่า Flatpickr สำหรับ filter วันที่
    flatpickr("#queue_date", {
        dateFormat: "Y-m-d",
        locale: "th"
    });

    // โหลดอำเภอตามจังหวัดที่เลือก
    function loadAmphur() {
        const provinceID = document.getElementById('province_id').value;
        const amphurSelect = document.getElementById('amphur_id');
        const locationSelect = document.getElementById('location');
        amphurSelect.innerHTML = '<option value="">ทุกอำเภอ</option>';
        locationSelect.innerHTML = '<option value="">ทุกจุดขึ้นรถ</option>';

        if (provinceID) {
            fetch('get_amphur.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'province_id=' + provinceID
            })
            .then(response => response.text())
            .then(data => {
                amphurSelect.innerHTML = '<option value="">ทุกอำเภอ</option>' + data;
            })
            .catch(error => console.error('Error:', error));
        }
    }

    // โหลดจุดขึ้นรถตามจังหวัดและอำเภอที่เลือก
    function loadLocation() {
        const provinceID = document.getElementById('province_id').value;
        const amphurID = document.getElementById('amphur_id').value;
        const locationSelect = document.getElementById('location');
        locationSelect.innerHTML = '<option value="">ทุกจุดขึ้นรถ</option>';

        if (provinceID && amphurID) {
            fetch(`get_location.php?province_id=${provinceID}&hur_id=${amphurID}`, {
                method: 'GET'
            })
            .then(response => response.text())
            .then(data => {
                locationSelect.innerHTML = data;
            })
            .catch(error => console.error('Error:', error));
        }
    }

    // เคลียร์ฟิลเตอร์
    function clearFilter() {
        document.getElementById('queue_date').value = '';
        document.getElementById('province_id').value = '';
        document.getElementById('amphur_id').innerHTML = '<option value="">ทุกอำเภอ</option>';
        document.getElementById('location').innerHTML = '<option value="">ทุกจุดขึ้นรถ</option>';
        document.getElementById('search').value = '';
        document.getElementById('filterForm').submit();
    }

    // ฟังก์ชันยืนยันการลบคิวด้วย SweetAlert
    function confirmDeleteQueue(queueId) {
        Swal.fire({
            title: 'ยืนยันการลบคิว?',
            text: "คุณต้องการลบคิวนี้หรือไม่ การดำเนินการนี้ไม่สามารถย้อนกลับได้!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteQueueForm_' + queueId).submit();
            }
        });
    }

    // ฟังก์ชันยืนยันการลบนักเรียนด้วย SweetAlert
    function confirmDeleteStudent(queueId, studentId) {
        Swal.fire({
            title: 'ยืนยันการลบนักเรียน?',
            text: "คุณต้องการลบนักเรียนนี้ออกจากคิวหรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteStudentForm_' + queueId + '_' + studentId).submit();
            }
        });
    }

    // โหลดอำเภอและจุดขึ้นรถเริ่มต้น + SweetAlert
    window.onload = function() {
        <?php if (isset($_GET['province_id']) && !empty($_GET['province_id'])): ?>
            loadAmphur();
            <?php if (isset($_GET['amphur_id']) && !empty($_GET['amphur_id'])): ?>
                setTimeout(loadLocation, 100);
            <?php endif; ?>
        <?php endif; ?>

        // ตรวจสอบ session เพื่อแสดง SweetAlert
        <?php
        // เพิ่มนักเรียน
        if (isset($_SESSION['add_success']) && $_SESSION['add_success']) {
            echo "Swal.fire({
                icon: 'success',
                title: 'เพิ่มนักเรียนสำเร็จ',
                text: 'นักเรียนถูกเพิ่มเข้าในคิวเรียบร้อยแล้ว',
                showConfirmButton: false,
                timer: 1500
            });";
            unset($_SESSION['add_success']);
        }
        if (isset($_SESSION['add_error'])) {
            echo "Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: '" . $_SESSION['add_error'] . "',
                showConfirmButton: true
            });";
            unset($_SESSION['add_error']);
        }

        // ลบคิว
        if (isset($_SESSION['delete_queue_success']) && $_SESSION['delete_queue_success']) {
            echo "Swal.fire({
                icon: 'success',
                title: 'ลบคิวสำเร็จ',
                text: 'คิวถูกลบออกเรียบร้อยแล้ว',
                showConfirmButton: false,
                timer: 1500
            });";
            unset($_SESSION['delete_queue_success']);
        }
        if (isset($_SESSION['delete_queue_error'])) {
            echo "Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: '" . $_SESSION['delete_queue_error'] . "',
                showConfirmButton: true
            });";
            unset($_SESSION['delete_queue_error']);
        }

        // ลบนักเรียน
        if (isset($_SESSION['delete_student_success']) && $_SESSION['delete_student_success']) {
            echo "Swal.fire({
                icon: 'success',
                title: 'ลบนักเรียนสำเร็จ',
                text: 'นักเรียนถูกลบออกจากคิวเรียบร้อยแล้ว',
                showConfirmButton: false,
                timer: 1500
            });";
            unset($_SESSION['delete_student_success']);
        }
        if (isset($_SESSION['delete_student_error'])) {
            echo "Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: '" . $_SESSION['delete_student_error'] . "',
                showConfirmButton: true
            });";
            unset($_SESSION['delete_student_error']);
        }

        // แก้ไขนักเรียน
        if (isset($_SESSION['edit_success']) && $_SESSION['edit_success']) {
            echo "Swal.fire({
                icon: 'success',
                title: 'แก้ไขนักเรียนสำเร็จ',
                text: 'ข้อมูลนักเรียนถูกแก้ไขเรียบร้อยแล้ว',
                showConfirmButton: false,
                timer: 1500
            });";
            unset($_SESSION['edit_success']);
        }
        if (isset($_SESSION['edit_error'])) {
            echo "Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: '" . $_SESSION['edit_error'] . "',
                showConfirmButton: true
            });";
            unset($_SESSION['edit_error']);
        }
        ?>
    };

    // แสดงรายชื่อนักเรียนใน Modal
    function showStudents(queueId) {
        document.getElementById('current_queue_id').value = queueId;
        fetch('get_students.php?queue_id=' + queueId)
            .then(response => response.text())
            .then(data => {
                document.getElementById('studentTableBody').innerHTML = data;
            })
            .catch(error => console.error('Error:', error));
    }

    // ตั้งค่า Modal เพิ่มนักเรียนและโหลดรายชื่อที่ยังไม่อยู่ในคิว
    function setAddStudentFromModal() {
        const queueId = document.getElementById('current_queue_id').value;
        document.getElementById('add_queue_id').value = queueId;

        fetch(`get_available_students.php?queue_id=${queueId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('student_id').innerHTML = '<option value="">-- เลือกนักเรียน --</option>' + data;
            })
            .catch(error => console.error('Error:', error));
    }

    // ตั้งค่า Modal แก้ไขนักเรียน
    function setEditStudent(queueId, studentId, studentName) {
        document.getElementById('edit_queue_id').value = queueId;
        document.getElementById('old_student_id').value = studentId;
        document.getElementById('current_student').value = studentName;
    }
</script>
</body>
</html>