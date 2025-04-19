<?php
require_once './config/condb.php';

// ดึงข้อมูลการชำระเงิน
$stmt = $conn->prepare("SELECT * FROM payment_info WHERE id = 1");
$stmt->execute();
$payment_info = $stmt->fetch(PDO::FETCH_ASSOC);

// ถ้าไม่มีข้อมูล ให้ใช้ค่าเริ่มต้น
if (!$payment_info) {
    $payment_info = [
        'bank_name' => 'ธนาคารกสิกรไทย',
        'account_name' => 'บริษัท ตัวอย่าง จำกัด',
        'account_number' => '123-4-56789-0',
        'qr_code_path' => 'imgQR/default-qr-code.png'
    ];
}

// ตรวจสอบและแก้ไขพาธ QR Code
$qr_code_path = $payment_info['qr_code_path'];
// ตรวจสอบชื่อไฟล์ (ป้องกันชื่อที่ไม่สมบูรณ์ เช่น ..รูป)
if (empty($qr_code_path) || strpos($qr_code_path, '..') !== false || !preg_match('/\.(jpg|jpeg|png)$/i', $qr_code_path)) {
    $qr_code_path = 'imgQR/default-qr-code.png';
    $payment_info['qr_code_path'] = $qr_code_path;
    // อัปเดตฐานข้อมูลถ้าพาธไม่ถูกต้อง
    try {
        $stmt = $conn->prepare("UPDATE payment_info SET qr_code_path = ? WHERE id = 1");
        $stmt->execute([$qr_code_path]);
        error_log("Updated DB with default QR Code Path due to invalid path: " . $qr_code_path);
    } catch (PDOException $e) {
        error_log("Error updating DB with default QR Code Path: " . $e->getMessage());
    }
}

// แปลงพาธให้สัมพันธ์กับโครงสร้างโฟลเดอร์ Transport_management_system/admin/imgQR/
$base_path = realpath(__DIR__); // ใช้ __DIR__ เพื่อให้ได้พาธของโฟลเดอร์ admin/
$qr_code_full_path = $base_path . DIRECTORY_SEPARATOR . ltrim($qr_code_path, './');
$default_qr_path = $base_path . DIRECTORY_SEPARATOR . 'imgQR' . DIRECTORY_SEPARATOR . 'default-qr-code.png';

// ดีบัก: แสดงพาธที่คำนวณได้และผลการตรวจสอบไฟล์
error_log("Base Path: " . $base_path);
error_log("QR Code Path from DB: " . $qr_code_path);
error_log("QR Code Full Path: " . $qr_code_full_path);
error_log("File Exists (QR Code): " . (file_exists($qr_code_full_path) ? 'Yes' : 'No'));
error_log("Default QR Path: " . $default_qr_path);
error_log("File Exists (Default QR): " . (file_exists($default_qr_path) ? 'Yes' : 'No'));

// ตรวจสอบว่าไฟล์ QR Code มีอยู่จริงหรือไม่
if (file_exists($qr_code_full_path)) {
    // เข้ารหัสช่องว่างในชื่อไฟล์เพื่อให้ URL ถูกต้อง
    $encoded_path = implode('/', array_map('rawurlencode', explode('/', $qr_code_path)));
    $qr_code_display_path = '/project-tms/Transport_management_system/admin/' . $encoded_path;
} else {
    // ตรวจสอบว่าไฟล์เริ่มต้นมีอยู่จริงหรือไม่
    if (file_exists($default_qr_path)) {
        $qr_code_display_path = '/project-tms/Transport_management_system/admin/imgQR/default-qr-code.png';
        $payment_info['qr_code_path'] = 'imgQR/default-qr-code.png';
        // อัปเดตฐานข้อมูลถ้าไฟล์หลักไม่มี
        try {
            $stmt = $conn->prepare("UPDATE payment_info SET qr_code_path = ? WHERE id = 1");
            $stmt->execute([$payment_info['qr_code_path']]);
            error_log("Updated DB with default QR Code Path: " . $payment_info['qr_code_path']);
        } catch (PDOException $e) {
            error_log("Error updating DB with default QR Code Path: " . $e->getMessage());
        }
    } else {
        $qr_code_display_path = '';
        $payment_info['qr_code_path'] = 'imgQR/default-qr-code.png';
    }
}

