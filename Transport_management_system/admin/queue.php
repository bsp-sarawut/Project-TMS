<?php

require_once 'config/condb.php';

// ดึงข้อมูลเดือนและปีจาก transport_schedule โดยเรียงจากปีและเดือนล่าสุด
$scheduleQuery = $conn->query("SELECT DISTINCT month, year FROM transport_schedule ORDER BY year DESC, month DESC");
$schedules = $scheduleQuery->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลจังหวัดจาก province
$provinceQuery = $conn->query("SELECT * FROM province");
$provinces = $provinceQuery->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลรถจาก car
$carQuery = $conn->query("SELECT * FROM car ORDER BY driver_id ASC");
$cars = $carQuery->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูล transport_registration
$registrationQuery = $conn->query("SELECT tr.*, s.stu_name, s.stu_lastname FROM transport_registration tr 
    LEFT JOIN students s ON tr.stu_username = s.stu_username");
$registrations = $registrationQuery->fetchAll(PDO::FETCH_ASSOC);

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
");
$registrations = $registrationQuery->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบค่าของ available_dates
$available_dates = isset($_POST['available_dates']) ? $_POST['available_dates'] : '';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคิวรถ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.0/dist/sweetalert2.all.min.js"></script>
</head>
<body>
<div class="container mt-4">
    <h2 class="text-center mb-4">จัดการคิวรถ</h2>
    <form method="post" action="save_queue.php">
        <div class="card p-3">
            <h3>เลือกข้อมูลสำหรับคิวรถ</h3>
            <div class="row mb-3">
                <!-- ปฏิทินเลือกวันที่ -->
                <div class="col-md-4 col-12 mt-2">
                    <label for="date_picker" class="form-label">เลือกวันที่</label>
                    <input type="text" id="date_picker" name="date_picker" class="form-control" placeholder="เลือกวันที่">
                </div>

                <!-- เลือกจังหวัด -->
                <div class="col-md-4 col-12 mt-2">
                    <label for="province" class="form-label">จังหวัด</label>
                    <select name="province" id="province" class="form-select" onchange="loadAmphur()">
                        <option value="">เลือกจังหวัด</option>
                        <?php foreach ($provinces as $province): ?>
                            <option value="<?= $province['PROVINCE_ID'] ?>">
                                <?= $province['PROVINCE_NAME'] ?>
                            </option>
                        <?php endforeach; ?> 
                    </select>
                </div>

                <!-- เลือกอำเภอ -->
                <div class="col-md-4 col-12 mt-2">
                    <label for="amphur" class="form-label">อำเภอ</label>
                    <select name="amphur" id="amphur" class="form-select" onchange="loadLocation()">
                        <option value="">เลือกอำเภอ</option>
                    </select>
                </div>

                <!-- เลือกจุดขึ้นรถ -->
                <div class="col-md-4 col-12 mt-2">
                    <label for="location" class="form-label">จุดขึ้นรถ</label>
                    <select name="location" id="location" class="form-select">
                        <option value="">เลือกจุดขึ้นรถ</option>
                    </select>
                </div>

                <!-- เลือกยานพาหนะ -->
                <div class="col-md-4 col-12 mt-2">
                    <label for="car" class="form-label">เลือกยานพาหนะ</label>
                    <select name="car" id="car" class="form-select">
                        <?php foreach ($cars as $car): ?>
                            <option value="<?= $car['car_id'] ?>">
                                <?= $car['car_license'] ?> - <?= $car['car_brand'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="card p-3 mt-3">
            <h3>เลือกนักเรียนสำหรับคิวนี้</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>เลือก</th>
                        <th>ID</th>
                        <th>เดือน</th>
                        <th>Available Dates</th>
                        <th>ชื่อ - นามสกุล</th>
                        <th>จังหวัด</th>
                        <th>จุดขึ้นรถ</th>
                        <th>อำเภอ</th>
                        <th>สถานะการชำระเงิน</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- ข้อมูลนักเรียนจะถูกเพิ่มโดย JS -->
                </tbody>
            </table>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-success btn-lg">บันทึกคิว</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script>
    // ตั้งค่า Flatpickr
    flatpickr("#date_picker", {
        mode: "range",
        dateFormat: "Y-m-d",
        locale: "th",
        onClose: filterData
    });

    function loadAmphur() {
        var provinceID = document.getElementById('province').value;
        var amphurSelect = document.getElementById('amphur');
        amphurSelect.innerHTML = '<option value="">เลือกอำเภอ</option>';
        document.getElementById('location').innerHTML = '<option value="">เลือกจุดขึ้นรถ</option>';

        console.log("Selected Province ID:", provinceID);

        if (provinceID) {
            var xhr = new XMLHttpRequest();
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
        var provinceID = document.getElementById('province').value;
        var amphurID = document.getElementById('amphur').value;
        var locationSelect = document.getElementById('location');
        locationSelect.innerHTML = '<option value="">เลือกจุดขึ้นรถ</option>';

        console.log("Province ID:", provinceID);
        console.log("Amphur ID:", amphurID);

        if (provinceID && amphurID) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', `get_location.php?province_id=${provinceID}&amphur_id=${amphurID}`, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    locationSelect.innerHTML += xhr.responseText;
                    filterData(); // เรียก filterData หลังจากโหลดข้อมูลเสร็จแล้ว
                }
            };
            xhr.send();
        } else {
            console.log("ยังไม่ได้เลือกจังหวัดหรืออำเภอ");
        }
    }

    function filterData() {
        const datePicker = document.getElementById('date_picker').value;
        const provinceID = document.getElementById('province').value;
        const amphurID = document.getElementById('amphur').value;
        const location = document.getElementById('location').value;

        console.log("Date:", datePicker);
        console.log("Province ID:", provinceID);
        console.log("Amphur ID:", amphurID);
        console.log("Location:", location);

        const queryParams = new URLSearchParams({
            date_picker: datePicker,
            province_id: provinceID,
            amphur_id: amphurID,
            location: location
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
        tbody.innerHTML = '';

        if (registrations.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center">ไม่พบข้อมูล</td></tr>';
            return;
        }

        registrations.forEach(reg => {
            const paymentStatus = reg.payment_status === 'Pending Confirmation' ? '✅ ชำระแล้ว' : '❌ ยังไม่ชำระ';
            const row = `
                <tr>
                    <td><input type="checkbox" name="students[]" value="${reg.stu_ID}"></td>
                    <td>${reg.stu_ID}</td>
                    <td>${reg.month}</td>
                    <td>${reg.available_dates}</td>
                    <td>${reg.stu_name} ${reg.stu_lastname}</td>
                    <td>${reg.province_name}</td>
                    <td>${reg.amphur_name}</td>
                    <td>${reg.location}</td>
                    <td>${paymentStatus}</td>
                </tr>`;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    }

    // โหลดข้อมูลเริ่มต้นเมื่อหน้าเปิด
    window.onload = filterData;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</body>
</html>
