<?php
    include('navbar.php');
    session_start();
    // ตรวจสอบว่าคนขับล็อกอินหรือยัง
    if (!isset($_SESSION['driver_user'])) {
        header("Location: driver_login.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดคนขับ</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: #f5f7fa;
            font-family: 'IBM Plex Sans Thai', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding-top: 80px;
        }
        .container {
            padding-top: 20px;
            padding-bottom: 50px;
        }
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .card-body {
            text-align: center;
            padding: 30px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .card i {
            color: #003087;
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 1.3rem;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .card-text {
            font-size: 1rem;
            color: #4a4a4a;
            flex-grow: 1;
        }
        .btn-primary {
            background-color: #003087;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            color: #ffffff;
            transition: background-color 0.2s ease;
            margin-top: auto;
        }
        .btn-primary:hover {
            background-color: #0044cc;
        }
        .col-md-3 {
            margin-bottom: 30px;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .col-md-3 {
            flex: 0 0 23%;
        }
        @media (max-width: 992px) {
            .col-md-3 {
                flex: 0 0 48%;
            }
        }
        @media (max-width: 576px) {
            .col-md-3 {
                flex: 0 0 100%;
            }
            .card-body {
                padding: 20px;
            }
            .card i {
                font-size: 2rem;
            }
            .card-title {
                font-size: 1.1rem;
            }
            .card-text {
                font-size: 0.9rem;
            }
            .btn-primary {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row text-center">
            <!-- ดูคิวงาน -->
            <div class="col-12 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-list-ul"></i>
                        <h5 class="card-title">ดูคิวงาน</h5>
                        <p class="card-text">ตรวจสอบคิวงานที่ได้รับมอบหมาย</p>
                        <a href="driver_queue.php" class="btn btn-primary">ดูคิวงาน</a>
                    </div>
                </div>
            </div>

            <!-- อัปเดตสถานะรถ
            <div class="col-12 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-sync-alt"></i>
                        <h5 class="card-title">อัปเดตสถานะรถ</h5>
                        <p class="card-text">เปลี่ยนสถานะการเดินรถ</p>
                        <a href="update_status.php" class="btn btn-primary">อัปเดตสถานะ</a>
                    </div>
                </div>
            </div>

            <!-- เพิ่มส่วนตั้งค่าโปรไฟล์กลับมา -->
            <!-- <div class="col-12 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-user-cog"></i>
                        <h5 class="card-title">ตั้งค่าโปรไฟล์</h5>
                        <p class="card-text">จัดการข้อมูลส่วนตัว</p>
                        <a href="driver_profile.php" class="btn btn-primary">ตั้งค่า</a>
                    </div>
                </div>
            </div> -->
        </div>
    </div> -->

    <!-- Bootstrap 5 JS, Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>