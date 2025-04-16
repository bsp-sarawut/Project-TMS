<?php
include('config/condb.php');
session_start();

// ฟังก์ชันแปลงเลขเดือนเป็นชื่อเดือนภาษาไทย
function getThaiMonth($month) {
    $thaiMonths = [
        1 => "มกราคม", 2 => "กุมภาพันธ์", 3 => "มีนาคม", 4 => "เมษายน",
        5 => "พฤษภาคม", 6 => "มิถุนายน", 7 => "กรกฎาคม", 8 => "สิงหาคม",
        9 => "กันยายน", 10 => "ตุลาคม", 11 => "พฤศจิกายน", 12 => "ธันวาคม"
    ];
    return $thaiMonths[$month];
}

// ดึงรายการปีที่มีในตาราง transport_schedule
try {
    $stmt = $conn->prepare("SELECT DISTINCT year FROM transport_schedule ORDER BY year DESC");
    $stmt->execute();
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $years = [];
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลปี: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดรูปแบบการลงทะเบียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        }
        .btn-danger:hover {
            background: #c0392b;
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
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="header-title">ระบบจัดรูปแบบการลงทะเบียน</h2>

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

            <!-- ส่วนที่ 1: ฟอร์มค้นหา -->
            <div class="card mb-4 search-section">
                <h3 class="mb-3">ค้นหารูปแบบการลงทะเบียน</h3>
                <div class="search-container">
                    <label for="search_input" class="form-label">ค้นหาตาราง</label>
                    <div class="search-input-group">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" id="search_input" class="form-control" placeholder="ค้นหา: ID, ปี หรือ เดือน">
                    </div>
                </div>
                <div class="filter-row">
                    <div class="col-md-3 col-12">
                        <label for="filter_year" class="form-label">เลือกปี</label>
                        <select id="filter_year" class="form-select">
                            <option value="">-- แสดงทั้งหมด --</option>
                            <?php foreach ($years as $year) { ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="filter_month" class="form-label">เลือกเดือน</label>
                        <select id="filter_month" class="form-select">
                            <option value="">-- แสดงทั้งหมด --</option>
                            <?php for ($m = 1; $m <= 12; $m++) { ?>
                                <option value="<?php echo $m; ?>"><?php echo getThaiMonth($m); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="action-buttons">
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-undo me-2"></i>เคลียร์ฟิลเตอร์
                    </button>
                </div>
            </div>

            <!-- ส่วนที่ 2: รายการตาราง -->
            <div class="card mb-4">
                <h3 class="mb-3">ข้อมูลรูปแบบการลงทะเบียนทั้งหมด</h3>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                        <i class="fas fa-plus me-2"></i>เพิ่มรูปแบบ
                    </button>
                </div>
                <div class="total-count">จำนวนรูปแบบทั้งหมด: <span id="totalItems">0</span></div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ไอดี</th>
                                <th>ปี</th>
                                <th>เดือน</th>
                                <th>จำนวนวันที่เปิดให้ลงทะเบียน</th>
                                <th>วันที่ขึ้นรถ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleTable">
                            <tr><td colspan="6" class="text-center text-muted">กำลังโหลดข้อมูล...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มตาราง -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScheduleModalLabel">เพิ่มการตั้งค่าการขึ้นรถ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addScheduleForm" method="POST" action="choice_insert.php">
                        <div class="mb-3">
                            <label for="year" class="form-label">เลือกปี:</label>
                            <select name="year" id="year" class="form-select" required>
                                <?php for ($i = date("Y"); $i <= date("Y") + 5; $i++) { ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == date("Y") ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="month" class="form-label">เลือกเดือน:</label>
                            <select name="month" id="month" class="form-select" required>
                                <?php for ($m = 1; $m <= 12; $m++) { ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m == date("m") ? 'selected' : ''; ?>><?php echo getThaiMonth($m); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="available_dates" class="form-label">เลือกวันที่ขึ้นรถ (เลือกหลายวันได้):</label>
                            <input type="text" name="available_dates[]" id="available_dates" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="num_of_days" class="form-label">จำนวนวันที่เปิดให้ลงทะเบียน:</label>
                            <input type="number" name="num_of_days" id="num_of_days" class="form-control" required min="1" max="31" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>บันทึกการตั้งค่า</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขตาราง -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editScheduleModalLabel">แก้ไขการตั้งค่าการขึ้นรถ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editScheduleForm" method="POST" action="choice_update.php">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_year" class="form-label">เลือกปี:</label>
                            <select name="year" id="edit_year" class="form-select" required>
                                <?php for ($i = date("Y"); $i <= date("Y") + 5; $i++) { ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_month" class="form-label">เลือกเดือน:</label>
                            <select name="month" id="edit_month" class="form-select" required>
                                <?php for ($m = 1; $m <= 12; $m++) { ?>
                                    <option value="<?php echo $m; ?>"><?php echo getThaiMonth($m); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_available_dates" class="form-label">เลือกวันที่ขึ้นรถ:</label>
                            <input type="text" name="available_dates[]" id="edit_available_dates" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_num_of_days" class="form-label">จำนวนวันที่เปิดให้ลงทะเบียน:</label>
                            <input type="number" name="num_of_days" id="edit_num_of_days" class="form-control" required min="1" max="31" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>บันทึกการแก้ไข</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        let searchTimeout;

        // โหลดข้อมูลตาราง
        // โหลดข้อมูลตาราง
function loadSchedules() {
    const search = $('#search_input').val();
    const month_filter = $('#filter_month').val();
    const year_filter = $('#filter_year').val();

    $.ajax({
        url: 'fetch_schedules.php',
        type: 'POST',
        data: {
            search: search,
            month_filter: month_filter,
            year_filter: year_filter
        },
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                Swal.fire('เกิดข้อผิดพลาด', data.error, 'error');
                $('#scheduleTable').html('<tr><td colspan="6" class="text-center text-muted">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
                $('#totalItems').text(0);
                return;
            }

            const schedules = data.schedules;
            const totalRows = data.totalRows;
            let html = '';

            if (schedules.length > 0) {
                schedules.forEach(schedule => {
                    // ตรวจสอบว่า schedule.available_dates มีค่าหรือไม่
                    const available_dates = schedule.available_dates && typeof schedule.available_dates === 'string'
                        ? schedule.available_dates.split(", ")
                        : [];
                    const dates_str = available_dates.length > 0 ? available_dates.join(", ") : "ไม่มีวันที่ระบุ";

                    html += `
                        <tr>
                            <td>${schedule.id}</td>
                            <td>${schedule.year}</td>
                            <td>${schedule.month_name}</td>
                            <td>${schedule.num_of_days}</td>
                            <td>${dates_str}</td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editScheduleModal"
                                    data-id="${schedule.id}"
                                    data-year="${schedule.year}"
                                    data-month="${schedule.month}"
                                    data-dates="${dates_str}"
                                    data-num_days="${schedule.num_of_days}">
                                    <i class="fas fa-edit"></i> แก้ไข
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(${schedule.id})">
                                    <i class="fas fa-trash-alt"></i> ลบ
                                </button>
                            </td>
                        </tr>`;
                });
            } else {
                html = '<tr><td colspan="6" class="text-center text-muted">ไม่มีข้อมูลการตั้งค่า</td></tr>';
            }

            $('#scheduleTable').html(html);
            $('#totalItems').text(totalRows);
        },
        error: function(xhr, status, error) {
            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้: ' + error, 'error');
            $('#scheduleTable').html('<tr><td colspan="6" class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</td></tr>');
            $('#totalItems').text(0);
        }
    });
}

// ฟังก์ชันเคลียร์ฟิลเตอร์
function clearFilters() {
    $('#search_input').val('');
    $('#filter_year').val('');
    $('#filter_month').val('');
    loadSchedules();
}

// Sidebar Toggle with localStorage
$(document).ready(function() {
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

    // โหลดข้อมูลเริ่มต้น
    loadSchedules();

    // ค้นหาแบบเรียลไทม์เมื่อพิมพ์
    let searchTimeout;
    $('#search_input').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadSchedules();
        }, 300);
    });

    // ค้นหาแบบเรียลไทม์เมื่อเปลี่ยนตัวกรองปี
    $('#filter_year').on('change', function() {
        loadSchedules();
    });

    // ค้นหาแบบเรียลไทม์เมื่อเปลี่ยนตัวกรองเดือน
    $('#filter_month').on('change', function() {
        loadSchedules();
    });

    // ฟังก์ชันคำนวณวันแรกและวันสุดท้ายของเดือน
    function getMonthRange(year, month) {
        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month, 0);
        return { minDate: firstDay, maxDate: lastDay };
    }

    // Flatpickr สำหรับเพิ่มการตั้งค่า
    let addFlatpickrInstance;
    const yearSelect = document.getElementById('year');
    const monthSelect = document.getElementById('month');

    function initializeAddFlatpickr() {
        const year = yearSelect.value;
        const month = monthSelect.value;
        const { minDate, maxDate } = getMonthRange(year, month);

        if (addFlatpickrInstance) addFlatpickrInstance.destroy();

        addFlatpickrInstance = flatpickr("#available_dates", {
            mode: "multiple",
            dateFormat: "d",
            minDate: minDate,
            maxDate: maxDate,
            onChange: function(selectedDates) {
                document.getElementById('num_of_days').value = selectedDates.length;
            }
        });
    }

    yearSelect.addEventListener('change', initializeAddFlatpickr);
    monthSelect.addEventListener('change', initializeAddFlatpickr);
    initializeAddFlatpickr();

    // Flatpickr สำหรับแก้ไขการตั้งค่า
    let editFlatpickrInstance;
    $('#editScheduleModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const id = button.data('id');
        const year = button.data('year');
        const month = button.data('month');
        const availableDates = button.data('dates') && typeof button.data('dates') === 'string'
            ? button.data('dates').split(", ").map(item => item.trim())
            : [];
        const numDays = button.data('num_days');

        $('#edit_id').val(id);
        $('#edit_year').val(year);
        $('#edit_month').val(month);
        $('#edit_num_of_days').val(numDays);

        const editYearSelect = document.getElementById('edit_year');
        const editMonthSelect = document.getElementById('edit_month');

        function initializeEditFlatpickr() {
            const editYear = editYearSelect.value;
            const editMonth = editMonthSelect.value;
            const { minDate, maxDate } = getMonthRange(editYear, editMonth);

            if (editFlatpickrInstance) editFlatpickrInstance.destroy();

            editFlatpickrInstance = flatpickr("#edit_available_dates", {
                mode: "multiple",
                dateFormat: "d",
                minDate: minDate,
                maxDate: maxDate,
                defaultDate: availableDates.map(date => new Date(editYear, editMonth - 1, parseInt(date))),
                onChange: function(selectedDates) {
                    document.getElementById('edit_num_of_days').value = selectedDates.length;
                }
            });
        }

        initializeEditFlatpickr();
        editYearSelect.addEventListener('change', initializeEditFlatpickr);
        editMonthSelect.addEventListener('change', initializeEditFlatpickr);
    });

    // SweetAlert สำหรับเพิ่มตาราง
    $('#addScheduleForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'ยืนยันการเพิ่มตาราง?',
            text: 'คุณต้องการเพิ่มข้อมูลตารางนี้หรือไม่?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData(this);
                formData.set('available_dates', $('#available_dates').val().split(", ").map(Number).join(", "));

                $.ajax({
                    url: 'choice_insert.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === "success") {
                            Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                                $('#addScheduleModal').modal('hide');
                                loadSchedules();
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

    // SweetAlert สำหรับแก้ไขตาราง
    $('#editScheduleForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'ยืนยันการแก้ไข?',
            text: 'คุณต้องการบันทึกการแก้ไขข้อมูลตารางนี้หรือไม่?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData(this);
                formData.set('available_dates', $('#edit_available_dates').val().split(", ").map(Number).join(", "));

                $.ajax({
                    url: 'choice_update.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === "success") {
                            Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                                $('#editScheduleModal').modal('hide');
                                loadSchedules();
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
});

// SweetAlert สำหรับการลบ
function confirmDelete(id) {
    Swal.fire({
        title: 'คุณแน่ใจหรือไม่?',
        text: 'คุณต้องการลบข้อมูลนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'choice_delete.php',
                type: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                            loadSchedules();
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
}
    </script>
</body>
</html>