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
                                <?php if (!empty($row['payment_receipt_image'])): ?>
                                    <?php 
                                        $imagePath = "./" . htmlspecialchars($row['payment_receipt_image']);
                                    ?>
                                    <!-- Image to trigger the modal -->
                                    <img src="<?= $imagePath ?>" alt="ใบเสร็จ" class="receipt-img" data-image="<?= $imagePath ?>" data-modal-id="imageModal-<?php echo htmlspecialchars($row['id']); ?>">

                                    <!-- Modal for each registration -->
                                    <div class="modal fade" id="imageModal-<?php echo htmlspecialchars($row['id']); ?>" tabindex="-1" aria-labelledby="imageModalLabel-<?php echo htmlspecialchars($row['id']); ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="imageModalLabel-<?php echo htmlspecialchars($row['id']); ?>">ภาพใบเสร็จ</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <img id="modalImage-<?php echo htmlspecialchars($row['id']); ?>" src="" alt="ใบเสร็จ" class="img-fluid">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">ไม่มีภาพ</span>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Track currently open modal to prevent multiple modals from opening
    let currentModal = null;

    // Select all receipt images
    const receiptImages = document.querySelectorAll('.receipt-img');

    receiptImages.forEach(function(image) {
        // Remove any existing event listeners to prevent duplicates
        image.removeEventListener('click', handleImageClick);
        image.addEventListener('click', handleImageClick);
    });

    function handleImageClick() {
        // If a modal is already open, do not open another one
        if (currentModal) {
            console.log('Modal already open, skipping...');
            return;
        }

        const imageSrc = this.getAttribute('data-image');
        const modalId = this.getAttribute('data-modal-id');
        const modal = document.querySelector(`#${modalId}`);

        if (!modal) {
            console.error('Modal not found for ID:', modalId);
            return;
        }

        const modalImage = modal.querySelector('img');
        if (modalImage) {
            modalImage.src = imageSrc; // Set the image source in the modal
        } else {
            console.error('Modal image element not found in modal:', modalId);
            return;
        }

        // Initialize and show the modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        currentModal = bsModal;

        console.log('Modal opened:', modalId);

        // When the modal is hidden, clear the currentModal
        modal.addEventListener('hidden.bs.modal', function handler() {
            if (modalImage) {
                modalImage.src = ''; // Clear the image source when modal is closed
            }
            currentModal = null;
            console.log('Modal closed:', modalId);
            // Remove this event listener to prevent accumulation
            modal.removeEventListener('hidden.bs.modal', handler);
        });
    }
});
</script>
</body>
</html>