// ดีบัก: แสดง URL ที่ใช้แสดงรูปภาพ
error_log("QR Code Display Path: " . $qr_code_display_path);

// อัปเดตข้อมูลเมื่อกดบันทึก
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bank_name = $_POST['bank_name'];
    $account_name = $_POST['account_name'];
    $account_number = $_POST['account_number'];

    // จัดการไฟล์ QR Code
    $qr_code_path = $payment_info['qr_code_path'];
    if (!empty($_FILES['qr_code']['name'])) {
        $qr_dir = './imgQR/';
        // ตรวจสอบว่าโฟลเดอร์ imgQR/ มีอยู่หรือไม่ ถ้าไม่มีให้สร้าง
        if (!is_dir($qr_dir)) {
            mkdir($qr_dir, 0755, true);
            error_log("Created directory: " . $qr_dir);
        }
        // เปลี่ยนชื่อไฟล์ให้ไม่มีช่องว่าง
        $original_filename = basename($_FILES['qr_code']['name']);
        $safe_filename = preg_replace('/[^A-Za-z0-9\.\-]/', '_', $original_filename);
        $qr_file = $qr_dir . $safe_filename;
        $qr_ext = strtolower(pathinfo($qr_file, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        if (in_array($qr_ext, $allowed_ext) && $_FILES['qr_code']['size'] <= 2 * 1024 * 1024) {
            if (move_uploaded_file($_FILES['qr_code']['tmp_name'], $qr_file)) {
                // อัปเดตพาธให้สัมพันธ์กับ admin/
                $qr_code_path = 'imgQR/' . $safe_filename;
                error_log("New QR Code Path after upload: " . $qr_code_path);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการอัปโหลด QR Code']);
                error_log("Failed to move uploaded file to: " . $qr_file);
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'QR Code ต้องเป็นไฟล์ JPG, JPEG, PNG และขนาดไม่เกิน 2MB']);
            exit();
        }
    }

    // อัปเดตข้อมูลในฐานข้อมูล
    try {
        $stmt = $conn->prepare("UPDATE payment_info SET bank_name = ?, account_name = ?, account_number = ?, qr_code_path = ? WHERE id = 1");
        $stmt->execute([$bank_name, $account_name, $account_number, $qr_code_path]);
        error_log("Database updated with QR Code Path: " . $qr_code_path);
        echo json_encode(['status' => 'success', 'message' => 'แก้ไขข้อมูลการชำระเงินสำเร็จ']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูล: ' . $e->getMessage()]);
        error_log("Error updating DB: " . $e->getMessage());
        exit();
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลการชำระเงิน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(to bottom, #f5f6f5, #e9ecef);
            min-height: 100vh;
            display: flex;
            margin: 0;
        }
        .sidebar {
            width: 250px;
            transition: transform 0.3s ease-in-out;
            position: fixed;
            height: 100%;
            overflow-y: auto;
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
        .container {
            padding: 30px;
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
        .card-header {
            background: #007bff;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: none;
            padding: 20px;
            position: relative;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }
        .card-header h3 {
            margin: 0;
            font-size: 1.5rem;
        }
        .card-body {
            padding: 30px;
        }
        .form-label {
            font-weight: 500;
            color: #444;
            font-size: 0.95rem;
        }
        .form-control {
            border-radius: 10px;
            border: 1px solid #ced4da;
            padding: 10px;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
            background: #fff;
        }
        .btn-primary {
            background: #007bff;
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: #0056b3;
            transform: scale(1.05);
        }
        .current-qr {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: transform 0.3s ease;
            cursor: pointer;
            margin-top: 10px;
        }
        .current-qr:hover {
            transform: scale(1.1);
        }
        .info-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .info-section p {
            margin: 5px 0;
            font-size: 0.95rem;
        }
        .info-section strong {
            color: #2c3e50;
        }
        .modal-content {
            border-radius: 15px;
            background: #fff;
            color: #2c3e50;
        }
        .modal-header {
            background: #007bff;
            color: #fff;
            border-bottom: none;
        }
        .modal-header .btn-close {
            filter: invert(1);
        }
        .modal-body img {
            border-radius: 10px;
            border: 2px solid #007bff;
        }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>
    <div class="content" id="content">
        <div class="container">
            <h2 class="header-title">แก้ไขข้อมูลการชำระเงิน</h2>

            <!-- ส่วนที่ 1: แสดงข้อมูลเก่า -->
            <div class="card shadow-lg">
                <div class="card-header">
                    <h3 class="text-center">ข้อมูลการชำระเงินปัจจุบัน</h3>
                </div>
                <div class="card-body">
                    <div class="info-section">
                        <p><strong>ชื่อธนาคาร:</strong> <?php echo htmlspecialchars($payment_info['bank_name']); ?></p>
                        <p><strong>ชื่อบัญชี:</strong> <?php echo htmlspecialchars($payment_info['account_name']); ?></p>
                        <p><strong>เลขที่บัญชี:</strong> <?php echo htmlspecialchars($payment_info['account_number']); ?></p>
                        <p><strong>QR Code ปัจจุบัน:</strong></p>
                        <?php if (!empty($qr_code_display_path)): ?>
                            <img src="<?php echo htmlspecialchars($qr_code_display_path); ?>" alt="QR Code" class="current-qr" id="current-qr-1">
                        <?php else: ?>
                            <p style="color: #dc3545;">ไม่พบรูปภาพ QR Code</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ 2: ฟอร์มแก้ไขข้อมูล -->
            <div class="card shadow-lg">
                <div class="card-header">
                    <h3 class="text-center">แก้ไขข้อมูลการชำระเงิน</h3>
                </div>
                <div class="card-body">
                    <form id="editPaymentForm" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">ชื่อธนาคาร</label>
                            <input type="text" class="form-control" name="bank_name" value="<?php echo htmlspecialchars($payment_info['bank_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ชื่อบัญชี</label>
                            <input type="text" class="form-control" name="account_name" value="<?php echo htmlspecialchars($payment_info['account_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">เลขที่บัญชี</label>
                            <input type="text" class="form-control" name="account_number" value="<?php echo htmlspecialchars($payment_info['account_number']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">QR Code ปัจจุบัน</label><br>
                            <?php if (!empty($qr_code_display_path)): ?>
                                <img src="<?php echo htmlspecialchars($qr_code_display_path); ?>" alt="QR Code" class="current-qr" id="current-qr-2">
                            <?php else: ?>
                                <p style="color: #dc3545;">ไม่พบรูปภาพ QR Code</p>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">อัปโหลด QR Code ใหม่ (ถ้าต้องการเปลี่ยน)</label>
                            <input type="file" class="form-control" name="qr_code" id="qr_code_input" accept="image/jpeg,image/jpg,image/png">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">บันทึกข้อมูล</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับแสดงรูป QR Code -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" style="display: none;" inert>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">ดู QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img id="modal-image" src="" class="img-fluid" alt="QR Code">
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {
        // Sidebar Toggle
        $('.close-btn').on('click', function() {
            $('.sidebar').addClass('closed');
            $('.content').addClass('closed');
        });

        $('.open-btn').on('click', function() {
            $('.sidebar').removeClass('closed');
            $('.content').removeClass('closed');
        });

        // คลิกที่รูป QR Code เพื่อเปิด Modal
        $('.current-qr').click(function() {
            var imageSrc = $(this).attr('src');
            $('#modal-image').attr('src', imageSrc);
            $('#imageModal').modal('show');
        });

        $('#imageModal').on('show.bs.modal', function() {
            $(this).removeAttr('inert');
        });

        $('#imageModal').on('hide.bs.modal', function() {
            $(this).attr('inert', '');
        });

        // จัดการการส่งฟอร์มด้วย AJAX และ SweetAlert2
        $('#editPaymentForm').on('submit', function(e) {
            e.preventDefault();

            // ดึงข้อมูลจากฟอร์ม
            var formData = new FormData(this);
            var bankName = formData.get('bank_name');
            var accountName = formData.get('account_name');
            var accountNumber = formData.get('account_number');
            var qrCodeFile = formData.get('qr_code');
            var currentQrSrc = $('#current-qr-2').attr('src') || '';

            // ฟังก์ชันสำหรับแปลงไฟล์ที่อัปโหลดเป็น URL (เพื่อแสดงตัวอย่าง)
            function getImageUrl(file) {
                return new Promise((resolve) => {
                    if (file && file.size > 0) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            resolve(e.target.result);
                        };
                        reader.readAsDataURL(file);
                    } else {
                        resolve('');
                    }
                });
            }

            // ดึง URL ของรูปใหม่ (ถ้ามี)
            getImageUrl(qrCodeFile).then(newQrSrc => {
                // สร้าง HTML สำหรับ SweetAlert
                var htmlContent = `
                    <p><strong>ชื่อธนาคาร:</strong> ${bankName}</p>
                    <p><strong>ชื่อบัญชี:</strong> ${accountName}</p>
                    <p><strong>เลขที่บัญชี:</strong> ${accountNumber}</p>
                `;
                
                // แสดง QR Code ปัจจุบัน
                if (currentQrSrc) {
                    htmlContent += `
                        <p><strong>QR Code ปัจจุบัน:</strong></p>
                        <img src="${currentQrSrc}" style="max-width: 150px; border-radius: 8px; margin-top: 10px;">
                    `;
                } else {
                    htmlContent += `
                        <p><strong>QR Code ปัจจุบัน:</strong> ไม่มีรูปภาพ</p>
                    `;
                }

                // แสดง QR Code ใหม่ (ถ้ามีการอัปโหลด)
                if (newQrSrc) {
                    htmlContent += `
                        <p><strong>QR Code ใหม่:</strong></p>
                        <img src="${newQrSrc}" style="max-width: 150px; border-radius: 8px; margin-top: 10px;">
                    `;
                } else {
                    htmlContent += `
                        <p><strong>QR Code ใหม่:</strong> ไม่มีการเปลี่ยนแปลง</p>
                    `;
                }

                // แสดง SweetAlert2 เพื่อยืนยันข้อมูล
                Swal.fire({
                    title: 'ยืนยันการแก้ไขข้อมูลการชำระเงิน?',
                    html: htmlContent,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // ส่งข้อมูลไปยังเซิร์ฟเวอร์ด้วย AJAX
                        $.ajax({
                            url: 'edit_payment_info.php',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        title: 'สำเร็จ!',
                                        text: response.message,
                                        icon: 'success',
                                        confirmButtonText: 'ตกลง'
                                    }).then(() => {
                                        // รีเฟรชหน้า
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'เกิดข้อผิดพลาด!',
                                        text: response.message,
                                        icon: 'error',
                                        confirmButtonText: 'ตกลง'
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    title: 'เกิดข้อผิดพลาด!',
                                    text: 'ไม่สามารถแก้ไขข้อมูลได้: ' + (xhr.responseJSON?.message || xhr.responseText),
                                    icon: 'error',
                                    confirmButtonText: 'ตกลง'
                                });
                            }
                        });
                    }
                });
            });
        });
    });
    </script>
</body>
</html>