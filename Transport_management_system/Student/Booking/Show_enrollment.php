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
               ts.id AS schedule_id,   -- ดึง ID ของ available_dates
               p.province_name,
               a.amphur_name,
               ts.available_dates,
               ts.month AS schedule_month -- เพิ่มค่าเดือนจาก transport_schedule
        FROM transport_registration tr
        LEFT JOIN routes r ON tr.route_id = r.route_ID
        LEFT JOIN transport_schedule ts ON tr.transport_schedule_id = ts.id
        LEFT JOIN province p ON r.province = p.province_id
        LEFT JOIN amphur a ON r.amphur = a.amphur_id
        WHERE tr.stu_username = :stu_username
        ORDER BY tr.created_at DESC"; 
    // Prepare, bind, and execute the query
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':stu_username', $stu_username, PDO::PARAM_STR);
        $stmt->execute();
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle error here
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
    <style>
        body {
            background: linear-gradient(135deg, #1c2526 0%, #2c3e50 100%);
            font-family: 'Chakra Petch', sans-serif;
            min-height: 100vh;
            margin: 0;
            position: relative;
            overflow-x: hidden;
            padding-top: 80px; /* ป้องกันเนื้อหาทับกับ Navbar */
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
        .receipt-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            border: 2px solid #ffca28;
            transition: transform 0.3s ease;
            cursor: pointer;
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
            .btn-primary {
                padding: 8px 16px;
                font-size: 0.9rem;
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
            .btn-primary {
                padding: 6px 14px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="text-center mb-3">ประวัติการลงทะเบียน</h2>

    <div class="d-flex justify-content-end mb-3">
        <a href="enrollment.php" class="btn btn-primary">+ ลงทะเบียนใหม่</a>
    </div>

    <div class="row">
        <?php if (!empty($registrations)): ?>
            <?php foreach ($registrations as $row): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <!-- Month -->
                            <p><strong>เดือน:</strong> <?php echo getMonthName(htmlspecialchars($row['schedule_month'])) ?></p>

                            <!-- Created At -->
                            <p><strong>วันที่ลงทะเบียน:</strong> <?php echo date("d/m/Y H:i", strtotime($row['created_at'])) ?></p>

                            <!-- Days -->
                            <p><strong>จำนวนวันที่ขึ้นรถ:</strong> <?php echo htmlspecialchars($row['num_of_days']) ?> วัน</p>

                            <!-- Available Dates -->
                            <p><strong>วันที่:</strong> <?php echo htmlspecialchars($row['available_dates']) ?></p>

                            <!-- จังหวัด -->
                            <p><strong>จังหวัด:</strong> <?php echo htmlspecialchars($row['province_name']) ?></p>

                            <!-- อำเภอ -->
                            <p><strong>อำเภอ:</strong> <?php echo htmlspecialchars($row['amphur_name']) ?></p>

                            <!-- สถานที่ขึ้นรถ -->
                            <p><strong>สถานที่ขึ้นรถ:</strong> <?php echo htmlspecialchars($row['route_location']) ?></p>

                            <!-- Total Price -->
                            <p><strong>ราคารวม:</strong> <?php echo number_format(htmlspecialchars($row['total_price']), 2) ?> ฿</p>

                            <!-- Payment Status -->
                            <p><strong>สถานะการชำระเงิน:</strong>
                                <span class="badge bg-<?php echo $row['payment_status'] == 'Paid' ? 'success' : 'danger' ?>">
                                    <?php echo htmlspecialchars($row['payment_status']) ?>
                                </span>
                            </p>

                            <div class="mb-2">
                                <?php 
                                    $imagePath = $row['payment_receipt_image'] ? "./" . htmlspecialchars($row['payment_receipt_image']) : '';
                                    // ตรวจสอบว่าไฟล์มีอยู่จริงหรือไม่
                                    $fileExists = $imagePath && file_exists($imagePath);
                                ?>
                                <?php if (!empty($row['payment_receipt_image']) && $fileExists): ?>
                                    <!-- Image to trigger the modal -->
                                    <img src="<?= $imagePath ?>" alt="ใบเสร็จ" class="receipt-img" data-image="<?= $imagePath ?>">
                                <?php else: ?>
                                    <span class="text-muted">ไม่มีภาพ (ไฟล์ไม่พบ)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center text-muted">ไม่พบข้อมูลการลงทะเบียน</p>
        <?php endif; ?>
    </div>

    <!-- Single Modal for all images -->
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the modal once
    const modalElement = document.getElementById('receiptModal');
    const modalImage = document.getElementById('modalImage');
    const bsModal = new bootstrap.Modal(modalElement);
    let isModalOpen = false;
    let lastClickTime = 0;
    const debounceDelay = 300; // Debounce delay in milliseconds

    // Event delegation for all receipt images
    document.querySelector('.container').addEventListener('click', function(event) {
        if (!event.target.classList.contains('receipt-img')) return;

        // Debounce: Ignore rapid successive clicks
        const currentTime = new Date().getTime();
        if (currentTime - lastClickTime < debounceDelay) {
            console.log('Click debounced, ignoring...');
            return;
        }
        lastClickTime = currentTime;

        // Prevent opening if modal is already open
        if (isModalOpen) {
            console.log('Modal is already open, ignoring click...');
            return;
        }

        const imageSrc = event.target.getAttribute('data-image');
        if (!imageSrc) {
            console.error('Image source not found for element:', event.target);
            return;
        }

        // Set the image source and show the modal
        modalImage.src = imageSrc;
        bsModal.show();
        isModalOpen = true;
        console.log('Modal opened with image:', imageSrc);
    });

    // Handle modal close
    modalElement.addEventListener('hidden.bs.modal', function() {
        modalImage.src = ''; // Clear the image source
        isModalOpen = false;
        console.log('Modal closed');
    });

    // Handle modal show (for debugging)
    modalElement.addEventListener('show.bs.modal', function() {
        console.log('Modal is being shown');
    });
});
</script>
</body>
</html>