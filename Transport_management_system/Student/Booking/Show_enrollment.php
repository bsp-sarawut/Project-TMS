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
    <title>My Transport Registrations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .receipt-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
    </style>
</head>
<body>
<div class="container mt-5"><br>
    <h2 class="text-center mb-3">ประวัติการลงทะเบียน</h2>

    <div class="d-flex justify-content-end mb-3">
        <a href="enrollment.php" class="btn btn-primary">+ ลงทะเบียนใหม่</a>
    </div>

    <div class="row">
        <?php if (! empty($registrations)): ?>
            <?php foreach ($registrations as $row): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <!-- Month -->
                            <p><strong>เดือน :</strong> <?php echo getMonthName(htmlspecialchars($row['schedule_month'])) ?></p>

                            <!-- Created At -->
                            <p><strong>วันที่ลงทะเบียน :</strong> <?php echo date("d/m/Y H:i", strtotime($row['created_at'])) ?></p>

                            <!-- Days -->
                            <p><strong>จำนวนวันที่ขึ้นรถ :</strong> <?php echo htmlspecialchars($row['num_of_days']) ?> วัน</p>

                            <!-- Available Dates -->
                            <p><strong>วันที่ :</strong> <?php echo htmlspecialchars($row['available_dates']) ?></p>

                            <!-- จังหวัด -->
                            <p><strong>จังหวัด:</strong> <?php echo htmlspecialchars($row['province_name']) ?></p>

                            <!-- อำเภอ -->
                            <p><strong>อำเภอ:</strong> <?php echo htmlspecialchars($row['amphur_name']) ?></p>

                            <!-- สถานที่ขึ้นรถ -->
                            <p><strong>สถานที่ขึ้นรถ :</strong> <?php echo htmlspecialchars($row['route_location']) ?></p>

                            <!-- Total Price -->
                            <p><strong>ราคารวม :</strong> <?php echo number_format(htmlspecialchars($row['total_price']), 2) ?> ฿</p>

                            <!-- Payment Status -->
                            <p><strong>Payment Status:</strong>
                                <span class="badge bg-<?php echo $row['payment_status'] == 'Paid' ? 'success' : 'danger' ?>">
                                    <?php echo htmlspecialchars($row['payment_status']) ?>
                                </span>
                            </p>

                            <div class="mb-2">
                                <?php if (!empty($row['payment_receipt_image'])): ?>
                                    <?php 
                                        $imagePath = "./" . htmlspecialchars($row['payment_receipt_image']);
                                    ?>
                                    <!-- Link to open the modal -->
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#imageModal" data-bs-image="<?= $imagePath ?>">
                                        <img src="<?= $imagePath ?>" alt="Receipt" class="receipt-img">
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No Image</span>
                                <?php endif; ?>
                            </div>

                            <!-- Modal -->
                            <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="imageModalLabel">Receipt Image</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <!-- Image will be shown here -->
                                            <img id="modalImage" src="" alt="Receipt" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center text-muted">No registrations found.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Use JavaScript to set the image source for the modal
    var imageModal = document.getElementById('imageModal');
    imageModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Button that triggered the modal
        var imageSrc = button.getAttribute('data-bs-image'); // Get the image source
        var modalImage = imageModal.querySelector('#modalImage'); // Find the image element inside the modal
        modalImage.src = imageSrc; // Set the image source in the modal
    });
</script>
</body>
</html>
