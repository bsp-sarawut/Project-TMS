
<?php
    // session_start(); // เริ่มต้นเซสชัน
    // if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
    //     echo "User is logged in with ID: " . $_SESSION['user_id'] . " and Name: " . $_SESSION['user_name'];
    // } else {
    //     echo "Please log in first.";
    // }

    include('navbar.php');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Page</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- เพิ่ม jQuery ก่อนใช้ $ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
 

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .container {
        padding-top: 50px; /* ระยะห่างจาก navbar */
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .card-body {
            text-align: center;
            padding: 25px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card i {
            color: #4CAF50;
            transition: color 0.3s;
        }

        .card i:hover {
            color: #007bff;
        }

        .card-title {
            font-size: 1.25rem;
            color: #333;
            font-weight: bold;
            margin-top: 15px;
        }

        .card-text {
            font-size: 1rem;
            color: #555;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 25px;
            padding: 10px 20px;
            font-weight: bold;
            transition: background-color 0.3s, border-color 0.3s;
            margin-top: auto;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
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
            flex: 0 0 22%;
        }

        @media (max-width: 768px) {
            .col-12 {
                flex: 0 0 100%;
                margin-bottom: 20px;
            }

            .card-body {
                padding: 20px;
            }

            .card-title {
                font-size: 1.1rem;
            }

            .card-text {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row text-center">
            <!-- ลงทะเบียนขึ้นรถรับส่ง -->
            <div class="col-12 col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <i class="fas fa-bus fa-3x mb-3"></i>
                        <h5 class="card-title">ลงทะเบียนขึ้นรถรับส่ง</h5>
                        <p class="card-text">ลงทะเบียนเพื่อขึ้นรถ</p>
                        <a href="enrollment.php" class="btn btn-primary">ลงทะเบียน</a>
                    </div>
                </div>
            </div>

            <!-- เช็คสถานะการลงทะเบียน -->
            <div class="col-12 col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h5 class="card-title">เช็คสถานะการลงทะเบียน</h5>
                        <p class="card-text">ตรวจสอบสถานะการลงทะเบียน</p>
                        <a href="Show_enrollment.php" class="btn btn-primary">เช็คสถานะ</a>
                    </div>
                </div>
            </div>

            <!-- เช็คตารางรถ -->
            <div class="col-12 col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                        <h5 class="card-title">เช็คตารางรถ</h5>
                        <p class="card-text">ดูตารางการเดินรถ</p>
                        <a href="#" class="btn btn-primary">ดูตาราง</a>
                    </div>
                </div>
            </div>

            <!-- ตั้งค่า -->
            <div class="col-12 col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <i class="fas fa-cogs fa-3x mb-3"></i>
                        <h5 class="card-title">ตั้งค่า</h5>
                        <p class="card-text">จัดการการตั้งค่า</p>
                        <a href="#" class="btn btn-primary">ตั้งค่า</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS, Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

    <script>
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
                    }
                });
            } else {
                $('#amphur').prop('disabled', true);
            }
        });
    </script>
</body>
</html>
