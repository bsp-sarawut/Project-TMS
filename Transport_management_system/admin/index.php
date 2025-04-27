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
    <link rel="icon" type="image/x-icon" href="../Logo/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
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

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(66, 165, 245, 0.2), rgba(33, 33, 33, 0.3));
            animation: gradientWave 15s infinite ease-in-out;
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

        .logo {
            display: block;
            margin: 0 auto 15px;
            width: 80px;
            height: 80px;
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
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            background: #2196f3; /* น้ำเงินเข้มขึ้นนิด */
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(66, 165, 245, 0.4);
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .btn-primary:hover::after {
            width: 300px;
            height: 300px;
        }

        .shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes gradientWave {
            0% { background: linear-gradient(135deg, rgba(66, 165, 245, 0.2), rgba(33, 33, 33, 0.3)); }
            50% { background: linear-gradient(135deg, rgba(33, 33, 33, 0.3), rgba(66, 165, 245, 0.2)); }
            100% { background: linear-gradient(135deg, rgba(66, 165, 245, 0.2), rgba(33, 33, 33, 0.3)); }
        }

        @keyframes shake {
            10%, 90% { transform: translateX(-1px); }
            20%, 80% { transform: translateX(2px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60% { transform: translateX(4px); }
        }
    </style>
</head>
<body>

<div class="container login-container">
    <img src="../Logo/logo.png" alt="Logo" class="logo">
    <h1 id="typewriter">เข้าสู่ระบบแอดมิน</h1>
    <hr>

    <?php if(isset($_SESSION["error"])) { ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาด',
                    text: '<?php echo $_SESSION["error"]; ?>',
                    confirmButtonText: 'ตกลง'
                });
            });
        </script>
        <?php unset($_SESSION["error"]); ?>
    <?php } ?>

    <?php if(isset($_SESSION["success"]) && (!isset($_SESSION['success_shown']) || $_SESSION['success_shown'] === false)) { ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: '<?php echo $_SESSION["success"]; ?>',
                    confirmButtonText: 'ตกลง'
                });
            });
        </script>
        <?php 
            $_SESSION['success_shown'] = true;
            unset($_SESSION["success"]);
        ?>
    <?php } ?>

    <form action="admin_signin_db.php" method="post">  
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
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
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

    // ป้องกันการส่งฟอร์มถ้าช่องว่างเปล่า + เพิ่มอนิเมชันสั่น
    const form = document.querySelector('form');
    const loginContainer = document.querySelector('.login-container');
    form.addEventListener('submit', (e) => {
        const username = document.getElementById('admin_username').value.trim();
        const password = document.getElementById('admin_password').value.trim();
        if (!username || !password) {
            e.preventDefault();
            loginContainer.classList.add('shake');
            Swal.fire({
                icon: 'warning',
                title: 'คำเตือน',
                text: 'กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน!',
                confirmButtonText: 'ตกลง'
            });
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