<?php
session_start();
require_once 'config/condb.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - แอดมิน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #212121; /* ดำเข้มเรียบ */
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        /* พื้นหลังอนิเมชันแบบคลื่นนุ่มนวล */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(66, 165, 245, 0.05), rgba(33, 33, 33, 0.1));
            animation: subtleWave 10s infinite ease-in-out;
            z-index: -1;
        }

        .login-container {
            max-width: 380px;
            background: #ffffff; /* สีขาว */
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.5s ease-in-out;
            position: relative;
            z-index: 1;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #212121; /* ดำเข้ม */
            text-align: center;
            margin-bottom: 20px;
        }

        hr {
            border: 0;
            height: 1px;
            background: #e0e0e0; /* เทาอ่อน */
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            color: #424242; /* เทาเข้ม */
        }

        .form-control {
            border-radius: 5px;
            padding: 10px;
            border: 1px solid #e0e0e0;
            background: #f9f9f9; /* ขาวเทาอ่อน */
            color: #212121;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #42a5f5; /* น้ำเงินอ่อน */
            box-shadow: 0 0 6px rgba(66, 165, 245, 0.4);
            background: #f9f9f9;
            color: #212121;
        }

        .btn-primary {
            background: #42a5f5; /* น้ำเงินอ่อน */
            border: none;
            padding: 10px;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2196f3; /* น้ำเงินเข้มขึ้นนิด */
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(66, 165, 245, 0.4);
        }

        .dropdown-toggle {
            background: #424242; /* เทาเข้ม */
            border: none;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 5px;
            color: #ffffff;
        }

        .dropdown-menu-sm {
            font-size: 0.8rem;
            width: 150px;
            padding: 5px 0;
            border-radius: 5px;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            border: 1px solid #e0e0e0;
        }

        .dropdown-item-sm {
            font-size: 0.8rem;
            padding: 5px 10px;
            color: #424242;
            transition: all 0.3s ease;
        }

        .dropdown-item-sm:hover {
            background: #42a5f5;
            color: #ffffff;
        }

        .alert {
            border-radius: 5px;
            font-size: 0.9rem;
            padding: 10px;
            margin-bottom: 20px;
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            color: #212121;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes subtleWave {
            0% { transform: translateY(0); opacity: 0.8; }
            50% { transform: translateY(-10px); opacity: 1; }
            100% { transform: translateY(0); opacity: 0.8; }
        }
    </style>
</head>
<body>

<div class="container login-container">
    <h1>เข้าสู่ระบบแอดมิน</h1>
    <hr>

    <?php if(isset($_SESSION["error"])) { ?>
        <div class="alert alert-danger" role="alert">
            <?php 
                echo $_SESSION["error"];
                unset($_SESSION["error"]);
            ?>
        </div>
    <?php } ?>

    <?php if(isset($_SESSION["success"])) { ?>
        <div class="alert alert-success" role="alert">
            <?php   
                echo $_SESSION["success"];
                unset($_SESSION["success"]);
            ?>
        </div>
    <?php } ?>

    <form action="admin_signin_db.php" method="post">  
        <!-- Dropdown Menu for Redirecting -->
        <div class="mt-3 mb-2 d-flex justify-content-end">
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    ผู้ใช้อื่น
                </button>
                <ul class="dropdown-menu dropdown-menu-sm" aria-labelledby="dropdownMenuButton">
                    <li><a class="dropdown-item dropdown-item-sm" href="#">คนขับรถ</a></li>
                    <li><a class="dropdown-item dropdown-item-sm" href="../student/index.php">นักศึกษา</a></li>
                </ul>
            </div>
        </div>

        <div class="mb-3">
            <label for="admin_username" class="form-label">ชื่อผู้ใช้</label>
            <input type="text" class="form-control" id="admin_username" name="admin_username" required>
        </div>
        <div class="mb-3">
            <label for="admin_password" class="form-label">รหัสผ่าน</label>
            <input type="password" class="form-control" id="admin_password" name="admin_password" required>
        </div>

        <button type="submit" name="signin" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
    </form>

    <!-- <p class="mt-3 text-center">หากยังไม่มีบัญชีผู้ใช้ <a href="admin_signup.php">ลงทะเบียนที่นี่</a></p> -->
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script>
    // แสดง/ซ่อนรหัสผ่าน
    const passwordInput = document.getElementById('admin_password');
    passwordInput.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            setTimeout(() => passwordInput.type = 'password', 1000); // ซ่อนกลับหลัง 1 วินาที
        }
    });

    // อนิเมชันเมื่อพิมพ์
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.style.transform = 'scale(1.02)';
            input.style.boxShadow = '0 0 6px rgba(66, 165, 245, 0.4)';
        });
        input.addEventListener('blur', () => {
            input.style.transform = 'scale(1)';
            input.style.boxShadow = 'none';
        });
    });

    // ป้องกันการส่งฟอร์มถ้าช่องว่างเปล่า
    const form = document.querySelector('form');
    form.addEventListener('submit', (e) => {
        const username = document.getElementById('admin_username').value.trim();
        const password = document.getElementById('admin_password').value.trim();
        if (!username || !password) {
            e.preventDefault();
            alert('กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน!');
        }
    });

    // อนิเมชันปุ่มเมื่อคลิก
    const button = document.querySelector('.btn-primary');
    button.addEventListener('mousedown', () => {
        button.style.transform = 'scale(0.95)';
    });
    button.addEventListener('mouseup', () => {
        button.style.transform = 'scale(1)';
    });
</script>

</body>
</html>