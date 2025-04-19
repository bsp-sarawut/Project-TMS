<?php
session_start();
include('navbar.php');

// สมมติว่ามีการดึงข้อมูลนักเรียนจากฐานข้อมูล (เช่น รูปภาพ)
$stu_img = isset($_SESSION['stu_img']) ? $_SESSION['stu_img'] : "uploads/68034f1d559e9.jpg";
$default_img = "uploads/default.jpg";
$image_path = $stu_img && file_exists($stu_img) ? $stu_img : $default_img;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เมนูนักศึกษา</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #1c2526 0%, #2c3e50 100%);
            font-family: 'Chakra Petch', sans-serif;
            min-height: 100vh;
            margin: 0;
            position: relative;
            overflow-x: hidden;
            padding-top: 80px; /* เพิ่ม padding เพื่อไม่ให้เนื้อหาทับกับ Navbar */
        }

        .container {
            padding-top: 20px;
            padding-bottom: 50px;
        }

        .logo {
            display: block;
            margin: 0 auto 15px;
            width: 80px;
            height: 80px;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #eceff1;
            text-align: center;
            margin-bottom: 30px;
            margin-top: -40px;
        }

        .card {
            border: none;
            border-radius: 15px;
            background: #263238;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3), 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease, background 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
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
            text-align: center;
            padding: 25px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card i {
            color: #ffca28;
            font-size: 3rem;
            margin-bottom: 15px;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .card:hover i {
            color: #ff8f00;
            transform: scale(1.1);
        }

        .card-title {
            font-size: 1.25rem;
            color: #eceff1;
            font-weight: 600;
            margin-top: 15px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .card-text {
            font-size: 1rem;
            color: #b0bec5;
            flex-grow: 1;
        }

        .btn-primary {
            background: linear-gradient(45deg, #ffca28, #ff8f00);
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            font-weight: 600;
            color: #1c2526;
            transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
            margin-top: auto;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #ff8f00, #ffca28);
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(255, 202, 40, 0.4);
        }

        .col-md-3 {
            margin-bottom: 30px;
            flex: 0 0 22%;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container {
            animation: fadeIn 0.5s ease-in-out;
        }

        @media (max-width: 992px) {
            .col-md-3 {
                flex: 0 0 48%;
            }
        }

        @media (max-width: 768px) {
            .col-md-3 {
                flex: 0 0 100%;
                margin-bottom: 20px;
            }

            .card-body {
                padding: 20px;
            }

            .card i {
                font-size: 2.5rem;
            }

            .card-title {
                font-size: 1.1rem;
            }

            .card-text {
                font-size: 0.9rem;
            }

            .btn-primary {
                padding: 8px 18px;
                font-size: 0.9rem;
            }

            .logo {
                width: 60px;
                height: 60px;
            }

            h1 {
                font-size: 1.4rem;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding-top: 10px;
                padding-bottom: 40px;
            }

            .card i {
                font-size: 2.2rem;
            }

            .card-title {
                font-size: 1rem;
            }

            .card-text {
                font-size: 0.85rem;
            }

            .btn-primary {
                padding: 8px 16px;
                font-size: 0.85rem;
            }

            .logo {
                width: 50px;
                height: 50px;
            }

            h1 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- <img src="../../Logo/Logo_stu.png" alt="Logo" class="logo"> -->
        <h1 id="typewriter">เมนูนักศึกษา</h1>
        <div class="row text-center">
            <!-- ลงทะเบียนขึ้นรถรับส่ง -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-bus fa-3x mb-3"></i>
                        <h5 class="card-title">ลงทะเบียนขึ้นรถรับส่ง</h5>
                        <p class="card-text">ลงทะเบียนเพื่อขึ้นรถ</p>
                        <a href="enrollment.php" class="btn btn-primary">ลงทะเบียน</a>
                    </div>
                </div>
            </div>

            <!-- เช็คสถานะการลงทะเบียน -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h5 class="card-title">เช็คสถานะการลงทะเบียน</h5>
                        <p class="card-text">ตรวจสอบสถานะการลงทะเบียน</p>
                        <a href="Show_enrollment.php" class="btn btn-primary">เช็คสถานะ</a>
                    </div>
                </div>
            </div>

            <!-- เช็คตารางรถ -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                        <h5 class="card-title">เช็คตารางรถ</h5>
                        <p class="card-text">ดูตารางการเดินรถ</p>
                        <a href="Check_schedule.php" class="btn btn-primary">ดูตาราง</a>
                    </div>
                </div>
            </div>

            <!-- ตั้งค่า -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-cogs fa-3x mb-3"></i>
                        <h5 class="card-title">ตั้งค่า</h5>
                        <p class="card-text">จัดการการตั้งค่า</p>
                        <a href="setting.php" class="btn btn-primary">ตั้งค่า</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS, Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

    <script>
        // Typewriter effect สำหรับ h1
        const typewriterElement = document.getElementById('typewriter');
        const text = typewriterElement.textContent;
        typewriterElement.textContent = '';
        let i = 0;

        function typeWriter() {
            if (i < text.length) {
                typewriterElement.textContent += text.charAt(i);
                i++;
                setTimeout(typeWriter, 100);
            }
        }
        document.addEventListener('DOMContentLoaded', typeWriter);

        // อนิเมชันปุ่มเมื่อคลิก
        const buttons = document.querySelectorAll('.btn-primary');
        buttons.forEach(button => {
            button.addEventListener('mousedown', () => {
                button.style.transform = 'scale(0.95)';
            });
            button.addEventListener('mouseup', () => {
                button.style.transform = 'scale(1)';
            });
        });

        // เมื่อเลือกจังหวัดแล้ว
        $('#province').change(function() {
            var province_id = $(this).val();
            if (province_id) {
                $.ajax({
                    type: "POST",
                    url: "get_amphur.php",
                    data: { province_id: province_id },
                    success: function(response) {
                        $('#amphur').html(response);
                        $('#amphur').prop('disabled', false);
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        alert('เกิดข้อผิดพลาดในการโหลดข้อมูลอำเภอ กรุณาลองใหม่อีกครั้ง');
                    }
                });
            } else {
                $('#amphur').prop('disabled', true);
                $('#amphur').html('<option value="">เลือกอำเภอ</option>');
            }
        });
    </script>
</body>
</html>