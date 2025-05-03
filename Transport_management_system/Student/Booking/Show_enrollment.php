<?php
require_once 'condb.php';
include 'navbar.php';

$stu_username = $_SESSION['user_name']; // ดึงค่าผู้ใช้ที่ล็อกอินอยู่

// ฟังก์ชันแปลงหมายเลขเดือนเป็นชื่อเดือนภาษาไทย
function getMonthName($monthNumber)
{
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
    ];
    return $months[$monthNumber] ?? 'ไม่ทราบเดือน';
}

// ดึงข้อมูลจากฐานข้อมูลโดย JOIN ตาราง routes และ transport_schedule
$sql = "SELECT tr.*,
           r.province AS route_province_id,
           r.amphur AS route_amphur_id,
           r.location AS route_location,
           ts.id AS schedule_id,
           p.province_name,
           a.amphur_name,
           ts.available_dates,
           ts.month AS schedule_month
    FROM transport_registration tr
    LEFT JOIN routes r ON tr.route_id = r.route_ID
    LEFT JOIN transport_schedule ts ON tr.transport_schedule_id = ts.id
    LEFT JOIN province p ON r.province = p.province_id
    LEFT JOIN amphur a ON r.amphur = a.amphur_id
    WHERE tr.stu_username = :stu_username
    ORDER BY tr.created_at DESC";
