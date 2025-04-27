<?php
session_start();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - นักศึกษา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1c2526 0%, #2c3e50 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        .login-container {
            max-width: 380px;
            background: #263238;
            border-radius: 20px; /* ปรับขอบให้โค้งมากขึ้น */
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.5s ease-in-out;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px); /* ยกตัวขึ้นเมื่อ hover */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); /* เพิ่มเงา */
        }

        .logo {
            display: block;
            margin: 0 auto 15px;
            width: 80px;
            height: 80px;
        }

        p {
            color: #b0bec5;
            font-size: 0.9rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #eceff1;
            text-align: center;
            margin-bottom: 20px;
        }

        hr {
            border: 0;
            height: 1px;
            background: #b0bec5;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            color: #b0bec5;
            font-size: 1rem;
        }

        .form-control {
            border-radius: 15px; /* ปรับขอบให้โค้งมากขึ้น */
            padding: 10px;
            border: 1px solid #b0bec5;
            background: #2c3e50;
            color: #eceff1;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .form-control:focus {
            border-color: #ffca28;
            box-shadow: 0 0 6px rgba(255, 202, 40, 0.4);
            background: #2c3e50;
            color: #eceff1;
        }

        .btn-primary {
            background: linear-gradient(45deg, #ffca28, #ff8f00);
            border: none;
            padding: 10px;
            font-weight: 600;
            border-radius: 15px; /* ปรับขอบให้โค้งมากขึ้น */
            transition: all 0.3s ease;
            color: #1c2526;
            font-size: 0.9rem;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #ff8f00, #ffca28);
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(255, 202, 40, 0.4);
        }

        .alert {
            border-radius: 15px; /* ปรับขอบให้โค้งมากขึ้น */
            font-size: 0.9rem;
            padding: 10px;
            margin-bottom: 20px;
            background: #2c3e50;
            border: 1px solid #b0bec5;
            color: #eceff1;
        }

        .text-center a {
            color: #ffca28;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .text-center a:hover {
            color: #ff8f00;
            text-decoration: underline;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* รองรับการแสดงผลบนมือถือ */
        @media (max-width: 576px) {
            .login-container {
                width: 90%;
                max-width: 320px;
                padding: 15px;
                margin: 10px;
                border-radius: 15px; /* ปรับขอบให้โค้งสำหรับมือถือ */
            }

            .logo {
                width: 60px;
                height: 60px;
            }

            h1 {
                font-size: 1.4rem;
            }

            .form-label {
                font-size: 0.9rem;
            }

            .form-control {
                padding: 8px;
                font-size: 0.85rem;
                border-radius: 10px; /* ปรับขอบให้โค้งสำหรับมือถือ */
            }

            .btn-primary {
                padding: 8px;
                font-size: 0.85rem;
                border-radius: 10px; /* ปรับขอบให้โค้งสำหรับมือถือ */
            }

            p {
                font-size: 0.85rem;
            }

            .text-center a {
                font-size: 0.85rem;
            }

            .alert {
                font-size: 0.85rem;
                padding: 8px;
                border-radius: 10px; /* ปรับขอบให้โค้งสำหรับมือถือ */
            }
        }

        @media (max-width: 400px) {
            .login-container {
                width: 95%;
                max-width: 280px;
                padding: 12px;
                border-radius: 12px; /* ปรับขอบให้โค้งสำหรับมือถือ */
            }

            .logo {
                width: 50px;
                height: 50px;
            }

            h1 {
                font-size: 1.2rem;
            }

            .form-label {
                font-size: 0.85rem;
            }

            .form-control {
                padding: 6px;
                font-size: 0.8rem;
                border-radius: 8px; /* ปรับขอบให้โค้งสำหรับมือถือ */
            }

            .btn-primary {
                padding: 6px;
                font-size: 0.8rem;
                border-radius: 8px; /* ปรับขอบให้โค้งสำหรับมือถือ */
            }

            p {
                font-size: 0.8rem;
            }

            .text-center a {
                font-size: 0.8rem;
            }

            .alert {
                font-size: 0.8rem;
                padding: 6px;
                border-radius: 8px; /* ปรับขอบให้โค้งสำหรับมือถือ */
            }
        }
    </style>
</head>
<body>

<div class="container login-container">
    <img src="../Logo/logo_index.png" alt="Logo" class="logo">
    <h1 id="typewriter">เข้าสู่ระบบนักศึกษา</h1>
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

    <form action="stu_signin_db.php" method="post">   
        <div class="mb-3">
            <label for="stu_username" class="form-label">ชื่อผู้ใช้</label>
            <input type="text" class="form-control" id="stu_username" name="stu_username" required>
        </div>
        <div class="mb-3">
            <label for="stu_password" class="form-label">รหัสผ่าน</label>
            <input type="password" class="form-control" id="stu_password" name="stu_password" required>
        </div>

        <button type="submit" name="signin" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
    </form>

    <p class="mt-3 text-center">ยังไม่มีบัญชี? <a href="stu_signup.php">ลงทะเบียนที่นี่</a></p>
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
            setTimeout(typeWriter, 100); // ความเร็วในการพิมพ์ (100ms ต่อตัวอักษร)
        }
    }
    document.addEventListener('DOMContentLoaded', typeWriter);

    // แสดง/ซ่อนรหัสผ่าน
    const passwordInput = document.getElementById('stu_password');
    passwordInput.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            setTimeout(() => passwordInput.type = 'password', 1000);
        }
    });

    // อนิเมชันเมื่อพิมพ์
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.style.transform = 'scale(1.02)';
            input.style.boxShadow = '0 0 6px rgba(255, 202, 40, 0.4)';
        });
        input.addEventListener('blur', () => {
            input.style.transform = 'scale(1)';
            input.style.boxShadow = 'none';
        });
    });

    // ป้องกันการส่งฟอร์มถ้าช่องว่างเปล่า
    const form = document.querySelector('form');
    form.addEventListener('submit', (e) => {
        const username = document.getElementById('stu_username').value.trim();
        const password = document.getElementById('stu_password').value.trim();
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