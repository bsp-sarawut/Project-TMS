<?php
session_start();
require_once("config/condb.php"); // ปรับพาธให้ถูกต้อง (จาก driver/ ไปที่ config/)

if (isset($_POST["signin"])) {
    $driver_user = $_POST["driver_user"];
    $driver_password = $_POST["driver_password"];

    // ตรวจสอบว่า driver_user และ driver_password ไม่ว่างเปล่า
    if (empty($driver_user)) {
        $_SESSION["error"] = "กรุณากรอกชื่อผู้ใช้";
        header("location:driver_signin.php");
        exit();
    } elseif (empty($driver_password)) {
        $_SESSION["error"] = "กรุณากรอกรหัสผ่าน";
        header("location:driver_signin.php");
        exit();
    } else {
        try {
            // ตรวจสอบในตาราง driver
            $check_data = $conn->prepare("SELECT * FROM driver WHERE driver_user = :driver_user");
            $check_data->bindParam(":driver_user", $driver_user, PDO::PARAM_STR);
            $check_data->execute();
            $row = $check_data->fetch(PDO::FETCH_ASSOC);

            // ตรวจสอบรหัสผ่านที่แฮช
            if ($row && password_verify($driver_password, $row['driver_password'])) {
                $_SESSION['driver_id'] = $row['driver_id'];
                $_SESSION['driver_user'] = $row['driver_user'];
                $_SESSION['driver_name'] = $row['driver_name'];
                $_SESSION['driver_lastname'] = $row['driver_lastname'];
                $_SESSION['first_login'] = $row['first_login'];
                $_SESSION['success'] = "เข้าสู่ระบบสำเร็จ";
                
                // ตรวจสอบว่าเป็นการเข้าสู่ระบบครั้งแรกหรือไม่
                if ($row['first_login'] == 1) {
                    header("location:driver_change_password.php"); // ไปยังหน้าเปลี่ยนรหัสผ่านสำหรับการเข้าสู่ระบบครั้งแรก
                } else {
                    header("location:driver.php"); // ไปยังหน้าแดชบอร์ดคนขับ
                }
            } else {
                $_SESSION['error'] = "ชื่อผู้ใช้หรือรหัสผ่านคนขับผิด";
                header("location:driver_signin.php");
            }
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage();
            header("location:driver_signin.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบคนขับ</title>
    <!-- Bootstrap 5 CSS -->
    <link rel="icon" type="image/x-icon" href="../Logo/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'IBM Plex Sans Thai', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
        }
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .card-body {
            padding: 30px;
        }
        .logo {
            display: block;
            margin: 0 auto 15px;
            width: 80px;
            height: 80px;
        }
        .card-title {
            font-size: 1.5rem;
            color: #1a1a1a;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-label {
            font-size: 1rem;
            color: #4a4a4a;
        }
        .form-control {
            border-radius: 8px;
            font-size: 1rem;
        }
        .btn-primary {
            background-color: #003087;
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0044cc;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        .btn-secondary {
            background-color: #6c757d;
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-weight: 500;
            width: 100%;
            color: #ffffff;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        .alert {
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        @media (max-width: 576px) {
            .login-container {
                padding: 15px;
            }
            .card-body {
                padding: 20px;
            }
            .logo {
                width: 60px;
                height: 60px;
            }
            .card-title {
                font-size: 1.3rem;
            }
            .form-control {
                font-size: 0.9rem;
            }
            .btn-primary, .btn-secondary {
                padding: 8px;
                font-size: 0.9rem;
                border-radius: 6px;
            }
            .alert {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-body">
                <img src="../Logo/logo.png" alt="Logo" class="logo">
                <h5 class="card-title">เข้าสู่ระบบคนขับ</h5>

                <!-- แสดงข้อความแจ้งเตือน -->
                <?php if (isset($_SESSION['error'])) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php } ?>
                <?php if (isset($_SESSION['success'])) { ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php } ?>

                <form action="driver_signin.php" method="POST">
                    <div class="mb-3">
                        <label for="driver_user" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="driver_user" name="driver_user" placeholder="กรอกชื่อผู้ใช้" required>
                    </div>
                    <div class="mb-3">
                        <label for="driver_password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="driver_password" name="driver_password" placeholder="กรอกรหัสผ่าน" required>
                    </div>
                    <button type="submit" name="signin" class="btn btn-primary">เข้าสู่ระบบ</button>
                    <a href="../index.php" class="btn btn-secondary mt-3">กลับสู่หน้าหลัก</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS, Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script>
        // อนิเมชันปุ่มเมื่อคลิก
        const buttons = document.querySelectorAll('.btn-primary, .btn-secondary');
        buttons.forEach(button => {
            button.addEventListener('mousedown', () => {
                button.style.transform = 'scale(0.95)';
            });
            button.addEventListener('mouseup', () => {
                button.style.transform = 'scale(1)';
            });
        });

        // ป้องกันการส่งฟอร์มถ้าช่องว่างเปล่า
        const form = document.querySelector('form');
        form.addEventListener('submit', (e) => {
            const username = document.getElementById('driver_user').value.trim();
            const password = document.getElementById('driver_password').value.trim();
            if (!username || !password) {
                e.preventDefault();
                alert('กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน!');
            }
        });
    </script>
</body>
</html>