try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':stu_username', $stu_username, PDO::PARAM_STR);
    $stmt->execute();
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql_provinces = "SELECT DISTINCT p.province_name 
                      FROM transport_registration tr
                      LEFT JOIN routes r ON tr.route_id = r.route_ID
                      LEFT JOIN province p ON r.province = p.province_id
                      WHERE tr.stu_username = :stu_username
                      ORDER BY p.province_name";
    $stmt_provinces = $conn->prepare($sql_provinces);
    $stmt_provinces->bindParam(':stu_username', $stu_username, PDO::PARAM_STR);
    $stmt_provinces->execute();
    $provinces = $stmt_provinces->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการลงทะเบียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1c2526 0%, #2c3e50 100%);
            font-family: 'Chakra Petch', sans-serif;
            min-height: 100vh;
            margin: 0;
            position: relative;
            overflow-x: hidden;
            padding-top: 80px;
            color: #eceff1;
        }
        .container {
            padding-top: 20px;
            padding-bottom: 50px;
        }
        h2 {
            color: #ffca28;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 30px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #ffca28, #ff8f00);
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            font-weight: 600;
            color: #1c2526;
            transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #ff8f00, #ffca28);
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(255, 202, 40, 0.4);
        }
        .card {
            border: none;
            border-radius: 15px;
            background: #263238;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3), 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease, background 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #ffca28, #ff8f00);
            transition: height 0.3s ease;
            z-index: 0;
            pointer-events: none;
        }
        .card:hover::before {
            height: 100%;
            opacity: 0.2;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 4px 12px rgba(0, 0, 0, 0.3);
            background: #2c3e50;
        }
        .card-body {
            padding: 25px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #b0bec5;
            position: relative;
            z-index: 1;
        }
        .card-body p {
            margin-bottom: 10px;
            font-size: 1rem;
        }
        .card-body strong {
            color: #ffca28;
            font-weight: 600;
        }
        .badge {
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 15px;
        }
        .badge.bg-success {
            background: #4caf50 !important;
        }
        .badge.bg-danger {
            background: #ef5350 !important;
        }
        .badge.bg-warning {
            background: #ffca28 !important;
            color: #1c2526 !important;
        }
        .receipt-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            border: 2px solid #ffca28;
            transition: transform 0.3s ease;
            cursor: pointer;
            position: relative;
            z-index: 2;
        }
        .receipt-img:hover {
            transform: scale(1.1);
        }
        .text-muted {
            color: #b0bec5 !important;
        }
        .modal-content {
            border-radius: 15px;
            background: #263238;
            color: #eceff1;
        }
        .modal-header {
            background: linear-gradient(45deg, #ffca28, #ff8f00);
            color: #1c2526;
            border-bottom: none;
        }
        .modal-header .btn-close {
            filter: invert(1);
        }
        .modal-body img {
            border-radius: 10px;
            border: 2px solid #ffca28;
        }
        .search-toggle {
            background: #ffca28;
            color: #1c2526;
            border: none;
            border-radius: 5px;
            padding: 8px 20px;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s ease;
        }
        .search-toggle:hover {
            background: #ffb300;
        }
        .filter-section {
            background: #2c3e50;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        .filter-section.active {
            display: flex;
        }
        .filter-section label {
            color: #ffca28;
            font-weight: 600;
            margin-right: 10px;
        }
        .filter-section input,
        .filter-section select {
            background: #37474f;
            color: #eceff1;
            border: 1px solid #ffca28;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 0.9rem;
        }
        .filter-section input[type="date"] {
            width: 150px;
        }
        .filter-section input:focus,
        .filter-section select:focus {
            outline: none;
            border-color: #17a2b8;
        }
        .filter-section .btn-reset {
            background: #dc3545;
            color: #eceff1;
            border: none;
            border-radius: 5px;
            padding: 5px 15px;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }
        .filter-section .btn-reset:hover {
            background: #c82333;
        }
        .upload-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ffca28;
            position: relative;
            z-index: 2;
        }
        .upload-form input[type="file"] {
            background: #37474f;
            color: #eceff1;
            border: 1px solid #ffca28;
            border-radius: 5px;
            padding: 5px;
        }
        .upload-form .btn-upload {
            background: #4caf50;
            color: #eceff1;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }
        .upload-form .btn-upload:hover {
            background: #388e3c;
        }
        @media (max-width: 768px) {
            .container {
                padding-top: 10px;
                padding-bottom: 40px;
            }
            .card-body {
                padding: 20px;
            }
            .card-body p {
                font-size: 0.9rem;
            }
            .badge {
                font-size: 0.8rem;
            }
            .receipt-img {
                width: 50px;
                height: 50px;
            }
            .btn-primary,
            .search-toggle {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-section label {
                margin-bottom: 5px;
            }
            .filter-section input,
            .filter-section select {
                width: 100%;
            }
            .filter-section input[type="date"] {
                width: 100%;
            }
            .upload-form input[type="file"] {
                width: 100%;
            }
        }
        @media (max-width: 576px) {
            h2 {
                font-size: 1.5rem;
            }
            .card-body p {
                font-size: 0.85rem;
            }
            .badge {
                font-size: 0.75rem;
            }
            .receipt-img {
                width: 45px;
                height: 45px;
            }
            .btn-primary,
            .search-toggle {
                padding: 6px 14px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="text-center mb-3">ประวัติการลงทะเบียน</h2>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="enrollment.php" class="btn btn-primary">+ ลงทะเบียนใหม่</a>
        <?php if (!empty($registrations)): ?>
            <button class="search-toggle" onclick="toggleFilterSection()">
                <i class="fas fa-search"></i> ค้นหา
            </button>
        <?php endif; ?>
    </div>

    <?php if (!empty($registrations)): ?>
        <div class="filter-section" id="filter-section">
            <div>
                <label for="filter-date-start">วันที่ลงทะเบียน (เริ่ม):</label>
                <input type="date" id="filter-date-start">
            </div>
            <div>
                <label for="filter-date-end">วันที่ลงทะเบียน (สิ้นสุด):</label>
                <input type="date" id="filter-date-end">
            </div>
            <div>
                <label for="filter-month">เดือน:</label>
                <select id="filter-month">
                    <option value="">ทั้งหมด</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo getMonthName($i); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="filter-province">จังหวัด:</label>
                <select id="filter-province">
                    <option value="">ทั้งหมด</option>
                    <?php foreach ($provinces as $province): ?>
                        <option value="<?php echo htmlspecialchars($province); ?>">
                            <?php echo htmlspecialchars($province); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter-status">สถานะการชำระเงิน:</label>
                <select id="filter-status">
                    <option value="">ทั้งหมด</option>
                    <option value="Paid">Paid</option>
                    <option value="Pending Confirmation">Pending Confirmation</option>
                    <option value="Upload again!">Upload again</option>
                </select>
            </div>
            <button class="btn-reset" onclick="resetFilters()">รีเซ็ต</button>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if (!empty($registrations)): ?>
            <?php foreach ($registrations as $row): ?>
                <div class="col-md-4 mb-4 registration-card"
                     data-date="<?php echo htmlspecialchars($row['created_at']); ?>"
                     data-month="<?php echo htmlspecialchars($row['schedule_month']); ?>"
                     data-province="<?php echo htmlspecialchars($row['province_name']); ?>"
                     data-status="<?php echo htmlspecialchars($row['payment_status']); ?>">
                    <div class="card">
                        <div class="card-body">
                            <p><strong>เดือน:</strong> <?php echo getMonthName(htmlspecialchars($row['schedule_month'])) ?></p>
                            <p><strong>วันที่ลงทะเบียน:</strong> <?php echo date("d/m/Y H:i", strtotime($row['created_at'])) ?></p>
                            <p><strong>จำนวนวันที่ขึ้นรถ:</strong> <?php echo htmlspecialchars($row['num_of_days']) ?> วัน</p>
                            <p><strong>วันที่:</strong> <?php echo htmlspecialchars($row['available_dates']) ?></p>
                            <p><strong>จังหวัด:</strong> <?php echo htmlspecialchars($row['province_name']) ?></p>
                            <p><strong>อำเภอ:</strong> <?php echo htmlspecialchars($row['amphur_name']) ?></p>
                            <p><strong>สถานที่ขึ้นรถ:</strong> <?php echo htmlspecialchars($row['route_location']) ?></p>
                            <p><strong>ราคารวม:</strong> <?php echo number_format(htmlspecialchars($row['total_price']), 2) ?> ฿</p>
                            <p><strong>สถานะการชำระเงิน:</strong>
                                <span class="badge bg-<?php 
                                    echo $row['payment_status'] == 'Paid' ? 'success' : 
                                         ($row['payment_status'] == 'Upload again!' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo htmlspecialchars($row['payment_status']) ?>
                                </span>
                            </p>
                            <div class="mb-2">
                                <?php 
                                    $imagePath = $row['payment_receipt_image'] ? "./" . htmlspecialchars($row['payment_receipt_image']) : '';
                                    $fileExists = $imagePath && file_exists($imagePath);
                                ?>
                                <?php if (!empty($row['payment_receipt_image']) && $fileExists): ?>
                                    <img src="<?= $imagePath ?>" alt="ใบเสร็จ" class="receipt-img" data-image="<?= $imagePath ?>">
                                <?php else: ?>
                                    <span class="text-muted">ไม่มีภาพ (ไฟล์ไม่พบ)</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($row['payment_status'] == 'Upload again!'): ?>
                                <div class="upload-form">
                                    <form class="receipt-upload-form" enctype="multipart/form-data">
                                        <input type="hidden" name="registration_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                        <div class="mb-2">
                                            <label for="receipt_image_<?php echo $row['id']; ?>" class="form-label text-muted">อัปโหลดใบเสร็จใหม่:</label>
                                            <input type="file" name="receipt_image" id="receipt_image_<?php echo $row['id']; ?>" accept="image/*" required>
                                        </div>
                                        <button type="submit" class="btn btn-upload">อัปโหลด</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center text-muted">ไม่พบข้อมูลการลงทะเบียน</p>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">ภาพใบเสร็จ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img id="modalImage" src="" alt="ใบเสร็จ" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('receiptModal');
    const modalImage = document.getElementById('modalImage');
    const bsModal = new bootstrap.Modal(modalElement);
    let isModalOpen = false;
    let lastClickTime = 0;
    const debounceDelay = 300;

    document.querySelector('.container').addEventListener('click', function(event) {
        if (!event.target.classList.contains('receipt-img')) return;

        const currentTime = new Date().getTime();
        if (currentTime - lastClickTime < debounceDelay) {
            console.log('Click debounced, ignoring...');
            return;
        }
        lastClickTime = currentTime;

        if (isModalOpen) {
            console.log('Modal is already open, ignoring click...');
            return;
        }

        const imageSrc = event.target.getAttribute('data-image');
        if (!imageSrc) {
            console.error('Image source not found for element:', event.target);
            return;
        }

        modalImage.src = imageSrc;
        bsModal.show();
        isModalOpen = true;
        console.log('Modal opened with image:', imageSrc);
    });

    modalElement.addEventListener('hidden.bs.modal', function() {
        modalImage.src = '';
        isModalOpen = false;
        console.log('Modal closed');
    });

    modalElement.addEventListener('show.bs.modal', function() {
        console.log('Modal is being shown');
    });

    $('.receipt-upload-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const formData = new FormData(this);
        const card = form.closest('.registration-card');

        $.ajax({
            url: 'upload_receipt.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: 'อัปโหลดใบเสร็จสำเร็จ! สถานะเปลี่ยนเป็น Pending Confirmation',
                        confirmButtonText: 'ตกลง'
                    });
                    // Update receipt image
                    form.closest('.card').find('.receipt-img').attr('src', './' + response.image_path).attr('data-image', './' + response.image_path);
                    // Update status badge
                    form.closest('.card').find('.badge')
                        .removeClass('bg-warning bg-success bg-danger')
                        .addClass('bg-danger')
                        .text(response.payment_status);
                    // Update card data-status for filtering
                    card.attr('data-status', response.payment_status);
                    // Remove upload form
                    form.closest('.upload-form').remove();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        text: response.message,
                        confirmButtonText: 'ตกลง'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาด',
                    text: 'เกิดข้อผิดพลาดในการอัปโหลด: ' + (xhr.responseJSON?.message || 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์'),
                    confirmButtonText: 'ตกลง'
                });
            }
        });
    });

    window.toggleFilterSection = function() {
        const filterSection = document.getElementById('filter-section');
        filterSection.classList.toggle('active');
    };

    function filterRegistrations() {
        const dateStart = document.getElementById('filter-date-start').value;
        const dateEnd = document.getElementById('filter-date-end').value;
        const month = document.getElementById('filter-month').value;
        const province = document.getElementById('filter-province').value;
        const status = document.getElementById('filter-status').value;

        const cards = document.querySelectorAll('.registration-card');

        cards.forEach(card => {
            const cardDate = new Date(card.getAttribute('data-date'));
            const cardMonth = card.getAttribute('data-month');
            const cardProvince = card.getAttribute('data-province');
            const cardStatus = card.getAttribute('data-status');

            let matchesDate = true;
            if (dateStart || dateEnd) {
                const start = dateStart ? new Date(dateStart) : new Date('1970-01-01');
                const end = dateEnd ? new Date(dateEnd) : new Date('9999-12-31');
                end.setHours(23, 59, 59, 999);
                matchesDate = cardDate >= start && cardDate <= end;
            }

            const matchesMonth = !month || cardMonth === month;
            const matchesProvince = !province || cardProvince === province;
            const matchesStatus = !status || cardStatus === status;

            if (matchesDate && matchesMonth && matchesProvince && matchesStatus) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    document.getElementById('filter-date-start')?.addEventListener('change', filterRegistrations);
    document.getElementById('filter-date-end')?.addEventListener('change', filterRegistrations);
    document.getElementById('filter-month')?.addEventListener('change', filterRegistrations);
    document.getElementById('filter-province')?.addEventListener('change', filterRegistrations);
    document.getElementById('filter-status')?.addEventListener('change', filterRegistrations);

    window.resetFilters = function() {
        document.getElementById('filter-date-start').value = '';
        document.getElementById('filter-date-end').value = '';
        document.getElementById('filter-month').value = '';
        document.getElementById('filter-province').value = '';
        document.getElementById('filter-status').value = '';
        filterRegistrations();
    };
});
</script>
</body>
</html>