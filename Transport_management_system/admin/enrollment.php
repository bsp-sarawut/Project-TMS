<?php
require_once 'config/condb.php';

// ดึงข้อมูลจังหวัดสำหรับ dropdown
$provinceQuery = $conn->query("SELECT * FROM province");
$provinces = $provinceQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการข้อมูลการลงทะเบียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
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
            border-radius: 10px;
            border: 1px solid #ced4da;
            padding: 10px;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }
        .form-select:focus, .form-control:focus {
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
        .btn-success {
            background: #28a745;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            color: #fff;
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            background: #218838;
            transform: scale(1.05);
        }
        .btn-danger {
            background: #e74c3c;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            color: #fff;
            transition: all 0.3s ease;
        }
        .btn-danger:hover {
            background: #c0392b;
            transform: scale(1.05);
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: scale(1.05);
        }
        .btn-info {
            background: #17a2b8;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            color: #fff;
            transition: all 0.3s ease;
        }
        .btn-info:hover {
            background: #138496;
            transform: scale(1.05);
        }
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            width: 100%;
            min-width: 900px;
        }
        .table thead th {
            background: #003087;
            color: #fff;
            text-align: center;
            padding: 15px 20px;
            font-weight: 500;
            font-size: 0.95rem;
            white-space: nowrap;
        }
        .table tbody tr {
            transition: background 0.2s ease;
        }
        .table tbody tr:hover {
            background: #f1f8ff;
        }
        .table td {
            vertical-align: middle;
            text-align: center;
            padding: 12px 20px;
            font-size: 0.9rem;
            word-break: break-word;
        }
        .table th:nth-child(1), .table td:nth-child(1) { /* ชื่อ-นามสกุล */
            min-width: 150px;
        }
        .table th:nth-child(2), .table td:nth-child(2) { /* จังหวัด */
            min-width: 100px;
        }
        .table th:nth-child(3), .table td:nth-child(3) { /* อำเภอ */
            min-width: 100px;
        }
        .table th:nth-child(4), .table td:nth-child(4) { /* สถานที่ */
            min-width: 120px;
        }
        .table th:nth-child(5), .table td:nth-child(5) { /* ข้อมูลเพิ่มเติม */
            min-width: 120px;
        }
        .table th:nth-child(6), .table td:nth-child(6) { /* สถานะ */
            min-width: 200px;
        }
        .table th:nth-child(7), .table td:nth-child(7) { /* จัดการ */
            min-width: 100px;
        }
        .total-count {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .search-section {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .search-section:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        .search-container {
            margin-bottom: 20px;
        }
        .search-container .form-control {
            border-radius: 25px;
            padding: 12px 20px 12px 45px;
            border: 1px solid #ced4da;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        .search-container .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }
        .search-input-group {
            position: relative;
        }
        .search-input-group .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
            font-size: 1.1rem;
        }
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filter-row .col-md-3 {
            flex: 1;
            min-width: 200px;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .img-preview {
            max-width: 100%;
            max-height: 300px;
            width: 300px;
            height: auto;
            object-fit: contain;
            border-radius: 8px;
            margin-top: 10px;
            border: 2px solid #007bff;
        }
        .modal-dialog {
            max-width: 600px;
            margin: 1.75rem auto;
        }
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            background: #007bff;
            color: #fff;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 15px;
        }
        .modal-title {
            font-weight: 600;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-body p {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        .modal-body strong {
            color: #2c3e50;
        }
        .modal-footer {
            border-top: none;
            padding: 10px 20px;
        }
        .alert-info {
            background-color: #e7f3ff;
            border-color: #b6d4fe;
            color: #0c5460;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
        }
        .alert-info ul {
            margin: 0;
            padding-left: 15px;
        }
        .alert-info li {
            margin-bottom: 3px;
            font-size: 1rem;
        }
        .pagination {
            justify-content: center;
            margin-top: 25px;
        }
        .pagination .page-item .page-link {
            border-radius: 8px;
            margin: 0 5px;
            color: #007bff;
            transition: all 0.3s ease;
        }
        .pagination .page-item.active .page-link {
            background: #007bff;
            border-color: #007bff;
            color: #fff;
        }
        .pagination .page-item .page-link:hover {
            background: #e9ecef;
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
            .filter-row {
                flex-direction: column;
            }
            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }
            .action-buttons .btn {
                width: 100%;
            }
            .header-title {
                font-size: 1.5rem;
            }
            .table-container {
                margin-left: -15px;
                margin-right: -15px;
            }
            .table thead th {
                padding: 10px 15px;
                font-size: 0.85rem;
            }
            .table td {
                padding: 8px 15px;
                font-size: 0.85rem;
            }
            .modal-dialog {
                max-width: 90%;
                margin: 1rem auto;
            }
            .modal-content {
                max-height: 90vh;
            }
            .img-preview {
                width: 250px;
                max-height: 250px;
            }
            .modal-body p {
                font-size: 0.9rem;
            }
            .alert-info li {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="header-title">จัดการข้อมูลการลงทะเบียน</h2>

            <!-- แสดงข้อความแจ้งเตือน -->
            <?php if (isset($_SESSION['success'])) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: '<?php echo $_SESSION['success']; ?>',
                            confirmButtonText: 'ตกลง',
                            allowOutsideClick: false,
                            focusConfirm: false
                        });
                    });
                </script>
                <?php unset($_SESSION['success']); ?>
            <?php } elseif (isset($_SESSION['error'])) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'ข้อผิดพลาด',
                            text: '<?php echo $_SESSION['error']; ?>',
                            confirmButtonText: 'ตกลง',
                            allowOutsideClick: false,
                            focusConfirm: false
                        });
                    });
                </script>
                <?php unset($_SESSION['error']); ?>
            <?php } ?>

            <!-- ส่วนที่ 1: ฟอร์มค้นหา -->
            <div class="card mb-4 search-section">
                <h3 class="mb-3">ค้นหาข้อมูลการลงทะเบียน</h3>
                <div class="search-container">
                    <label for="search_input" class="form-label">ค้นหาข้อมูลการลงทะเบียน</label>
                    <div class="search-input-group">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" id="search_input" class="form-control" placeholder="ค้นหา: ชื่อ, นามสกุล, สถานที่, วันที่ลงทะเบียน">
                    </div>
                </div>
                <div class="filter-row">
                    <div class="col-md-3 col-12">
                        <label for="payment_status_filter" class="form-label">สถานะการชำระเงิน</label>
                        <select id="payment_status_filter" class="form-select">
                            <option value="">-- แสดงทั้งหมด --</option>
                            <option value="Pending Confirmation">Pending Confirmation</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="province_filter" class="form-label">จังหวัด</label>
                        <select id="province_filter" class="form-select" onchange="loadAmphur()">
                            <option value="">-- แสดงทั้งหมด --</option>
                            <?php foreach ($provinces as $province): ?>
                                <option value="<?php echo $province['PROVINCE_ID']; ?>">
                                    <?php echo htmlspecialchars($province['PROVINCE_NAME']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="amphur_filter" class="form-label">อำเภอ</label>
                        <select id="amphur_filter" class="form-select" onchange="loadLocation()">
                            <option value="">-- แสดงทั้งหมด --</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="location_filter" class="form-label">จุดขึ้นรถ</label>
                        <select id="location_filter" class="form-select">
                            <option value="">-- แสดงทั้งหมด --</option>
                        </select>
                    </div>
                </div>
                <div class="action-buttons">
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-undo me-2"></i>เคลียร์ฟิลเตอร์
                    </button>
                </div>
            </div>

            <!-- ส่วนที่ 2: รายการการลงทะเบียน -->
            <div class="card mb-4">
                <h3 class="mb-3">ข้อมูลการลงทะเบียนทั้งหมด</h3>
                <div class="total-count">จำนวนการลงทะเบียนทั้งหมด: <span id="totalItems">0</span></div>
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ชื่อ-นามสกุล</th>
                                <th>จังหวัด</th>
                                <th>อำเภอ</th>
                                <th>จุดขึ้นรถ</th>
                                <th>ข้อมูลเพิ่มเติม</th>
                                <th>สถานะ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="registrationTable">
                            <tr><td colspan="7" class="text-center text-muted">กำลังโหลดข้อมูล...</td></tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <nav aria-label="Page navigation">
                    <ul class="pagination" id="pagination">
                        <!-- Pagination จะถูกโหลดด้วย AJAX -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal แสดงข้อมูลเพิ่มเติม -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">ข้อมูลเพิ่มเติม</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>วันที่ลงทะเบียน:</strong> <span id="modalCreatedAt"></span></p>
                    <p><strong>จำนวนวัน:</strong> <span id="modalNumOfDays"></span></p>
                    <p><strong>วันที่ขึ้นรถ:</strong> <span id="modalAvailableDates"></span></p>
                    <p><strong>ราคารวม:</strong> <span id="modalTotalPrice"></span></p>
                    <p><strong>ใบเสร็จ:</strong></p>
                    <div id="modalReceiptImage" class="text-center"></div>
                    <div class="alert alert-info mt-3">
                        <strong>คำแนะนำการสแกน QR Code:</strong>
                        <ul>
                            <li>หยิบมือถือของคุณและเปิดแอปธนาคาร (เช่น K PLUS, SCB EASY)</li>
                            <li>ใช้ฟีเจอร์ "สแกน QR Code" ในแอปเพื่อสแกน QR Code บนใบเสร็จด้านบน</li>
                            <li>ตรวจสอบรายละเอียดการโอน เช่น จำนวนเงิน วันที่ และเลขที่บัญชี</li>
                            <li>หากสแกนแล้วไม่พบข้อมูล หรือข้อมูลไม่ตรงกัน อาจเป็นใบเสร็จปลอม กรุณาติดต่อธนาคารทันที</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script>
        let searchTimeout;
        let lastScrollPosition = 0;
        let currentPage = 1;

        // ฟังก์ชันเก็บตำแหน่ง scroll
        function saveScrollPosition() {
            lastScrollPosition = window.scrollY || window.pageYOffset;
        }

        // ฟังก์ชันเลื่อนกลับไปยังตำแหน่งเดิม
        function restoreScrollPosition() {
            window.scrollTo({
                top: lastScrollPosition,
                behavior: 'instant'
            });
        }

        // โหลดข้อมูลการลงทะเบียน
        function loadRegistrations(page = 1) {
            currentPage = page;
            saveScrollPosition(); // บันทึกตำแหน่ง scroll ก่อนโหลดข้อมูล

            const search = $('#search_input').val();
            const payment_status = $('#payment_status_filter').val();
            const province = $('#province_filter').val();
            const amphur = $('#amphur_filter').val();
            const location = $('#location_filter').val();

            $.ajax({
                url: 'fetch_registrations.php',
                type: 'POST',
                data: {
                    search: search,
                    payment_status: payment_status,
                    province: province,
                    amphur: amphur,
                    location: location,
                    page: page
                },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: data.error,
                            confirmButtonText: 'ตกลง',
                            allowOutsideClick: false,
                            focusConfirm: false,
                            willClose: () => {
                                restoreScrollPosition();
                            }
                        });
                        $('#registrationTable').html('<tr><td colspan="7" class="text-center text-muted">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
                        $('#totalItems').text(0);
                        $('#pagination').html('');
                        restoreScrollPosition();
                        return;
                    }

                    const registrations = data.registrations;
                    const totalRows = data.totalRows;
                    const totalPages = data.totalPages;
                    let html = '';

                    if (registrations.length > 0) {
                        registrations.forEach(reg => {
                            html += `
                                <tr>
                                    <td>${reg.stu_name} ${reg.stu_lastname}</td>
                                    <td>${reg.PROVINCE_NAME || '-'}</td>
                                    <td>${reg.AMPHUR_NAME || '-'}</td>
                                    <td>${reg.location || '-'}</td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm view-details-btn"
                                            data-created-at="${new Date(reg.created_at).toLocaleDateString('th-TH', { day: '2-digit', month: '2-digit', year: 'numeric' })}"
                                            data-num-of-days="${reg.schedule_num_of_days} วัน"
                                            data-available-dates="${reg.available_dates}"
                                            data-total-price="${parseFloat(reg.total_price).toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿"
                                            data-receipt-image="${reg.payment_receipt_image ? '../Student/booking/' + reg.payment_receipt_image : ''}">
                                            <i class="bi bi-eye"></i> ดู
                                        </button>
                                    </td>
                                    <td>
                                        <form class="update-status-form d-flex align-items-center justify-content-center gap-2">
                                            <input type="hidden" name="id" value="${reg.id}">
                                            <select name="payment_status" class="form-select form-select-sm">
                                                <option value="Pending Confirmation" ${reg.payment_status === 'Pending Confirmation' ? 'selected' : ''}>Pending Confirmation</option>
                                                <option value="Paid" ${reg.payment_status === 'Paid' ? 'selected' : ''}>Paid</option>
                                            </select>
                                            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check"></i></button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="${reg.id}">
                                            <i class="fas fa-trash-alt"></i> ลบ
                                        </button>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        html = '<tr><td colspan="7" class="text-center text-muted">ไม่มีข้อมูลการลงทะเบียน</td></tr>';
                    }

                    $('#registrationTable').html(html);
                    $('#totalItems').text(totalRows);

                    // สร้าง pagination
                    let paginationHtml = '';
                    paginationHtml += `
                        <li class="page-item ${page <= 1 ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="loadRegistrations(${page - 1})" aria-label="Previous">
                                <span aria-hidden="true">«</span>
                            </a>
                        </li>`;
                    for (let i = 1; i <= totalPages; i++) {
                        paginationHtml += `
                            <li class="page-item ${page == i ? 'active' : ''}">
                                <a class="page-link" href="javascript:void(0)" onclick="loadRegistrations(${i})">${i}</a>
                            </li>`;
                    }
                    paginationHtml += `
                        <li class="page-item ${page >= totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="loadRegistrations(${page + 1})" aria-label="Next">
                                <span aria-hidden="true">»</span>
                            </a>
                        </li>`;
                    $('#pagination').html(paginationHtml);

                    setTimeout(() => {
                        restoreScrollPosition();
                    }, 100); // เพิ่มการหน่วงเวลาเล็กน้อยเพื่อให้แน่ใจว่าตารางโหลดเสร็จ
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถโหลดข้อมูลได้: ' + error,
                        confirmButtonText: 'ตกลง',
                        allowOutsideClick: false,
                        focusConfirm: false,
                        willClose: () => {
                            restoreScrollPosition();
                        }
                    });
                    $('#registrationTable').html('<tr><td colspan="7" class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</td></tr>');
                    $('#totalItems').text(0);
                    $('#pagination').html('');
                    restoreScrollPosition();
                }
            });
        }

        // ฟังก์ชันเคลียร์ฟิลเตอร์
        function clearFilters() {
            saveScrollPosition();
            $('#search_input').val('');
            $('#payment_status_filter').val('');
            $('#province_filter').val('');
            $('#amphur_filter').val('');
            $('#location_filter').val('');
            loadAmphur();
            loadLocation();
            loadRegistrations(1);
        }

        // โหลดข้อมูลอำเภอ
        function loadAmphur() {
            const provinceID = $('#province_filter').val();
            if (provinceID) {
                $.ajax({
                    url: 'get_amphur.php',
                    type: 'POST',
                    data: { province_id: provinceID },
                    success: function(data) {
                        $('#amphur_filter').html(data);
                        loadLocation();
                    }
                });
            } else {
                $('#amphur_filter').html('<option value="">-- แสดงทั้งหมด --</option>');
                $('#location_filter').html('<option value="">-- แสดงทั้งหมด --</option>');
            }
        }

        // โหลดข้อมูลจุดขึ้นรถ
        function loadLocation() {
            const provinceID = $('#province_filter').val();
            const amphurID = $('#amphur_filter').val();
            if (provinceID && amphurID) {
                $.ajax({
                    url: 'get_location.php',
                    type: 'GET',
                    data: { province_id: provinceID, hur_id: amphurID },
                    success: function(data) {
                        $('#location_filter').html(data);
                    }
                });
            } else {
                $('#location_filter').html('<option value="">-- แสดงทั้งหมด --</option>');
            }
        }

        // เริ่มต้นหน้า
        $(document).ready(function() {
            // Sidebar Toggle with localStorage
            const sidebarState = localStorage.getItem('sidebarState');
            if (sidebarState === 'closed') {
                $('.sidebar').addClass('closed');
                $('.content').addClass('closed');
                $('#open-btn').show();
            }

            $('#close-btn').on('click', function() {
                $('.sidebar').addClass('closed');
                $('.content').addClass('closed');
                $('#open-btn').show();
                localStorage.setItem('sidebarState', 'closed');
            });

            $('#open-btn').on('click', function() {
                $('.sidebar').removeClass('closed');
                $('.content').removeClass('closed');
                $('#open-btn').hide();
                localStorage.setItem('sidebarState', 'open');
            });

            // โหลดข้อมูลเริ่มต้น
            loadRegistrations();

            // ค้นหาแบบเรียลไทม์เมื่อพิมพ์
            $('#search_input').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadRegistrations(1);
                }, 300);
            });

            // ค้นหาแบบเรียลไทม์เมื่อเปลี่ยนตัวกรอง
            $('#payment_status_filter, #province_filter, #amphur_filter, #location_filter').on('change', function() {
                loadRegistrations(1);
            });

            // แสดงข้อมูลเพิ่มเติมใน Modal
            $(document).on('click', '.view-details-btn', function() {
                saveScrollPosition();
                const createdAt = $(this).data('created-at');
                const numOfDays = $(this).data('num-of-days');
                const availableDates = $(this).data('available-dates');
                const totalPrice = $(this).data('total-price');
                const receiptImage = $(this).data('receipt-image');

                $('#modalCreatedAt').text(createdAt);
                $('#modalNumOfDays').text(numOfDays);
                $('#modalAvailableDates').text(availableDates);
                $('#modalTotalPrice').text(totalPrice);

                if (receiptImage) {
                    $('#modalReceiptImage').html(`<img src="${receiptImage}" class="img-preview" alt="ใบเสร็จ">`);
                } else {
                    $('#modalReceiptImage').html('<p class="text-muted">ไม่มีใบเสร็จ</p>');
                }

                const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
                detailsModal.show();
            });

            // อัปเดตสถานะการชำระเงิน
            $(document).on('submit', '.update-status-form', function(e) {
                e.preventDefault();
                saveScrollPosition(); // บันทึกตำแหน่ง scroll

                const formData = new FormData(this);

                Swal.fire({
                    title: 'ยืนยันการแก้ไข?',
                    text: 'คุณต้องการบันทึกการแก้ไขสถานะการชำระเงินนี้หรือไม่?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก',
                    allowOutsideClick: false,
                    focusConfirm: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'enrollment_update.php',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'สำเร็จ!',
                                        text: response.message,
                                        confirmButtonText: 'ตกลง',
                                        allowOutsideClick: false,
                                        focusConfirm: false,
                                        willClose: () => {
                                            loadRegistrations(currentPage);
                                            setTimeout(() => {
                                                restoreScrollPosition();
                                            }, 150); // เพิ่มการหน่วงเวลาเล็กน้อยหลังจาก SweetAlert ปิด
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'เกิดข้อผิดพลาด!',
                                        text: response.message,
                                        confirmButtonText: 'ตกลง',
                                        allowOutsideClick: false,
                                        focusConfirm: false,
                                        willClose: () => {
                                            restoreScrollPosition();
                                        }
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด!',
                                    text: 'ไม่สามารถอัปเดตสถานะได้: ' + (xhr.responseJSON?.message || xhr.responseText),
                                    confirmButtonText: 'ตกลง',
                                    allowOutsideClick: false,
                                    focusConfirm: false,
                                    willClose: () => {
                                        restoreScrollPosition();
                                    }
                                });
                            }
                        });
                    } else {
                        restoreScrollPosition();
                    }
                });
            });

            // ลบการลงทะเบียน
            $(document).on('click', '.delete-btn', function() {
                saveScrollPosition();
                const deleteId = $(this).data('id');
                Swal.fire({
                    title: 'คุณแน่ใจหรือไม่?',
                    text: 'คุณต้องการลบข้อมูลการลงทะเบียนนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก',
                    allowOutsideClick: false,
                    focusConfirm: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'enrollment_delete.php',
                            type: 'POST',
                            data: { id: deleteId, delete: true },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'สำเร็จ!',
                                        text: response.message,
                                        confirmButtonText: 'ตกลง',
                                        allowOutsideClick: false,
                                        focusConfirm: false,
                                        willClose: () => {
                                            loadRegistrations(currentPage);
                                            setTimeout(() => {
                                                restoreScrollPosition();
                                            }, 150);
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'เกิดข้อผิดพลาด!',
                                        text: response.message,
                                        confirmButtonText: 'ตกลง',
                                        allowOutsideClick: false,
                                        focusConfirm: false,
                                        willClose: () => {
                                            restoreScrollPosition();
                                        }
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด!',
                                    text: 'ไม่สามารถลบข้อมูลได้: ' + (xhr.responseJSON?.message || xhr.responseText),
                                    confirmButtonText: 'ตกลง',
                                    allowOutsideClick: false,
                                    focusConfirm: false,
                                    willClose: () => {
                                        restoreScrollPosition();
                                    }
                                });
                            }
                        });
                    } else {
                        restoreScrollPosition();
                    }
                });
            });
        });
    </script>
</body>
</html>