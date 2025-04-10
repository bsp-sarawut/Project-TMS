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

// ดึงข้อมูลการตั้งค่าจากฐานข้อมูล
try {
    $stmt = $conn->prepare("SELECT * FROM transport_schedule ORDER BY year DESC, month DESC");
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
    $schedules = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการการตั้งค่าการขึ้นรถ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            font-family: 'Kanit', sans-serif; 
            background: #f5f6f5; 
            min-height: 100vh; 
            margin: 0;
            display: flex;
        }
        .sidebar { 
            width: 250px; 
            transition: width 0.3s ease-in-out; 
        }
        .sidebar.closed { 
            width: 0; 
            overflow: hidden; 
        }
        .content { 
            margin-left: 250px; 
            padding: 20px; 
            flex-grow: 1; 
            transition: margin-left 0.3s ease-in-out; 
        }
        .content.expanded { 
            margin-left: 0; 
        }
        .card { 
            border-radius: 10px; 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); 
            background: #fff; 
            padding: 15px; 
            margin-bottom: 20px; 
        }
        .card h3 { 
            color: #333; 
            font-weight: 600; 
            border-bottom: 1px solid #e0e0e0; 
            padding-bottom: 5px; 
            margin-bottom: 15px; 
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
            background: #007bff; 
            border: none; 
            border-radius: 8px; 
            padding: 8px 20px; 
            transition: background 0.3s ease; 
        }
        .btn-primary:hover { 
            background: #0056b3; 
        }
        .btn-warning { 
            background: #f39c12; 
            border: none; 
            border-radius: 5px; 
            padding: 5px 15px; 
            color: #fff; 
        }
        .btn-danger { 
            background: #e74c3c; 
            border: none; 
            border-radius: 5px; 
            padding: 5px 15px; 
            color: #fff; 
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
            text-align: center; 
        }
        .total-count { 
            font-size: 1.1rem; 
            color: #333; 
            margin-bottom: 10px; 
            text-align: left;
        }
        .modal-content { 
            border-radius: 10px; 
        }
        .modal-header { 
            background: #007bff; 
            color: #fff; 
            border-radius: 10px 10px 0 0; 
        }
        .open-btn { 
            position: fixed; 
            top: 10px; 
            left: 10px; 
            z-index: 1000; 
            background: #007bff; 
            color: #fff; 
            border: none; 
            border-radius: 5px; 
            padding: 6px 12px; 
            cursor: pointer; 
            display: none; 
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
            .open-btn { 
                display: block; 
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="text-center mb-4" style="color: #333; font-weight: 600;">ระบบจัดตารางลงทะเบียน</h2>
            <?php
            if (isset($_SESSION['success'])) {
                echo "<script>Swal.fire({icon: 'success', title: 'สำเร็จ', text: '{$_SESSION['success']}', confirmButtonColor: '#3085d6'});</script>";
                unset($_SESSION['success']);
            } elseif (isset($_SESSION['error'])) {
                echo "<script>Swal.fire({icon: 'error', title: 'เกิดข้อผิดพลาด', text: '{$_SESSION['error']}', confirmButtonColor: '#d33'});</script>";
                unset($_SESSION['error']);
            }
            ?>

            <div class="card">
                <h3 class="mb-3">จัดการการตั้งค่าการขึ้นรถ</h3>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                            <i class="bi bi-plus-circle me-2"></i>เพิ่มการตั้งค่าการขึ้นรถ
                        </button>
                        <div class="mb-3">
                            <label for="filter_month" class="form-label">เลือกเดือน:</label>
                            <select id="filter_month" class="form-select" style="width: 200px;">
                                <option value="">-- แสดงทั้งหมด --</option>
                                <?php for ($m = 1; $m <= 12; $m++) { ?>
                                    <option value="<?php echo $m; ?>"><?php echo getThaiMonth($m); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <div class="total-count">จำนวนรายการทั้งหมด: <span id="totalItems"><?php echo count($schedules); ?></span></div>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ปี</th>
                                    <th>เดือน</th>
                                    <th>จำนวนวันที่เปิดให้ลงทะเบียน</th>
                                    <th>วันที่ขึ้นรถ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleTable">
                                <?php
                                if ($schedules) {
                                    foreach ($schedules as $schedule) {
                                        $available_dates = explode(", ", $schedule['available_dates']);
                                        echo "<tr>";
                                        echo "<td>{$schedule['year']}</td>";
                                        echo "<td>" . getThaiMonth($schedule['month']) . "</td>";
                                        echo "<td>{$schedule['num_of_days']}</td>";
                                        echo "<td>" . implode(", ", $available_dates) . "</td>";
                                        echo "<td>
                                                <button type='button' class='btn btn-warning btn-sm me-2' data-bs-toggle='modal' data-bs-target='#editScheduleModal' 
                                                    data-id='{$schedule['id']}' data-year='{$schedule['year']}' data-month='{$schedule['month']}' 
                                                    data-dates='" . implode(", ", $available_dates) . "' data-num_days='{$schedule['num_of_days']}'>
                                                    <i class='bi bi-pencil-square'></i> แก้ไข
                                                </button>
                                                <button type='button' class='btn btn-danger btn-sm' onclick='confirmDelete({$schedule['id']})'>
                                                    <i class='bi bi-trash'></i> ลบ
                                                </button>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>ไม่มีข้อมูลการตั้งค่า</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มการตั้งค่าการขึ้นรถ -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScheduleModalLabel">เพิ่มการตั้งค่าการขึ้นรถ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="choice_insert.php" id="addScheduleForm">
                        <div class="mb-3">
                            <label for="year" class="form-label">เลือกปี:</label>
                            <select name="year" id="year" class="form-select" required>
                                <?php for ($i = date("Y"); $i <= date("Y") + 5; $i++) { ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="month" class="form-label">เลือกเดือน:</label>
                            <select name="month" id="month" class="form-select" required>
                                <?php for ($m = 1; $m <= 12; $m++) { ?>
                                    <option value="<?php echo $m; ?>"><?php echo getThaiMonth($m); ?></option>
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
                        <button type="submit" name="submit" class="btn btn-primary w-100">บันทึกการตั้งค่า</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขการตั้งค่าการขึ้นรถ -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editScheduleModalLabel">แก้ไขการตั้งค่าการขึ้นรถ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="choice_update.php" id="editScheduleForm">
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
                        <button type="submit" name="submit" class="btn btn-primary w-100">บันทึกการแก้ไข</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <button class="open-btn" id="open-btn">☰</button>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Sidebar Toggle
        document.getElementById('close-btn')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('closed');
            document.querySelector('.content').classList.add('expanded');
            document.getElementById('open-btn').style.display = 'block';
        });

        document.getElementById('open-btn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('closed');
            document.querySelector('.content').classList.remove('expanded');
            this.style.display = 'none';
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
        document.addEventListener('DOMContentLoaded', initializeAddFlatpickr);

        // Flatpickr สำหรับแก้ไขการตั้งค่า
        let editFlatpickrInstance;
        const editScheduleModal = document.getElementById('editScheduleModal');
        editScheduleModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const year = button.getAttribute('data-year');
            const month = button.getAttribute('data-month');
            const availableDates = button.getAttribute('data-dates').split(", ");
            const numDays = button.getAttribute('data-num_days');

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_year').value = year;
            document.getElementById('edit_month').value = month;
            document.getElementById('edit_num_of_days').value = numDays;

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

        // การกรองตารางตามเดือน
        $(document).ready(function() {
            $("#filter_month").change(function() {
                const selectedMonth = $(this).val();
                $("#scheduleTable tr").each(function() {
                    const rowMonth = $(this).find("td:nth-child(2)").text().trim();
                    const rowMonthValue = getMonthValue(rowMonth);
                    if (selectedMonth === "" || rowMonthValue === selectedMonth) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                updateTotalItems();
            });

            function getMonthValue(monthName) {
                const thaiMonths = {
                    "มกราคม": "1", "กุมภาพันธ์": "2", "มีนาคม": "3", "เมษายน": "4",
                    "พฤษภาคม": "5", "มิถุนายน": "6", "กรกฎาคม": "7", "สิงหาคม": "8",
                    "กันยายน": "9", "ตุลาคม": "10", "พฤศจิกายน": "11", "ธันวาคม": "12"
                };
                return thaiMonths[monthName] || "";
            }

            function updateTotalItems() {
                const visibleRows = $("#scheduleTable tr:visible").length;
                $("#totalItems").text(visibleRows);
            }
        });

        // SweetAlert2 สำหรับการลบ
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
                    window.location.href = 'choice_delete.php?id=' + id;
                }
            });
        }
    </script>
</body>
</html>