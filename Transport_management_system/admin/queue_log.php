<?php
session_start();
require_once 'config/condb.php';

// ฟังก์ชันแปลงวันที่เป็นรูปแบบภาษาไทย (มีตัวเลือกแสดงเวลา)
function getFormattedThaiDate($date, $show_time = true) {
    if (!$date) return 'ไม่ระบุ';
    $dateTime = new DateTime($date);
    $day = $dateTime->format('j');
    $monthNames = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    $month = $monthNames[(int)$dateTime->format('n')];
    $year = $dateTime->format('Y') + 543;
    if ($show_time) {
        return "$day $month $year " . $dateTime->format('H:i:s');
    }
    return "$day $month $year";
}

// ดึงวันที่ที่มีคิวปิดงาน (สำหรับปฏิทิน)
try {
    $stmt_dates = $conn->prepare("SELECT DISTINCT queue_date FROM queue WHERE status_car = 'ปิดงาน'");
    $stmt_dates->execute();
    $closed_dates = $stmt_dates->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}

// ดึงข้อมูลจังหวัดทั้งหมดสำหรับ dropdown
try {
    $stmt_provinces = $conn->prepare("SELECT PROVINCE_ID, PROVINCE_NAME FROM province ORDER BY PROVINCE_NAME");
    $stmt_provinces->execute();
    $provinces = $stmt_provinces->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}

