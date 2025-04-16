<?php
require_once 'config/condb.php';

// ดึงข้อมูลเดือนและปีจาก transport_schedule
try {
    $scheduleQuery = $conn->query("SELECT DISTINCT month, year FROM transport_schedule ORDER BY year DESC, month DESC");
    $schedules = $scheduleQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching transport_schedule: " . $e->getMessage());
}

// ดึงข้อมูลจังหวัดจาก province
try {
    $provinceQuery = $conn->query("SELECT * FROM province");
    $provinces = $provinceQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching provinces: " . $e->getMessage());
}

// ดึงข้อมูลรถจาก car (จะถูกแทนที่ด้วย AJAX ใน JS)
try {
    $carQuery = $conn->query("SELECT * FROM car ORDER BY driver_id ASC");
    $cars = $carQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching cars: " . $e->getMessage());
}

// ดึงข้อมูล transport_registration เฉพาะที่ชำระเงินแล้ว (payment_status = 'Paid')
try {
    $registrationQuery = $conn->query("
        SELECT tr.transport_schedule_id, ts.available_dates, ts.month, 
            tr.*, s.stu_name, s.stu_lastname, s.stu_ID, 
            p.PROVINCE_NAME AS province_name, a.AMPHUR_NAME AS amphur_name, r.location 
        FROM transport_registration tr 
        LEFT JOIN students s ON tr.stu_username = s.stu_username
        LEFT JOIN routes r ON tr.route_id = r.route_ID
        LEFT JOIN province p ON r.province = p.PROVINCE_ID
        LEFT JOIN amphur a ON r.amphur = a.AMPHUR_ID
        LEFT JOIN transport_schedule ts ON tr.transport_schedule_id = ts.id
        WHERE tr.payment_status = 'Paid'  /* ปรับเงื่อนไข */
    ");
    $registrations = $registrationQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching registrations: " . $e->getMessage());
}

$available_dates = isset($_POST['available_dates']) ? $_POST['available_dates'] : '';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดคิวรถ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.0/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f5f6f5;
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
            display: flex;
            margin: 0;
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
            padding: 30px;
            flex-grow: 1;
            transition: margin-left 0.3s ease;
        }
        .content.closed { 
            margin-left: 0; 
        }
        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
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
            transform: translateY(-2px);
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
            border-radius: 5px;
            border: 1px solid #ccc;
            padding: 10px;
            font-size: 0.95rem;
        }
        .form-select:focus, .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 3px rgba(0, 123, 255, 0.3);
        }
        .btn-primary {
            border-radius: 8px;
            padding: 10px 30px;
            font-size: 1rem;
            background: #007bff;
            border: none;
            transition: background 0.3s ease;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .table {
            border-radius: 5px;
            overflow: hidden;
            background: #fff;
        }
        .table thead th {
            background: #003087;
            color: #fff;
            text-align: center;
            padding: 15px;
        }
        .table tbody tr:hover {
            background: #f9f9f9;
        }
        .table td {
            vertical-align: middle;
            padding: 12px;
            text-align: center;
        }
        .badge-success {
            background: #28a745;
        }
        .badge-danger {
            background: #dc3545;
        }
        #driverInfo {
            font-size: 0.9rem;
            color: #555;
        }
        .total-count, .selected-count {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 10px;
            display: inline-block;
            margin-right: 20px;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
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
            .container-custom {
                padding: 0 10px;
            }
            .card {
                padding: 15px;
            }
            .card h3 {
                font-size: 1.2rem;
            }
            .form-label, .form-select, .form-control, .total-count, .selected-count, .table {
                font-size: 0.9rem;
            }
            .btn-primary {
                font-size: 0.9rem;
                padding: 8px 20px;
            }
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="content" id="content">
    <div class="container-custom mt-4">
        <h2 class="text-center mb-4" style="color: #333; font-weight: 600; font-size: 2rem;">ระบบจัดคิวรถ</h2>
        <form method="post" action="save_queue.php" id="queueForm">
            <div class="card">
                <h3 class="mb-3">เลือกข้อมูลสำหรับจัดคิว</h3>
                <div class="row g-3">
                    <!-- ปฏิทินเลือกวันที่ -->
                    <div class="col-md-4 col-12">
                        <label for="date_picker" class="form-label">วันที่</label>
                        <input type="text" id="date_picker" name="date_picker" class="form-control" placeholder="เลือกวันที่">
                    </div>

                    <!-- เลือกจังหวัด -->
                    <div class="col-md-4 col-12">
                        <label for="province" class="form-label">จังหวัด</label>
                        <select name="province" id="province" class="form-select" onchange="loadAmphur(); loadCars();">
                            <option value="">เลือกจังหวัด</option>
                            <?php foreach ($provinces as $province): ?>
                                <option value="<?= $province['PROVINCE_ID'] ?>">
                                    <?= $province['PROVINCE_NAME'] ?>
                                </option>
                            <?php endforeach; ?> 
                        </select>
                    </div>

                    <!-- เลือกอำเภอ -->
                    <div class="col-md-4 col-12">
                        <label for="amphur" class="form-label">อำเภอ</label>
                        <select name="amphur" id="amphur" class="form-select" onchange="loadLocation(); loadCars();">
                            <option value="">เลือกอำเภอ</option>
                        </select>
                    </div>

                    <!-- เลือกจุดขึ้นรถ -->
                    <div class="col-md-4 col-12">
                        <label for="location" class="form-label">จุดขึ้นรถ</label>
                        <select name="location" id="location" class="form-select">
                            <option value="">เลือกจุดขึ้นรถ</option>
                        </select>
                    </div>

                    <!-- เลือกยานพาหนะ -->
                    <div class="col-md-4 col-12">
                        <label for="car" class="form-label">ยานพาหนะ (จำนวนที่นั่ง)</label>
                        <select name="car" id="car" class="form-select" onchange="showDriverInfo()">
                            <option value="">เลือกยานพาหนะ</option>
                        </select>
                    </div>

                    <!-- ข้อมูลคนขับ -->
                    <div class="col-md-4 col-12">
                        <label class="form-label">ข้อมูลคนขับ</label>
                        <div id="driverInfo"></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 class="mb-3">เลือกนักเรียนสำหรับคิวนี้</h3>
                <div class="total-count" id="totalCount">จำนวนทั้งหมด: 0 รายการ</div>
                <div class="selected-count" id="selectedCount">เลือกแล้ว: 0 คน</div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th>รหัสนักเรียน</th>
                                <th>เดือน</th>
                                <th>วันที่ว่าง</th>
                                <th>ชื่อ - นามสกุล</th>
                                <th>จังหวัด</th>
                                <th>จุดขึ้นรถ</th>
                                <th>อำเภอ</th>
                                <th>สถานะการชำระเงิน</th>  <!-- คืนคอลัมน์สถานะการชำระเงิน -->
                            </tr>
                        </thead>
                        <tbody>
                            <!-- ข้อมูลนักเรียนจะถูกเพิ่มโดย JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="button" class="btn btn-primary" onclick="confirmQueue()">ยืนยันการจัดคิว</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
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

    // ตั้งค่า Flatpickr
    const datePicker = flatpickr("#date_picker", {
        mode: "range",
        dateFormat: "Y-m-d",
        locale: "th",
        onClose: function() {
            filterData();
            loadCars();
        }
    });

    function loadAmphur() {
        const provinceID = document.getElementById('province').value;
        const amphurSelect = document.getElementById('amphur');
        amphurSelect.innerHTML = '<option value="">เลือกอำเภอ</option>';
        document.getElementById('location').innerHTML = '<option value="">เลือกจุดขึ้นรถ</option>';

        if (provinceID) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'get_amphur.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    amphurSelect.innerHTML += xhr.responseText;
                }
            };
            xhr.send('province_id=' + provinceID);
        }
        filterData();
    }

    function loadLocation() {
        const provinceID = document.getElementById('province').value;
        const amphurID = document.getElementById('amphur').value;
        const locationSelect = document.getElementById('location');
        locationSelect.innerHTML = '<option value="">เลือกจุดขึ้นรถ</option>';

        if (provinceID && amphurID) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `get_location.php?province_id=${provinceID}&hur_id=${amphurID}`, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    locationSelect.innerHTML += xhr.responseText;
                    filterData();
                } else {
                    console.error("Error loading locations:", xhr.status, xhr.responseText);
                }
            };
            xhr.send();
        }
    }

    document.getElementById('location').addEventListener('change', function() {
        filterData();
    });

    let carData = [];
    let maxSeats = 0;

    function loadCars() {
        const datePicker = document.getElementById('date_picker').value;
        const provinceID = document.getElementById('province').value;
        const amphurID = document.getElementById('amphur').value;
        const carSelect = document.getElementById('car');

        if (!datePicker) {
            carSelect.innerHTML = '<option value="">เลือกยานพาหนะ</option>';
            document.getElementById('driverInfo').innerHTML = '';
            maxSeats = 0;
            return;
        }

        const date = datePicker.split(" to ")[0];
        let url = `get_available_cars.php?date=${date}`;
        if (provinceID) url += `&province_id=${provinceID}`;
        if (amphurID) url += `&hur_id=${amphurID}`;

        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                carData = JSON.parse(xhr.responseText);
                carSelect.innerHTML = '<option value="">เลือกยานพาหนะ</option>';
                carData.forEach(car => {
                    carSelect.innerHTML += `<option value="${car.car_id}">${car.car_license} - ${car.car_brand} (${car.car_seat} ที่นั่ง)</option>`;
                });
                showDriverInfo();
            }
        };
        xhr.send();
    }

    function showDriverInfo() {
        const carSelect = document.getElementById('car');
        const driverInfoDiv = document.getElementById('driverInfo');
        const selectedCarId = carSelect.value;

        if (!selectedCarId) {
            driverInfoDiv.innerHTML = 'ไม่มีการเลือกยานพาหนะ';
            maxSeats = 0;
            return;
        }

        const selectedCar = carData.find(car => car.car_id == selectedCarId);
        if (selectedCar) {
            const driverName = `${selectedCar.driver_name || 'ไม่ระบุ'} ${selectedCar.driver_lastname || ''}`.trim();
            const driverLocation = `${selectedCar.driver_province_name || 'ไม่ระบุ'} - ${selectedCar.driver_amphur_name || 'ไม่ระบุ'}`;
            driverInfoDiv.innerHTML = `คนขับ: ${driverName} <br> ที่อยู่: ${driverLocation}`;
            maxSeats = parseInt(selectedCar.car_seat) || 0;
        } else {
            driverInfoDiv.innerHTML = 'ไม่พบข้อมูลคนขับ';
            maxSeats = 0;
        }
    }

    function filterData() {
        const datePicker = document.getElementById('date_picker').value;
        const provinceID = document.getElementById('province').value;
        const amphurID = document.getElementById('amphur').value;
        const location = document.getElementById('location').value;

        const queryParams = new URLSearchParams({
            date_picker: datePicker,
            province_id: provinceID,
            amphur_id: amphurID,
            location: location,
            payment_status: 'Paid'  /* ปรับให้สอดคล้อง */
        }).toString();

        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'filter_data.php?' + queryParams, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const registrations = JSON.parse(xhr.responseText);
                    updateTable(registrations);
                } catch (e) {
                    console.error("Error parsing JSON:", e, xhr.responseText);
                }
            }
        };
        xhr.send();
    }

    function updateTable(registrations) {
        const tbody = document.querySelector('table tbody');
        const totalCountDiv = document.getElementById('totalCount');
        const selectedCountDiv = document.getElementById('selectedCount');
        const selectAllCheckbox = document.getElementById('selectAll');
        tbody.innerHTML = '';

        if (registrations.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">ไม่พบข้อมูล</td></tr>';  /* ปรับ colspan กลับเป็น 9 */
            totalCountDiv.textContent = 'จำนวนทั้งหมด: 0 รายการ';
            selectedCountDiv.textContent = 'เลือกแล้ว: 0 คน';
            selectAllCheckbox.checked = false;
            selectAllCheckbox.disabled = true;
            return;
        }

        selectAllCheckbox.disabled = false;

        registrations.forEach(reg => {
            const paymentStatus = '<span class="badge badge-success">ชำระแล้ว</span>';  /* แสดงเฉพาะ "ชำระแล้ว" */
            const row = `
                <tr>
                    <td><input type="checkbox" name="students[]" value="${reg.stu_ID}" onchange="updateSelectAll()"></td>
                    <td>${reg.stu_ID}</td>
                    <td>${reg.month || 'ไม่ระบุ'}</td>
                    <td>${reg.available_dates || 'ไม่ระบุ'}</td>
                    <td>${reg.stu_name} ${reg.stu_lastname}</td>
                    <td>${reg.province_name || 'ไม่ระบุ'}</td>
                    <td>${reg.amphur_name || 'ไม่ระบุ'}</td>
                    <td>${reg.location || 'ไม่ระบุ'}</td>
                    <td>${paymentStatus}</td>  <!-- คืนการแสดงสถานะ -->
                </tr>`;
            tbody.insertAdjacentHTML('beforeend', row);
        });

        totalCountDiv.textContent = `จำนวนทั้งหมด: ${registrations.length} รายการ`;
        updateSelectedCount();
    }

    function updateSelectAll() {
        const checkboxes = document.querySelectorAll('input[name="students[]"]');
        const selectAllCheckbox = document.getElementById('selectAll');
        const checkedCount = document.querySelectorAll('input[name="students[]"]:checked').length;

        selectAllCheckbox.checked = checkedCount === checkboxes.length;
        updateSelectedCount();

        if (checkedCount > maxSeats && maxSeats > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'จำนวนนักเรียนเกินจำนวนที่นั่ง',
                text: `รถที่เลือกมี ${maxSeats} ที่นั่ง แต่คุณเลือกนักเรียน ${checkedCount} คน กรุณาเลือกไม่เกิน ${maxSeats} คน`,
                confirmButtonText: 'ตกลง'
            });
            checkboxes.forEach((checkbox, index) => {
                if (index >= maxSeats) {
                    checkbox.checked = false;
                }
            });
            updateSelectedCount();
        }
    }

    function updateSelectedCount() {
        const selectedCountDiv = document.getElementById('selectedCount');
        const selectedCount = document.querySelectorAll('input[name="students[]"]:checked').length;
        selectedCountDiv.textContent = `เลือกแล้ว: ${selectedCount} คน`;
    }

    function toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('input[name="students[]"]');
        let selectedCount = 0;

        if (selectAllCheckbox.checked) {
            if (maxSeats === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาเลือกยานพาหนะ',
                    text: 'โปรดเลือกยานพาหนะก่อนเลือกนักเรียน',
                    confirmButtonText: 'ตกลง'
                });
                selectAllCheckbox.checked = false;
                return;
            }

            checkboxes.forEach((checkbox, index) => {
                if (selectedCount < maxSeats) {
                    checkbox.checked = true;
                    selectedCount++;
                } else {
                    checkbox.checked = false;
                }
            });

            if (checkboxes.length > maxSeats) {
                Swal.fire({
                    icon: 'warning',
                    title: 'จำนวนนักเรียนเกินจำนวนที่นั่ง',
                    text: `รถที่เลือกมี ${maxSeats} ที่นั่ง เลือกนักเรียนได้ ${maxSeats} คนจากทั้งหมด ${checkboxes.length} คน`,
                    confirmButtonText: 'ตกลง'
                });
            }
        } else {
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }
        updateSelectedCount();
    }

    function resetFilters() {
        datePicker.clear();
        const provinceSelect = document.getElementById('province');
        provinceSelect.value = '';
        const amphurSelect = document.getElementById('amphur');
        amphurSelect.innerHTML = '<option value="">เลือกอำเภอ</option>';
        const locationSelect = document.getElementById('location');
        locationSelect.innerHTML = '<option value="">เลือกจุดขึ้นรถ</option>';
        const carSelect = document.getElementById('car');
        carSelect.innerHTML = '<option value="">เลือกยานพาหนะ</option>';
        document.getElementById('driverInfo').innerHTML = 'ไม่มีการเลือกยานพาหนะ';
        maxSeats = 0;
        filterData();
        loadCars();
    }

    function confirmQueue() {
        const selectedStudents = document.querySelectorAll('input[name="students[]"]:checked').length;
        const datePickerValue = document.getElementById('date_picker').value;
        const carSelect = document.getElementById('car');
        const selectedCarId = carSelect.value;

        if (!selectedCarId) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาเลือกยานพาหนะ',
                text: 'โปรดเลือกยานพาหนะก่อนยืนยันการจัดคิว',
                confirmButtonText: 'ตกลง'
            });
            return;
        }

        if (!datePickerValue) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาเลือกวันที่',
                text: 'โปรดเลือกวันที่เดินทางก่อนยืนยันการจัดคิว',
                confirmButtonText: 'ตกลง'
            });
            return;
        }

        if (selectedStudents === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาเลือกนักเรียน',
                text: 'โปรดเลือกนักเรียนอย่างน้อย 1 คนก่อนยืนยันการจัดคิว',
                confirmButtonText: 'ตกลง'
            });
            return;
        }

        if (maxSeats > 0 && selectedStudents > maxSeats) {
            Swal.fire({
                icon: 'warning',
                title: 'จำนวนนักเรียนเกินจำนวนที่นั่ง',
                text: `รถที่เลือกมี ${maxSeats} ที่นั่ง แต่คุณเลือกนักเรียน ${selectedStudents} คน กรุณาเลือกไม่เกิน ${maxSeats} คน`,
                confirmButtonText: 'ตกลง'
            });
            return;
        }

        const selectedCar = carData.find(car => car.car_id == selectedCarId);
        const driverName = selectedCar ? `${selectedCar.driver_name || 'ไม่ระบุ'} ${selectedCar.driver_lastname || ''}`.trim() : 'ไม่ระบุ';
        const carLicense = selectedCar ? selectedCar.car_license || 'ไม่ระบุ' : 'ไม่ระบุ';
        const travelDate = datePickerValue ? datePickerValue : 'ไม่ระบุ';

        Swal.fire({
            title: 'ยืนยันการจัดคิว',
            html: `
                <p><strong>เลือกนักเรียน:</strong> ${selectedStudents} คน</p>
                <p><strong>วันที่เดินทาง:</strong> ${travelDate}</p>
                <p><strong>เลขทะเบียนรถ:</strong> ${carLicense}</p>
                <p><strong>ชื่อคนขับ:</strong> ${driverName}</p>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData(document.getElementById('queueForm'));
                fetch('save_queue.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    console.log("Raw response:", data);
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    const scripts = doc.getElementsByTagName('script');
                    for (let script of scripts) {
                        const scriptElement = document.createElement('script');
                        scriptElement.textContent = script.textContent;
                        document.body.appendChild(scriptElement);
                    }
                    if (data.includes('icon: "success"')) {
                        Swal.fire({
                            icon: 'success',
                            title: 'บันทึกข้อมูลสำเร็จ',
                            text: 'ข้อมูลคิวรถได้ถูกบันทึกเรียบร้อยแล้ว',
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            setTimeout(() => {
                                resetFilters();
                            }, 500);
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่',
                        confirmButtonText: 'ตกลง'
                    });
                    console.error('Fetch error:', error);
                });
            }
        });
    }

    window.onload = function() {
        filterData();
        loadCars();
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>