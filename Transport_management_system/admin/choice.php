<?php
include('config/condb.php');
session_start();

// ฟังก์ชันแปลงเลขเดือนเป็นชื่อเดือนภาษาไทย
function getThaiMonth($month) {
    $thaiMonths = [
        1 => "มกราคม",
        2 => "กุมภาพันธ์",
        3 => "มีนาคม",
        4 => "เมษายน",
        5 => "พฤษภาคม",
        6 => "มิถุนายน",
        7 => "กรกฎาคม",
        8 => "สิงหาคม",
        9 => "กันยายน",
        10 => "ตุลาคม",
        11 => "พฤศจิกายน",
        12 => "ธันวาคม"
    ];
    return $thaiMonths[$month];
}

// ดึงข้อมูลการตั้งค่าจากฐานข้อมูล
$stmt = $conn->prepare("SELECT * FROM transport_schedule ORDER BY year DESC, month DESC");
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- เชื่อมโยงไฟล์ Bootstrap 5 และ SweetAlert2 -->
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?> <!-- เรียกใช้ sidebar.php -->

    <!-- ฟอร์มสำหรับแอดมิน -->
    <div class="content" id="content">
        <div class="container mt-5">
            <?php
            
            // แสดงข้อความแจ้งเตือนสำเร็จหรือข้อผิดพลาด
            if (isset($_SESSION['success'])) {
                echo "<script>Swal.fire('สำเร็จ', '{$_SESSION['success']}', 'success');</script>";
                unset($_SESSION['success']); // ลบ session หลังจากแสดงผล
            } elseif (isset($_SESSION['error'])) {
                echo "<script>Swal.fire('เกิดข้อผิดพลาด', '{$_SESSION['error']}', 'error');</script>";
                unset($_SESSION['error']); // ลบ session หลังจากแสดงผล
            }
            ?>
            <h2>จัดการการตั้งค่าการขึ้นรถ</h2>

            <!-- ปุ่มเปิด Modal -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                เพิ่มการตั้งค่าการขึ้นรถ
            </button>

            <!-- Modal สำหรับเพิ่มการตั้งค่าการขึ้นรถ -->
            <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addScheduleModalLabel">เพิ่มการตั้งค่าการขึ้นรถ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- ฟอร์มเพิ่มการตั้งค่าการขึ้นรถ -->
                            <form method="POST" action="choice_insert.php" id="addScheduleForm">
                                <!-- เลือกปี -->
                                <div class="mb-3">
                                    <label for="year" class="form-label">เลือกปี:</label>
                                    <select name="year" id="year" class="form-select" required>
                                        <?php
                                        // สร้างตัวเลือกปี โดยเริ่มจากปีปัจจุบัน
                                        for ($i = date("Y"); $i <= date("Y") + 5; $i++) {
                                            echo "<option value='$i'>$i</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- เลือกเดือน -->
                                <div class="mb-3">
                                    <label for="month" class="form-label">เลือกเดือน:</label>
                                    <select name="month" id="month" class="form-select" required>
                                        <option value="1">มกราคม</option>
                                        <option value="2">กุมภาพันธ์</option>
                                        <option value="3">มีนาคม</option>
                                        <option value="4">เมษายน</option>
                                        <option value="5">พฤษภาคม</option>
                                        <option value="6">มิถุนายน</option>
                                        <option value="7">กรกฎาคม</option>
                                        <option value="8">สิงหาคม</option>
                                        <option value="9">กันยายน</option>
                                        <option value="10">ตุลาคม</option>
                                        <option value="11">พฤศจิกายน</option>
                                        <option value="12">ธันวาคม</option>
                                    </select>
                                </div>

                                <!-- เลือกวันที่ขึ้นรถ -->
                                <div class="mb-3">
                                    <label for="available_dates" class="form-label">เลือกวันที่ขึ้นรถ (เลือกหลายวันได้):</label>
                                    <input type="text" name="available_dates[]" id="available_dates" class="form-control" multiple required>
                                </div>

                                <!-- จำนวนวันที่เปิดให้ลงทะเบียน -->
                                <div class="mb-3">
                                    <label for="num_of_days" class="form-label">จำนวนวันที่เปิดให้ลงทะเบียน:</label>
                                    <input type="number" name="num_of_days" id="num_of_days" class="form-control" required min="1" max="31">
                                </div>

                                <button type="submit" name="submit" class="btn btn-primary">บันทึกการตั้งค่า</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <!-- เพิ่มช่องค้นหา -->
<div class="mb-3">
    <label for="filter_month" class="form-label">เลือกเดือน:</label>
    <select id="filter_month" class="form-select">
        <option value="">-- แสดงทั้งหมด --</option>
        <option value="1">มกราคม</option>
        <option value="2">กุมภาพันธ์</option>
        <option value="3">มีนาคม</option>
        <option value="4">เมษายน</option>
        <option value="5">พฤษภาคม</option>
        <option value="6">มิถุนายน</option>
        <option value="7">กรกฎาคม</option>
        <option value="8">สิงหาคม</option>
        <option value="9">กันยายน</option>
        <option value="10">ตุลาคม</option>
        <option value="11">พฤศจิกายน</option>
        <option value="12">ธันวาคม</option>
    </select>
</div>

<!-- เพิ่ม JavaScript สำหรับการกรองข้อมูล -->
<script>
$(document).ready(function() {
    $("#filter_month").change(function() {
        var selectedMonth = $(this).val();
        
        $("tbody tr").each(function() {
            var rowMonth = $(this).find("td:nth-child(2)").text().trim();
            var rowMonthValue = getMonthValue(rowMonth);

            if (selectedMonth === "" || rowMonthValue == selectedMonth) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    function getMonthValue(monthName) {
        var thaiMonths = {
            "มกราคม": 1, "กุมภาพันธ์": 2, "มีนาคม": 3, "เมษายน": 4, 
            "พฤษภาคม": 5, "มิถุนายน": 6, "กรกฎาคม": 7, "สิงหาคม": 8, 
            "กันยายน": 9, "ตุลาคม": 10, "พฤศจิกายน": 11, "ธันวาคม": 12
        };
        return thaiMonths[monthName] || "";
    }
});
</script>

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
                <tbody>
                    <?php
                    if ($schedules) {
                        foreach ($schedules as $schedule) {
                            $available_dates = explode(", ", $schedule['available_dates']);
                            echo "<tr>";
                            echo "<td>" . $schedule['year'] . "</td>";
                            echo "<td>" . getThaiMonth($schedule['month']) . "</td>"; // แสดงเดือนเป็นภาษาไทย
                            echo "<td>" . $schedule['num_of_days'] . "</td>";
                            echo "<td>" . implode(", ", $available_dates) . "</td>";
                            echo "<td>
                                    <!-- ปุ่มเปิด Modal สำหรับแก้ไข -->
                                    <button type='button' class='btn btn-warning btn-sm' data-bs-toggle='modal' data-bs-target='#editScheduleModal' data-id='".$schedule['id']."' data-year='".$schedule['year']."' data-month='".$schedule['month']."' data-dates='".implode(", ", $available_dates)."' data-num_days='".$schedule['num_of_days']."'>แก้ไข</button> | 
                                    <a href='#' onclick='confirmDelete(" . $schedule['id'] . ")' class='btn btn-danger btn-sm'>ลบ</a>   
                                  </td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
            <!-- ส่วนสรุปข้อมูล -->

            <p>จำนวนรายการทั้งหมด: <span id="totalItems"><?php echo count($schedules); ?></span></p>
        </div>
    </div>

    <!-- Modal สำหรับแก้ไขการตั้งค่าการขึ้นรถ -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editScheduleModalLabel">แก้ไขการตั้งค่าการขึ้นรถ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- ฟอร์มแก้ไขการตั้งค่าการขึ้นรถ -->
                    <form method="POST" action="choice_update.php" id="editScheduleForm">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_year" class="form-label">เลือกปี:</label>
                            <select name="year" id="edit_year" class="form-select" required>
                                <?php
                                // สร้างตัวเลือกปี โดยเริ่มจากปีปัจจุบัน
                                for ($i = date("Y"); $i <= date("Y") + 5; $i++) {
                                    echo "<option value='$i'>$i</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit_month" class="form-label">เลือกเดือน:</label>
                            <select name="month" id="edit_month" class="form-select" required>
                                <option value="1">มกราคม</option>
                                <option value="2">กุมภาพันธ์</option>
                                <option value="3">มีนาคม</option>
                                <option value="4">เมษายน</option>
                                <option value="5">พฤษภาคม</option>
                                <option value="6">มิถุนายน</option>
                                <option value="7">กรกฎาคม</option>
                                <option value="8">สิงหาคม</option>
                                <option value="9">กันยายน</option>
                                <option value="10">ตุลาคม</option>
                                <option value="11">พฤศจิกายน</option>
                                <option value="12">ธันวาคม</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit_available_dates" class="form-label">เลือกวันที่ขึ้นรถ:</label>
                            <input type="text" name="available_dates[]" id="edit_available_dates" class="form-control" multiple required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_num_of_days" class="form-label">จำนวนวันที่เปิดให้ลงทะเบียน:</label>
                            <input type="number" name="num_of_days" id="edit_num_of_days" class="form-control" required min="1" max="31">
                        </div>

                        <button type="submit" name="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- เพิ่มการใช้งาน Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
    // Initializing flatpickr for date selection in the add modal
    const availableDatesInput = flatpickr("#available_dates", {
        mode: "multiple",
        dateFormat: "d",  // วันที่แสดงในรูปแบบ Y-m-d
        onChange: function(selectedDates, dateStr, instance) {
            // นับจำนวนวันที่เลือก
            const numOfDays = selectedDates.length;
            // อัปเดตค่าในฟิลด์ "จำนวนวันที่เปิดให้ลงทะเบียน"
            document.getElementById('num_of_days').value = numOfDays;
        }
    });

    // เมื่อเปิด Modal สำหรับการแก้ไขข้อมูล
    const editScheduleModal = document.getElementById('editScheduleModal');
    editScheduleModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // ปุ่มที่คลิกเปิด modal
        const id = button.getAttribute('data-id');
        const year = button.getAttribute('data-year');
        const month = button.getAttribute('data-month');
        const availableDates = button.getAttribute('data-dates').split(", ");
        const numDays = button.getAttribute('data-num_days');

        // กำหนดค่าให้กับฟอร์ม
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_year').value = year;
        document.getElementById('edit_month').value = month;
        document.getElementById('edit_num_of_days').value = numDays;

        // รีเซ็ต flatpickr และตั้งค่าหมายเลขวันที่ที่เลือกจากข้อมูล
        const editAvailableDatesInput = flatpickr("#edit_available_dates", {
            mode: "multiple",
            dateFormat: "d",  // วันที่แสดงในรูปแบบ Y-m-d
            defaultDate: availableDates
        });
    });

    function confirmDelete(id) {
    Swal.fire({
        title: 'คุณแน่ใจหรือไม่?',
        text: 'คุณต้องการลบข้อมูลนี้หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            // ถ้ายืนยันการลบ
            window.location.href = 'choice_delete.php?id=' + id;
        }
    });
}
    </script>
</body>
