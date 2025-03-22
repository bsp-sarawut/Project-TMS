<?php
require_once 'config/condb.php';

// ดึงข้อมูลเดือนและปีจาก transport_schedule
$scheduleQuery = $conn->query("SELECT DISTINCT month, year FROM transport_schedule ORDER BY year DESC, month DESC");
$schedules = $scheduleQuery->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลจังหวัดจาก province
$provinceQuery = $conn->query("SELECT * FROM province");
$provinces = $provinceQuery->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลรถจาก car (จะถูกแทนที่ด้วย AJAX ใน JS)
$carQuery = $conn->query("SELECT * FROM car ORDER BY driver_id ASC");
$cars = $carQuery->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูล transport_registration
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
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f5f6f5;
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
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
        .btn-success {
            border-radius: 8px;
            padding: 10px 30px;
            font-size: 1.1rem;
            background: #007bff;
            border: none;
            transition: background 0.3s ease;
        }
        .btn-success:hover {
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
            padding: 12px;
        }
        .table tbody tr:hover {
            background: #f9f9f9;
        }
        .table td {
            vertical-align: middle;
        }
        .badge-success {
            background: #28a745;
        }
        .badge-danger {
            background: #dc3545;
        }
        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="content" id="content">
    <div class="container mt-4">
        <h2 class="text-center mb-4" style="color: #333; font-weight: 600;">ระบบจัดการคิวรถ</h2>
        <form method="post" action="save_queue.php" id="queueForm">
            <div class="card mb-4">
                <h3 class="mb-3">ข้อมูลสำหรับการจัดคิวรถ</h3>
                <div class="row g-3">
                    <!-- ปฏิทินเลือกวันที่ -->
                    <div class="col-md-4 col-12">
                        <label for="date_picker" class="form-label">วันที่</label>
                        <input type="text" id="date_picker" name="date_picker" class="form-control" placeholder="เลือกวันที่">
                    </div>

                    <!-- เลือกจังหวัด -->
                    <div class="col-md-4 col-12">
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
                    <div class="col-md-4 col-12">
                        <label for="amphur" class="form-label">อำเภอ</label>
                        <select name="amphur" id="amphur" class="form-select" onchange="loadLocation()">
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
                        <label for="car" class="form-label">ยานพาหนะ</label>
                        <select name="car" id="car" class="form-select">
                            <option value="">เลือกยานพาหนะ</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <h3 class="mb-3">รายชื่อนักเรียนสำหรับคิวนี้</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>เลือก</th>
                                <th>รหัสนักเรียน</th>
                                <th>เดือน</th>
                                <th>วันที่ว่าง</th>
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
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-success">บันทึกข้อมูลคิว</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script>
    // ตั้งค่า Flatpickr
    flatpickr("#date_picker", {
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

    function loadCars() {
        const datePicker = document.getElementById('date_picker').value;
        const carSelect = document.getElementById('car');
        if (!datePicker) {
            carSelect.innerHTML = '<option value="">เลือกยานพาหนะ</option>';
            return;
        }

        const date = datePicker.split(" to ")[0];
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `get_available_cars.php?date=${date}`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const cars = JSON.parse(xhr.responseText);
                carSelect.innerHTML = '<option value="">เลือกยานพาหนะ</option>';
                cars.forEach(car => {
                    carSelect.innerHTML += `<option value="${car.car_id}">${car.car_license} - ${car.car_brand}</option>`;
                });
            }
        };
        xhr.send();
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
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">ไม่พบข้อมูล</td></tr>';
            return;
        }

        registrations.forEach(reg => {
            const paymentStatus = reg.payment_status === 'Pending Confirmation' 
                ? '<span class="badge badge-success">ชำระแล้ว</span>' 
                : '<span class="badge badge-danger">ยังไม่ชำระ</span>';
            const row = `
                <tr>
                    <td><input type="checkbox" name="students[]" value="${reg.stu_ID}"></td>
                    <td>${reg.stu_ID}</td>
                    <td>${reg.month || 'ไม่ระบุ'}</td>
                    <td>${reg.available_dates || 'ไม่ระบุ'}</td>
                    <td>${reg.stu_name} ${reg.stu_lastname}</td>
                    <td>${reg.province_name || 'ไม่ระบุ'}</td>
                    <td>${reg.amphur_name || 'ไม่ระบุ'}</td>
                    <td>${reg.location || 'ไม่ระบุ'}</td>
                    <td>${paymentStatus}</td>
                </tr>`;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    }

    // การส่งฟอร์มด้วย SweetAlert
    document.getElementById('queueForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
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
                        filterData();
                        loadCars();
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
    });

    // โหลดข้อมูลเริ่มต้น
    window.onload = function() {
        filterData();
        loadCars();
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>