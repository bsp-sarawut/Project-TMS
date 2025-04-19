<?php
session_start();
require_once 'config/condb.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียน - นักศึกษา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1c2526 0%, #2c3e50 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
            position: relative;
        }

        .signup-container {
            max-width: 500px;
            width: 100%;
            background: #263238;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.5s ease-in-out;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .signup-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
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

        .form-control, .form-select {
            border-radius: 15px;
            padding: 10px;
            border: 1px solid #b0bec5;
            background: #2c3e50;
            color: #eceff1;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #ffca28;
            box-shadow: 0 0 6px rgba(255, 202, 40, 0.4);
            background: #2c3e50;
            color: #eceff1;
        }

        .form-control::placeholder, .form-select option {
            color: #b0bec5;
        }

        .btn-primary {
            background: linear-gradient(45deg, #ffca28, #ff8f00);
            border: none;
            padding: 10px;
            font-weight: 600;
            border-radius: 15px;
            transition: all 0.3s ease;
            color: #1c2526;
            font-size: 0.9rem;
            width: 100%;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #ff8f00, #ffca28);
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(255, 202, 40, 0.4);
        }

        .alert {
            border-radius: 15px;
            font-size: 0.9rem;
            padding: 10px;
            margin-bottom: 20px;
            background: #2c3e50;
            border: 1px solid #b0bec5;
            color: #eceff1;
            text-align: center;
        }

        .alert a {
            color: #ffca28;
            text-decoration: none;
        }

        .alert a:hover {
            color: #ff8f00;
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
            color: #b0bec5;
        }

        .footer a {
            color: #ffca28;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer a:hover {
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
            .signup-container {
                width: 90%;
                max-width: 400px;
                padding: 15px;
                margin: 10px;
                border-radius: 15px;
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

            .form-control, .form-select {
                padding: 8px;
                font-size: 0.85rem;
                border-radius: 10px;
            }

            .btn-primary {
                padding: 8px;
                font-size: 0.85rem;
                border-radius: 10px;
            }

            .footer {
                font-size: 0.85rem;
            }

            .footer a {
                font-size: 0.85rem;
            }

            .alert {
                font-size: 0.85rem;
                padding: 8px;
                border-radius: 10px;
            }
        }

        @media (max-width: 400px) {
            .signup-container {
                width: 95%;
                max-width: 340px;
                padding: 12px;
                border-radius: 12px;
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

            .form-control, .form-select {
                padding: 6px;
                font-size: 0.8rem;
                border-radius: 8px;
            }

            .btn-primary {
                padding: 6px;
                font-size: 0.8rem;
                border-radius: 8px;
            }

            .footer {
                font-size: 0.8rem;
            }

            .footer a {
                font-size: 0.8rem;
            }

            .alert {
                font-size: 0.8rem;
                padding: 6px;
                border-radius: 8px;
            }
        }
    </style>
</head>
<body>
<div class="container signup-container">
    <img src="../Logo/Logo_stu.png" alt="Logo" class="logo">
    <h1 id="typewriter">ลงทะเบียน</h1>
    <hr>

    <?php if (isset($_SESSION["error"])) { ?>
        <div class="alert alert-danger" role="alert">
            <?php 
                echo $_SESSION["error"];
                unset($_SESSION["error"]);
            ?>
        </div>
    <?php } ?>

    <?php if (isset($_SESSION["warning"])) { ?>
        <div class="alert alert-warning" role="alert">
            <?php 
                echo $_SESSION["warning"];
                unset($_SESSION["warning"]);
            ?>
        </div>
    <?php } ?>

    <?php if (isset($_SESSION["success"])) { ?>
        <div class="alert alert-success" role="alert">
            <?php 
                echo $_SESSION["success"];
                unset($_SESSION["success"]);
            ?>
        </div>
    <?php } ?>

    <form action="stu_signup_db.php" method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="stu_username" class="form-label">ชื่อผู้ใช้</label>
            <input type="text" class="form-control" name="stu_username" id="stu_username" required>
        </div>

        <div class="mb-3">
            <label for="stu_password" class="form-label">รหัสผ่าน</label>
            <input type="password" class="form-control" name="stu_password" id="stu_password" required>
        </div>

        <div class="mb-3">
            <label for="stu_year" class="form-label">ปีการศึกษา</label>
            <select class="form-select" name="stu_year" id="stu_year" required>
                <option value="" selected>เลือกปีการศึกษา</option>
                <?php
                    $current_year = date("Y") + 543;
                    for ($i = $current_year - 4; $i <= $current_year; $i++) {
                        echo "<option value='$i'>$i</option>";
                    }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="stu_license" class="form-label">รหัสนักศึกษา</label>
            <input type="text" class="form-control" name="stu_license" id="stu_license" required>
        </div>

        <div class="mb-3">
            <label for="stu_name" class="form-label">ชื่อ</label>
            <input type="text" class="form-control" name="stu_name" id="stu_name" required>
        </div>

        <div class="mb-3">
            <label for="stu_lastname" class="form-label">นามสกุล</label>
            <input type="text" class="form-control" name="stu_lastname" id="stu_lastname" required>
        </div>

        <div class="mb-3">
            <label for="stu_tel" class="form-label">เบอร์โทร</label>
            <input type="tel" class="form-control" name="stu_tel" id="stu_tel" required>
        </div>

        <div class="mb-3">
            <label for="stu_faculty" class="form-label">คณะ</label>
            <select class="form-select" name="stu_faculty" id="stu_faculty" onchange="updateMajors()" required>
                <option value="" selected>เลือกคณะ</option>
                <option value="วิศวกรรมศาสตร์">วิศวกรรมศาสตร์</option>
                <option value="เทคโนโลยี">เทคโนโลยี</option>
                <option value="บริหารธุรกิจ">บริหารธุรกิจ</option>
                <option value="วิทยาศาสตร์">วิทยาศาสตร์</option>
                <option value="บัญชี">บัญชี</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="stu_major" class="form-label">สาขา</label>
            <select class="form-select" name="stu_major" id="stu_major" required>
                <option value="" selected>เลือกสาขา</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="stu_img" class="form-label">รูปภาพประจำตัว</label>
            <input type="file" class="form-control" id="stu_img" name="stu_img" accept="image/*">
        </div>

        <button type="submit" name="signup" class="btn btn-primary mt-3">สมัครสมาชิก</button>
    </form>

    <p class="footer mt-3">เป็นสมาชิกแล้ว? <a href="index.php">เข้าสู่ระบบ</a></p>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

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

    // อัปเดตสาขาตามคณะ
    const majorsByFaculty = {
        "วิศวกรรมศาสตร์": ["สาขาวิชาวิศวกรรมเครื่องกล", "สาขาวิชาวิศวกรรมไฟฟ้า", "สาขาวิชาวิศวกรรมอุตสาหการ"],
        "เทคโนโลยี": ["สาขาวิชาเทคโนโลยีการจัดการอุตสาหกรรม"],
        "บริหารธุรกิจ": ["สาขาวิชาการบัญชี", "สาขาวิชาคอมพิวเตอร์ธุรกิจ", "สาขาวิชาการจัดการทั่วไป"],
        "วิทยาศาสตร์": ["สาขาวิชาเทคโนโลยีสารสนเทศ", "สาขาวิชาเทคโนโลยีคอมพิวเตอร์"],
        "บัญชี": ["สาขาวิชาการบัญชี"]
    };

    function updateMajors() {
        const facultySelect = document.getElementById("stu_faculty");
        const majorSelect = document.getElementById("stu_major");
        const selectedFaculty = facultySelect.value;

        majorSelect.innerHTML = '<option value="" selected>เลือกสาขา</option>';

        if (majorsByFaculty[selectedFaculty]) {
            majorsByFaculty[selectedFaculty].forEach(major => {
                const option = document.createElement("option");
                option.value = major;
                option.textContent = major;
                majorSelect.appendChild(option);
            });
        }
    }

    // อนิเมชันเมื่อพิมพ์
    const inputs = document.querySelectorAll('.form-control, .form-select');
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

    // อนิเมชันปุ่มเมื่อคลิก
    const button = document.querySelector('.btn-primary');
    button.addEventListener('mousedown', () => {
        button.style.transform = 'scale(0.95)';
    });
    button.addEventListener('mouseup', () => {
        button.style.transform = 'scale(1)';
    });

    // ตรวจสอบฟอร์มก่อนส่ง
    const form = document.querySelector('form');
    form.addEventListener('submit', (e) => {
        const username = document.getElementById('stu_username').value.trim();
        const password = document.getElementById('stu_password').value.trim();
        const tel = document.getElementById('stu_tel').value.trim();

        // ตรวจสอบความยาวรหัสผ่าน (อย่างน้อย 8 ตัวอักษร)
        if (password.length < 8) {
            e.preventDefault();
            alert('รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร!');
            return;
        }

        // ตรวจสอบรูปแบบเบอร์โทร (ต้องเป็นตัวเลข 10 หลัก)
        const telPattern = /^[0-9]{10}$/;
        if (!telPattern.test(tel)) {
            e.preventDefault();
            alert('เบอร์โทรต้องเป็นตัวเลข 10 หลัก!');
            return;
        }
    });
</script>
</body>
</html>