// ดึงข้อมูลอำเภอทั้งหมด
try {
    $stmt_amphurs = $conn->prepare("SELECT AMPHUR_ID, AMPHUR_NAME, PROVINCE_ID FROM amphur ORDER BY AMPHUR_NAME");
    $stmt_amphurs->execute();
    $amphurs = $stmt_amphurs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log คิวรถที่ปิดงานแล้ว</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .btn-secondary {
            background: #6c757d;
            border: none;
        }
        .btn-secondary:hover {
            background: #5a6268;
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
            min-width: 500px;
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
        .table tbody tr.phase-row {
            background: #f1f1f1;
            font-weight: 600;
        }
        .table td {
            vertical-align: middle;
            text-align: center;
            padding: 12px 20px;
            font-size: 0.9rem;
            word-break: break-word;
        }
        .table th:nth-child(1), .table td:nth-child(1) { /* สถานะ */
            min-width: 150px;
        }
        .table th:nth-child(2), .table td:nth-child(2) { /* วันที่และเวลา */
            min-width: 200px;
        }
        .queue-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .queue-details-grid .detail-item {
            display: flex;
            flex-direction: column;
        }
        .queue-details-grid .detail-item label {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }
        .queue-details-grid .detail-item span {
            color: #555;
        }
        .status-closed {
            background-color: #ff6666;
            color: #fff;
            padding: 6px 12px;
            border-radius: 5px;
            font-weight: 500;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }
        .status-arrived {
            background-color: #66cc99;
            color: #fff;
            padding: 6px 12px;
            border-radius: 5px;
            font-weight: 500;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }
        .status-departed {
            background-color: #ff9966;
            color: #fff;
            padding: 6px 12px;
            border-radius: 5px;
            font-weight: 500;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }
        .status-pickup {
            background-color: #6699ff;
            color: #fff;
            padding: 6px 12px;
            border-radius: 5px;
            font-weight: 500;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }
        .status-default {
            background-color: #b3c6e6;
            color: #fff;
            padding: 6px 12px;
            border-radius: 5px;
            font-weight: 500;
            display: inline-block;
            min-width: 100px;
            text-align: center;
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
            flex-wrap: wrap;
        }
        .queue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .no-data {
            text-align: center;
            color: #555;
            font-size: 1.2rem;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .no-data i {
            font-size: 2.5rem;
            color: #007bff;
            margin-bottom: 15px;
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
        .flatpickr-calendar {
            font-family: 'Kanit', sans-serif;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .flatpickr-day.has-queue {
            position: relative;
        }
        .flatpickr-day.has-queue::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background-color: #ff0000;
            border-radius: 50%;
        }
        .flatpickr-day.selected {
            background-color: #007bff;
            border-color: #007bff;
        }
        .flatpickr-day.today {
            border-color: #007bff;
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
            .queue-details-grid {
                grid-template-columns: 1fr;
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
            .status-closed,
            .status-arrived,
            .status-departed,
            .status-pickup,
            .status-default {
                min-width: 80px;
                padding: 4px 8px;
                font-size: 0.85rem;
            }
        }
        #queueResults {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="header-title">ระบบจัดการ Log คิวรถที่ปิดงานแล้ว</h2>

            <!-- ฟอร์มค้นหา -->
            <div class="card mb-4 search-section">
                <h3 class="mb-3">ตัวกรองข้อมูลคิวรถที่ปิดงานแล้ว</h3>
                <div id="searchForm">
                    <div class="search-container">
                        <label for="search" class="form-label">ค้นหาคิว</label>
                        <div class="search-input-group">
                            <span class="search-icon"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" id="search" class="form-control" placeholder="เช่น Queue ID, วันที่, รถ, จังหวัด, อำเภอ">
                        </div>
                    </div>
                    <div class="filter-row">
                        <div class="col-md-3 col-12">
                            <label for="datePicker" class="form-label">วันที่</label>
                            <input type="text" name="filter_date" id="datePicker" class="form-control" placeholder="เลือกวันที่">
                        </div>
                        <div class="col-md-3 col-12">
                            <label for="filterProvince" class="form-label">จังหวัด</label>
                            <select name="filter_province" id="filterProvince" class="form-select">
                                <option value="">เลือกจังหวัด</option>
                                <?php foreach ($provinces as $province): ?>
                                    <option value="<?php echo htmlspecialchars($province['PROVINCE_ID']); ?>">
                                        <?php echo htmlspecialchars($province['PROVINCE_NAME']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 col-12">
                            <label for="filterAmphur" class="form-label">อำเภอ</label>
                            <select name="filter_amphur" id="filterAmphur" class="form-select">
                                <option value="">เลือกอำเภอ</option>
                            </select>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-undo me-2"></i>เคลียร์ฟิลเตอร์
                        </button>
                        <a href="export_csv.php" class="btn btn-success">
                            <i class="fas fa-file-csv me-2"></i>Export ทั้งหมด (CSV)
                        </a>
                    </div>
                </div>
            </div>

            <!-- ผลลัพธ์การค้นหา -->
            <div id="queueResults">
                <!-- ผลลัพธ์จะถูกโหลดที่นี่ผ่าน AJAX -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
    <script>
        let searchTimeout;
        let currentPage = 1;

        // ฟังก์ชันดึงข้อมูลคิว
        function fetchQueues(page = 1) {
            currentPage = page;
            const search = document.getElementById('search').value;
            const filter_date = document.getElementById('datePicker').value;
            const filter_province = document.getElementById('filterProvince').value;
            const filter_amphur = document.getElementById('filterAmphur').value;

            // อัปเดต session ด้วยตัวกรองสำหรับการ export
            $.post('update_session.php', {
                search: search,
                filter_date: filter_date,
                filter_province: filter_province,
                filter_amphur: filter_amphur
            });

            $.ajax({
                url: 'fetch_log_queues.php',
                method: 'POST',
                data: {
                    search: search,
                    filter_date: filter_date,
                    filter_province: filter_province,
                    filter_amphur: filter_amphur,
                    page: page
                },
                success: function(response) {
                    $('#queueResults').html(response);
                    if (response.includes('ไม่มีข้อมูลคิวรถที่ปิดงานแล้ว')) {
                        Swal.fire({
                            icon: 'info',
                            title: 'ไม่พบข้อมูล',
                            text: 'ไม่มีข้อมูลคิวรถที่ปิดงานแล้วตามเงื่อนไขที่ระบุ',
                            confirmButtonText: 'ตกลง'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถดึงข้อมูลได้ กรุณาลองใหม่',
                        confirmButtonText: 'ตกลง'
                    });
                }
            });
        }

        // ฟังก์ชันเคลียร์ฟิลเตอร์
        function clearFilters() {
            document.getElementById('search').value = '';
            document.getElementById('datePicker').value = '';
            document.getElementById('filterProvince').value = '';
            document.getElementById('filterAmphur').innerHTML = '<option value="">เลือกอำเภอ</option>';
            currentPage = 1;
            fetchQueues(1);
        }

        // Sidebar Toggle with localStorage
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        const closeBtn = document.getElementById('close-btn');
        const openBtn = document.getElementById('open-btn');

        window.addEventListener('load', () => {
            const sidebarState = localStorage.getItem('sidebarState');
            if (sidebarState === 'closed') {
                sidebar.classList.add('closed');
                content.classList.add('closed');
                openBtn.style.display = 'block';
            }
            fetchQueues(); // โหลดข้อมูลครั้งแรกเมื่อหน้าโหลด
        });

        closeBtn.addEventListener('click', () => {
            sidebar.classList.add('closed');
            content.classList.add('closed');
            openBtn.style.display = 'block';
            localStorage.setItem('sidebarState', 'closed');
        });

        openBtn.addEventListener('click', () => {
            sidebar.classList.remove('closed');
            content.classList.remove('closed');
            openBtn.style.display = 'none';
            localStorage.setItem('sidebarState', 'open');
        });

        // Flatpickr Initialization
        const closedDates = <?php echo json_encode($closed_dates); ?>;
        flatpickr("#datePicker", {
            dateFormat: "Y-m-d",
            locale: "th",
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const date = dayElem.dateObj;
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const dateStr = `${year}-${month}-${day}`;
                
                if (closedDates.includes(dateStr)) {
                    dayElem.classList.add('has-queue');
                }
            },
            onChange: function() {
                currentPage = 1;
                fetchQueues(1);
            }
        });

        // ตัวเลือกจังหวัดและอำเภอ
        const amphurs = <?php echo json_encode($amphurs); ?>;
        const provinceSelect = document.getElementById('filterProvince');
        const amphurSelect = document.getElementById('filterAmphur');

        provinceSelect.addEventListener('change', function() {
            const provinceId = this.value;
            amphurSelect.innerHTML = '<option value="">เลือกอำเภอ</option>';

            if (provinceId) {
                const filteredAmphurs = amphurs.filter(amphur => amphur.PROVINCE_ID == provinceId);
                filteredAmphurs.forEach(amphur => {
                    const option = document.createElement('option');
                    option.value = amphur.AMPHUR_ID;
                    option.textContent = amphur.AMPHUR_NAME;
                    amphurSelect.appendChild(option);
                });
            }
            currentPage = 1;
            fetchQueues(1);
        });

        amphurSelect.addEventListener('change', function() {
            currentPage = 1;
            fetchQueues(1);
        });

        // การค้นหาแบบเรียลไทม์
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                fetchQueues(1);
            }, 300); // รอ 300ms เพื่อลดการเรียก AJAX บ่อยเกินไป
        });
    </script>
</body>
</html>