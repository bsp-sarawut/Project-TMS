<?php
session_start();
include 'config/condb.php';

// ตรวจสอบว่าแอดมินล็อกอินอยู่หรือไม่
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['error'] = 'กรุณาล็อกอินก่อนใช้งาน!';
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข่าวสาร - ระบบจัดการการขนส่ง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
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
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #ced4da;
            padding: 10px;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }
        .form-control:focus, .form-select:focus {
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
        .btn-warning {
            background: #f39c12;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            color: #fff;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            width: 80px;
            text-align: center;
        }
        .btn-warning:hover {
            background: #d35400;
            transform: scale(1.05);
        }
        .btn-danger {
            background: #e74c3c;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            color: #fff;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            width: 80px;
            text-align: center;
        }
        .btn-danger:hover {
            background: #c0392b;
            transform: scale(1.05);
        }
        .action-buttons-column {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }
        .table thead th {
            background: #003087;
            color: #fff;
            text-align: center;
            padding: 15px;
            font-weight: 500;
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
            padding: 12px;
        }
        .news-image {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .modal-header {
            background: #007bff;
            color: #fff;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 20px;
        }
        .modal-title {
            font-weight: 600;
        }
        .modal-body {
            padding: 25px;
        }
        .image-preview {
            width: 150px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #ddd;
            margin-top: 10px;
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
            .action-buttons-column {
                flex-direction: column;
                gap: 5px;
            }
            .btn-warning, .btn-danger {
                width: 100%;
            }
            .header-title {
                font-size: 1.5rem;
            }
            .news-image {
                width: 80px;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content" id="content">
        <div class="container mt-4">
            <h2 class="header-title">จัดการข่าวสารประชาสัมพันธ์</h2>

            <!-- แสดงข้อความแจ้งเตือนจาก Session -->
            <?php if (isset($_SESSION['success']) && (!isset($_SESSION['success_shown']) || $_SESSION['success_shown'] === false)) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: '<?php echo $_SESSION['success']; ?>',
                            confirmButtonText: 'ตกลง'
                        });
                    });
                </script>
                <?php 
                    $_SESSION['success_shown'] = true;
                    unset($_SESSION['success']);
                ?>
            <?php } elseif (isset($_SESSION['error'])) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'ข้อผิดพลาด',
                            text: '<?php echo $_SESSION['error']; ?>',
                            confirmButtonText: 'ตกลง'
                        });
                    });
                </script>
                <?php unset($_SESSION['error']); ?>
            <?php } ?>

            <!-- ส่วนที่ 1: จัดการข่าวสาร -->
            <div class="card mb-4">
                <h3 class="mb-3">จัดการข่าวสาร</h3>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewsModal">
                        <i class="fas fa-plus me-2"></i>เพิ่มข่าวสาร
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ลำดับ</th>
                                <th>หัวข้อ</th>
                                <th>วันที่</th>
                                <th>รูปภาพ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="newsTableBody">
                            <!-- จะโหลดข้อมูลด้วย AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มข่าวสาร -->
    <div class="modal fade" id="addNewsModal" tabindex="-1" aria-labelledby="addNewsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNewsModalLabel">เพิ่มข่าวสาร</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addNewsForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="news_title" class="form-label">หัวข้อ:</label>
                            <input type="text" id="news_title" name="news_title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="news_content" class="form-label">เนื้อหา:</label>
                            <textarea id="news_content" name="news_content" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="news_date" class="form-label">วันที่:</label>
                            <input type="date" id="news_date" name="news_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="news_image" class="form-label">รูปภาพ:</label>
                            <input type="file" id="news_image" name="news_image" class="form-control" accept="image/*" onchange="previewImage(this, 'preview_add')">
                            <div class="image-preview-container">
                                <img id="preview_add" class="image-preview" alt="ตัวอย่างรูปภาพ">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>เพิ่มข่าวสาร</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขข่าวสาร -->
    <div class="modal fade" id="editNewsModal" tabindex="-1" aria-labelledby="editNewsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editNewsModalLabel">แก้ไขข่าวสาร</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editNewsForm" enctype="multipart/form-data">
                        <input type="hidden" id="edit_news_id" name="news_id">
                        <div class="mb-3">
                            <label for="edit_news_title" class="form-label">หัวข้อ:</label>
                            <input type="text" id="edit_news_title" name="news_title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_news_content" class="form-label">เนื้อหา:</label>
                            <textarea id="edit_news_content" name="news_content" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_news_date" class="form-label">วันที่:</label>
                            <input type="date" id="edit_news_date" name="news_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_news_image" class="form-label">รูปภาพ:</label>
                            <input type="file" id="edit_news_image" name="news_image" class="form-control" accept="image/*" onchange="previewImage(this, 'preview_edit')">
                            <div class="image-preview-container">
                                <img id="preview_edit" class="image-preview" alt="ตัวอย่างรูปภาพ">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>บันทึก</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function loadNews() {
            $.ajax({
                url: 'fetch_news.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        Swal.fire('เกิดข้อผิดพลาด', data.error, 'error');
                        $('#newsTableBody').html('<tr><td colspan="5" class="text-center text-muted">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
                        return;
                    }

                    const newsItems = data.news;
                    let html = '';
                    if (newsItems.length > 0) {
                        newsItems.forEach((news, index) => {
                            const imageSrc = news.news_image ? `uploads/news/${news.news_image}` : 'images/default_news.jpg';
                            html += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${news.news_title}</td>
                                    <td>${news.news_date}</td>
                                    <td>
                                        ${news.news_image ? 
                                            `<img src="${imageSrc}" class="news-image" alt="รูปภาพข่าวสาร">` : 
                                            'ไม่มีรูปภาพ'}
                                    </td>
                                    <td>
                                        <div class="action-buttons-column">
                                            <button type="button" class="btn btn-warning btn-sm edit-btn"
                                                data-news_id="${news.news_id}"
                                                data-news_title="${news.news_title}"
                                                data-news_content="${news.news_content}"
                                                data-news_date="${news.news_date}"
                                                data-news_image="${imageSrc}"><i class="fas fa-edit"></i> แก้ไข</button>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn"
                                                data-news_id="${news.news_id}"><i class="fas fa-trash-alt"></i> ลบ</button>
                                        </div>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        html = '<tr><td colspan="5" class="text-center text-muted">ไม่มีข่าวสาร</td></tr>';
                    }
                    $('#newsTableBody').html(html);
                },
                error: function() {
                    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
                    $('#newsTableBody').html('<tr><td colspan="5" class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</td></tr>');
                }
            });
        }

        function previewImage(input, previewId) {
            var preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

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

            loadNews();

            // ตรวจสอบฟอร์มเพิ่มข่าวสาร
            $('#addNewsForm').on('submit', function(e) {
                e.preventDefault();
                let errors = [];

                const newsTitle = $('#news_title').val().trim();
                const newsContent = $('#news_content').val().trim();
                const newsDate = $('#news_date').val();
                const newsImage = $('#news_image')[0].files[0];

                if (!newsTitle) errors.push('กรุณากรอกหัวข้อ');
                if (!newsContent) errors.push('กรุณากรอกเนื้อหา');
                if (!newsDate) errors.push('กรุณาเลือกวันที่');
                if (newsImage) {
                    const fileExt = newsImage.name.split('.').pop().toLowerCase();
                    const allowedExts = ['jpg', 'jpeg', 'png'];
                    if (!allowedExts.includes(fileExt)) {
                        errors.push('ไฟล์รูปภาพต้องเป็น JPG หรือ PNG เท่านั้น');
                    }
                    if (newsImage.size > 2 * 1024 * 1024) {
                        errors.push('ไฟล์รูปภาพต้องมีขนาดไม่เกิน 2MB');
                    }
                }

                if (errors.length > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        html: errors.join('<br>'),
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }

                Swal.fire({
                    title: 'ยืนยันการเพิ่มข่าวสาร?',
                    text: 'คุณต้องการเพิ่มข่าวสารนี้หรือไม่?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var formData = new FormData(this);
                        $.ajax({
                            url: 'news_insert.php',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === "success") {
                                    Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                                        $('#addNewsModal').modal('hide');
                                        $('#addNewsForm')[0].reset();
                                        $('#preview_add').hide();
                                        loadNews();
                                    });
                                } else {
                                    Swal.fire('เกิดข้อผิดพลาด!', response.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถเพิ่มข้อมูลได้: ' + (xhr.responseJSON?.message || xhr.responseText), 'error');
                            }
                        });
                    }
                });
            });

            // ตั้งค่า Modal แก้ไขข่าวสาร
            $(document).on('click', '.edit-btn', function() {
                const newsId = $(this).data('news_id');
                const newsTitle = $(this).data('news_title');
                const newsContent = $(this).data('news_content');
                const newsDate = $(this).data('news_date');
                const newsImage = $(this).data('news_image');

                $('#edit_news_id').val(newsId);
                $('#edit_news_title').val(newsTitle);
                $('#edit_news_content').val(newsContent);
                $('#edit_news_date').val(newsDate);

                if (newsImage && newsImage !== 'images/default_news.jpg') {
                    $('#preview_edit').attr('src', newsImage).show();
                } else {
                    $('#preview_edit').hide();
                }

                $('#editNewsModal').modal('show');
            });

            // ตรวจสอบฟอร์มแก้ไขข่าวสาร
            $('#editNewsForm').on('submit', function(e) {
                e.preventDefault();
                let errors = [];

                const newsTitle = $('#edit_news_title').val().trim();
                const newsContent = $('#edit_news_content').val().trim();
                const newsDate = $('#edit_news_date').val();
                const newsImage = $('#edit_news_image')[0].files[0];

                if (!newsTitle) errors.push('กรุณากรอกหัวข้อ');
                if (!newsContent) errors.push('กรุณากรอกเนื้อหา');
                if (!newsDate) errors.push('กรุณาเลือกวันที่');
                if (newsImage) {
                    const fileExt = newsImage.name.split('.').pop().toLowerCase();
                    const allowedExts = ['jpg', 'jpeg', 'png'];
                    if (!allowedExts.includes(fileExt)) {
                        errors.push('ไฟล์รูปภาพต้องเป็น JPG หรือ PNG เท่านั้น');
                    }
                    if (newsImage.size > 2 * 1024 * 1024) {
                        errors.push('ไฟล์รูปภาพต้องมีขนาดไม่เกิน 2MB');
                    }
                }

                if (errors.length > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        html: errors.join('<br>'),
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }

                Swal.fire({
                    title: 'ยืนยันการแก้ไข?',
                    text: 'คุณต้องการบันทึกการแก้ไขข่าวสารนี้หรือไม่?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var formData = new FormData(this);
                        $.ajax({
                            url: 'news_update.php',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === "success") {
                                    Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                                        $('#editNewsModal').modal('hide');
                                        loadNews();
                                    });
                                } else {
                                    Swal.fire('เกิดข้อผิดพลาด!', response.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถแก้ไขข้อมูลได้: ' + (xhr.responseJSON?.message || xhr.responseText), 'error');
                            }
                        });
                    }
                });
            });

            // SweetAlert สำหรับยืนยันการลบข่าวสาร
            $(document).on('click', '.delete-btn', function() {
                const newsId = $(this).data('news_id');
                Swal.fire({
                    title: 'ยืนยันการลบ?',
                    text: 'คุณต้องการลบข่าวสารนี้หรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'news_delete.php',
                            type: 'POST',
                            data: { news_id: newsId },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === "success") {
                                    Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                                        loadNews();
                                    });
                                } else {
                                    Swal.fire('เกิดข้อผิดพลาด!', response.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถลบข้อมูลได้: ' + (xhr.responseJSON?.message || xhr.responseText), 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>