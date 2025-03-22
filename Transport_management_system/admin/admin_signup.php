<?php
session_start();
require_once 'config/condb.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิกแอดมิน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        body {
            background-color: #f4f4f4;
            font-family: 'Arial', sans-serif;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        h1, h3 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .form-label {
            font-weight: 500;
        }
        .btn-primary {
            width: 100%;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
        }
        .alert {
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>สมัครสมาชิกแอดมิน</h1>
    <h3 class="mb-4">กรุณากรอกข้อมูล</h3>
        <hr>
    <?php if (isset($_SESSION["error"])) { ?>
        <div class="alert alert-danger" role="alert"><?= $_SESSION["error"]; unset($_SESSION["error"]); ?></div>
    <?php } ?>

    <?php if (isset($_SESSION["warning"])) { ?>
        <div class="alert alert-warning" role="alert"><?= $_SESSION["warning"]; unset($_SESSION["warning"]); ?></div>
    <?php } ?>

    <?php if (isset($_SESSION["success"])) { ?>
        <div class="alert alert-success" role="alert"><?= $_SESSION["success"]; unset($_SESSION["success"]); ?></div>
    <?php } ?>

    <form action="admin_signup_db.php" method="post">
        <div class="mb-3">
            <label for="admin_username" class="form-label">ชื่อผู้ใช้</label>
            <input type="text" class="form-control" name="admin_username" required>
        </div>

        <div class="mb-3">
            <label for="admin_password" class="form-label">รหัสผ่าน</label>
            <input type="password" class="form-control" name="admin_password" required>
        </div>

        <div class="mb-3">
            <label for="admin_name" class="form-label">ชื่อ</label>
            <input type="text" class="form-control" name="admin_name" required>
        </div>

        <div class="mb-3">
            <label for="admin_lastname" class="form-label">นามสกุล</label>
            <input type="text" class="form-control" name="admin_lastname" required>
        </div>

        <button type="submit" name="signup" class="btn btn-primary">สมัครสมาชิก</button>
    </form>

    <p class="footer mt-3">เป็นสมาชิกแล้ว? <a href="index.php">เข้าสู่ระบบ</a></p>
</div>
</body>
</html>
