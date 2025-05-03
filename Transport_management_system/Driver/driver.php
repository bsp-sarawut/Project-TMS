<?php
session_start();
require_once("config/condb.php");

// ตรวจสอบว่าคนขับล็อกอินหรือยัง
if (!isset($_SESSION['driver_user'])) {
    header("Location: driver_signin.php");
    exit();
}

// ตรวจสอบว่าเป็นการเข้าสู่ระบบครั้งแรกหรือไม่
if (isset($_SESSION['first_login']) && $_SESSION['first_login'] == 1) {
    header("Location: driver_change_password.php");
    exit();
}

// ทำการ include navbar หลังจาก session_start และการตรวจสอบสิทธิ์
include('navbar.php');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดคนขับ</title>
    <link rel="icon" type="image/x-icon" href="../Logo/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600&display=swap" rel="stylesheet">
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
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-success" role="alert">
                    <h4 class="alert-heading mb-2"><i class="fas fa-user-check me-2"></i>ยินดีต้อนรับ <?php echo $_SESSION['driver_name'] . ' ' . $_SESSION['driver_lastname']; ?></h4>
                    <?php if (isset($_SESSION['success'])) { ?>
                        <hr>
                        <p class="mb-0"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="row text-center">
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

            </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>